<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['booking_detail_id', 'room_type_id', 'price', 'total_price'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function bookingDetail(): BelongsTo
    {
        return $this->belongsTo(BookingDetail::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
