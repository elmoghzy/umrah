<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Actions\Bookings\UpdateBookingAction;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Bookings\Schemas\BookingForm;
use App\Models\Booking;
use App\Models\Room;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateBookingAction::class)->handle($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Booking $booking */
        $booking = $this->getRecord()->load(['details.rooms', 'transfers']);
        $data['rooms'] = $booking->details->first()?->rooms->map(fn (Room $room): array => [
            'room_type_id' => $room->room_type_id,
            'price' => $room->price,
        ])->all() ?? [];

        $data['stay_scope'] = $booking->stay_scope;
        $data = [...$data, ...BookingForm::transferState($booking)];
        foreach ($booking->details as $detail) {
            $prefix = $detail->city_id === 2 ? 'madina' : 'makka';
            $data[$prefix.'_hotel_id'] = $detail->hotel_id;
            $data[$prefix.'_company_id'] = $detail->company_id;
            $data[$prefix.'_meal_id'] = $detail->meal_id;
            $data[$prefix.'_check_in'] = $detail->check_in?->toDateString();
            $data[$prefix.'_check_out'] = $detail->check_out?->toDateString();
            $data[$prefix.'_nights'] = $detail->check_in && $detail->check_out
                ? $detail->check_in->diffInDays($detail->check_out)
                : null;
        }

        return $data;
    }
}
