<?php

namespace Database\Seeders;

use App\Enums\ApplicationStage;
use App\Enums\ApplicationStatus;
use App\Enums\ContractActionType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoleType;
use App\Enums\TransitionAction;
use App\Models\Application;
use App\Models\Contract;
use App\Models\District;
use App\Models\RealEstateObject;
use App\Models\User;
use App\Services\ApplicationWorkflowService;
use App\Services\ContractService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private ApplicationWorkflowService $workflow;
    private ContractService $contracts;

    /** @var array<string, User> */
    private array $staff = [];
    private District $mainDistrict;
    /** @var \Illuminate\Support\Collection<int, User> */
    private $applicants;

    public function run(): void
    {
        $this->workflow = app(ApplicationWorkflowService::class);
        $this->contracts = app(ContractService::class);

        foreach ([
            'moderator' => 'moderator@test.uz',
            'masul' => 'masul@test.uz',
            'orinbosar' => 'orinbosar@test.uz',
            'rahbar' => 'rahbar@test.uz',
        ] as $key => $email) {
            $this->staff[$key] = User::where('email', $email)->firstOrFail();
        }

        $this->mainDistrict = $this->staff['masul']->district
            ?? throw new \RuntimeException('masul@test.uz аккаунтига туман бириктирилмаган.');

        $this->applicants = User::role(RoleType::Applicant->value)->get();

        $objects = $this->seedObjects();
        $this->seedApplications($objects);
        $this->seedContractControls();

        // Тошкент шаҳри кесими бўйича мониторинг жадвали учун — 12 туманга
        // бўйича катта ҳажмдаги намунавий маълумот (аризалар + шартномалар + тўловлар).
        $this->seedDistrictData();
    }

    /**
     * Тошкент шаҳрининг 12 туманига аризалар, шартномалар ва тўловларни
     * тарқатиб яратади — мониторинг (ҳудудлар кесими) жадвалини тўлдириш учун.
     * Тарихий тафсилотларсиз, тўғридан-тўғри ҳолат қўйиб яратилади (ҳажм учун).
     */
    private function seedDistrictData(): void
    {
        $districts = collect([$this->mainDistrict]);

        $companyPrefixes = ['SARDOR', 'MEGA BUILD', 'FRESH MARKET', 'CITY CAFE', 'TEXNO MARKET',
            'BARAKA NON', 'MODA STYLE', 'GREEN GARDEN', 'DELTA PHARM', 'SMART OFFICE',
            'UNIVERSAL TRADE', 'AVTO SERVIS', 'GULSHAN', 'EXPRESS', 'FAYZ', 'NUR SAVDO',
            'OQ YO\'L', 'ZAFAR', 'IMKON', 'BARAKA MARKET'];
        $suffixes = ['МЧЖ', 'ЯТТ', 'савдо мажмуаси', 'дўкони', 'хизмат маркази'];
        $activities = ['Савдо / Дўкон', 'Умумий овқатланиш', 'Кафе', 'Дорихона', 'Маиший техника',
            'Кийим-кечак', 'Логистика', 'Авто хизмат', 'Нонвойхона', 'Озиқ-овқат дўкони'];
        $directors = ['Каримов Сардор', 'Полатов Бойсун', 'Усмонов Жавлон', 'Юлдашева Малика',
            'Эргашев Дилшод', 'Набиев Жамшид', 'Қодирова Зухра', 'Тўраев Ботир', 'Саидова Гулнора'];

        $seq = 0;

        foreach ($districts as $district) {
            $total = rand(14, 30);

            for ($n = 0; $n < $total; $n++) {
                $seq++;
                $owner = $this->applicants->random();
                $company = '«'.$companyPrefixes[array_rand($companyPrefixes)].'» '.$suffixes[array_rand($suffixes)];
                $activity = $activities[array_rand($activities)];

                $object = RealEstateObject::create([
                    'owner_id' => $owner->id,
                    'cadastre_number' => sprintf('10:%02d:%02d:%02d:%02d:%04d', $district->id, rand(1, 9), rand(1, 9), rand(1, 9), 1000 + $seq),
                    'hokimiyat_cadastre' => sprintf('25537%05d/%04d', rand(10000, 99999), rand(1000, 9999)),
                    'tin_pinfl' => (string) rand(300000000, 399999999),
                    'company_name' => $company,
                    'director_name' => $directors[array_rand($directors)],
                    'phone' => '+998 71 '.rand(200, 299).' '.rand(10, 99).' '.rand(10, 99),
                    'region_id' => $district->region_id,
                    'district_id' => $district->id,
                    'mahalla_id' => optional($district->mahallas()->inRandomOrder()->first())->id,
                    'street' => optional($district->streets()->inRandomOrder()->first())->name ?? 'Кўча',
                    'street_status' => 'Шаҳар кўчаси',
                    'house_number' => (string) rand(1, 150),
                    'created_by' => $owner->id,
                ]);
                $object->tenants()->create([
                    'tin_pinfl' => (string) rand(300000000, 399999999),
                    'name' => $company,
                    'activity_type' => $activity,
                ]);

                [$stage, $status] = $this->randomOutcome();
                $area = (float) [33, 36, 42, 44, 48, 52, 55, 64, 78, 90][array_rand([0,1,2,3,4,5,6,7,8,9])];

                $created = now()->subDays(rand(20, 1300)); // 2022-2026 оралиғи

                $application = Application::create([
                    'application_number' => 'А-'.$created->format('y').'-'.Str::upper(Str::random(5)),
                    'object_id' => $object->id,
                    'applicant_id' => $owner->id,
                    'status' => $status,
                    'current_stage' => $stage,
                    'region_id' => $object->region_id,
                    'district_id' => $object->district_id,
                    'submitted_at' => $stage === ApplicationStage::Draft ? null : $created,
                    'finished_at' => $stage->isTerminal() ? $created->copy()->addDays(rand(5, 40)) : null,
                ]);
                $application->adjacentAreas()->create([
                    'activity' => $activity,
                    'area_m2' => $area,
                    'structures' => collect(['Терраса', 'Навес', 'Витрина', 'Зинапоя', '—'])->random(),
                ]);
                $application->created_at = $created;
                $application->updated_at = $created;
                $application->save();

                if ($status === ApplicationStatus::Approved) {
                    $this->spawnContract($application, $created, $area);
                }
            }
        }
    }

    /** Аризанинг тасодифий якуний босқич/ҳолатини қайтаради (вазнли тақсимот). */
    private function randomOutcome(): array
    {
        $r = rand(1, 100);

        if ($r <= 35) {
            return [ApplicationStage::Approved, ApplicationStatus::Approved];
        }
        if ($r <= 50) {
            return [ApplicationStage::Rejected, ApplicationStatus::Rejected];
        }
        if ($r <= 58) {
            return [ApplicationStage::Draft, ApplicationStatus::Draft];
        }

        // Қолгани — жараёнда (турли босқичларда, шу жумладан тадбиркор имзосида).
        $stage = [ApplicationStage::Moderation, ApplicationStage::ResponsibleReview,
            ApplicationStage::DeputyReview, ApplicationStage::HeadReview,
            ApplicationStage::AwaitingSignature][array_rand([0, 1, 2, 3, 4])];

        return [$stage, ApplicationStatus::InProgress];
    }

    /** Тасдиқланган ариза учун шартнома + тўлов графиги (ўтмишга мосланган) яратади. */
    private function spawnContract(Application $application, Carbon $base, float $area): void
    {
        $start = $base->copy()->addDays(rand(5, 20))->startOfMonth();
        if ($start->gt(now())) {
            $start = now()->subMonths(rand(2, 10))->startOfMonth();
        }

        $contract = $this->contracts->createFromApplication($application, [
            'area' => $area,
            'start_date' => $start,
            'contract_date' => $start->copy()->subDays(rand(1, 10)),
        ]);

        // Шартнома ҳолати: 78% фаол, 12% тўхтатилган, 10% бекор қилинган.
        $cr = rand(1, 100);
        if ($cr <= 12) {
            $contract->update(['status' => \App\Enums\ContractStatus::Suspended, 'control_status' => 'suspended']);
        } elseif ($cr <= 22) {
            $contract->update(['status' => \App\Enums\ContractStatus::Terminated, 'control_status' => 'terminated']);
        }

        $contract->load('schedules.invoice');
        $delinquent = rand(1, 100) <= 32;
        $pastSchedules = $contract->schedules->filter(fn ($s) => $s->due_date->lt(now()))->values();
        $overdueFrom = $pastSchedules->count() - rand(1, 3);

        foreach ($contract->schedules as $schedule) {
            if ($schedule->due_date->gte(now())) {
                continue; // келажак — pending (кутилмоқда)
            }

            $pastIndex = $pastSchedules->search(fn ($s) => $s->id === $schedule->id);
            $isOverdue = $delinquent && $pastIndex !== false && $pastIndex >= $overdueFrom;

            if ($isOverdue) {
                $days = (int) $schedule->due_date->copy()->diffInDays(now());
                $penalty = round((float) $schedule->amount * (0.1 / 100) * $days, 2);
                $schedule->update(['status' => PaymentStatus::Overdue, 'penalty_amount' => $penalty]);
                $schedule->invoice?->update(['status' => InvoiceStatus::Overdue]);
            } else {
                $schedule->update(['status' => PaymentStatus::Paid]);
                $schedule->invoice?->update([
                    'status' => InvoiceStatus::Paid,
                    'paid_at' => $schedule->due_date->copy()->addDays(rand(0, 6)),
                ]);
            }
        }

        $contract->created_at = $contract->contract_date;
        $contract->updated_at = $contract->contract_date;
        $contract->save();
    }

    /** Намуна учун битта шартномани тўхтатамиз, биттасини бекор қиламиз. */
    private function seedContractControls(): void
    {
        // Қарздор (delinquent) шартномалар фаол қолади — улар overdue тўловларни кўрсатади.
        // Тоза шартномалардан иккитасини назорат ҳаракати учун оламиз.
        $clean = Contract::where('status', \App\Enums\ContractStatus::Active->value)
            ->whereDoesntHave('schedules', fn ($q) => $q->where('status', PaymentStatus::Overdue->value))
            ->orderBy('id')
            ->take(2)
            ->get();

        if ($clean->count() >= 1) {
            $this->contracts->suspend($clean[0], $this->staff['rahbar'], 'Объектдан нотўғри фойдаланиш аниқланди — текширув давом этмоқда');
        }
        if ($clean->count() >= 2) {
            $this->contracts->terminate($clean[1], $this->staff['rahbar'], 'Тадбиркор аризаси асосида шартнома бекор қилинди');
        }
    }

    /** 18 та объект — асосан Мирзо-Улуғбек туманида. */
    private function seedObjects()
    {
        $companies = [
            ['«SARDOR» МАСЪУЛИЯТИ ЧЕКЛАНГАН ЖАМИЯТ', 'Савдо / Дўкон', 'Каримов Сардор'],
            ['«POLATOV BOYSUN GAZOVICH» МЧЖ', 'Умумий овқатланиш', 'Полатов Бойсун'],
            ['«MEGA BUILD» МЧЖ', 'Қурилиш материаллари', 'Усмонов Жавлон'],
            ['«FRESH MARKET» МЧЖ', 'Озиқ-овқат дўкони', 'Юлдашева Малика'],
            ['«TASHKENT TEXTILE» МЧЖ', 'Тўқимачилик', 'Эргашев Дилшод'],
            ['«AVTO SERVIS PLUS» ЯТТ', 'Авто хизмат', 'Набиев Жамшид'],
            ['«GULSHAN» савдо мажмуаси', 'Савдо мажмуаси', 'Қодирова Зухра'],
            ['«BARAKA NON» МЧЖ', 'Нонвойхона', 'Тўраев Ботир'],
            ['«CITY CAFE» ЯТТ', 'Кафе', 'Саидова Гулнора'],
            ['«TEXNO MARKET» МЧЖ', 'Маиший техника', 'Каримов Алишер'],
            ['«SOG\'LOM HAYOT» дорихона', 'Дорихона', 'Усмонов Сардор'],
            ['«MODA STYLE» бутиги', 'Кийим-кечак', 'Юлдашева Малика'],
            ['«EXPRESS LOGISTIC» МЧЖ', 'Логистика', 'Эргашев Дилшод'],
            ['«FAYZ» тўйхонаси', 'Маросимлар', 'Набиев Жамшид'],
            ['«DELTA PHARM» МЧЖ', 'Фармацевтика', 'Қодирова Зухра'],
            ['«SMART OFFICE» МЧЖ', 'Офис ижараси', 'Тўраев Ботир'],
            ['«GREEN GARDEN» МЧЖ', 'Гулчилик', 'Саидова Гулнора'],
            ['«UNIVERSAL TRADE» МЧЖ', 'Улгуржи савдо', 'Каримов Алишер'],
        ];

        $streets = ['Бобур кўчаси', 'Амир Темур шоҳ кўчаси', 'Мустақиллик кўчаси', 'Шаҳрисабз кўчаси', 'Лабзак кўчаси', 'Чилонзор кўчаси'];
        $objects = collect();
        foreach ($companies as $i => [$company, $activity, $director]) {
            $district = $this->mainDistrict;
            $owner = $this->applicants->random();

            $object = RealEstateObject::create([
                'owner_id' => $owner->id,
                'cadastre_number' => sprintf('10:11:%02d:%02d:%02d:%04d', rand(1, 9), rand(1, 9), rand(1, 9), 100 + $i),
                'hokimiyat_cadastre' => sprintf('25537%05d/%04d', rand(10000, 99999), rand(1000, 9999)),
                'tin_pinfl' => (string) rand(300000000, 399999999),
                'company_name' => $company,
                'director_name' => $director,
                'phone' => '+998 71 '.rand(200, 299).' '.rand(10, 99).' '.rand(10, 99),
                'region_id' => $district->region_id,
                'district_id' => $district->id,
                'mahalla_id' => optional($district->mahallas()->inRandomOrder()->first())->id,
                'street' => optional($district->streets()->inRandomOrder()->first())->name ?? $streets[array_rand($streets)],
                'street_status' => 'Шаҳар кўчаси',
                'house_number' => (string) rand(1, 120),
                'created_by' => $owner->id,
            ]);

            $object->tenants()->create([
                'tin_pinfl' => (string) rand(300000000, 399999999),
                'name' => $company,
                'activity_type' => $activity,
            ]);

            $objects->push(['object' => $object, 'activity' => $activity]);
        }

        return $objects;
    }

    private function seedApplications($objects): void
    {
        // Асосий туман объектлари (pipeline'дан тўлиқ ўтказамиз).
        $mainObjects = $objects->filter(fn ($o) => $o['object']->district_id === $this->mainDistrict->id)->values();

        // Ҳар бир мақсадли босқич учун нечтадан ариза.
        $plan = [
            [ApplicationStage::Draft, null, 2],
            [ApplicationStage::Moderation, null, 3],
            [ApplicationStage::ResponsibleReview, null, 3],
            [ApplicationStage::DeputyReview, null, 3],
            [ApplicationStage::HeadReview, null, 3],
            [ApplicationStage::AwaitingSignature, null, 2],
            [ApplicationStage::Approved, 'good', 4],
            [ApplicationStage::Approved, 'delinquent', 2],
            [ApplicationStage::Rejected, ApplicationStage::Moderation, 1],
            [ApplicationStage::Rejected, ApplicationStage::ResponsibleReview, 1],
            [ApplicationStage::Rejected, ApplicationStage::DeputyReview, 1],
        ];

        $idx = 0;
        foreach ($plan as [$target, $variant, $count]) {
            for ($c = 0; $c < $count; $c++) {
                $row = $mainObjects[$idx % $mainObjects->count()];
                $idx++;
                $this->makeApplication($row['object'], $row['activity'], $target, $variant);
            }
        }

        // Бошқа туман объектлари — moderation'да қолади (бошқа туман ходими кўради).
        foreach ($objects->filter(fn ($o) => $o['object']->district_id !== $this->mainDistrict->id) as $row) {
            $this->makeApplication($row['object'], $row['activity'], ApplicationStage::Moderation, null);
        }
    }

    private function makeApplication(RealEstateObject $object, string $activity, ApplicationStage $target, $variant): void
    {
        $applicant = $object->owner;
        $area = $this->areaForVariant($target);

        $base = $target === ApplicationStage::Approved
            ? now()->subMonths(rand(4, 9))->subDays(rand(0, 20))
            : now()->subDays(rand(1, 45));

        $application = Application::create([
            'application_number' => 'А-'.$base->format('y').'-'.Str::upper(Str::random(5)),
            'object_id' => $object->id,
            'applicant_id' => $applicant->id,
            'status' => ApplicationStatus::Draft,
            'current_stage' => ApplicationStage::Draft,
            'region_id' => $object->region_id,
            'district_id' => $object->district_id,
        ]);
        $application->adjacentAreas()->create([
            'activity' => $activity,
            'area_m2' => $area,
            'structures' => collect(['Терраса', 'Навес', 'Витрина', 'Зинапоя', '—'])->random(),
        ]);

        // Rejected: аввал rejectStage'гача юрамиз, кейин рад этамиз.
        $walkTarget = $target === ApplicationStage::Rejected ? $variant : $target;

        if ($application->district_id === $this->mainDistrict->id) {
            $this->walkForward($application, $applicant, $walkTarget, $area);

            if ($target === ApplicationStage::Rejected) {
                $this->rejectAt($application, $walkTarget);
            }
        } else {
            // Бошқа туман — фақат топширилади (moderation).
            $this->workflow->submit($application, $applicant, 'Онлайн топширилди');
        }

        $this->backdate($application, $base);

        if ($target === ApplicationStage::Approved) {
            $this->setupContract($application->fresh(), $base, $variant);
        }
    }

    /** draft -> ... -> $target гача олдинга юради, реал workflow орқали. */
    private function walkForward(Application $application, User $applicant, ApplicationStage $target, float $area): void
    {
        $guard = 0;
        while ($application->current_stage->order() < $target->order() && $guard++ < 10) {
            $stage = $application->current_stage;

            // ResponsibleReview'дан чиқишдан олдин ўлчов яратамиз.
            if ($stage === ApplicationStage::ResponsibleReview) {
                $this->makeSurvey($application, $area);
            }

            [$actor, $action, $comment] = $this->stepFor($stage, $applicant);
            $this->workflow->transition($application, $actor, $action, $comment);
            $application->refresh();
        }
    }

    private function stepFor(ApplicationStage $stage, User $applicant): array
    {
        return match ($stage) {
            ApplicationStage::Draft => [$applicant, TransitionAction::Submit, 'Ариза топширилди'],
            ApplicationStage::Moderation => [$this->staff['moderator'], TransitionAction::Forward, 'Ҳужжатлар тўлиқ, мас\'ул ходимга юборилди'],
            ApplicationStage::ResponsibleReview => [$this->staff['masul'], TransitionAction::Forward, 'Жой ўлчанди, маълумотлар тўлдирилди, ўринбосарга юборилди'],
            ApplicationStage::DeputyReview => [$this->staff['orinbosar'], TransitionAction::Forward, 'Кўриб чиқилди, раҳбар тасдиғига юборилди'],
            ApplicationStage::HeadReview => [$this->staff['rahbar'], TransitionAction::Approve, 'Тасдиқланди, тадбиркор имзосига юборилди'],
            ApplicationStage::AwaitingSignature => [$applicant, TransitionAction::Sign, 'Тадбиркор томонидан имзоланди'],
            default => [$applicant, TransitionAction::Forward, null],
        };
    }

    private function rejectAt(Application $application, ApplicationStage $stage): void
    {
        $actor = match ($stage) {
            ApplicationStage::Moderation => $this->staff['moderator'],
            ApplicationStage::ResponsibleReview => $this->staff['masul'],
            ApplicationStage::DeputyReview => $this->staff['orinbosar'],
            default => $this->staff['moderator'],
        };
        $reasons = [
            'Ҳужжатлар тўлиқ эмас',
            'Туташ ҳудуд бошқа объект билан тўқнашади',
            'Йўл ажратиш чизиғини бузади',
            'Талаблар бажарилмаган',
        ];
        $this->workflow->transition($application, $actor, TransitionAction::Reject, $reasons[array_rand($reasons)]);
        $application->refresh();
    }

    private function makeSurvey(Application $application, float $area): void
    {
        $length = round(sqrt($area) * (0.8 + (rand(0, 40) / 100)), 1);
        $width = round($area / max($length, 1), 1);

        $lat = 41.3 + (rand(0, 9000) / 100000);
        $lng = 69.2 + (rand(0, 9000) / 100000);

        // Demo rasmlar (placeholder SVG'lar) — 2-3 ta.
        $allPhotos = ['uploads/surveys/sample-1.svg', 'uploads/surveys/sample-2.svg', 'uploads/surveys/sample-3.svg'];
        shuffle($allPhotos);
        $photos = array_slice($allPhotos, 0, rand(2, 3));

        $application->surveys()->create([
            'surveyed_by' => $this->staff['masul']->id,
            'stage' => ApplicationStage::ResponsibleReview->value,
            'length_m' => $length,
            'width_m' => $width,
            'calculated_area' => round($length * $width, 2),
            'total_area' => $area,
            'calc_method' => 'manual',
            'facade_length_m' => $length,
            'distance_to_road_m' => rand(3, 15),
            'distance_to_sidewalk_m' => rand(1, 5),
            'usage_purpose' => \App\Models\ApplicationSurvey::USAGE_PURPOSES[array_rand(\App\Models\ApplicationSurvey::USAGE_PURPOSES)],
            'street_type' => \App\Models\ApplicationSurvey::STREET_TYPES[array_rand(\App\Models\ApplicationSurvey::STREET_TYPES)],
            'activity_type' => \App\Models\ApplicationSurvey::ACTIVITY_TYPES[array_rand(\App\Models\ApplicationSurvey::ACTIVITY_TYPES)],
            'terrace_structures' => 'Енгил конструкция',
            'permanent_structures' => 'Йўқ',
            'permit' => \App\Models\ApplicationSurvey::PERMIT_STATUSES[array_rand(\App\Models\ApplicationSurvey::PERMIT_STATUSES)],
            'latitude' => $lat,
            'longitude' => $lng,
            'photos' => $photos,
            'study_report_path' => 'uploads/documents/doc_8_9KQU8LSgGG.docx',
            'geo_area' => $this->areaPolygon($lat, $lng, $area),
            'extra_info' => 'Жой текширилди, ўлчовлар олинди.',
        ]);
        $application->load('surveys');
    }

    /** Maydon (m²) atrofida taxminiy to'rtburchak GeoJSON poligon (lng,lat tartibida). */
    private function areaPolygon(float $lat, float $lng, float $area): array
    {
        // m² -> taxminiy yarim-tomon (gradusda). 1° ≈ 111_000 m.
        $half = sqrt(max($area, 9)) / 2 / 111000;

        return [
            'type' => 'Polygon',
            'coordinates' => [[
                [round($lng - $half, 7), round($lat - $half, 7)],
                [round($lng + $half, 7), round($lat - $half, 7)],
                [round($lng + $half, 7), round($lat + $half, 7)],
                [round($lng - $half, 7), round($lat + $half, 7)],
                [round($lng - $half, 7), round($lat - $half, 7)],
            ]],
        ];
    }

    /** Воқеаларни ўтмишга суриб, реалистик тарих ясаймиз. */
    private function backdate(Application $application, Carbon $base): void
    {
        $application->created_at = $base;
        $application->updated_at = $base;
        $application->save();

        $ts = $base->copy();
        $reviewExitTime = null;

        foreach ($application->transitions()->orderBy('id')->get() as $tr) {
            $ts = $ts->copy()->addHours(rand(5, 36));
            $tr->created_at = $ts;
            $tr->updated_at = $ts;
            $tr->save();

            if ($tr->from_stage === ApplicationStage::ResponsibleReview) {
                $reviewExitTime = $ts->copy()->subHours(2);
            }
            if ($tr->action === TransitionAction::Submit) {
                $application->submitted_at = $ts;
            }
        }

        // Ўлчов вақтини review босқичи ичига жойлаймиз.
        foreach ($application->surveys()->orderBy('id')->get() as $sv) {
            $sv->created_at = $reviewExitTime ?? $base->copy()->addDays(2);
            $sv->updated_at = $sv->created_at;
            $sv->save();
        }

        $last = $application->transitions()->max('created_at');
        $application->updated_at = $last ?? $base;
        if ($application->current_stage->isTerminal()) {
            $application->finished_at = $last;
        }
        $application->save();
    }

    /** Тасдиқлангандан сўнг шартномани ўтмишга мослаб тўлов ҳолатларини белгилаймиз. */
    private function setupContract(Application $application, Carbon $base, ?string $variant): void
    {
        $contract = $application->contract;
        if (! $contract) {
            return;
        }

        // Шартнома ўтмишда бошланган бўлсин (бир неча ой ўтган).
        $start = $base->copy()->addDays(rand(3, 12))->startOfMonth();
        $contract->update([
            'contract_date' => $base->copy()->addDays(rand(1, 5)),
            'start_date' => $start,
            'end_date' => $start->copy()->addMonths(12)->subDay(),
            'penalty_rate' => 0.1,
        ]);
        $this->contracts->regenerateSchedule($contract->fresh());

        $contract->load('schedules.invoice');
        $past = $contract->schedules->filter(fn ($s) => $s->due_date->lt(now()));
        $overdueCount = 0;

        foreach ($contract->schedules as $i => $schedule) {
            if ($schedule->due_date->gte(now())) {
                continue; // келажак — pending
            }

            $isLastTwoPast = $variant === 'delinquent'
                && $i >= ($past->count() - 2);

            if ($isLastTwoPast) {
                $days = (int) $schedule->due_date->copy()->diffInDays(now());
                $penalty = round((float) $schedule->amount * (0.1 / 100) * $days, 2);
                $schedule->update(['status' => PaymentStatus::Overdue, 'penalty_amount' => $penalty]);
                $schedule->invoice?->update(['status' => InvoiceStatus::Overdue]);
                $overdueCount++;
            } else {
                $schedule->update(['status' => PaymentStatus::Paid]);
                $schedule->invoice?->update([
                    'status' => InvoiceStatus::Paid,
                    'paid_at' => $schedule->due_date->copy()->addDays(rand(0, 6)),
                ]);
            }
        }
    }

    private function areaForVariant(ApplicationStage $target): float
    {
        return (float) [44, 36, 52, 64, 78, 30, 48, 55, 42, 33][array_rand([44, 36, 52, 64, 78, 30, 48, 55, 42, 33])];
    }
}
