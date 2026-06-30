<?php

namespace App\Services;

use App\Enums\ApplicationStage;
use App\Enums\TransitionAction;
use App\Exceptions\WorkflowException;
use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Ariza pipeline'ini boshqaradi: o'tkazish / qaytarish / tasdiqlash / bekor qilish,
 * ruxsat tekshiruvi va audit (application_transitions) yozuvi.
 */
class ApplicationWorkflowService
{
    public function __construct(
        private ContractService $contractService,
        private ContractDraftService $draftService,
    ) {
    }

    /**
     * Foydalanuvchi shu arizada qaysi harakatlarni bajara oladi.
     *
     * @return array<int, TransitionAction>
     */
    public function availableActions(Application $application, User $user): array
    {
        $stage = $application->stage();

        if ($stage->isTerminal() || ! $this->userCanActAtStage($application, $user)) {
            return [];
        }

        return array_map(
            fn (string $action) => TransitionAction::from($action),
            array_keys($stage->transitions())
        );
    }

    public function canPerform(Application $application, User $user, TransitionAction $action): bool
    {
        $stage = $application->stage();

        return ! $stage->isTerminal()
            && $stage->canPerform($action)
            && $this->userCanActAtStage($application, $user);
    }

    /**
     * Asosiy o'tkazish metodi. Bitta DB tranzaksiyada:
     * audit yozadi, bosqich/holatni yangilaydi, approved bo'lsa shartnoma yaratadi.
     */
    public function transition(
        Application $application,
        User $user,
        TransitionAction $action,
        ?string $comment = null
    ): Application {
        $from = $application->stage();

        if (! $this->canPerform($application, $user, $action)) {
            throw new WorkflowException(
                "Бу ҳаракатга рухсат йўқ ёки жорий босқичда мумкин эмас: {$action->value}."
            );
        }

        $to = $from->targetFor($action);

        if ($to === null) {
            throw new WorkflowException('Мақсадли босқич аниқланмади.');
        }

        return DB::transaction(function () use ($application, $user, $action, $comment, $from, $to) {
            $application->transitions()->create([
                'from_stage' => $from,
                'to_stage' => $to,
                'action' => $action,
                'performed_by' => $user->id,
                'comment' => $comment,
            ]);

            $application->current_stage = $to;
            $application->status = $to->toStatus();

            if ($action === TransitionAction::Submit) {
                $application->submitted_at = now();
            }

            if ($to === ApplicationStage::Rejected) {
                $application->reject_reason = $comment;
                $application->finished_at = now();
            }

            if ($to === ApplicationStage::Approved) {
                $application->finished_at = now();
            }

            $application->save();

            // Mas'ul xodim ariza ma'lumotlarini to'ldirib o'rinbosarga uzatganda —
            // shu ma'lumotlar asosida shartnoma loyihasi (.docx) yaratiladi.
            if ($from === ApplicationStage::ResponsibleReview && $to === ApplicationStage::DeputyReview) {
                $application->draft_document_path = $this->draftService->generate($application);
                $application->save();
            }

            // Rahbar tasdiqladi (head_review -> awaiting_signature): shartnoma loyihasi
            // rahbar (ijaraga beruvchi) imzosi + QR bilan qayta yaratiladi; tadbirkor imzosi kutiladi.
            if ($to === ApplicationStage::AwaitingSignature) {
                $application->draft_document_path = $this->draftService->generate($application);
                $application->save();
            }

            // Tadbirkor imzoladi (awaiting_signature -> approved): shartnoma + grafik + invoyslar
            // avtomatik yaratiladi va loyiha tadbirkor (ijaraga oluvchi) QR imzosi bilan yakunlanadi.
            if ($to === ApplicationStage::Approved && ! $application->contract()->exists()) {
                $this->contractService->createFromApplication($application);
            }
            if ($to === ApplicationStage::Approved) {
                $application->draft_document_path = $this->draftService->generate($application);
                $application->save();
            }

            return $application->refresh();
        });
    }

    /** Mulkdor draft arizani topshiradi (qisqartma). */
    public function submit(Application $application, User $user, ?string $comment = null): Application
    {
        return $this->transition($application, $user, TransitionAction::Submit, $comment);
    }

    /**
     * Foydalanuvchi shu arizaning joriy bosqichida harakat qila oladimi?
     * Qoidalar: rol bosqichga mos + hududiy filtr (o'z tumani).
     */
    public function userCanActAtStage(Application $application, User $user): bool
    {
        $stage = $application->stage();

        if ($stage->isTerminal()) {
            return false;
        }

        // Draft (topshirish) va Tadbirkor imzosi bosqichлари — faqat ariza egаси (mulkdor).
        if (in_array($stage, [ApplicationStage::Draft, ApplicationStage::AwaitingSignature], true)) {
            return $application->isOwnedBy($user);
        }

        $role = $user->roleType();
        if ($role === null || ! in_array($role, $stage->actingRoles(), true)) {
            return false;
        }

        // Hududiy filtr — xodim faqat o'z tumani arizalarini boshqaradi.
        if ($user->district_id !== null && $application->district_id !== $user->district_id) {
            return false;
        }

        return true;
    }
}
