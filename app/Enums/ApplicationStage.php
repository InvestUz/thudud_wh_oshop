<?php

namespace App\Enums;

/**
 * Ariza pipeline bosqichlari + ruxsat etilgan o'tishlar matritsasi.
 *
 * draft -> moderation -> responsible_review -> deputy_review
 *       -> head_review -> awaiting_signature -> approved | rejected
 */
enum ApplicationStage: string
{
    case Draft = 'draft';
    case Moderation = 'moderation';
    case ResponsibleReview = 'responsible_review';
    case DeputyReview = 'deputy_review';
    case HeadReview = 'head_review';
    case AwaitingSignature = 'awaiting_signature';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Лойиҳа (қоралама)',
            self::Moderation => 'Модерацияда',
            self::ResponsibleReview => "Мас'ул ходим текширувида",
            self::DeputyReview => 'Ўринбосар кўригида',
            self::HeadReview => 'Раҳбар тасдиғида',
            self::AwaitingSignature => 'Тадбиркор имзосида',
            self::Approved => 'Тасдиқланган',
            self::Rejected => 'Бекор қилинган',
        };
    }

    /** Badge rangi (CSS klassi suffiksi: badge-{color}). */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Moderation => 'amber',
            self::ResponsibleReview => 'blue',
            self::DeputyReview => 'teal',
            self::HeadReview => 'violet',
            self::AwaitingSignature => 'blue',
            self::Approved => 'green',
            self::Rejected => 'red',
        };
    }

    /** Shu bosqichda harakat qila oladigan rollar. */
    public function actingRoles(): array
    {
        return match ($this) {
            self::Draft => [RoleType::Applicant],
            self::Moderation => [RoleType::Moderator],
            self::ResponsibleReview => [RoleType::ResponsibleOfficer],
            self::DeputyReview => [RoleType::DeputyHead],
            self::HeadReview => [RoleType::Head],
            self::AwaitingSignature => [RoleType::Applicant],
            self::Approved, self::Rejected => [],
        };
    }

    /**
     * Shu bosqichdan ruxsat etilgan o'tishlar: [action => target stage].
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Draft => [
                TransitionAction::Submit->value => self::Moderation,
            ],
            self::Moderation => [
                TransitionAction::Forward->value => self::ResponsibleReview,
                TransitionAction::Reject->value => self::Rejected,
            ],
            self::ResponsibleReview => [
                TransitionAction::Forward->value => self::DeputyReview,
                TransitionAction::Return->value => self::Moderation,
                TransitionAction::Reject->value => self::Rejected,
            ],
            self::DeputyReview => [
                TransitionAction::Forward->value => self::HeadReview,
                TransitionAction::Return->value => self::ResponsibleReview,
                TransitionAction::Reject->value => self::Rejected,
            ],
            self::HeadReview => [
                TransitionAction::Approve->value => self::AwaitingSignature,
                TransitionAction::Return->value => self::DeputyReview,
                TransitionAction::Reject->value => self::Rejected,
            ],
            self::AwaitingSignature => [
                TransitionAction::Sign->value => self::Approved,
                TransitionAction::Reject->value => self::Rejected,
            ],
            self::Approved, self::Rejected => [],
        };
    }

    /** Berilgan harakat shu bosqichdan ruxsat etilganmi? */
    public function canPerform(TransitionAction $action): bool
    {
        return array_key_exists($action->value, $this->transitions());
    }

    public function targetFor(TransitionAction $action): ?self
    {
        return $this->transitions()[$action->value] ?? null;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected], true);
    }

    /** Pipeline progress indikatori uchun bosqich tartibi. */
    public function order(): int
    {
        return match ($this) {
            self::Draft => 0,
            self::Moderation => 1,
            self::ResponsibleReview => 2,
            self::DeputyReview => 3,
            self::HeadReview => 4,
            self::AwaitingSignature => 5,
            self::Approved, self::Rejected => 6,
        };
    }

    /** Progress chizig'i uchun asosiy bosqichlar (terminalsiz). */
    public static function pipeline(): array
    {
        return [
            self::Draft,
            self::Moderation,
            self::ResponsibleReview,
            self::DeputyReview,
            self::HeadReview,
            self::AwaitingSignature,
            self::Approved,
        ];
    }

    /**
     * Berilgan rol harakat qila oladigan bosqichlar.
     *
     * @return array<int, self>
     */
    public static function stagesForRole(RoleType $role): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $stage) => in_array($role, $stage->actingRoles(), true)
        ));
    }

    public function toStatus(): ApplicationStatus
    {
        return match ($this) {
            self::Draft => ApplicationStatus::Draft,
            self::Approved => ApplicationStatus::Approved,
            self::Rejected => ApplicationStatus::Rejected,
            default => ApplicationStatus::InProgress,
        };
    }
}
