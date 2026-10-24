<?php

namespace App\Filament\Resources\RoomTypes\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;

class RoomTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('size')
                    ->required()
                    ->numeric()
                    ->live(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->suffix(config('umrah.currency_symbol'))
                    ->default(0)
                    ->live(),
                Placeholder::make('total_price')
                    ->label('السعر الإجمالي')
                    ->content(fn (Get $get): string => Number::currency(
                        (float) ($get('price') ?? 0) * (int) ($get('size') ?? 0),
                        config('umrah.currency_code'),
                        config('umrah.currency_locale'),
                        (int) config('umrah.currency_decimal_places'),
                    )),
            ]);
    }
}
