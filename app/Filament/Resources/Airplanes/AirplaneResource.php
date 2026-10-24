<?php

namespace App\Filament\Resources\Airplanes;

use App\Filament\Resources\Airplanes\Pages\CreateAirplane;
use App\Filament\Resources\Airplanes\Pages\EditAirplane;
use App\Filament\Resources\Airplanes\Pages\ListAirplanes;
use App\Filament\Resources\Airplanes\Schemas\AirplaneForm;
use App\Filament\Resources\Airplanes\Tables\AirplanesTable;
use App\Models\Airplane;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AirplaneResource extends Resource
{
    protected static ?string $model = Airplane::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'البيانات الأساسية';

    protected static ?string $navigationLabel = 'الطائرات';

    public static function form(Schema $schema): Schema
    {
        return AirplaneForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AirplanesTable::configure($table);
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
            'index' => ListAirplanes::route('/'),
            'create' => CreateAirplane::route('/create'),
            'edit' => EditAirplane::route('/{record}/edit'),
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
