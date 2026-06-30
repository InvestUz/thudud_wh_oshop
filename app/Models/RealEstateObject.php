<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Noturar (tijorat) ob'ekt. Jadval nomi `objects`.
 */
class RealEstateObject extends Model
{
    protected $table = 'objects';

    protected $fillable = [
        'owner_id',
        'cadastre_number',
        'hokimiyat_cadastre',
        'tin_pinfl',
        'company_name',
        'director_name',
        'phone',
        'region_id',
        'district_id',
        'mahalla_id',
        'street',
        'street_status',
        'house_number',
        'created_by',
    ];

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

    public function mahalla(): BelongsTo
    {
        return $this->belongsTo(Mahalla::class);
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(ObjectTenant::class, 'object_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'object_id');
    }

    public function fullAddress(): string
    {
        return collect([
            $this->district?->name,
            $this->street,
            $this->house_number ? '№'.$this->house_number : null,
        ])->filter()->implode(', ');
    }
}
