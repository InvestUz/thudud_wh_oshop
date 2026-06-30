<?php

namespace App\Http\Controllers;

use App\Enums\ContractActionType;
use App\Enums\ContractStatus;
use App\Enums\RoleType;
use App\Http\Requests\ContractActionRequest;
use App\Models\Contract;
use App\Models\District;
use App\Services\ContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function __construct(private ContractService $contracts)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Contract::class);

        $user = $request->user();
        $query = Contract::query()->with(['owner', 'object', 'district']);

        if ($user->isRole(RoleType::Applicant)) {
            $query->where('owner_id', $user->id);
        } else {
            $query->forDistrictOf($user);
        }

        // Monitoring sahifasidagi kartochkalardan o'tib kelganda — tuman bo'yicha drill-down.
        if ($request->filled('district_id')) {
            $query->where('district_id', $request->integer('district_id'));
        }

        // To'lov holati bo'yicha drill-down: "Тўланган" yoki "Муддати ўтган" kartochkasidan.
        $payment = $request->input('payment');
        $payment = in_array($payment, ['paid', 'overdue'], true) ? $payment : null;
        if ($payment) {
            $query->whereHas('schedules', fn ($q) => $q->where('status', $payment));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->where(function ($q) use ($term) {
                $q->where('contract_number', 'like', "%{$term}%")
                    ->orWhereHas('object', fn ($o) => $o->where('company_name', 'like', "%{$term}%")
                        ->orWhere('cadastre_number', 'like', "%{$term}%"));
            });
        }

        $contracts = $query->latest()->paginate(15)->withQueryString();

        return view('contracts.index', [
            'contracts' => $contracts,
            'statuses' => ContractStatus::cases(),
            'canControl' => $user->canControlContracts(),
            'district' => $request->filled('district_id') ? District::find($request->integer('district_id')) : null,
            'payment' => $payment,
        ]);
    }

    public function show(Contract $contract): View
    {
        $this->authorize('view', $contract);

        $contract->load([
            'owner', 'object.district', 'application',
            'schedules.invoice', 'actions.user',
        ]);

        return view('contracts.show', [
            'contract' => $contract,
            'canControl' => request()->user()->can('control', $contract),
        ]);
    }

    public function action(ContractActionRequest $request, Contract $contract): RedirectResponse
    {
        $action = $request->action();
        $reason = $request->validated('reason');

        $this->authorize($action->value, $contract);

        match ($action) {
            ContractActionType::Suspend => $this->contracts->suspend($contract, $request->user(), $reason),
            ContractActionType::Resume => $this->contracts->resume($contract, $request->user(), $reason),
            ContractActionType::Terminate => $this->contracts->terminate($contract, $request->user(), $reason),
            default => null,
        };

        return redirect()
            ->route('contracts.show', $contract)
            ->with('status', 'Шартнома бўйича ҳаракат бажарилди: '.$action->label());
    }
}
