<?php

namespace App\Filament\Resources\Transports;

use App\Filament\Resources\Transports\Pages\CreateTransport;
use App\Filament\Resources\Transports\Pages\EditTransport;
use App\Filament\Resources\Transports\Pages\ListTransports;
use App\Filament\Resources\Transports\Schemas\TransportForm;
use App\Filament\Resources\Transports\Tables\TransportsTable;
use App\Models\Transport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransportResource extends Resource
{
    protected static ?string $model = Transport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'البيانات الأساسية';

    protected static ?string $navigationLabel = 'وسائل المواصلات';

    protected static ?string $modelLabel = 'وسيلة مواصلات';

    protected static ?string $pluralModelLabel = 'وسائل المواصلات';

    public static function form(Schema $schema): Schema
    {
        return TransportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransportsTable::configure($table);
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
            'index' => ListTransports::route('/'),
            'create' => CreateTransport::route('/create'),
            'edit' => EditTransport::route('/{record}/edit'),
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
