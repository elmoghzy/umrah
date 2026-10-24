<?php

namespace App\Actions\Bookings;

use App\Models\Booking;
use App\Models\User;
use App\Models\Visa;
use App\Enums\VisaStatus;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateBookingAction
{
    public function handle(Booking $booking, array $data): Booking
    {
        if (auth()->user()) {
            Gate::authorize('update', $booking);
        }

        $preservedVisaNames = [];

        $updatedBooking = DB::transaction(function () use ($booking, $data, &$preservedVisaNames): Booking {
            $creator = app(CreateBookingAction::class);
            $normalized = $creator->validateAndNormalize($data);

            /** @var User|null $user */
            $user = auth()->user();
            if ($user && ! $user->isMainAdmin()) {
                $normalized['booking']['broker_id'] = $user->broker_id;
            }

            $booking->fill($normalized['booking']);
            $booking->updated_by = auth()->id();
            $booking->save();

            $incomingCustomerIds = $normalized['residents'];
            $existingCustomerIds = $booking->residents()->pluck('customer_id')->map(fn ($id): int => (int) $id)->all();
            $removedCustomerIds = array_diff($existingCustomerIds, $incomingCustomerIds);

            foreach ($removedCustomerIds as $customerId) {
                $visa = Visa::withTrashed()
                    ->with('customer')
                    ->where('booking_id', $booking->getKey())
                    ->where('customer_id', $customerId)
                    ->first();

                if (! $visa) {
                    continue;
                }

                if ($visa->status === VisaStatus::Pending) {
                    if (! $visa->trashed()) {
                        $visa->delete();
                    }

                    continue;
                }

                $preservedVisaNames[] = $visa->customer?->name_ar ?? (string) $customerId;
            }

            $booking->residents()->delete();
            $booking->transfers()->delete();
            $booking->details()->each(function ($detail): void {
                $detail->rooms()->delete();
                $detail->delete();
            });
            $creator->syncRelations($booking, $normalized);

            return $booking->fresh(['details.rooms', 'residents', 'transfers']);
        });

        if ($preservedVisaNames !== []) {
            Notification::make()
                ->warning()
                ->title('تأشيرات محفوظة')
                ->body('لم تُحذف تأشيرات المعتمرين: '.implode('، ', array_unique($preservedVisaNames)).'.')
                ->send();
        }

        return $updatedBooking;
    }
}
