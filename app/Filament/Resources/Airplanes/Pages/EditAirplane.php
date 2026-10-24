<?php

namespace App\Filament\Resources\Airplanes\Pages;

use App\Filament\Resources\Airplanes\AirplaneResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditAirplane extends EditRecord
{
    protected static string $resource = AirplaneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
