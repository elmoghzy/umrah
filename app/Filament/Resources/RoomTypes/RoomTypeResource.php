<?php

namespace App\Filament\Resources\RoomTypes;

use App\Filament\Resources\RoomTypes\Pages\CreateRoomType;
use App\Filament\Resources\RoomTypes\Pages\EditRoomType;
use App\Filament\Resources\RoomTypes\Pages\ListRoomTypes;
use App\Filament\Resources\RoomTypes\Schemas\RoomTypeForm;
use App\Filament\Resources\RoomTypes\Tables\RoomTypesTable;
use App\Models\RoomType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoomTypeResource extends Resource
{
    protected static ?string $model = RoomType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'البيانات الأساسية';

    protected static ?string $navigationLabel = 'أنواع الغرف';

    public static function form(Schema $schema): Schema
    {
        return RoomTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoomTypesTable::configure($table);
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
            'index' => ListRoomTypes::route('/'),
            'create' => CreateRoomType::route('/create'),
            'edit' => EditRoomType::route('/{record}/edit'),
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
