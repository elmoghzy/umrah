<?php

namespace App\Filament\Resources\Brokers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BrokerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('contact_name'),
                TextInput::make('contact_phone')
                    ->tel(),
                Textarea::make('note')
                    ->columnSpanFull(),
            ]);
    }
}
