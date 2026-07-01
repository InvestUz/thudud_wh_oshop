<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationSurvey extends Model
{
    /** Фойдаланиш мақсади — танлаш учун рухсат этилган қийматлар. */
    public const USAGE_PURPOSES = ['Умумий овқатланиш', 'Савдо', 'Хизмат'];

    /** Кўча тури — танлаш учун рухсат этилган қийматлар. */
    public const STREET_TYPES = ['Марказий', 'Шох (магистрал)', 'Ички', 'Туризм (гастрономик)'];

    public const ACTIVITY_TYPES = ['Умумий овқатланиш', 'Савдо', 'Хизмат'];

    public const PERMIT_STATUSES = ['Мавжуд', 'Мавжуд эмас'];

    protected $fillable = [
        'application_id',
        'surveyed_by',
        'stage',
        'length_m',
        'width_m',
        'calculated_area',
        'total_area',
        'calc_method',
        'facade_length_m',
        'terrace_sides',
        'street_type',
        'distance_to_road_m',
        'distance_to_sidewalk_m',
        'usage_purpose',
        'activity_type',
        'terrace_structures',
        'permanent_structures',
        'permit',
        'latitude',
        'longitude',
        'extra_info',
        'photos',
        'documents',
        'study_report_path',
        'geo_area',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'length_m' => 'decimal:2',
            'width_m' => 'decimal:2',
            'calculated_area' => 'decimal:2',
            'total_area' => 'decimal:2',
            'facade_length_m' => 'decimal:2',
            'distance_to_road_m' => 'decimal:2',
            'distance_to_sidewalk_m' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'photos' => 'array',
            'documents' => 'array',
            'geo_area' => 'array',
            'data' => 'array',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function surveyor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'surveyed_by');
    }
}
