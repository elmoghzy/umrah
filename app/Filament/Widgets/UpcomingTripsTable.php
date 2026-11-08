<?php

namespace App\Filament\Widgets;

use App\Models\BookingDetail;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingTripsTable extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(BookingDetail::query()->whereHas('booking', fn (Builder $booking): Builder => $booking->forUser(auth()->user()))->with(['booking.customer', 'hotel'])->whereBetween('check_in', [today(), today()->addDays(30)])->orderBy('check_in'))
            ->columns([
                TextColumn::make('booking.booking_number')->label('رقم الحجز')->searchable(),
                TextColumn::make('booking.customer.name_ar')->label('ولي الأمر')->searchable(),
                TextColumn::make('hotel.name_ar')->label('الفندق')->searchable(),
                TextColumn::make('check_in')->label('الدخول')->date(),
            ])
            ->deferLoading(app()->isProduction())
            ->paginated([10, 25, 50])
            ->persistSearchInSession()
            ->persistSortInSession()
            ->searchDebounce('500ms');
    }
}
