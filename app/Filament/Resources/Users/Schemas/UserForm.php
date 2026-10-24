<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('الاسم')
                    ->required(),
                TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('job')
                    ->label('الوظيفة'),
                Select::make('role')
                    ->label('نوع المستخدم')
                    ->options([
                        User::ROLE_MAIN_ADMIN => 'مدير',
                        User::ROLE_BRANCH_EMPLOYEE => 'مستخدم',
                    ])
                    ->required()
                    ->live()
                    ->disabled(fn (?User $record): bool => $record !== null && (int) $record->getKey() === (int) auth()->id())
                    ->dehydrated()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if ($state === User::ROLE_MAIN_ADMIN) {
                            $set('broker_id', null);
                        }
                    }),
                Select::make('broker_id')
                    ->label('الفرع')
                    ->relationship('broker', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder(fn (Get $get): string => $get('role') === User::ROLE_MAIN_ADMIN ? 'الفرع الرئيسي' : 'اختر الفرع')
                    ->disabled(fn (Get $get): bool => $get('role') !== User::ROLE_BRANCH_EMPLOYEE)
                    ->dehydrated()
                    ->required(fn (Get $get): bool => $get('role') === User::ROLE_BRANCH_EMPLOYEE),
                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
                TextInput::make('password')
                    ->label('كلمة المرور')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
            ]);
    }
}
