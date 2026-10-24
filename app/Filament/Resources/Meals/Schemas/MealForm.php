<?php

namespace App\Filament\Resources\Meals\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MealForm
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
