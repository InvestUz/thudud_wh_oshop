<?php

namespace App\Models;

use App\Enums\RoleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'full_name',
        'pinfl',
        'tin',
        'email',
        'phone',
        'password',
        'region_id',
        'district_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // --- Relationships -----------------------------------------------------

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'applicant_id');
    }

    public function ownedObjects(): HasMany
    {
        return $this->hasMany(RealEstateObject::class, 'owner_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'owner_id');
    }

    // --- Helpers -----------------------------------------------------------

    public function roleType(): ?RoleType
    {
        $name = $this->roles->first()?->name;

        return $name ? RoleType::tryFrom($name) : null;
    }

    public function isRole(RoleType $role): bool
    {
        return $this->hasRole($role->value);
    }

    public function displayName(): string
    {
        return $this->full_name ?: $this->name;
    }

    public function isPipelineActor(): bool
    {
        $role = $this->roleType();

        return $role !== null && in_array($role, RoleType::pipelineRoles(), true);
    }

    public function canControlContracts(): bool
    {
        $role = $this->roleType();

        return $role !== null && in_array($role, RoleType::contractControlRoles(), true);
    }

    public function canViewMonitoring(): bool
    {
        $role = $this->roleType();

        return $role !== null && in_array($role, RoleType::monitoringRoles(), true);
    }
}
