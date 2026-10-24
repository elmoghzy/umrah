<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Bookings\Schemas\BookingForm;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Booking $booking */
        $booking = $this->getRecord()->load('transfers');

        return [...$data, ...BookingForm::transferState($booking)];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('receipt')
                ->label('طباعة الإيصال')
                ->url(fn (): string => route('bookings.receipt', ['booking' => $this->getRecord()]))
                ->openUrlInNewTab(),
            Action::make('new_booking')
                ->label('حجز جديد')
                ->url(fn (): string => BookingResource::getUrl('create')),
        ];
    }
}
