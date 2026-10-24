<?php

namespace App\Filament\Resources\Transports\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('الاسم')
                    ->required()
                    ->maxLength(150),
                Textarea::make('note')
                    ->label('ملاحظة')
                    ->rows(3),
            ]);
    }
}
