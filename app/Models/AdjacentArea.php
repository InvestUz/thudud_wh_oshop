<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdjacentArea extends Model
{
    protected $fillable = ['application_id', 'activity', 'area_m2', 'structures'];

    protected function casts(): array
    {
        return [
            'area_m2' => 'decimal:2',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
