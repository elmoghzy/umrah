<?php

namespace App\Filament\Pages\Auth;

use App\Models\Broker;
use App\Models\User;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class BranchLogin extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->label('الفرع')
                    ->options($this->getBranchOptions())
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('user_id', null);
                    }),
                Group::make([
                    Select::make('user_id')
                        ->label('اسم المستخدم')
                        ->placeholder(fn (Get $get): string => filled($get('branch_id'))
                            ? 'اختر اسمك أو اكتب حروفًا للبحث'
                            : 'اختر الفرع أولًا')
                        ->options(fn (Get $get): array => $this->getUserOptions($get('branch_id')))
                        ->dynamicOptions(false)
                        ->searchable()
                        ->required()
                        ->disabled(fn (Get $get): bool => blank($get('branch_id'))),
                ])
                    ->key(fn (Get $get): string => 'login-user-options-'.($get('branch_id') ?: 'none'), isInheritable: false),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'id' => $data['user_id'],
            'password' => $data['password'],
        ];
    }

    protected function isUserAllowedToAccessPanel(Authenticatable $user): bool
    {
        if (! parent::isUserAllowedToAccessPanel($user) || ! ($user instanceof User)) {
            return false;
        }

        $selectedBranch = (string) ($this->data['branch_id'] ?? '');

        if ($selectedBranch === 'main') {
            return $user->isMainAdmin();
        }

        $branchId = filter_var($selectedBranch, FILTER_VALIDATE_INT);

        return $branchId !== false
            && $branchId > 0
            && $user->isBranchEmployee()
            && (int) $user->broker_id === $branchId;
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.password' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }

    /** @return array<string, string> */
    private function getBranchOptions(): array
    {
        $branchOptions = Broker::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->mapWithKeys(fn (string $name, int|string $id): array => [(string) $id => $name])
            ->all();

        return ['main' => 'الفرع الرئيسي'] + $branchOptions;
    }

    /** @return array<int, string> */
    private function getUserOptions(mixed $branchId): array
    {
        return $this->usersForBranch($branchId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return Builder<User> */
    private function usersForBranch(mixed $branchId): Builder
    {
        $query = User::query()->where('is_active', true);
        $selectedBranch = (string) ($branchId ?? '');

        if ($selectedBranch === 'main') {
            return $query->where('role', User::ROLE_MAIN_ADMIN);
        }

        $branchId = filter_var($selectedBranch, FILTER_VALIDATE_INT);

        if ($branchId === false || $branchId < 1) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('role', User::ROLE_BRANCH_EMPLOYEE)
            ->where('broker_id', $branchId);
    }
}
