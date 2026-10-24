<?php

namespace App\Filament\Resources\Meals;

use App\Filament\Resources\Meals\Pages\CreateMeal;
use App\Filament\Resources\Meals\Pages\EditMeal;
use App\Filament\Resources\Meals\Pages\ListMeals;
use App\Filament\Resources\Meals\Schemas\MealForm;
use App\Filament\Resources\Meals\Tables\MealsTable;
use App\Models\Meal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MealResource extends Resource
{
    protected static ?string $model = Meal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCake;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'البيانات الأساسية';

    protected static ?string $navigationLabel = 'الوجبات';

    public static function form(Schema $schema): Schema
    {
        return MealForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MealsTable::configure($table);
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
            'index' => ListMeals::route('/'),
            'create' => CreateMeal::route('/create'),
            'edit' => EditMeal::route('/{record}/edit'),
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
