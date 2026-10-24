<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'size', 'price', 'total_price'];

    protected static function booted(): void
    {
        static::saving(function (self $roomType): void {
            $roomType->total_price = (float) $roomType->price * (int) $roomType->size;
        });
    }

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }
}
