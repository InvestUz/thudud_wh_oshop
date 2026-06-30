<?php

namespace App\Models;

use App\Enums\ContractActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAction extends Model
{
    protected $fillable = ['contract_id', 'user_id', 'action', 'reason'];

    protected function casts(): array
    {
        return [
            'action' => ContractActionType::class,
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
