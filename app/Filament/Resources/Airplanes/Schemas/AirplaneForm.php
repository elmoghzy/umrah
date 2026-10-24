<?php

namespace App\Filament\Resources\Airplanes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AirplaneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
            ]);
    }
}
