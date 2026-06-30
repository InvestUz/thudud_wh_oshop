<?php

namespace App\Services;

use App\Enums\ContractActionType;
use App\Enums\ContractStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Application;
use App\Models\Contract;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Shartnoma yaratish (12 oylik grafik + invoyslar) va nazorat harakatlari.
 */
class ContractService
{
    /** Demo uchun 1 m² uchun oylik ijara narxi (so'm). */
    public const MONTHLY_RATE_PER_M2 = 45000;

    /** Standart kunlik penya foizi. */
    public const DEFAULT_PENALTY_RATE = 0.1;

    public const TERM_MONTHS = 12;

    /**
     * Tasdiqlangan arizadan shartnoma + to'lov grafigi + invoyslar yaratadi.
     *
     * @param  array{area?:float,monthly_amount?:float,start_date?:string,contract_date?:string,penalty_rate?:float}  $options
     */
    public function createFromApplication(Application $application, array $options = []): Contract
    {
        return DB::transaction(function () use ($application, $options) {
            $object = $application->object;

            $area = $options['area'] ?? $this->resolveArea($application);
            $monthly = round($options['monthly_amount'] ?? $area * self::MONTHLY_RATE_PER_M2, 2);
            $penaltyRate = $options['penalty_rate'] ?? self::DEFAULT_PENALTY_RATE;
            $startDate = isset($options['start_date'])
                ? Carbon::parse($options['start_date'])->startOfDay()
                : now()->addMonth()->startOfMonth();
            $contractDate = isset($options['contract_date'])
                ? Carbon::parse($options['contract_date'])->startOfDay()
                : now()->startOfDay();
            $endDate = $startDate->copy()->addMonths(self::TERM_MONTHS)->subDay();
            $total = round($monthly * self::TERM_MONTHS, 2);

            $contract = Contract::create([
                'contract_number' => $this->generateContractNumber($application, $contractDate),
                'application_id' => $application->id,
                'object_id' => $application->object_id,
                'owner_id' => $application->applicant_id,
                'region_id' => $application->region_id,
                'district_id' => $application->district_id,
                'contract_date' => $contractDate,
                'total_amount' => $total,
                'monthly_amount' => $monthly,
                'penalty_rate' => $penaltyRate,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => ContractStatus::Active,
            ]);

            $this->regenerateSchedule($contract);

            return $contract->load('schedules', 'invoices');
        });
    }

    /**
     * Shartnomaning start_date / monthly_amount asosida 12 oylik grafik + invoyslarni
     * (qaytadan) yaratadi. Mavjudlari o'chiriladi.
     */
    public function regenerateSchedule(Contract $contract): void
    {
        $contract->invoices()->delete();
        $contract->schedules()->delete();

        $startDate = $contract->start_date->copy();
        $monthly = (float) $contract->monthly_amount;

        for ($m = 1; $m <= self::TERM_MONTHS; $m++) {
            $due = $startDate->copy()->addMonths($m - 1)->day(min(10, $startDate->daysInMonth));

            $schedule = $contract->schedules()->create([
                'month_no' => $m,
                'period' => $due->format('Y-m'),
                'due_date' => $due,
                'amount' => $monthly,
                'penalty_amount' => 0,
                'status' => PaymentStatus::Pending,
            ]);

            $contract->invoices()->create([
                'invoice_number' => sprintf('%s/%02d', $contract->contract_number, $m),
                'payment_schedule_id' => $schedule->id,
                'amount' => $monthly,
                'due_date' => $due,
                'status' => InvoiceStatus::Pending,
            ]);
        }
    }

    public function suspend(Contract $contract, User $user, ?string $reason = null): Contract
    {
        return DB::transaction(function () use ($contract, $user, $reason) {
            $contract->update([
                'status' => ContractStatus::Suspended,
                'control_status' => 'suspended',
                'problem_reason' => $reason,
            ]);
            $this->cancelFutureInvoices($contract);
            $this->recordAction($contract, $user, ContractActionType::Suspend, $reason);

            return $contract->refresh();
        });
    }

    public function resume(Contract $contract, User $user, ?string $reason = null): Contract
    {
        return DB::transaction(function () use ($contract, $user, $reason) {
            $contract->update([
                'status' => ContractStatus::Active,
                'control_status' => null,
                'problem_reason' => null,
            ]);
            // Bekor qilingan kelajakdagi invoyslarni qayta tiklash.
            $contract->invoices()
                ->where('status', InvoiceStatus::Cancelled)
                ->whereDate('due_date', '>=', now())
                ->update(['status' => InvoiceStatus::Pending]);
            $this->recordAction($contract, $user, ContractActionType::Resume, $reason);

            return $contract->refresh();
        });
    }

    public function terminate(Contract $contract, User $user, ?string $reason = null): Contract
    {
        return DB::transaction(function () use ($contract, $user, $reason) {
            $contract->update([
                'status' => ContractStatus::Terminated,
                'control_status' => 'terminated',
                'problem_reason' => $reason,
            ]);
            $this->cancelFutureInvoices($contract);
            $this->recordAction($contract, $user, ContractActionType::Terminate, $reason);

            return $contract->refresh();
        });
    }

    /** Kelajakdagi (hali to'lanmagan) invoyslarni bekor qiladi. */
    protected function cancelFutureInvoices(Contract $contract): void
    {
        $contract->invoices()
            ->where('status', InvoiceStatus::Pending)
            ->whereDate('due_date', '>=', now())
            ->update(['status' => InvoiceStatus::Cancelled]);
    }

    protected function recordAction(Contract $contract, User $user, ContractActionType $action, ?string $reason): void
    {
        $contract->actions()->create([
            'user_id' => $user->id,
            'action' => $action,
            'reason' => $reason,
        ]);
    }

    protected function resolveArea(Application $application): float
    {
        $survey = $application->relationLoaded('latestSurvey')
            ? $application->latestSurvey
            : $application->latestSurvey()->first();

        if ($survey?->total_area) {
            return (float) $survey->total_area;
        }
        if ($survey?->calculated_area) {
            return (float) $survey->calculated_area;
        }

        $adjacent = (float) $application->adjacentAreas()->sum('area_m2');

        return $adjacent > 0 ? $adjacent : 44.0;
    }

    protected function generateContractNumber(Application $application, Carbon $date): string
    {
        return sprintf('ШНТ-%d-%04d', $date->year, $application->id);
    }
}
