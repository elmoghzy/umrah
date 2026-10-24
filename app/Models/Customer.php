<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['guardian_id', 'name_ar', 'name_en', 'phone', 'note'];

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(self::class, 'guardian_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(self::class, 'guardian_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function residentBookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'room_residents');
    }

    protected static function booted(): void
    {
        static::saving(function (self $customer): void {
            if ($customer->guardian_id === null) {
                return;
            }

            if ($customer->exists && (int) $customer->guardian_id === (int) $customer->getKey()) {
                throw ValidationException::withMessages([
                    'guardian_id' => 'لا يمكن للعميل أن يكون وليًا لنفسه.',
                ]);
            }

            $guardian = self::query()->find($customer->guardian_id);

            if ($guardian?->guardian_id !== null) {
                throw ValidationException::withMessages([
                    'guardian_id' => 'لا يمكن اختيار مرافق كولي أمر لتجنب إنشاء سلسلة أولياء أمور.',
                ]);
            }
        });
    }
}
