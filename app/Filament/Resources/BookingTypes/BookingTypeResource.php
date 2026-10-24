<?php

namespace App\Filament\Resources\BookingTypes;

use App\Filament\Resources\BookingTypes\Pages\CreateBookingType;
use App\Filament\Resources\BookingTypes\Pages\EditBookingType;
use App\Filament\Resources\BookingTypes\Pages\ListBookingTypes;
use App\Filament\Resources\BookingTypes\Schemas\BookingTypeForm;
use App\Filament\Resources\BookingTypes\Tables\BookingTypesTable;
use App\Models\BookingType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingTypeResource extends Resource
{
    protected static ?string $model = BookingType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'البيانات الأساسية';

    protected static ?string $navigationLabel = 'أنواع الحجوزات';

    public static function form(Schema $schema): Schema
    {
        return BookingTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookingTypes::route('/'),
            'create' => CreateBookingType::route('/create'),
            'edit' => EditBookingType::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
