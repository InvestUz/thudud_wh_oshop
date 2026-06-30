<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStage;
use App\Enums\ApplicationStatus;
use App\Enums\RoleType;
use App\Exceptions\WorkflowException;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\SurveyRequest;
use App\Http\Requests\TransitionRequest;
use App\Models\Application;
use App\Models\District;
use App\Models\RealEstateObject;
use App\Services\ApplicationWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(
        private ApplicationWorkflowService $workflow,
        private \App\Services\ContractDraftService $draftService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $role = $user->roleType();

        $query = Application::query()->with(['object', 'applicant', 'district', 'adjacentAreas']);

        if ($role === RoleType::Applicant) {
            $query->where('applicant_id', $user->id);
        } else {
            $query->forDistrictOf($user);

            // Pipeline xodimi uchun standart: o'z bosqichidagi "navbat".
            if ($user->isPipelineActor() && $request->input('scope', 'queue') === 'queue') {
                $query->inStages(ApplicationStage::stagesForRole($role));
            }
        }

        // Monitoring sahifasidagi kartochkalardan o'tib kelganda — tuman bo'yicha drill-down.
        if ($request->filled('district_id')) {
            $query->where('district_id', $request->integer('district_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('stage')) {
            $query->where('current_stage', $request->string('stage'));
        }

        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->where(function ($q) use ($term) {
                $q->where('application_number', 'like', "%{$term}%")
                    ->orWhereHas('object', fn ($o) => $o
                        ->where('cadastre_number', 'like', "%{$term}%")
                        ->orWhere('company_name', 'like', "%{$term}%"));
            });
        }

        $applications = $query->latest()->paginate(15)->withQueryString();

        return view('applications.index', [
            'applications' => $applications,
            'role' => $role,
            'statuses' => ApplicationStatus::cases(),
            'stages' => ApplicationStage::cases(),
            'scope' => $request->input('scope', 'queue'),
            'district' => $request->filled('district_id') ? District::find($request->integer('district_id')) : null,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Application::class);

        $objects = $request->user()->ownedObjects()->with('district')->get();
        // Yangi obyekt kiritish uchun — hozircha faqat Toshkent shahri (lending bilan bir xil).
        $regions = \App\Models\Region::where('name', 'Тошкент шаҳри')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('applications.create', compact('objects', 'regions'));
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $this->authorize('create', Application::class);

        $user = $request->user();

        $application = DB::transaction(function () use ($request, $user) {
            $object = $this->resolveObject($request, $user);

            $application = Application::create([
                'application_number' => $this->generateNumber(),
                'object_id' => $object->id,
                'applicant_id' => $user->id,
                'status' => ApplicationStatus::Draft,
                'current_stage' => ApplicationStage::Draft,
                'region_id' => $object->region_id,
                'district_id' => $object->district_id,
            ]);

            $application->adjacentAreas()->create([
                'activity' => $request->validated('activity'),
                'area_m2' => $request->validated('area_m2'),
                'structures' => $request->validated('structures'),
            ]);

            // Darhol topshirish belgilangan bo'lsa — pipeline'ga uzatamiz.
            if ($request->boolean('submit_now')) {
                $this->workflow->submit($application, $user, $request->validated('comment'));
            }

            return $application;
        });

        return redirect()
            ->route('applications.show', $application)
            ->with('status', 'Ариза яратилди: '.$application->application_number);
    }

    /**
     * Ariza uchun obyektni aniqlaydi: mavjudini tanlash yoki yangisini yaratish.
     */
    private function resolveObject(StoreApplicationRequest $request, \App\Models\User $user): RealEstateObject
    {
        if (! $request->isNewObject()) {
            return $user->ownedObjects()->findOrFail($request->validated('object_id'));
        }

        $v = $request->validated();

        return RealEstateObject::firstOrCreate(
            ['cadastre_number' => $v['cadastre_number']],
            [
                'owner_id' => $user->id,
                'company_name' => $v['company_name'],
                'tin_pinfl' => ($v['tin_pinfl'] ?? null) ?: $user->pinfl,
                'phone' => ($v['phone'] ?? null) ?: $user->phone,
                'region_id' => $v['region_id'],
                'district_id' => $v['district_id'],
                'mahalla_id' => $v['mahalla_id'] ?? null,
                'street' => $v['street'],
                'street_status' => 'Шаҳар кўчаси',
                'house_number' => $v['house_number'] ?? null,
                'created_by' => $user->id,
            ]
        );
    }

    public function show(Application $application): View
    {
        $this->authorize('view', $application);

        $user = request()->user();
        $application->load([
            'object.district', 'object.region', 'object.mahalla', 'object.tenants',
            'applicant', 'adjacentAreas',
            'transitions.performer', 'surveys.surveyor', 'contract',
        ]);

        $availableActions = $this->workflow->availableActions($application, $user);

        // Мас'ул ходим киритган маълумотларни ариза тасдиқлангунча (терминал бўлмагунча)
        // таҳрирлай олади — фақат ўз тумани доирасида.
        $canEditSurvey = $user->isRole(RoleType::ResponsibleOfficer)
            && ! $application->stage()->isTerminal()
            && ($user->district_id === null || $application->district_id === $user->district_id);

        return view('applications.show', [
            'application' => $application,
            'availableActions' => $availableActions,
            'canEditSurvey' => $canEditSurvey,
            'latestSurvey' => $application->surveys->last(),
        ]);
    }

    /**
     * Shartnoma loyihasini brauzerda ko'rsatadi (yuklab olmasdan, modal/iframe uchun).
     */
    public function contractDraft(Application $application): View
    {
        $this->authorize('view', $application);

        return view('applications.contract-draft', [
            'application' => $application,
            'documentHtml' => $this->draftService->html($application),
        ]);
    }

    public function transition(TransitionRequest $request, Application $application): RedirectResponse
    {
        $user = $request->user();

        try {
            $this->workflow->transition($application, $user, $request->action(), $request->validated('comment'));
        } catch (WorkflowException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }

        return redirect()
            ->route('applications.show', $application)
            ->with('status', 'Ҳаракат бажарилди: '.$request->action()->label());
    }

    public function storeSurvey(SurveyRequest $request, Application $application): RedirectResponse
    {
        $this->authorize('view', $application);
        $user = $request->user();

        // Ариза тасдиқлангунча (терминал бўлмагунча) мас'ул ходим таҳрирлай олади.
        abort_unless(
            $user->isRole(RoleType::ResponsibleOfficer)
                && ! $application->stage()->isTerminal()
                && ($user->district_id === null || $application->district_id === $user->district_id),
            403
        );

        // Мавжуд ўлчов бўлса — уни янгилаймиз (такрор яратмаймиз).
        $survey = $application->surveys()->latest('id')->first();

        // Расмлар: янги юкланса — public/uploads/surveys га сақлаб, эскисини алмаштирамиз;
        // юкланмаса — олдингилари сақланиб қолади.
        $photoPaths = $this->storeUploads($request->file('photos', []), 'uploads/surveys', 'survey_'.$application->id);
        if (empty($photoPaths)) {
            $photoPaths = $survey?->photos;
        }

        // "Керакли ҳужжатлар": шунга ўхшаш — янги юкланса алмаштирамиз.
        $documentPaths = $this->storeUploads($request->file('documents', []), 'uploads/documents', 'doc_'.$application->id);
        if (empty($documentPaths)) {
            $documentPaths = $survey?->documents;
        }

        // Xaritada belgilangan maydon (GeoJSON matni) -> massiv.
        $geoArea = $survey?->geo_area;
        if ($request->filled('geo_area')) {
            $decoded = json_decode($request->validated('geo_area'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $geoArea = $decoded;
            }
        }

        $attributes = [
            'surveyed_by' => $survey?->surveyed_by ?? $user->id,
            'stage' => $survey?->stage ?? $application->current_stage->value,
            'length_m' => $request->validated('length_m'),
            'width_m' => $request->validated('width_m'),
            'calculated_area' => $request->filled('length_m') && $request->filled('width_m')
                ? round((float) $request->validated('length_m') * (float) $request->validated('width_m'), 2)
                : null,
            'total_area' => $request->validated('total_area'),
            'calc_method' => 'manual',
            'facade_length_m' => $request->validated('facade_length_m'),
            'distance_to_road_m' => $request->validated('distance_to_road_m'),
            'distance_to_sidewalk_m' => $request->validated('distance_to_sidewalk_m'),
            'street_type' => $request->validated('street_type'),
            'usage_purpose' => $request->validated('usage_purpose'),
            'activity_type' => $request->validated('activity_type'),
            'terrace_structures' => $request->validated('terrace_structures'),
            'permanent_structures' => $request->validated('permanent_structures'),
            'permit' => $request->validated('permit'),
            'extra_info' => $request->validated('extra_info'),
            'latitude' => $request->validated('latitude'),
            'longitude' => $request->validated('longitude'),
            'photos' => $photoPaths ?: null,
            'documents' => $documentPaths ?: null,
            'geo_area' => $geoArea,
        ];

        if ($survey) {
            $survey->update($attributes);
            $message = 'Маълумотлар янгиланди.';
        } else {
            $application->surveys()->create($attributes);
            $message = 'Маълумотлар сақланди. Энди аризани кейинги босқичга узатишингиз мумкин.';
        }

        // Ариза аллақачон ўринбосар/раҳбарга узатилган бўлса — шартнома лойиҳасини
        // янги маълумотлар билан қайта шакллантирамиз.
        if (in_array($application->stage(), [ApplicationStage::DeputyReview, ApplicationStage::HeadReview], true)) {
            $application->draft_document_path = $this->draftService->generate($application->fresh());
            $application->save();
        }

        return redirect()
            ->route('applications.show', $application)
            ->with('status', $message);
    }

    /**
     * Yuklangan fayllarni public/{$dir} ga ko'chiradi, nisbiy yo'llar ro'yxatini qaytaradi.
     *
     * @param  array<int, \Illuminate\Http\UploadedFile>|null  $files
     * @return array<int, string>
     */
    protected function storeUploads(?array $files, string $dir, string $prefix): array
    {
        $paths = [];
        foreach ((array) $files as $file) {
            $name = $prefix.'_'.Str::random(10).'.'.$file->getClientOriginalExtension();
            $file->move(public_path($dir), $name);
            $paths[] = $dir.'/'.$name;
        }

        return $paths;
    }

    protected function generateNumber(): string
    {
        do {
            $number = 'А-'.now()->format('y').'-'.Str::upper(Str::random(5));
        } while (Application::where('application_number', $number)->exists());

        return $number;
    }
}
