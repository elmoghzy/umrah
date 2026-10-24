<?php

namespace App\Reports;

use App\Models\BookingDetail;
use Illuminate\Database\Eloquent\Builder;

class TripMovementsQuery
{
    public static function make(): Builder
    {
        return BookingDetail::query()->whereHas('booking', fn (Builder $booking): Builder => $booking->forUser(auth()->user()))->with([
            'booking.customer',
            'booking.bookingType',
            'booking.broker',
            'booking.airplane',
            'hotel',
        ]);
    }

    public static function apply(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['hotel_id'] ?? null, fn (Builder $query, $value) => $query->where('hotel_id', $value))
            ->when($filters['city_id'] ?? null, fn (Builder $query, $value) => $query->where('city_id', $value))
            ->when($filters['company_id'] ?? null, fn (Builder $query, $value) => $query->where('company_id', $value))
            ->when($filters['booking_type_id'] ?? null, fn (Builder $query, $value) => $query->whereHas('booking', fn (Builder $booking) => $booking->where('booking_type_id', $value)))
            ->when($filters['broker_id'] ?? null, fn (Builder $query, $value) => $query->whereHas('booking', fn (Builder $booking) => $booking->where('broker_id', $value)))
            ->when($filters['airplane_id'] ?? null, fn (Builder $query, $value) => $query->whereHas('booking', fn (Builder $booking) => $booking->where('airplane_id', $value)))
            ->when($filters['date_range']['from'] ?? null, fn (Builder $query, $value) => $query->whereDate('check_in', '>=', $value))
            ->when($filters['date_range']['to'] ?? null, fn (Builder $query, $value) => $query->whereDate('check_in', '<=', $value));
    }
}
