<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStage;
use App\Enums\ApplicationStatus;
use App\Enums\ContractStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoleType;
use App\Models\Application;
use App\Models\Contract;
use App\Models\PaymentSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $role = $user->roleType();

        $cards = [];
        $recent = collect();

        if ($role === RoleType::Applicant) {
            $cards = $this->applicantCards($user);
            $recent = $user->applications()->with('object', 'adjacentAreas')->latest()->take(6)->get();
        } elseif ($user->isPipelineActor()) {
            $cards = $this->pipelineCards($user, $role);
            $recent = $this->pipelineQueue($user, $role)->with('object', 'applicant', 'adjacentAreas')->latest()->take(6)->get();
        } else { // lawyer / compliance
            $cards = $this->contractControlCards($user);
            $recent = Contract::forDistrictOf($user)->with('owner', 'object')->latest()->take(6)->get();
        }

        return view('dashboard', [
            'cards' => $cards,
            'recent' => $recent,
            'role' => $role,
        ]);
    }

    protected function applicantCards($user): array
    {
        $apps = $user->applications();

        return [
            ['label' => 'Менинг аризаларим', 'value' => (clone $apps)->count(), 'icon' => 'file', 'color' => 'teal'],
            ['label' => 'Жараёнда', 'value' => (clone $apps)->where('status', ApplicationStatus::InProgress->value)->count(), 'icon' => 'clock', 'color' => 'amber'],
            ['label' => 'Тасдиқланган', 'value' => (clone $apps)->where('status', ApplicationStatus::Approved->value)->count(), 'icon' => 'check', 'color' => 'green'],
            ['label' => 'Фаол шартномалар', 'value' => $user->contracts()->where('status', ContractStatus::Active->value)->count(), 'icon' => 'doc', 'color' => 'blue'],
        ];
    }

    protected function pipelineCards($user, RoleType $role): array
    {
        $queue = $this->pipelineQueue($user, $role)->count();
        $districtApps = Application::forDistrictOf($user);

        return [
            ['label' => 'Кутаётган аризалар', 'value' => $queue, 'icon' => 'inbox', 'color' => 'teal'],
            ['label' => 'Жараёнда (туман)', 'value' => (clone $districtApps)->where('status', ApplicationStatus::InProgress->value)->count(), 'icon' => 'clock', 'color' => 'amber'],
            ['label' => 'Тасдиқланган (туман)', 'value' => (clone $districtApps)->where('status', ApplicationStatus::Approved->value)->count(), 'icon' => 'check', 'color' => 'green'],
            ['label' => 'Бекор қилинган (туман)', 'value' => (clone $districtApps)->where('status', ApplicationStatus::Rejected->value)->count(), 'icon' => 'x', 'color' => 'red'],
        ];
    }

    protected function contractControlCards($user): array
    {
        $contracts = Contract::forDistrictOf($user);
        $overdue = PaymentSchedule::where('status', PaymentStatus::Overdue->value)
            ->whereHas('contract', fn ($q) => $q->forDistrictOf($user));

        return [
            ['label' => 'Фаол шартномалар', 'value' => (clone $contracts)->where('status', ContractStatus::Active->value)->count(), 'icon' => 'doc', 'color' => 'green'],
            ['label' => 'Тўхтатилган', 'value' => (clone $contracts)->where('status', ContractStatus::Suspended->value)->count(), 'icon' => 'pause', 'color' => 'amber'],
            ['label' => 'Бекор қилинган', 'value' => (clone $contracts)->where('status', ContractStatus::Terminated->value)->count(), 'icon' => 'x', 'color' => 'red'],
            ['label' => 'Муддати ўтган тўловлар', 'value' => (clone $overdue)->count(), 'icon' => 'alert', 'color' => 'red'],
        ];
    }

    /** Foydalanuvchi roli harakat qila oladigan bosqichdagi (o'z tumani) arizalar. */
    protected function pipelineQueue($user, RoleType $role)
    {
        $stages = ApplicationStage::stagesForRole($role);

        return Application::query()
            ->forDistrictOf($user)
            ->inStages($stages);
    }
}
