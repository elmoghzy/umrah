<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $record->fill(Arr::except($data, ['role', 'broker_id']));
        $record->assignAccessFromUserResource(
            (string) ($data['role'] ?? $record->role),
            filled($data['broker_id'] ?? null) ? (int) $data['broker_id'] : null,
        );
        $record->save();

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
