<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Кутилмоқда',
            self::Paid => 'Тўланган',
            self::Overdue => 'Муддати ўтган',
            self::Cancelled => 'Бекор қилинган',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Paid => 'green',
            self::Overdue => 'red',
            self::Cancelled => 'slate',
        };
    }
}
