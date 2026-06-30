<?php

namespace App\Models;

use App\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    protected $fillable = [
        'contract_number',
        'application_id',
        'object_id',
        'owner_id',
        'region_id',
        'district_id',
        'contract_date',
        'total_amount',
        'monthly_amount',
        'penalty_rate',
        'start_date',
        'end_date',
        'status',
        'control_status',
        'problem_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContractStatus::class,
            'contract_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'total_amount' => 'decimal:2',
            'monthly_amount' => 'decimal:2',
            'penalty_rate' => 'decimal:2',
        ];
    }

    // --- Relationships -----------------------------------------------------

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function object(): BelongsTo
    {
        return $this->belongsTo(RealEstateObject::class, 'object_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(PaymentSchedule::class)->orderBy('month_no');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ContractAction::class)->latest();
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeForDistrictOf(Builder $query, User $user): Builder
    {
        if ($user->district_id) {
            return $query->where('district_id', $user->district_id);
        }

        return $query;
    }

    // --- Helpers -----------------------------------------------------------

    public function isActive(): bool
    {
        return $this->status === ContractStatus::Active;
    }

    public function paidAmount(): float
    {
        return (float) $this->schedules->where('status', \App\Enums\PaymentStatus::Paid)->sum('amount');
    }

    public function overdueAmount(): float
    {
        return (float) $this->schedules->where('status', \App\Enums\PaymentStatus::Overdue)->sum('amount');
    }

    public function totalPenalty(): float
    {
        return (float) $this->schedules->sum('penalty_amount');
    }
}
