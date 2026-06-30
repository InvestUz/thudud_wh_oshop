@extends('layouts.app')
@section('title', 'Ариза '.$application->application_number)
@section('heading', 'Ариза '.$application->application_number)

@php
    use App\Enums\ApplicationStage;
    use App\Enums\RoleType;
    use App\Enums\TransitionAction;
    $btnClass = ['teal'=>'btn-teal','green'=>'btn-green','amber'=>'btn-amber','red'=>'btn-red'];

    // Tarix uchun voqealarni xronologik birlashtirish: yaratilish + survey'lar + o'tishlar
    $events = collect();
    $events->push(['at' => $application->created_at, 'type' => 'created']);
    foreach ($application->surveys as $sv) {
        $events->push(['at' => $sv->created_at, 'type' => 'survey', 'survey' => $sv]);
    }
    foreach ($application->transitions as $tr) {
        $events->push(['at' => $tr->created_at, 'type' => 'transition', 'tr' => $tr]);
    }
    $events = $events->sortBy('at')->values();

    // Mas'ul xodim va o'rinbosar yozgan xulosalar (rahbariyat uchun) —
    // shu bosqichlardan chiqishda yozilgan izohlar.
    $conclusionStages = [
        ApplicationStage::ResponsibleReview->value => "Мас'ул ходим хулосаси",
        ApplicationStage::DeputyReview->value => 'Ўринбосар хулосаси',
    ];
    $conclusions = $application->transitions
        ->filter(fn ($t) => $t->from_stage && array_key_exists($t->from_stage->value, $conclusionStages));

    $viewerRole = auth()->user()->roleType();
    $isLeadership = in_array($viewerRole, [RoleType::DeputyHead, RoleType::Head], true);
@endphp

@section('content')
    <div class="page-head">
        <div class="flex items-center gap-12 wrap">
            <span class="mono" style="font-size:18px;font-weight:800">{{ $application->application_number }}</span>
            <x-badge :color="$application->current_stage->color()" :label="$application->current_stage->label()" />
            <x-badge :color="$application->status->color()" :label="$application->status->label()" />
        </div>
        <div class="flex items-center gap-8 wrap">
            <button type="button" class="btn btn-outline btn-sm" onclick="openHistory()">
                <i class="fa-solid fa-clock-rotate-left"></i> Ариза ҳаракати тарихи
            </button>
            <a href="{{ url()->previous() }}" class="btn btn-outline btn-sm">← Орқага</a>
        </div>
    </div>

    {{-- Босқичлар оқими --}}
    <div class="card mb-16">
        <div class="card-body">
            <div class="stage-flow">
                @foreach(ApplicationStage::pipeline() as $i => $st)
                    @php
                        $cur = $application->current_stage;
                        $isRejected = $cur === ApplicationStage::Rejected;
                        $state = $st->order() < $cur->order() ? 'done' : ($st === $cur ? 'current' : '');
                    @endphp
                    @if($i > 0)<span class="arrow">→</span>@endif
                    <span class="step {{ $state }}">{{ $st->label() }}</span>
                @endforeach
                @if($application->current_stage === ApplicationStage::Rejected)
                    <span class="arrow">→</span>
                    <span class="step reject">{{ ApplicationStage::Rejected->label() }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid-2">
        {{-- Чап устун: маълумот + тарих --}}
        <div>
            <div class="card mb-16">
                <div class="card-head"><h2>Объект ва ариза маълумотлари</h2></div>
                <div class="card-body">
                    <dl class="dl">
                        <dt>Тадбиркор</dt><dd>{{ $application->applicant?->displayName() }}</dd>
                        <dt>ПИНФЛ / СТИР</dt><dd class="mono">{{ $application->applicant?->pinfl ?: $application->object?->tin_pinfl ?: '—' }}</dd>
                        <dt>Телефон</dt><dd>{{ $application->applicant?->phone ?: $application->object?->phone ?: '—' }}</dd>
                        <dt>Фирма номи</dt><dd>{{ $application->object?->company_name }}</dd>
                        <dt>Кадастр рақами</dt><dd class="mono">{{ $application->object?->cadastre_number }}</dd>
                        <dt>Вилоят / Туман</dt><dd>{{ $application->region?->name }}, {{ $application->district?->name }}</dd>
                        <dt>Маҳалла</dt><dd>{{ $application->object?->mahalla?->name ?: '—' }}</dd>
                        <dt>Кўча / Манзил</dt><dd>{{ $application->object?->fullAddress() ?: '—' }}</dd>
                    </dl>

                    <div class="divider"></div>
                    <h2 style="font-size:14px;margin:0 0 10px">Туташ ҳудуд</h2>
                    <table class="tbl">
                        <thead><tr><th>Фаолият</th><th>Майдон (м²)</th><th>Иншоотлар</th></tr></thead>
                        <tbody>
                        @forelse($application->adjacentAreas as $a)
                            <tr>
                                <td>{{ $a->activity ?: '—' }}</td>
                                <td class="num">{{ rtrim(rtrim(number_format((float)$a->area_m2,2,'.',' '),'0'),'.') }}</td>
                                <td>{{ $a->structures ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="muted">—</td></tr>
                        @endforelse
                        </tbody>
                    </table>

                    @if($latestSurvey)
                        <div class="divider"></div>
                        <h2 style="font-size:14px;margin:0 0 10px">Ўлчов натижалари (мас'ул ходим)</h2>
                        <dl class="dl">
                            <dt>А томони (узунлик)</dt><dd>{{ $latestSurvey->length_m ?? '—' }} м</dd>
                            <dt>Б томони (эни)</dt><dd>{{ $latestSurvey->width_m ?? '—' }} м</dd>
                            <dt>Умумий майдон</dt><dd><b>{{ $latestSurvey->total_area ?? '—' }} м²</b></dd>
                            <dt>Фасад узунлиги</dt><dd>{{ $latestSurvey->facade_length_m ?? '—' }} м</dd>
                            <dt>Йўлгача масофа</dt><dd>{{ $latestSurvey->distance_to_road_m ?? '—' }} м</dd>
                            <dt>Кўча тури</dt><dd>{{ $latestSurvey->street_type ?? '—' }}</dd>
                            <dt>Фойдаланиш мақсади</dt><dd>{{ $latestSurvey->usage_purpose ?? '—' }}</dd>
                        </dl>

                        @if(!empty($latestSurvey->photos))
                            <div class="mt-16"><b class="tiny muted">Объект расмлари ({{ count($latestSurvey->photos) }})</b></div>
                            <div class="photo-grid mt-8">
                                @foreach($latestSurvey->photos as $photo)
                                    <a href="{{ asset($photo) }}" target="_blank" class="photo-thumb" style="background-image:url('{{ asset($photo) }}')"></a>
                                @endforeach
                            </div>
                        @endif

                        @if(!empty($latestSurvey->documents))
                            <div class="mt-16"><b class="tiny muted">Керакли ҳужжатлар ({{ count($latestSurvey->documents) }})</b></div>
                            @include('partials.file-cards', ['files' => $latestSurvey->documents])
                        @endif

                        @if(!empty($latestSurvey->geo_area))
                            <div class="mt-16"><b class="tiny muted"><i class="fa-solid fa-map-location-dot"></i> Ижарага олинаётган майдон (харитада)</b></div>
                            <div class="map-view mt-8" id="surveyMap"
                                 data-geo='@json($latestSurvey->geo_area)'
                                 data-lat="{{ $latestSurvey->latitude }}" data-lng="{{ $latestSurvey->longitude }}"></div>
                        @endif
                    @endif

                    @if($application->current_stage === ApplicationStage::AwaitingSignature && $application->isOwnedBy(auth()->user()))
                        <div class="divider"></div>
                        <div class="alert alert-info mb-0">
                            <i class="fa-solid fa-pen-nib"></i> Раҳбар шартномани тасдиқлади. Илтимос, лойиҳани <b>«Кўриш»</b> орқали кўриб чиқинг ва пастдаги <b>«Шартномани имзолаш»</b> тугмаси билан имзоланг.
                        </div>
                    @endif

                    @if($application->draft_document_path)
                        <div class="divider"></div>
                        <div class="flex items-center justify-between gap-12 wrap" style="padding:12px 14px;border:1px solid var(--line);border-radius:10px;background:#f8fafa">
                            <span><i class="fa-solid fa-file-word" style="color:#2b579a"></i> Шартнома лойиҳаси тайёр</span>
                            <span class="flex items-center gap-8 wrap">
                                <button type="button" class="btn btn-teal btn-sm" onclick="openDraft()"><i class="fa-solid fa-eye"></i> Кўриш</button>
                                <a href="{{ asset($application->draft_document_path) }}" class="btn btn-outline btn-sm" download><i class="fa-solid fa-download"></i> Юклаб олиш (DOCX)</a>
                            </span>
                        </div>
                    @endif

                    @if($application->contract)
                        <div class="divider"></div>
                        <div class="alert alert-success mb-0 flex items-center justify-between">
                            <span><i class="fa-solid fa-circle-check"></i> Шартнома тузилган: <b>{{ $application->contract->contract_number }}</b></span>
                            <a href="{{ route('contracts.show', $application->contract) }}" class="btn btn-green btn-sm">Шартномани кўриш</a>
                        </div>
                    @endif
                </div>
            </div>

            @if($application->draft_document_path)
            {{-- Шартнома лойиҳаси — "Кўриш" босилганда модалда (iframe) очилади, юклаб олинмайди --}}
            <div class="modal-overlay" id="draftModal" style="display:none" onclick="if(event.target===this) closeDraft()">
                <div class="modal-panel modal-panel-wide">
                    <div class="modal-head">
                        <h2><i class="fa-solid fa-file-contract"></i> Шартнома лойиҳаси</h2>
                        <button type="button" class="modal-x" onclick="closeDraft()" title="Ёпиш"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body modal-body-flush">
                        <iframe id="draftFrame" title="Шартнома лойиҳаси" style="width:100%;height:100%;border:0;display:block"></iframe>
                    </div>
                </div>
            </div>
            @endif

            {{-- Тарих — юқоридаги тугма босилганда popup (модал)да очилади --}}
            <div class="modal-overlay" id="historyModal" style="display:none" onclick="if(event.target===this) closeHistory()">
                <div class="modal-panel">
                    <div class="modal-head">
                        <h2>Тарих — кимдан кимга</h2>
                        <button type="button" class="modal-x" onclick="closeHistory()" title="Ёпиш"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                        <div class="timeline">
                        @foreach($events as $ev)
                            @if($ev['type'] === 'created')
                                <div class="tl-item">
                                    <div class="tl-dot teal">●</div>
                                    <div class="tl-time">{{ optional($ev['at'])->format('d.m.Y H:i') }}</div>
                                    <div class="tl-card">
                                        <div class="tl-title">Ариза яратилди</div>
                                        <div class="tl-meta">Тадбиркор: <b>{{ $application->applicant?->displayName() }}</b>
                                            @if($application->applicant?->phone) · {{ $application->applicant->phone }}@endif</div>
                                        <div class="tl-meta">Объект: {{ $application->object?->company_name }} · {{ $application->object?->cadastre_number }}</div>
                                    </div>
                                </div>
                            @elseif($ev['type'] === 'survey')
                                @php $sv = $ev['survey']; @endphp
                                <div class="tl-item">
                                    <div class="tl-dot slate"><i class="fa-solid fa-location-crosshairs"></i></div>
                                    <div class="tl-time">{{ optional($ev['at'])->format('d.m.Y H:i') }}</div>
                                    <div class="tl-card">
                                        <div class="tl-title">Объект ўлчови ўтказилди</div>
                                        <div class="tl-meta">Мас'ул ходим: <b>{{ $sv->surveyor?->displayName() }}</b></div>
                                        <div class="tl-meta">А томони: {{ $sv->length_m ?? '—' }} м · Б томони: {{ $sv->width_m ?? '—' }} м · Майдон: <b>{{ $sv->total_area ?? '—' }} м²</b></div>
                                        @if(!empty($sv->photos))
                                            <div class="photo-grid mt-8">
                                                @foreach(array_slice($sv->photos, 0, 4) as $photo)
                                                    <a href="{{ asset($photo) }}" target="_blank" class="photo-thumb sm" style="background-image:url('{{ asset($photo) }}')"></a>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if(!empty($sv->geo_area))
                                            <div class="tl-meta"><i class="fa-solid fa-map-location-dot"></i> Майдон харитада белгиланган</div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                @php $tr = $ev['tr']; $col = $tr->action->color(); @endphp
                                <div class="tl-item">
                                    <div class="tl-dot {{ $col }}">●</div>
                                    <div class="tl-time">{{ optional($ev['at'])->format('d.m.Y H:i') }}</div>
                                    <div class="tl-card">
                                        <div class="tl-title">{{ $tr->action->label() }}</div>
                                        <div class="tl-meta">
                                            <b>{{ $tr->performer?->displayName() }}</b>
                                            @if($tr->performer?->roleType()) · {{ $tr->performer->roleType()->label() }}@endif
                                            @if($tr->performer?->phone) · {{ $tr->performer->phone }}@endif
                                        </div>
                                        <div class="tl-meta">
                                            {{ $tr->from_stage?->label() ?? '—' }} → <b>{{ $tr->to_stage->label() }}</b>
                                        </div>
                                        @if($tr->comment)
                                            <div class="tl-comment"><i class="fa-solid fa-comment"></i> {{ $tr->comment }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ўнг устун: ҳаракатлар + ўлчов формаси --}}
        <div>
            @if($canEditSurvey)
                <div class="card mb-16">
                    <div class="card-head"><h2>{!! $latestSurvey ? '<i class="fa-solid fa-pen"></i> Маълумотларни таҳрирлаш' : 'Ўлчов (жой ўрганиш)' !!}</h2></div>
                    <div class="card-body">
                        <p class="tiny muted mb-16">
                            @if($latestSurvey)
                                Киритилган маълумотларни ариза тасдиқлангунча таҳрирлай оласиз.
                            @else
                                Ўлчовни тўлдиринг, 4 та расм ва керакли ҳужжатларни юкланг, харитада майдонни белгиланг.
                            @endif
                        </p>
                        <form method="POST" action="{{ route('applications.survey', $application) }}" enctype="multipart/form-data" id="surveyForm">
                            @csrf
                            <div class="form-grid">
                                <div class="form-row"><label class="lbl">А томони — узунлик (м)</label><input class="inp" type="number" step="0.01" name="length_m" id="lengthInp" value="{{ old('length_m', $latestSurvey?->length_m) }}" placeholder="8"></div>
                                <div class="form-row"><label class="lbl">Б томони — эни (м)</label><input class="inp" type="number" step="0.01" name="width_m" id="widthInp" value="{{ old('width_m', $latestSurvey?->width_m) }}" placeholder="10"></div>
                            </div>
                            <div class="form-row">
                                <label class="lbl">Умумий майдон (м²) <span class="req">*</span></label>
                                <input class="inp" type="number" step="0.01" name="total_area" id="totalArea" value="{{ old('total_area', $latestSurvey?->total_area) }}" placeholder="80" required readonly>
                                <span class="tiny muted mt-8">Узунлик × эни асосида автоматик ҳисобланади.</span>
                            </div>
                            <div class="form-grid">
                                <div class="form-row"><label class="lbl">Фасад узунлиги (м)</label><input class="inp" type="number" step="0.01" name="facade_length_m" value="{{ old('facade_length_m', $latestSurvey?->facade_length_m) }}"></div>
                                <div class="form-row"><label class="lbl">Йўлгача масофа (м)</label><input class="inp" type="number" step="0.01" name="distance_to_road_m" value="{{ old('distance_to_road_m', $latestSurvey?->distance_to_road_m) }}"></div>
                            </div>
                            <div class="form-grid">
                                <div class="form-row">
                                    <label class="lbl">Кўча тури <span class="req">*</span></label>
                                    <select class="inp" name="street_type" required>
                                        <option value="">— Танланг —</option>
                                        @foreach(\App\Models\ApplicationSurvey::STREET_TYPES as $t)
                                            <option value="{{ $t }}" @selected(old('street_type', $latestSurvey?->street_type) === $t)>{{ $t }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label class="lbl">Фойдаланиш мақсади <span class="req">*</span></label>
                                    <select class="inp" name="usage_purpose" required>
                                        <option value="">— Танланг —</option>
                                        @foreach(\App\Models\ApplicationSurvey::USAGE_PURPOSES as $p)
                                            <option value="{{ $p }}" @selected(old('usage_purpose', $latestSurvey?->usage_purpose) === $p)>{{ $p }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row"><label class="lbl">Изоҳ</label><textarea class="inp" name="extra_info" placeholder="Қўшимча...">{{ old('extra_info', $latestSurvey?->extra_info) }}</textarea></div>

                            {{-- Расм юклаш — ҳар бир расм алоҳида катакка; 4 тадан кейин 5-катак очилади --}}
                            <div class="form-row">
                                <label class="lbl">Объект расмлари — камида 4 та (jpg/png) <span class="req">*</span></label>
                                <div id="photoSlots" class="photo-slots" data-has-existing="{{ $latestSurvey && $latestSurvey->photos ? '1' : '0' }}"></div>
                                <div class="tiny muted mt-8" id="photoHint">Камида 4 та расм юкланг</div>
                                {{-- Яширин: бири form submit учун (photos[]), бири битта расм танлаш учун --}}
                                <input class="dz-input" type="file" name="photos[]" id="photoInput" accept="image/*" multiple>
                                <input class="dz-input" type="file" id="photoPicker" accept="image/*">
                                @if($latestSurvey && $latestSurvey->photos)
                                    <div class="tiny muted mt-8">Сақланган расмлар (янги юкласангиз алмаштирилади):</div>
                                    <div class="photo-grid mt-8">
                                        @foreach($latestSurvey->photos as $p)
                                            <a href="{{ asset($p) }}" target="_blank" class="photo-thumb sm" style="background-image:url('{{ asset($p) }}')"></a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- Керакли ҳужжатлар --}}
                            <div class="form-row">
                                <label class="lbl">Керакли ҳужжатлар (pdf, расм, Word/Excel)</label>
                                <label class="dropzone dz-doc" for="docInput">
                                    <span class="dz-icon"><i class="fa-solid fa-paperclip"></i></span>
                                    <span class="dz-text">Ҳужжат(лар)ни танлаш учун босинг</span>
                                    <span class="dz-hint">Кўпи 10 та · ҳар бири 10 МБ гача</span>
                                </label>
                                <input class="dz-input" type="file" name="documents[]" id="docInput" accept=".pdf,.doc,.docx,.xls,.xlsx,image/*" multiple>
                                <ul id="docPreview" class="doc-list mt-8"></ul>
                                @if($latestSurvey && $latestSurvey->documents)
                                    <div class="tiny muted mt-8">Сақланган ҳужжатлар (янги юкласангиз алмаштирилади):</div>
                                    @include('partials.file-cards', ['files' => $latestSurvey->documents])
                                @endif
                            </div>

                            {{-- Харитада майдонни белгилаш --}}
                            <div class="form-row">
                                <label class="lbl"><i class="fa-solid fa-map-location-dot"></i> Ижарага олинаётган майдонни харитада белгиланг</label>
                                <div class="map-edit" id="drawMap" data-lat="41.311" data-lng="69.279"></div>
                                <div class="flex gap-8 mt-8">
                                    <button type="button" class="btn btn-outline btn-sm" id="clearDraw">Тозалаш</button>
                                    <span class="tiny muted" id="drawHint" style="align-self:center">Тўртбурчак/кўпбурчак чизинг</span>
                                </div>
                                <input type="hidden" name="geo_area" id="geoArea" value="{{ old('geo_area', $latestSurvey && $latestSurvey->geo_area ? json_encode($latestSurvey->geo_area) : '') }}">
                                <input type="hidden" name="latitude" id="latInput" value="{{ old('latitude', $latestSurvey?->latitude) }}">
                                <input type="hidden" name="longitude" id="lngInput" value="{{ old('longitude', $latestSurvey?->longitude) }}">
                            </div>

                            <button class="btn btn-teal btn-block" type="submit">{{ $latestSurvey ? 'Ўзгаришларни сақлаш' : 'Маълумотларни сақлаш' }}</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Раҳбарият учун: мас'ул ходим ва ўринбосар хулосалари (қарордан олдин) --}}
            @if($isLeadership && $conclusions->isNotEmpty())
                <div class="card mb-16">
                    <div class="card-head"><h2>Хулосалар (мас'ул ходим ва ўринбосар)</h2></div>
                    <div class="card-body">
                        @foreach($conclusions as $c)
                            <div class="conclusion">
                                <div class="conclusion-head">
                                    <x-badge :color="$c->from_stage->color()"
                                             :label="$conclusionStages[$c->from_stage->value]" />
                                    <span class="tiny muted">{{ optional($c->created_at)->format('d.m.Y H:i') }}</span>
                                </div>
                                <div class="tiny muted mt-8">
                                    <b>{{ $c->performer?->displayName() }}</b>
                                    @if($c->performer?->roleType()) · {{ $c->performer->roleType()->label() }}@endif
                                    · <span>{{ $c->action->label() }}</span>
                                </div>
                                <div class="conclusion-text">{{ filled($c->comment) ? $c->comment : 'Изоҳ ёзилмаган' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Ҳаракатлар — фақат бажариш мумкин бўлган ҳаракат бўлса кўринади (акс ҳолда яширин) --}}
            @if(count($availableActions) > 0)
                <div class="card">
                    <div class="card-head"><h2>Ҳаракатлар</h2></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('applications.transition', $application) }}">
                            @csrf
                            <div class="form-row">
                                <label class="lbl">Изоҳ (бекор қилиш/қайтаришда мажбурий)</label>
                                <textarea class="inp" name="comment" placeholder="Изоҳ ёки сабаб...">{{ old('comment') }}</textarea>
                            </div>
                            <div class="flex gap-8 wrap">
                                @foreach($availableActions as $action)
                                    <button class="btn {{ $btnClass[$action->color()] ?? 'btn-outline' }}"
                                            type="submit" name="action" value="{{ $action->value }}">
                                        {{ $action->buttonLabel() }}
                                    </button>
                                @endforeach
                            </div>
                            <p class="tiny muted mt-16"><i class="fa-solid fa-triangle-exclamation"></i> Имзо (Е-ИМЗО) демода симуляция қилинмаган — ҳаракат тугма босиш билан амалга ошади.</p>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Ҳаракатлар остида — шартнома лойиҳаси бевосита кўриниб туради (DOCX viewer) --}}
            @if($application->draft_document_path)
                <div class="card mt-16">
                    <div class="card-head">
                        <h2><i class="fa-solid fa-file-contract"></i> Шартнома лойиҳаси</h2>
                        <a href="{{ asset($application->draft_document_path) }}" class="btn btn-outline btn-sm" download><i class="fa-solid fa-download"></i> DOCX</a>
                    </div>
                    <div class="card-body" style="padding:0">
                        <iframe src="{{ route('applications.contract-draft', $application) }}" title="Шартнома лойиҳаси"
                                style="width:100%;height:640px;border:0;display:block;border-radius:0 0 12px 12px"></iframe>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">
    <style>
        .photo-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
        .photo-thumb { display: block; aspect-ratio: 4/3; border-radius: 8px; background-size: cover; background-position: center; background-color: #eef2f4; border: 1px solid var(--line); }
        .photo-thumb.sm { aspect-ratio: 1/1; border-radius: 6px; }
        /* Файл юклаш dropzone'и */
        .dz-input { position: absolute; width: 1px; height: 1px; opacity: 0; overflow: hidden; }
        .dropzone { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
            padding: 22px 16px; border: 2px dashed var(--teal); border-radius: 12px; background: var(--teal-light);
            color: var(--teal-dark); text-align: center; cursor: pointer; transition: background .15s, border-color .15s; }
        .dropzone:hover { background: #e3f3f1; border-color: var(--teal-dark); }
        .dropzone .dz-icon { font-size: 30px; line-height: 1; }
        .dropzone .dz-text { font-weight: 700; font-size: 14px; }
        .dropzone .dz-hint { font-size: 12px; color: var(--muted); }
        .dropzone .dz-hint.ok { color: var(--teal-dark); font-weight: 700; }
        .dropzone .dz-hint.bad { color: var(--red, #d23); font-weight: 700; }
        .dropzone.dz-doc { border-color: var(--line); background: #f8fafa; color: var(--ink, #1f2937); }
        .dropzone.dz-doc:hover { background: #eef2f4; }
        .doc-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 4px; }
        .doc-list li { font-size: 13px; display: flex; align-items: center; gap: 8px; }
        .doc-list a { color: var(--teal-dark); text-decoration: none; }
        .doc-list a:hover { text-decoration: underline; }
        /* Сақланган файллар — чиройли карточкалар */
        .file-cards { display: flex; flex-wrap: wrap; gap: 8px; }
        .file-card { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border: 1px solid var(--line);
            border-radius: 10px; background: #fff; text-decoration: none; color: inherit; min-width: 160px;
            transition: border-color .15s, box-shadow .15s; }
        .file-card:hover { border-color: var(--teal); box-shadow: var(--shadow); }
        .file-card .fc-icon { font-size: 24px; line-height: 1; }
        .file-card .fc-body { display: flex; flex-direction: column; line-height: 1.25; }
        .file-card .fc-name { font-size: 13px; font-weight: 700; }
        .file-card .fc-ext { font-size: 11px; color: var(--muted); }
        .file-card .fc-open { margin-left: auto; color: var(--muted); font-size: 12px; }
        /* Тарих popup (модал) */
        .modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.55); z-index: 1000;
            display: flex; align-items: flex-start; justify-content: center; padding: 40px 16px; overflow-y: auto; }
        .modal-panel { background: #fff; border-radius: 14px; width: 100%; max-width: 720px;
            box-shadow: var(--shadow-lg); max-height: 88vh; display: flex; flex-direction: column; overflow: hidden; }
        .modal-head { display: flex; align-items: center; justify-content: space-between;
            padding: 14px 20px; border-bottom: 1px solid var(--line); }
        .modal-head h2 { margin: 0; font-size: 16px; }
        .modal-x { border: none; background: transparent; font-size: 20px; cursor: pointer; color: var(--muted);
            line-height: 1; padding: 4px 9px; border-radius: 6px; }
        .modal-x:hover { background: #f1f5f9; color: #1f2937; }
        .modal-body { padding: 18px 20px; overflow-y: auto; }
        /* Кенг модал (шартнома лойиҳаси кўриниши учун) */
        .modal-panel-wide { max-width: 900px; height: 88vh; }
        .modal-body-flush { padding: 0; flex: 1; overflow: hidden; }
        /* Юкланган файлни ўчириш тугмаси */
        .thumb-wrap { position: relative; }
        .thumb-x { position: absolute; top: -6px; right: -6px; }
        .thumb-x, .doc-x { cursor: pointer; border: none; background: rgba(15,23,42,.7); color: #fff;
            border-radius: 50%; width: 20px; height: 20px; font-size: 11px; line-height: 1;
            display: inline-grid; place-items: center; padding: 0; }
        .thumb-x:hover, .doc-x:hover { background: var(--red, #d23); }
        /* Расм катаклари (ҳар бир расм алоҳида) */
        .photo-slots { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .photo-slot { position: relative; aspect-ratio: 1/1; border-radius: 12px; background-size: cover; background-position: center;
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; }
        .photo-slot.empty { border: 2px dashed var(--teal); background: var(--teal-light); color: var(--teal-dark); cursor: pointer; transition: background .15s; }
        .photo-slot.empty:hover { background: #e3f3f1; }
        .photo-slot.filled { border: 1px solid var(--line); }
        .photo-slot .slot-plus { font-size: 22px; line-height: 1; }
        .photo-slot .slot-n { font-size: 11px; color: var(--muted); }
        .slot-x { position: absolute; top: -7px; right: -7px; cursor: pointer; border: none; background: rgba(15,23,42,.72);
            color: #fff; border-radius: 50%; width: 22px; height: 22px; font-size: 12px; line-height: 1; display: inline-grid; place-items: center; padding: 0; }
        .slot-x:hover { background: var(--red, #d23); }
        @media (max-width: 560px) { .photo-slots { grid-template-columns: repeat(2, 1fr); } }
        .map-edit { height: 300px; border-radius: 10px; border: 1px solid var(--line); overflow: hidden; }
        .map-view { height: 230px; border-radius: 10px; border: 1px solid var(--line); overflow: hidden; }
        .leaflet-container { font: inherit; }
    </style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const TILES = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        const ATTR = '© OpenStreetMap';

        // Ариза ҳаракати тарихи — popup (модал) ойнада очилади.
        window.openHistory = function () {
            const m = document.getElementById('historyModal');
            if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
        };
        window.closeHistory = function () {
            const m = document.getElementById('historyModal');
            if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
        };
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') window.closeHistory(); });

        // Шартнома лойиҳаси — модалда (iframe) очилади, юклаб олинмайди.
        window.openDraft = function () {
            const m = document.getElementById('draftModal');
            const f = document.getElementById('draftFrame');
            if (!m || !f) return;
            if (!f.src) { f.src = '{{ route('applications.contract-draft', $application) }}'; }
            m.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        };
        window.closeDraft = function () {
            const m = document.getElementById('draftModal');
            if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
        };
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') window.closeDraft(); });

        // DataTransfer'ни тозалаш ёрдамчиси.
        function clearStore(dt) { while (dt.items.length) dt.items.remove(0); }

        // --- Расм юклаш: ҳар бир расм алоҳида катакка; 4 та тўлгач 5-катак очилади ---
        const photoInput = document.getElementById('photoInput');     // submit учун (photos[])
        const photoPicker = document.getElementById('photoPicker');   // битта расм танлаш
        const photoSlots = document.getElementById('photoSlots');
        const photoHint = document.getElementById('photoHint');
        const photoStore = new DataTransfer();
        const MIN_PHOTOS = 4;
        const MAX_PHOTOS = 10;
        const hasExistingPhotos = photoSlots && photoSlots.dataset.hasExisting === '1';

        function syncPhotoInput() {
            if (photoInput) photoInput.files = photoStore.files;
            if (photoHint) {
                const n = photoStore.files.length;
                const ok = n >= MIN_PHOTOS;
                photoHint.textContent = n === 0
                    ? 'Камида 4 та расм юкланг'
                    : (ok ? (n + ' та расм юкланди') : (n + ' та юкланди — яна камида ' + (MIN_PHOTOS - n) + ' та керак'));
                photoHint.classList.toggle('ok', ok);
                photoHint.classList.toggle('bad', n > 0 && !ok);
            }
        }

        function removePhotoAt(index) {
            const keep = Array.from(photoStore.files).filter((_, j) => j !== index);
            clearStore(photoStore);
            keep.forEach(f => photoStore.items.add(f));
            renderPhotoSlots();
        }

        function renderPhotoSlots() {
            if (!photoSlots) return;
            const n = photoStore.files.length;
            // Камида 4 катак; ҳаммаси тўлса — яна битта бўш катак (кўпи MAX_PHOTOS гача).
            let slots = Math.max(MIN_PHOTOS, n + 1);
            if (n >= MAX_PHOTOS) slots = MAX_PHOTOS;
            photoSlots.innerHTML = '';
            for (let i = 0; i < slots; i++) {
                const cell = document.createElement('div');
                cell.className = 'photo-slot';
                if (i < n) {
                    cell.classList.add('filled');
                    cell.style.backgroundImage = 'url(' + URL.createObjectURL(photoStore.files[i]) + ')';
                    const rm = document.createElement('button');
                    rm.type = 'button';
                    rm.className = 'slot-x';
                    rm.title = 'Ўчириш';
                    rm.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                    rm.addEventListener('click', (e) => { e.stopPropagation(); removePhotoAt(i); });
                    cell.appendChild(rm);
                } else {
                    cell.classList.add('empty');
                    cell.innerHTML = '<span class="slot-plus"><i class="fa-solid fa-plus"></i></span>'
                        + '<span class="slot-n">' + (i + 1) + '-расм</span>';
                    cell.addEventListener('click', () => {
                        if (photoStore.files.length >= MAX_PHOTOS) return;
                        photoPicker && photoPicker.click();
                    });
                }
                photoSlots.appendChild(cell);
            }
            syncPhotoInput();
        }

        if (photoPicker) {
            photoPicker.addEventListener('change', function () {
                const f = this.files && this.files[0];
                if (f && photoStore.files.length < MAX_PHOTOS) {
                    const dup = Array.from(photoStore.files).some(x => x.name === f.name && x.size === f.size);
                    if (!dup) photoStore.items.add(f);
                }
                this.value = '';   // кейин шу файлни яна танлаш мумкин бўлсин
                renderPhotoSlots();
            });
        }
        renderPhotoSlots();

        // --- Ҳужжатлар: ТЎПЛАНИБ боради, ёнига қўшилади ---
        const docInput = document.getElementById('docInput');
        const docStore = new DataTransfer();

        function renderDocs() {
            const list = document.getElementById('docPreview');
            list.innerHTML = '';
            Array.from(docStore.files).forEach((f, i) => {
                const li = document.createElement('li');
                const span = document.createElement('span');
                span.innerHTML = '<i class="fa-solid fa-file-lines"></i> ' + f.name;
                const rm = document.createElement('button');
                rm.type = 'button';
                rm.className = 'doc-x';
                rm.title = 'Ўчириш';
                rm.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                rm.addEventListener('click', () => {
                    const keep = Array.from(docStore.files).filter((_, j) => j !== i);
                    clearStore(docStore);
                    keep.forEach(x => docStore.items.add(x));
                    renderDocs();
                });
                li.appendChild(span);
                li.appendChild(rm);
                list.appendChild(li);
            });
            if (docInput) docInput.files = docStore.files;
        }

        if (docInput) {
            docInput.addEventListener('change', function (e) {
                Array.from(e.target.files).forEach(f => {
                    const dup = Array.from(docStore.files).some(x => x.name === f.name && x.size === f.size);
                    if (!dup) docStore.items.add(f);
                });
                renderDocs();
            });
        }

        // --- Умумий майдон авто-ҳисоб (узунлик × эни) ---
        const lengthInp = document.getElementById('lengthInp');
        const widthInp = document.getElementById('widthInp');
        const totalArea = document.getElementById('totalArea');
        if (lengthInp && widthInp && totalArea) {
            const calcArea = () => {
                const l = parseFloat(lengthInp.value);
                const w = parseFloat(widthInp.value);
                totalArea.value = (l > 0 && w > 0) ? (Math.round(l * w * 100) / 100) : '';
            };
            lengthInp.addEventListener('input', calcArea);
            widthInp.addEventListener('input', calcArea);
        }

        // --- Форма юборишда: камида 4 та расм бўлсин ---
        const surveyForm = document.getElementById('surveyForm');
        if (surveyForm) {
            surveyForm.addEventListener('submit', function (e) {
                const n = photoStore.files.length;
                // Янги расм юкланмаган, аммо аввал сақланган бўлса — ўтказамиз.
                if (n === 0 && hasExistingPhotos) return;
                if (n < MIN_PHOTOS) {
                    e.preventDefault();
                    alert('Камида 4 та расм юкланг (ҳозир ' + n + ' та).');
                }
            });
        }

        // --- Чизиш харитаси (survey формаси) ---
        const drawEl = document.getElementById('drawMap');
        if (drawEl && window.L && L.Control && L.Control.Draw) {
            const map = L.map('drawMap').setView([parseFloat(drawEl.dataset.lat), parseFloat(drawEl.dataset.lng)], 17);
            L.tileLayer(TILES, { maxZoom: 19, attribution: ATTR }).addTo(map);
            const drawn = new L.FeatureGroup().addTo(map);

            map.addControl(new L.Control.Draw({
                draw: {
                    polygon: { showArea: false, shapeOptions: { color: '#0f7b7b' } },
                    rectangle: { showArea: false, shapeOptions: { color: '#0f7b7b' } },
                    polyline: false, circle: false, marker: false, circlemarker: false
                },
                edit: { featureGroup: drawn }
            }));

            function save() {
                const gj = drawn.toGeoJSON();
                const hint = document.getElementById('drawHint');
                if (!gj.features.length) {
                    document.getElementById('geoArea').value = '';
                    hint.textContent = 'Тўртбурчак/кўпбурчак чизинг';
                    return;
                }
                document.getElementById('geoArea').value = JSON.stringify(gj.features[0].geometry);
                const c = drawn.getBounds().getCenter();
                document.getElementById('latInput').value = c.lat.toFixed(7);
                document.getElementById('lngInput').value = c.lng.toFixed(7);
                hint.textContent = 'Майдон белгиланди ✓';
            }

            map.on(L.Draw.Event.CREATED, e => { drawn.clearLayers(); drawn.addLayer(e.layer); save(); });
            map.on(L.Draw.Event.EDITED, save);
            map.on(L.Draw.Event.DELETED, save);
            document.getElementById('clearDraw').addEventListener('click', () => { drawn.clearLayers(); save(); });

            // old() қийматни тиклаш (валидация хатоси бўлса)
            const old = document.getElementById('geoArea').value;
            if (old) {
                try {
                    L.geoJSON({ type: 'Feature', geometry: JSON.parse(old) }).eachLayer(l => drawn.addLayer(l));
                    map.fitBounds(drawn.getBounds(), { maxZoom: 18 });
                } catch (e) {}
            }
            setTimeout(() => map.invalidateSize(), 200);
        }

        // --- Фақат кўриш харитаси (детал) ---
        const viewEl = document.getElementById('surveyMap');
        if (viewEl && window.L) {
            const map = L.map('surveyMap', { scrollWheelZoom: false });
            L.tileLayer(TILES, { maxZoom: 19, attribution: ATTR }).addTo(map);
            try {
                const layer = L.geoJSON({ type: 'Feature', geometry: JSON.parse(viewEl.dataset.geo) },
                    { style: { color: '#0f7b7b', weight: 2, fillOpacity: 0.25 } }).addTo(map);
                map.fitBounds(layer.getBounds(), { maxZoom: 18 });
            } catch (e) {
                map.setView([parseFloat(viewEl.dataset.lat) || 41.311, parseFloat(viewEl.dataset.lng) || 69.279], 16);
            }
            setTimeout(() => map.invalidateSize(), 200);
        }
    });
    </script>
@endpush
