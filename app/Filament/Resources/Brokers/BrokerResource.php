<?php

namespace App\Filament\Resources\Brokers;

use App\Filament\Resources\Brokers\Pages\CreateBroker;
use App\Filament\Resources\Brokers\Pages\EditBroker;
use App\Filament\Resources\Brokers\Pages\ListBrokers;
use App\Filament\Resources\Brokers\Schemas\BrokerForm;
use App\Filament\Resources\Brokers\Tables\BrokersTable;
use App\Models\Broker;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BrokerResource extends Resource
{
    protected static ?string $model = Broker::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'البيانات الأساسية';

    protected static ?string $navigationLabel = 'الفروع';

    protected static ?string $modelLabel = 'فرع';

    protected static ?string $pluralModelLabel = 'الفروع';

    public static function form(Schema $schema): Schema
    {
        return BrokerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BrokersTable::configure($table);
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
            'index' => ListBrokers::route('/'),
            'create' => CreateBroker::route('/create'),
            'edit' => EditBroker::route('/{record}/edit'),
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
