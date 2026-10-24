<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hotel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['city_id', 'name_ar', 'name_en', 'rooms_count'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
