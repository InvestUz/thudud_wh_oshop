<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentSchedule extends Model
{
    protected $fillable = [
        'contract_id',
        'month_no',
        'period',
        'due_date',
        'amount',
        'penalty_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'penalty_amount' => 'decimal:2',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function totalDue(): float
    {
        return (float) $this->amount + (float) $this->penalty_amount;
    }
}
