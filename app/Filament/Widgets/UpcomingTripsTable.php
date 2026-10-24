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
            ->query(BookingDetail::query()->whereHas('booking', fn (Builder $booking): Builder => $booking->forUser(auth()->user()))->with(['booking.customer', 'hotel'])->whereDate('check_in', '>=', today())->whereDate('check_in', '<=', today()->addDays(30))->orderBy('check_in'))
            ->columns([
                TextColumn::make('booking.booking_number')->label('رقم الحجز'),
                TextColumn::make('booking.customer.name_ar')->label('ولي الأمر'),
                TextColumn::make('hotel.name_ar')->label('الفندق'),
                TextColumn::make('check_in')->label('الدخول')->date(),
            ]);
    }
}
