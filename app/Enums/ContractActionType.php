<?php

namespace App\Enums;

enum ContractActionType: string
{
    case Suspend = 'suspend';
    case Resume = 'resume';
    case Terminate = 'terminate';
    case View = 'view';

    public function label(): string
    {
        return match ($this) {
            self::Suspend => 'Тўхтатиш',
            self::Resume => 'Қайта тиклаш',
            self::Terminate => 'Бекор қилиш',
            self::View => 'Кўрилди',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Suspend => 'amber',
            self::Resume => 'green',
            self::Terminate => 'red',
            self::View => 'slate',
        };
    }
}
