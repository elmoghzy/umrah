<?php

namespace App\Filament\Resources\Visas;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Visas\Pages\ListVisas;
use App\Filament\Resources\Visas\Tables\VisasTable;
use App\Models\Visa;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VisaResource extends Resource
{
    protected static ?string $model = Visa::class;

    protected static ?string $recordTitleAttribute = 'visa_number';

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['visa_number', 'customer.name_ar', 'customer.name_en', 'booking.booking_number'];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['booking:id,booking_number', 'customer:id,name_ar,name_en']);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return filled($record->visa_number)
            ? 'تأشيرة '.$record->visa_number
            : 'حجز '.($record->booking?->booking_number ?? '—');
    }

    /** @return array<string, string> */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'المعتمر' => $record->customer?->name_ar ?? $record->customer?->name_en ?? '—',
            'رقم الحجز' => (string) ($record->booking?->booking_number ?? '—'),
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): ?string
    {
        return BookingResource::getUrl('view', ['record' => $record->booking_id]);
    }

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
