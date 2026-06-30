<?php

namespace App\Enums;

/**
 * Foydalanuvchi rollari (8 ta). Spatie rol nomi sifatida value ishlatiladi.
 */
enum RoleType: string
{
    case Applicant = 'applicant';                 // Mulkdor
    case Moderator = 'moderator';                 // Moderator
    case ResponsibleOfficer = 'responsible_officer'; // Mas'ul xodim
    case WorkingGroup = 'working_group';          // Tuman ishchi guruhi
    case DeputyHead = 'deputy_head';              // O'rinbosar (zam rahbar)
    case Head = 'head';                           // Rahbar
    case Lawyer = 'lawyer';                        // Yurist
    case Compliance = 'compliance';               // Komplayens

    public function label(): string
    {
        return match ($this) {
            self::Applicant => 'Мулкдор',
            self::Moderator => 'Модератор',
            self::ResponsibleOfficer => "Мас'ул ходим",
            self::WorkingGroup => 'Ишчи гуруҳ',
            self::DeputyHead => 'Ўринбосар',
            self::Head => 'Раҳбар',
            self::Lawyer => 'Юрист',
            self::Compliance => 'Комплаенс',
        };
    }

    /** Pipeline'da qatnashadigan rollar (Ishchi guruh, Yurist va Komplayens YO'Q). */
    public static function pipelineRoles(): array
    {
        return [
            self::Moderator,
            self::ResponsibleOfficer,
            self::DeputyHead,
            self::Head,
        ];
    }

    /** Shartnoma nazoratiga ruxsati bor rollar. */
    public static function contractControlRoles(): array
    {
        return [
            self::DeputyHead,
            self::Head,
            self::Lawyer,
            self::Compliance,
        ];
    }

    /** Monitoring/hisobot sahifasini ko'ra oladigan rollar. */
    public static function monitoringRoles(): array
    {
        return [
            self::DeputyHead,
            self::Head,
            self::Compliance,
        ];
    }

    public static function values(): array
    {
        return array_map(fn (self $r) => $r->value, self::cases());
    }
}
