<?php

namespace App\Filament\Resources\Hotels\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HotelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('city_id')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name_ar')
                    ->required(),
                TextInput::make('name_en'),
                TextInput::make('rooms_count')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
