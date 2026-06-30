<?php

namespace App\Enums;

enum ContractStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Фаол',
            self::Suspended => 'Тўхтатилган',
            self::Terminated => 'Бекор қилинган',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Suspended => 'amber',
            self::Terminated => 'red',
        };
    }
}
