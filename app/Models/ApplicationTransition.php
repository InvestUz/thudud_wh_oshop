<?php

namespace App\Models;

use App\Enums\ApplicationStage;
use App\Enums\TransitionAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationTransition extends Model
{
    protected $fillable = [
        'application_id',
        'from_stage',
        'to_stage',
        'action',
        'performed_by',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'from_stage' => ApplicationStage::class,
            'to_stage' => ApplicationStage::class,
            'action' => TransitionAction::class,
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
