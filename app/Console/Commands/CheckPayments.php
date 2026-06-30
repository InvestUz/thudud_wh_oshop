<?php

namespace App\Console\Commands;

use App\Enums\ContractStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\PaymentSchedule;
use Illuminate\Console\Command;

/**
 * Kechikkan to'lovlarni `overdue` ga o'tkazadi va penya hisoblaydi.
 *
 * Penya = amount * (penalty_rate / 100) * kechikkan_kunlar
 *
 * Qo'lda chaqirish: php artisan payments:check
 */
class CheckPayments extends Command
{
    protected $signature = 'payments:check {--date= : Tekshiruv sanasi (YYYY-MM-DD), bo\'sh bo\'lsa bugun}';

    protected $description = "Kechikkan to'lovlarni belgilaydi va penya hisoblaydi";

    public function handle(): int
    {
        $today = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : now();
        $today = $today->startOfDay();

        $schedules = PaymentSchedule::query()
            ->with('contract')
            ->where('status', '!=', PaymentStatus::Paid->value)
            ->whereDate('due_date', '<', $today)
            ->get();

        $overdueCount = 0;
        $totalPenalty = 0.0;

        foreach ($schedules as $schedule) {
            // Faqat faol shartnomalar bo'yicha penya hisoblanadi.
            if ($schedule->contract->status !== ContractStatus::Active) {
                continue;
            }

            $days = (int) $schedule->due_date->copy()->startOfDay()->diffInDays($today);
            $rate = (float) $schedule->contract->penalty_rate;
            $penalty = round((float) $schedule->amount * ($rate / 100) * $days, 2);

            $schedule->update([
                'status' => PaymentStatus::Overdue,
                'penalty_amount' => $penalty,
            ]);

            $schedule->invoice?->update([
                'status' => InvoiceStatus::Overdue,
            ]);

            $overdueCount++;
            $totalPenalty += $penalty;
        }

        $this->info("Текширилди: {$schedules->count()} та график.");
        $this->info("Муддати ўтган деб белгиланди: {$overdueCount} та.");
        $this->info('Жами ҳисобланган пеня: '.number_format($totalPenalty, 2, '.', ' ').' сўм.');

        return self::SUCCESS;
    }
}
