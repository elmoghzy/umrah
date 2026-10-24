<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('guardian_id')
                    ->label('ولي الأمر')
                    ->relationship(
                        'guardian',
                        'name_ar',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->whereNull('guardian_id'),
                    )
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name_ar')->label('اسم ولي الأمر')->required(),
                        TextInput::make('phone')->label('الهاتف')->tel(),
                    ])
                    ->createOptionUsing(fn (array $data): int => Customer::create([
                        ...$data,
                        'guardian_id' => null,
                    ])->getKey())
                    ->hiddenOn(['create', 'edit'])
                    ->nullable(),
                TextInput::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->required(),
                TextInput::make('name_en')
                    ->label('الاسم بالإنجليزية'),
                TextInput::make('phone')
                    ->label('الهاتف')
                    ->tel(),
                Repeater::make('dependents')
                    ->label('المعتمرون التابعون')
                    ->visibleOn('create')
                    ->table([
                        TableColumn::make('اسم المعتمر')->markAsRequired(),
                        TableColumn::make('الاسم بالإنجليزية'),
                        TableColumn::make('الهاتف'),
                    ])
                    ->schema([
                        TextInput::make('name_ar')
                            ->required(),
                        TextInput::make('name_en')
                            ->nullable(),
                        TextInput::make('phone')
                            ->nullable()
                            ->tel(),
                    ])
                    ->addActionLabel('إضافة معتمر')
                    ->defaultItems(0)
                    ->reorderable(false)
                    ->columnSpanFull(),
            ]);
    }
}
