<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $fillable = ['region_id', 'name', 'code'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function mahallas(): HasMany
    {
        return $this->hasMany(Mahalla::class);
    }

    public function streets(): HasMany
    {
        return $this->hasMany(Street::class);
    }
}
