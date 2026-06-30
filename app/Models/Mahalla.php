<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mahalla extends Model
{
    protected $fillable = ['district_id', 'name'];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
