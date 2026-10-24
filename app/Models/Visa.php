<?php

namespace App\Models;

use App\Enums\VisaStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Visa extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'booking_id', 'customer_id', 'status', 'file_path', 'original_name', 'file_size',
        'issued_at', 'issued_by', 'rejected_reason', 'visa_number', 'downloads_count',
        'first_downloaded_at', 'last_downloaded_at',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(VisaEvent::class)->latest('created_at');
    }

    public function latestEvent(): HasOne
    {
        return $this->hasOne(VisaEvent::class)->latestOfMany();
    }

    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        if (! $user || ! $user->hasPanelAccess()) {
            return $query->whereNull('id');
        }

        if ($user->isMainAdmin()) {
            return $query;
        }

        if (! $user->isBranchEmployee() || ! $user->broker_id) {
            return $query->whereNull('id');
        }

        return $query->whereHas('booking', fn (Builder $booking): Builder => $booking->forUser($user));
    }

    public function isDownloadable(): bool
    {
        return $this->status === VisaStatus::Issued
            && filled($this->file_path)
            && Storage::disk(config('umrah.visa_disk', 'visas'))->exists($this->file_path);
    }

    protected function casts(): array
    {
        return [
            'status' => VisaStatus::class,
            'file_size' => 'integer',
            'issued_at' => 'datetime',
            'downloads_count' => 'integer',
            'first_downloaded_at' => 'datetime',
            'last_downloaded_at' => 'datetime',
        ];
    }
}
