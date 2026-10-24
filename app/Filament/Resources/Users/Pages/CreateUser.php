<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = new User;
        $user->fill(Arr::except($data, ['role', 'broker_id']));
        $user->assignAccessFromUserResource(
            (string) ($data['role'] ?? User::ROLE_BRANCH_EMPLOYEE),
            filled($data['broker_id'] ?? null) ? (int) $data['broker_id'] : null,
        );
        $user->save();

        return $user;
    }
}
