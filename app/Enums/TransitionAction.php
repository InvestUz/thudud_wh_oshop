<?php

namespace App\Enums;

/**
 * Pipeline'dagi harakat turlari (audit jurnaliga yoziladi).
 */
enum TransitionAction: string
{
    case Submit = 'submit';   // mulkdor arizani topshiradi (draft -> moderation)
    case Forward = 'forward'; // keyingi bosqichga uzatish
    case Return = 'return';   // oldingi bosqichga qaytarish
    case Approve = 'approve'; // rahbar tasdiqlaydi (head_review -> awaiting_signature)
    case Sign = 'sign';       // tadbirkor shartnomani imzolaydi (awaiting_signature -> approved)
    case Reject = 'reject';   // bekor qilish

    public function label(): string
    {
        return match ($this) {
            self::Submit => 'Топширилди',
            self::Forward => 'Узатилди',
            self::Return => 'Қайтарилди',
            self::Approve => 'Тасдиқланди',
            self::Sign => 'Имзоланди',
            self::Reject => 'Бекор қилинди',
        };
    }

    public function buttonLabel(): string
    {
        return match ($this) {
            self::Submit => 'Топшириш',
            self::Forward => 'Кейингига узатиш',
            self::Return => 'Қайтариш',
            self::Approve => 'Тасдиқлаш',
            self::Sign => 'Шартномани имзолаш',
            self::Reject => 'Бекор қилиш',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Submit, self::Forward => 'teal',
            self::Approve, self::Sign => 'green',
            self::Return => 'amber',
            self::Reject => 'red',
        };
    }
}
