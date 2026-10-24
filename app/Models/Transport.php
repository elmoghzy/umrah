<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'note'];

    public function transfers(): HasMany
    {
        return $this->hasMany(BookingTransfer::class);
    }
}
