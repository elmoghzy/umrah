<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Actions\Bookings\CreateBookingAction;
use App\Filament\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateBookingAction::class)->handle($data);
    }
}
