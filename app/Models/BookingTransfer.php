<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id', 'transport_id', 'from_city_id', 'to_city_id',
        'travel_date', 'travel_time', 'price', 'note',
    ];

    protected function casts(): array
    {
        return [
            'travel_date' => 'date',
            'price' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function transport(): BelongsTo
    {
        return $this->belongsTo(Transport::class);
    }

    public function fromCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'from_city_id');
    }

    public function toCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'to_city_id');
    }
}
