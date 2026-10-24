<?php

namespace App\Filament\Resources\Airplanes\Pages;

use App\Filament\Resources\Airplanes\AirplaneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAirplanes extends ListRecords
{
    protected static string $resource = AirplaneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
