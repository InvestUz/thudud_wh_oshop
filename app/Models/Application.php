<?php

namespace App\Models;

use App\Enums\ApplicationStage;
use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    protected $fillable = [
        'application_number',
        'object_id',
        'applicant_id',
        'status',
        'current_stage',
        'region_id',
        'district_id',
        'reject_reason',
        'draft_document_path',
        'submitted_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'current_stage' => ApplicationStage::class,
            'submitted_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    // --- Relationships -----------------------------------------------------

    public function object(): BelongsTo
    {
        return $this->belongsTo(RealEstateObject::class, 'object_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(ApplicationTransition::class)->orderBy('created_at');
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(ApplicationSurvey::class);
    }

    public function latestSurvey(): HasOne
    {
        return $this->hasOne(ApplicationSurvey::class)->latestOfMany();
    }

    public function adjacentAreas(): HasMany
    {
        return $this->hasMany(AdjacentArea::class);
    }

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }

    // --- Scopes ------------------------------------------------------------

    /** Hududiy filtr — xodim faqat o'z tumani arizalarini ko'radi. */
    public function scopeForDistrictOf(Builder $query, User $user): Builder
    {
        if ($user->district_id) {
            return $query->where('district_id', $user->district_id);
        }

        return $query;
    }

    public function scopeAtStage(Builder $query, ApplicationStage $stage): Builder
    {
        return $query->where('current_stage', $stage->value);
    }

    public function scopeInStages(Builder $query, array $stages): Builder
    {
        return $query->whereIn('current_stage', array_map(
            fn (ApplicationStage $s) => $s->value,
            $stages
        ));
    }

    // --- Helpers -----------------------------------------------------------

    public function stage(): ApplicationStage
    {
        return $this->current_stage;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->applicant_id === $user->id;
    }
}
