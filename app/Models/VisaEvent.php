<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['visa_id', 'user_id', 'from_status', 'to_status', 'note', 'created_at'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function visa(): BelongsTo
    {
        return $this->belongsTo(Visa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
