<?php

namespace App\Filament\Resources\BookingTypes\Pages;

use App\Filament\Resources\BookingTypes\BookingTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBookingType extends CreateRecord
{
    protected static string $resource = BookingTypeResource::class;
}
