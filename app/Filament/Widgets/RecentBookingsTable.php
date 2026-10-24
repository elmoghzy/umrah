<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentBookingsTable extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'أحدث الحجوزات';

    public static function canView(): bool
    {
        return BookingResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->forUser(auth()->user())
                    ->with(['customer', 'bookingType'])
                    ->withCount('residents')
                    ->withMin('details', 'check_in')
                    ->latest('id')
            )
            ->columns([
                TextColumn::make('booking_number')
                    ->label('رقم الحجز')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('customer.name_ar')
                    ->label('ولي الأمر')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('residents_count')
                    ->label('المعتمرون')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('details_min_check_in')
                    ->label('تاريخ السفر')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('stay_scope')
                    ->label('النطاق')
                    ->badge(),
                TextColumn::make('net_amount')
                    ->label('الصافي')
                    ->money(
                        config('umrah.currency_code'),
                        locale: config('umrah.currency_locale'),
                        decimalPlaces: (int) config('umrah.currency_decimal_places'),
                    )
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('تاريخ الحجز')
                    ->dateTime('d/m/Y، h:i A')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('عرض')
                    ->url(fn (Booking $record): string => BookingResource::getUrl('view', ['record' => $record])),
            ])
            ->recordUrl(fn (Booking $record): string => BookingResource::getUrl('view', ['record' => $record]))
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('لا توجد حجوزات بعد')
            ->emptyStateDescription('ستظهر الحجوزات الجديدة هنا فور حفظها.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
