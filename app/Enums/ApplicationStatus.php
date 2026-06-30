<?php

namespace App\Enums;

/**
 * Arizaning umumiy (yiriklashtirilgan) holati — dashboard/filtr uchun.
 */
enum ApplicationStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Лойиҳа',
            self::InProgress => 'Жараёнда',
            self::Approved => 'Тасдиқланган',
            self::Rejected => 'Бекор қилинган',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::InProgress => 'blue',
            self::Approved => 'green',
            self::Rejected => 'red',
        };
    }
}
