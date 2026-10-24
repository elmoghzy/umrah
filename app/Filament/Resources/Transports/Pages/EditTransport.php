<?php

namespace App\Filament\Resources\Transports\Pages;

use App\Actions\PreventForceDeleteWithRelations;
use App\Filament\Resources\Transports\TransportResource;
use App\Models\Transport;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditTransport extends EditRecord
{
    protected static string $resource = TransportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make()
                ->before(function (Transport $record): void {
                    PreventForceDeleteWithRelations::handle($record);
                }),
            RestoreAction::make(),
        ];
    }
}
