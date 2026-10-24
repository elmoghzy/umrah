<?php

namespace App\Filament\Resources\Visas;

use App\Filament\Resources\Visas\Pages\ListVisas;
use App\Filament\Resources\Visas\Tables\VisasTable;
use App\Models\Visa;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VisaResource extends Resource
{
    protected static ?string $model = Visa::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'الحجوزات';

    protected static ?string $navigationLabel = 'التأشيرات';

    protected static ?string $modelLabel = 'تأشيرة';

    protected static ?string $pluralModelLabel = 'التأشيرات';

    public static function table(Table $table): Table
    {
        return VisasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVisas::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forUser(auth()->user());
    }

    public static function getNavigationLabel(): string
    {
        return 'التأشيرات';
    }

    public static function getModelLabel(): string
    {
        return 'تأشيرة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'التأشيرات';
    }
}
