<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObjectTenant extends Model
{
    protected $fillable = ['object_id', 'tin_pinfl', 'name', 'activity_type'];

    public function object(): BelongsTo
    {
        return $this->belongsTo(RealEstateObject::class, 'object_id');
    }
}
