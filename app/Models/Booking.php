<?php

namespace App\Models;

use App\Enums\StayScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id', 'broker_id', 'airplane_id', 'booking_type_id',
        'booking_number', 'notes', 'services_note', 'service_amount',
        'total_amount', 'transport_amount', 'discount', 'net_amount', 'stay_scope',
        'created_by', 'updated_by',
    ];

    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        if (! $user || ! $user->hasPanelAccess()) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isMainAdmin()) {
            return $query;
        }

        if (! $user->isBranchEmployee() || ! $user->broker_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('broker_id', $user->broker_id);
    }

    protected function casts(): array
    {
        return [
            'service_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'transport_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'stay_scope' => StayScope::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function broker(): BelongsTo
    {
        return $this->belongsTo(Broker::class);
    }

    public function airplane(): BelongsTo
    {
        return $this->belongsTo(Airplane::class);
    }

    public function bookingType(): BelongsTo
    {
        return $this->belongsTo(BookingType::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(BookingDetail::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(BookingTransfer::class);
    }

    public function visas(): HasMany
    {
        return $this->hasMany(Visa::class);
    }

    public function publicUrl(): ?string
    {
        if (! Route::has('visas.public') || blank($this->public_token)) {
            return null;
        }

        $path = route('visas.public', ['token' => $this->public_token], absolute: false);

        $baseUrl = filled(config('umrah.public_url')) ? config('umrah.public_url') : config('app.url');

        return rtrim((string) $baseUrl, '/').'/'.ltrim($path, '/');
    }

    public function madinaDetail(): HasOne
    {
        return $this->hasOne(BookingDetail::class)->where('city_id', 2);
    }

    public function makkaDetail(): HasOne
    {
        return $this->hasOne(BookingDetail::class)->where('city_id', 1);
    }

    public function residents(): HasMany
    {
        return $this->hasMany(RoomResident::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking): void {
            if (blank($booking->public_token)) {
                $booking->public_token = Str::random(48);
            }
        });
    }
}
