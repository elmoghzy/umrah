<?php

namespace App\Actions;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Database\Eloquent\Model;

class SimpleResourceTableActions
{
    public static function recordActions(): array
    {
        return [
            EditAction::make()->label('تعديل'),
            DeleteAction::make()->label('حذف'),
            RestoreAction::make()->label('استرجاع'),
            ForceDeleteAction::make()
                ->label('حذف نهائي')
                ->before(function (Model $record): void {
                    PreventForceDeleteWithRelations::handle($record);
                }),
        ];
    }
}
