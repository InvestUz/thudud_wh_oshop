<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\ContractStatus;
use App\Enums\PaymentStatus;
use App\Models\Application;
use App\Models\Contract;
use App\Models\District;
use App\Models\PaymentSchedule;
use App\Models\Region;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Monitoring faqat O'rinbosar, Rahbar va Komplayens uchun.
        abort_unless($user->canViewMonitoring(), 403);

        // --- Tuman bo'yicha filtr ----------------------------------------
        // Hududga biriktirilgan xodim (district_id bor) faqat o'z tumanini ko'radi —
        // u uchun tanlov majburan o'z tumaniga qulflanadi. Markaziy xodim (district_id
        // yo'q) ixtiyoriy tumanni tanlashi yoki "barcha tumanlar"ni ko'rishi mumkin.
        $lockedDistrictId = $user->district_id;
        $selectedDistrictId = $request->integer('district_id') ?: null;
        if ($lockedDistrictId) {
            $selectedDistrictId = $lockedDistrictId;
        }

        // Tanlangan tuman bo'lsa — faqat o'sha tuman; aks holda — xodim huquqi bo'yicha barchasi.
        $scopeDistrict = function (Builder $query) use ($user, $selectedDistrictId): Builder {
            return $selectedDistrictId
                ? $query->where('district_id', $selectedDistrictId)
                : $query->forDistrictOf($user);
        };

        $appsByStatus = [];
        foreach (ApplicationStatus::cases() as $status) {
            $appsByStatus[$status->value] = [
                'label' => $status->label(),
                'color' => $status->color(),
                'count' => $scopeDistrict(Application::query())->where('status', $status->value)->count(),
            ];
        }

        $contractsByStatus = [];
        foreach (ContractStatus::cases() as $status) {
            $contractsByStatus[$status->value] = [
                'label' => $status->label(),
                'color' => $status->color(),
                'count' => $scopeDistrict(Contract::query())->where('status', $status->value)->count(),
            ];
        }

        $paidSum = PaymentSchedule::where('status', PaymentStatus::Paid->value)
            ->whereHas('contract', $scopeDistrict)->sum('amount');
        $overdueSum = PaymentSchedule::where('status', PaymentStatus::Overdue->value)
            ->whereHas('contract', $scopeDistrict)->sum('amount');
        $penaltySum = PaymentSchedule::whereHas('contract', $scopeDistrict)->sum('penalty_amount');
        $pendingSum = PaymentSchedule::where('status', PaymentStatus::Pending->value)
            ->whereHas('contract', $scopeDistrict)->sum('amount');

        // Filtr ro'yxati: ariza yoki shartnomaga ega tumanlar (xodim huquqi bo'yicha),
        // viloят bo'yicha guruhlangan.
        $districtIds = Application::query()->forDistrictOf($user)->distinct()->pluck('district_id')
            ->merge(Contract::query()->forDistrictOf($user)->distinct()->pluck('district_id'))
            ->filter()
            ->unique()
            ->values();

        $districtsByRegion = District::with('region')
            ->whereIn('id', $districtIds)
            ->orderBy('name')
            ->get()
            ->groupBy(fn (District $d) => $d->region?->name ?? '—')
            ->sortKeys();

        // Xaritada ko'rsatish uchun: tuzilgan shartnomalardagi belgilangan maydonlar (geo_area),
        // to'lov holati bo'yicha ranglanadi — muddati o'tgan (qizil) > kutilmoqda (sariq) > to'langan/qarzsiz (yashil).
        $mapContracts = Contract::query()
            ->when($selectedDistrictId,
                fn (Builder $q) => $q->where('district_id', $selectedDistrictId),
                fn (Builder $q) => $q->forDistrictOf($user))
            ->with(['object:id,company_name', 'application.latestSurvey', 'schedules:id,contract_id,status'])
            ->get()
            ->map(function (Contract $contract) {
                $survey = $contract->application?->latestSurvey;
                if (! $survey || (empty($survey->geo_area) && $survey->latitude === null)) {
                    return null;
                }

                $statuses = $contract->schedules->pluck('status');
                $state = $statuses->contains(fn ($s) => $s === PaymentStatus::Overdue) ? 'overdue'
                    : ($statuses->contains(fn ($s) => $s === PaymentStatus::Pending) ? 'pending' : 'paid');

                return [
                    'number' => $contract->contract_number,
                    'company' => $contract->object?->company_name,
                    'state' => $state,
                    'geo' => $survey->geo_area,
                    'lat' => $survey->latitude !== null ? (float) $survey->latitude : null,
                    'lng' => $survey->longitude !== null ? (float) $survey->longitude : null,
                    'url' => route('contracts.show', $contract),
                ];
            })
            ->filter()
            ->values();

        $monitoring = $this->buildTashkentMonitoring();

        return view('monitoring.index', [
            'mapContracts' => $mapContracts,
            'monPeriods' => $monitoring['periods'],
            'monYears' => $monitoring['years'],
            'monRows' => $monitoring['rows'],
            'monTotal' => $monitoring['total'],
            'appsByStatus' => $appsByStatus,
            'contractsByStatus' => $contractsByStatus,
            'districtsByRegion' => $districtsByRegion,
            'selectedDistrictId' => $selectedDistrictId,
            'districtFilterEnabled' => $lockedDistrictId === null,
            'totals' => [
                'applications' => $scopeDistrict(Application::query())->count(),
                'contracts' => $scopeDistrict(Contract::query())->count(),
                'paidSum' => (float) $paidSum,
                'overdueSum' => (float) $overdueSum,
                'pendingSum' => (float) $pendingSum,
                'penaltySum' => (float) $penaltySum,
            ],
        ]);
    }

    /** Oylik (latin) nomlar — Excel'dagi kabi. */
    private const LAT_MONTHS = [
        1 => 'yanvar', 2 => 'fevral', 3 => 'mart', 4 => 'aprel', 5 => 'may', 6 => 'iyun',
        7 => 'iyul', 8 => 'avgust', 9 => 'sentyabr', 10 => 'oktyabr', 11 => 'noyabr', 12 => 'dekabr',
    ];

    /**
     * Тошкент шаҳри кесими — "24/7 кўчалари" ижара шартномалари бўйича ойлик тўлов мониторинги
     * (Excel шаклида): туман бўйича умумий кўрсаткичлар + ой кесимида Режа/Амалда/Қолдиқ/Ортиқча/Фоиз.
     *
     * @return array{periods: array<int,array>, years: array<string,int>, rows: array<int,array>, total: array}
     */
    private function buildTashkentMonitoring(): array
    {
        $tashkent = Region::where('name', 'Тошкент шаҳри')->first();
        if (! $tashkent) {
            return ['periods' => [], 'years' => [], 'rows' => [], 'total' => []];
        }

        $districts = District::where('region_id', $tashkent->id)->orderBy('name')->get();
        $ids = $districts->pluck('id');

        // Ойлар ойнаси (Excel'даги каби): 2025-ноябрь … 2027-март.
        $periods = [];
        $years = [];
        $cursor = Carbon::create(2025, 11, 1);
        $end = Carbon::create(2027, 3, 1);
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m');
            $periods[] = ['key' => $key, 'year' => (string) $cursor->year, 'label' => self::LAT_MONTHS[$cursor->month]];
            $years[(string) $cursor->year] = ($years[(string) $cursor->year] ?? 0) + 1;
            $cursor->addMonth();
        }
        $periodKeys = array_column($periods, 'key');

        // Туман бўйича: шартномалар сони + умумий шартнома суммаси.
        $contracts = Contract::query()->whereIn('district_id', $ids)
            ->selectRaw('district_id, count(*) as cnt, sum(total_amount) as total')
            ->groupBy('district_id')->get()->keyBy('district_id');

        // Туман бўйича: туташ ҳудуд майдони (шартнома тузилган аризалар).
        $areas = DB::table('adjacent_areas as aa')
            ->join('applications as a', 'a.id', '=', 'aa.application_id')
            ->join('contracts as c', 'c.application_id', '=', 'a.id')
            ->whereIn('a.district_id', $ids)
            ->selectRaw('a.district_id as did, sum(aa.area_m2) as area')
            ->groupBy('a.district_id')->pluck('area', 'did');

        // Туман бўйича: жами тушум (бутун давр учун тўланган).
        $paidAll = DB::table('payment_schedules as ps')
            ->join('contracts as c', 'c.id', '=', 'ps.contract_id')
            ->whereIn('c.district_id', $ids)->where('ps.status', PaymentStatus::Paid->value)
            ->selectRaw('c.district_id as did, sum(ps.amount) as paid')
            ->groupBy('c.district_id')->pluck('paid', 'did');

        // Туман + ой кесимида: режа (жами) ва амалда (тўланган).
        $monthly = DB::table('payment_schedules as ps')
            ->join('contracts as c', 'c.id', '=', 'ps.contract_id')
            ->whereIn('c.district_id', $ids)->whereIn('ps.period', $periodKeys)
            ->selectRaw("c.district_id as did, ps.period as p, sum(ps.amount) as reja, "
                ."sum(case when ps.status = '".PaymentStatus::Paid->value."' then ps.amount else 0 end) as amalda")
            ->groupBy('c.district_id', 'ps.period')->get()->groupBy('did');

        $rows = [];
        $total = $this->emptyMonRow($periodKeys);

        foreach ($districts as $i => $district) {
            $row = $this->emptyMonRow($periodKeys);
            $row['n'] = $i + 1;
            $row['name'] = str_replace(' тумани', '', $district->name);
            $row['district_id'] = $district->id;

            $c = $contracts->get($district->id);
            $row['area'] = (float) ($areas[$district->id] ?? 0);
            $row['count'] = (int) ($c->cnt ?? 0);
            $row['summa'] = (float) ($c->total ?? 0);
            $row['tushum'] = (float) ($paidAll[$district->id] ?? 0);
            $row['qoldiq'] = $row['summa'] - $row['tushum'];
            $row['foiz'] = $row['summa'] > 0 ? $row['tushum'] / $row['summa'] * 100 : 0.0;

            foreach (($monthly->get($district->id) ?? collect()) as $m) {
                $reja = (float) $m->reja;
                $amalda = (float) $m->amalda;
                $row['months'][$m->p] = [
                    'reja' => $reja,
                    'amalda' => $amalda,
                    'qoldiq' => max(0, $reja - $amalda),
                    'ortiqcha' => max(0, $amalda - $reja),
                    'foiz' => $reja > 0 ? $amalda / $reja * 100 : 0.0,
                ];
            }

            $this->accumulate($total, $row, $periodKeys);
            $rows[] = $row;
        }

        // Жами сатр учун фоизларни қайта ҳисоблаймиз.
        $total['foiz'] = $total['summa'] > 0 ? $total['tushum'] / $total['summa'] * 100 : 0.0;
        foreach ($periodKeys as $k) {
            $r = $total['months'][$k]['reja'];
            $total['months'][$k]['foiz'] = $r > 0 ? $total['months'][$k]['amalda'] / $r * 100 : 0.0;
        }

        return ['periods' => $periods, 'years' => $years, 'rows' => $rows, 'total' => $total];
    }

    /** Bo'sh monitoring satri (barcha oylar nol bilan). */
    private function emptyMonRow(array $periodKeys): array
    {
        $months = [];
        foreach ($periodKeys as $k) {
            $months[$k] = ['reja' => 0.0, 'amalda' => 0.0, 'qoldiq' => 0.0, 'ortiqcha' => 0.0, 'foiz' => 0.0];
        }

        return [
            'n' => null, 'name' => 'Jami', 'district_id' => null,
            'area' => 0.0, 'count' => 0, 'summa' => 0.0, 'tushum' => 0.0, 'qoldiq' => 0.0, 'foiz' => 0.0,
            'months' => $months,
        ];
    }

    /** $row qiymatlarini $total ga qo'shadi. */
    private function accumulate(array &$total, array $row, array $periodKeys): void
    {
        foreach (['area', 'count', 'summa', 'tushum', 'qoldiq'] as $f) {
            $total[$f] += $row[$f];
        }
        foreach ($periodKeys as $k) {
            foreach (['reja', 'amalda', 'qoldiq', 'ortiqcha'] as $f) {
                $total['months'][$k][$f] += $row['months'][$k][$f];
            }
        }
    }
}
