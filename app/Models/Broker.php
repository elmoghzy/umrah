<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Broker extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'contact_name', 'contact_phone', 'note'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
