@extends('layouts.app')
@section('title', 'Ариза '.$application->application_number)
@section('heading', 'Ариза '.$application->application_number)

@php
    use App\Enums\ApplicationStage;
    use App\Enums\RoleType;

    $btnClass = ['teal' => 'btn-teal', 'green' => 'btn-green', 'amber' => 'btn-amber', 'red' => 'btn-red'];
    $events = collect([['at' => $application->created_at, 'type' => 'created']]);
    foreach ($application->surveys as $survey) {
        $events->push(['at' => $survey->created_at, 'type' => 'survey', 'survey' => $survey]);
    }
    foreach ($application->transitions as $transition) {
        $events->push(['at' => $transition->created_at, 'type' => 'transition', 'transition' => $transition]);
    }
    $events = $events->sortBy('at')->values();

    $conclusionStages = [
        ApplicationStage::ResponsibleReview->value => "Масъул ходим хулосаси",
        ApplicationStage::DeputyReview->value => 'Ўринбосар хулосаси',
    ];
    $conclusions = $application->transitions
        ->filter(fn ($transition) => $transition->from_stage
            && array_key_exists($transition->from_stage->value, $conclusionStages));
    $viewerRole = auth()->user()->roleType();
    $isLeadership = in_array($viewerRole, [RoleType::DeputyHead, RoleType::Head], true);
    $fileCount = count($latestSurvey?->photos ?? []) + count($latestSurvey?->documents ?? []);
    $mapLat = old('latitude', $latestSurvey?->latitude ?: 41.311081);
    $mapLng = old('longitude', $latestSurvey?->longitude ?: 69.279737);
@endphp

@section('content')
    <div class="application-hero">
        <div>
            <div class="application-kicker">АРИЗА КАРТОЧКАСИ</div>
            <div class="flex items-center gap-12 wrap">
                <span class="application-number">{{ $application->application_number }}</span>
                <x-badge :color="$application->current_stage->color()" :label="$application->current_stage->label()" />
                <x-badge :color="$application->status->color()" :label="$application->status->label()" />
            </div>
            <div class="application-subtitle">
                {{ $application->object?->company_name }} · {{ $application->district?->name }} · {{ optional($application->created_at)->format('d.m.Y') }}
            </div>
        </div>
        <a href="{{ route('applications.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Аризаларга қайтиш</a>
    </div>

    <div class="stage-shell">
        <div class="stage-flow">
            @foreach(ApplicationStage::pipeline() as $index => $stage)
                @php
                    $current = $application->current_stage;
                    $state = $stage->order() < $current->order() ? 'done' : ($stage === $current ? 'current' : '');
                @endphp
                @if($index > 0)<span class="arrow"><i class="fa-solid fa-chevron-right"></i></span>@endif
                <span class="step {{ $state }}">
                    @if($state === 'done')<i class="fa-solid fa-check"></i>@endif
                    {{ $stage->label() }}
                </span>
            @endforeach
            @if($application->current_stage === ApplicationStage::Rejected)
                <span class="arrow"><i class="fa-solid fa-chevron-right"></i></span>
                <span class="step reject">{{ ApplicationStage::Rejected->label() }}</span>
            @endif
        </div>
    </div>

    <div class="application-workspace">
        <section class="application-main">
            <nav class="detail-tabs" aria-label="Ариза бўлимлари">
                <button type="button" class="detail-tab active" data-tab="overview"><i class="fa-regular fa-rectangle-list"></i> Умумий</button>
                <button type="button" class="detail-tab" data-tab="survey"><i class="fa-solid fa-map-location-dot"></i> Ўлчов ва харита</button>
                <button type="button" class="detail-tab" data-tab="files"><i class="fa-regular fa-folder-open"></i> Файллар <span class="tab-count">{{ $fileCount }}</span></button>
                <button type="button" class="detail-tab" data-tab="history"><i class="fa-solid fa-clock-rotate-left"></i> Тарих <span class="tab-count">{{ $events->count() }}</span></button>
            </nav>

            <div class="tab-panel active" data-panel="overview">
                <div class="info-grid">
                    <article class="info-card">
                        <div class="section-title"><span class="section-icon"><i class="fa-solid fa-user-tie"></i></span><div><h2>Тадбиркор</h2><p>Ариза берувчи маълумотлари</p></div></div>
                        <dl class="detail-list">
                            <div><dt>Ф.И.Ш.</dt><dd>{{ $application->applicant?->displayName() }}</dd></div>
                            <div><dt>ПИНФЛ / СТИР</dt><dd class="mono">{{ $application->applicant?->pinfl ?: $application->object?->tin_pinfl ?: '—' }}</dd></div>
                            <div><dt>Телефон</dt><dd>{{ $application->applicant?->phone ?: $application->object?->phone ?: '—' }}</dd></div>
                        </dl>
                    </article>

                    <article class="info-card">
                        <div class="section-title"><span class="section-icon"><i class="fa-solid fa-building"></i></span><div><h2>Объект</h2><p>Кадастр ва манзил маълумотлари</p></div></div>
                        <dl class="detail-list">
                            <div><dt>Ташкилот</dt><dd>{{ $application->object?->company_name }}</dd></div>
                            <div><dt>Кадастр рақами</dt><dd class="mono">{{ $application->object?->cadastre_number }}</dd></div>
                            <div><dt>Ҳудуд</dt><dd>{{ $application->region?->name }}, {{ $application->district?->name }}</dd></div>
                            <div><dt>Маҳалла</dt><dd>{{ $application->object?->mahalla?->name ?: '—' }}</dd></div>
                            <div><dt>Манзил</dt><dd>{{ $application->object?->fullAddress() ?: '—' }}</dd></div>
                        </dl>
                    </article>
                </div>

                <article class="info-card mt-16">
                    <div class="section-title"><span class="section-icon"><i class="fa-solid fa-vector-square"></i></span><div><h2>Туташ ҳудуд</h2><p>Фойдаланиш мақсади ва ажратилган майдон</p></div></div>
                    <div class="metric-row">
                        @forelse($application->adjacentAreas as $area)
                            <div class="metric"><span>Фаолият</span><strong>{{ $area->activity ?: '—' }}</strong></div>
                            <div class="metric accent"><span>Майдон</span><strong>{{ rtrim(rtrim(number_format((float) $area->area_m2, 2, '.', ' '), '0'), '.') }} м²</strong></div>
                            <div class="metric"><span>Иншоотлар</span><strong>{{ $area->structures ?: '—' }}</strong></div>
                        @empty
                            <div class="empty">Маълумот киритилмаган</div>
                        @endforelse
                    </div>
                </article>

                @if($latestSurvey)
                    <article class="info-card mt-16">
                        <div class="section-title"><span class="section-icon"><i class="fa-solid fa-ruler-combined"></i></span><div><h2>Ўлчов натижалари</h2><p>{{ $latestSurvey->surveyor?->displayName() }} томонидан киритилган</p></div></div>
                        <div class="survey-summary">
                            <div><span>Узунлик</span><strong>{{ $latestSurvey->length_m ?? '—' }} м</strong></div>
                            <div><span>Эни</span><strong>{{ $latestSurvey->width_m ?? '—' }} м</strong></div>
                            <div class="primary"><span>Умумий майдон</span><strong>{{ $latestSurvey->total_area ?? '—' }} м²</strong></div>
                            <div><span>Фасад</span><strong>{{ $latestSurvey->facade_length_m ?? '—' }} м</strong></div>
                            <div><span>Йўлгача</span><strong>{{ $latestSurvey->distance_to_road_m ?? '—' }} м</strong></div>
                            <div><span>Кўча тури</span><strong>{{ $latestSurvey->street_type ?? '—' }}</strong></div>
                        </div>
                    </article>
                @endif

                @if($isLeadership && $conclusions->isNotEmpty())
                    <article class="info-card mt-16">
                        <div class="section-title"><span class="section-icon"><i class="fa-solid fa-comments"></i></span><div><h2>Хулосалар</h2><p>Қарор қабул қилиш учун хизмат ёзувлари</p></div></div>
                        @foreach($conclusions as $conclusion)
                            <div class="conclusion">
                                <div class="conclusion-head">
                                    <strong>{{ $conclusionStages[$conclusion->from_stage->value] }}</strong>
                                    <span class="tiny muted">{{ optional($conclusion->created_at)->format('d.m.Y H:i') }}</span>
                                </div>
                                <div class="tiny muted mt-8">{{ $conclusion->performer?->displayName() }} · {{ $conclusion->action->label() }}</div>
                                <div class="conclusion-text">{{ filled($conclusion->comment) ? $conclusion->comment : 'Изоҳ ёзилмаган' }}</div>
                            </div>
                        @endforeach
                    </article>
                @endif
            </div>

            <div class="tab-panel" data-panel="survey">
                @if($canEditSurvey)
                    <form method="POST" action="{{ route('applications.survey', $application) }}" enctype="multipart/form-data" id="surveyForm">
                        @csrf
                        <div class="panel-heading">
                            <div><h2>{{ $latestSurvey ? 'Ўлчовни таҳрирлаш' : 'Жой ўрганиш маълумотлари' }}</h2><p>Харитада майдонни белгиланг ва ўлчов натижаларини киритинг.</p></div>
                            <button class="btn btn-teal" type="submit"><i class="fa-solid fa-floppy-disk"></i> Сақлаш</button>
                        </div>

                        <div id="uploadGuard" class="upload-guard" hidden></div>

                        <article class="info-card map-card">
                            <div class="map-toolbar">
                                <div><strong><i class="fa-solid fa-layer-group"></i> Hybrid харита</strong><span id="drawHint">Майдон чегарасини чизинг ёки таҳрирланг</span></div>
                                <span class="map-action-status" id="mapActionStatus">Тайёр</span>
                            </div>
                            <div class="map-actionbar" aria-label="Харита бошқаруви">
                                <button type="button" class="map-action-btn primary draw-attention" id="startDraw"><i class="fa-solid fa-draw-polygon"></i><span>Чизиш</span></button>
                                <button type="button" class="map-action-btn" id="finishDraw" disabled><i class="fa-solid fa-check"></i><span>Якунлаш</span></button>
                                <button type="button" class="map-action-btn" id="findLocation"><i class="fa-solid fa-location-crosshairs"></i><span>Жойлашувни топ</span></button>
                                <button type="button" class="map-action-btn" id="undoPoint" disabled><i class="fa-solid fa-rotate-left"></i><span>Охирги нуқта</span></button>
                                <button type="button" class="map-action-btn" id="showArea"><i class="fa-solid fa-expand"></i><span>Кўрсатиш</span></button>
                                <button type="button" class="map-action-btn danger" id="clearDraw"><i class="fa-solid fa-trash-can"></i><span>Тозалаш</span></button>
                            </div>
                            <div class="map-edit" id="drawMap" data-lat="{{ $mapLat }}" data-lng="{{ $mapLng }}" data-geo='@json(old('geo_area', $latestSurvey?->geo_area))'></div>
                            <input type="hidden" name="geo_area" id="geoArea" value="{{ old('geo_area', $latestSurvey?->geo_area ? json_encode($latestSurvey->geo_area) : '') }}">
                            <input type="hidden" name="latitude" id="latInput" value="{{ old('latitude', $latestSurvey?->latitude) }}">
                            <input type="hidden" name="longitude" id="lngInput" value="{{ old('longitude', $latestSurvey?->longitude) }}">
                        </article>

                        <div class="info-grid mt-16">
                            <article class="info-card">
                                <div class="section-title compact"><span class="section-icon"><i class="fa-solid fa-ruler"></i></span><div><h2>Ўлчамлар</h2><p>Асосий геометрик кўрсаткичлар</p></div></div>
                                <div class="form-grid">
                                    <div class="form-row"><label class="lbl">А томони — узунлик (м)</label><input class="inp" type="number" step="0.01" name="length_m" id="lengthInp" value="{{ old('length_m', $latestSurvey?->length_m) }}"></div>
                                    <div class="form-row"><label class="lbl">Б томони — эни (м)</label><input class="inp" type="number" step="0.01" name="width_m" id="widthInp" value="{{ old('width_m', $latestSurvey?->width_m) }}"></div>
                                </div>
                                <div class="form-row"><label class="lbl">Умумий майдон (м²) <span class="req">*</span></label><input class="inp total-area" type="number" step="0.01" name="total_area" id="totalArea" value="{{ old('total_area', $latestSurvey?->total_area) }}" required readonly><div class="help">Узунлик × эни асосида автоматик ҳисобланади.</div></div>
                                <div class="form-grid">
                                    <div class="form-row"><label class="lbl">Фасад узунлиги (м)</label><input class="inp" type="number" step="0.01" name="facade_length_m" value="{{ old('facade_length_m', $latestSurvey?->facade_length_m) }}"></div>
                                    <div class="form-row"><label class="lbl">Йўлгача масофа (м)</label><input class="inp" type="number" step="0.01" name="distance_to_road_m" value="{{ old('distance_to_road_m', $latestSurvey?->distance_to_road_m) }}"></div>
                                </div>
                                <div class="form-row mb-0"><label class="lbl">Йўлаккача масофа (м)</label><input class="inp" type="number" step="0.01" name="distance_to_sidewalk_m" value="{{ old('distance_to_sidewalk_m', $latestSurvey?->distance_to_sidewalk_m) }}"></div>
                            </article>

                            <article class="info-card">
                                <div class="section-title compact"><span class="section-icon"><i class="fa-solid fa-clipboard-check"></i></span><div><h2>Фойдаланиш маълумотлари</h2><p>Майдоннинг ҳолати ва мақсади</p></div></div>
                                <div class="form-row"><label class="lbl">Кўча тури <span class="req">*</span></label><select class="inp" name="street_type" required><option value="">— Танланг —</option>@foreach(\App\Models\ApplicationSurvey::STREET_TYPES as $type)<option value="{{ $type }}" @selected(old('street_type', $latestSurvey?->street_type) === $type)>{{ $type }}</option>@endforeach</select></div>
                                <div class="form-row"><label class="lbl">Фойдаланиш мақсади <span class="req">*</span></label><select class="inp" name="usage_purpose" required><option value="">— Танланг —</option>@foreach(\App\Models\ApplicationSurvey::USAGE_PURPOSES as $purpose)<option value="{{ $purpose }}" @selected(old('usage_purpose', $latestSurvey?->usage_purpose) === $purpose)>{{ $purpose }}</option>@endforeach</select></div>
                                <div class="form-row"><label class="lbl">Фаолият тури</label><input class="inp" name="activity_type" value="{{ old('activity_type', $latestSurvey?->activity_type) }}"></div>
                                <div class="form-row"><label class="lbl">Терраса иншоотлари</label><input class="inp" name="terrace_structures" value="{{ old('terrace_structures', $latestSurvey?->terrace_structures) }}"></div>
                                <div class="form-row"><label class="lbl">Доимий иншоотлар</label><input class="inp" name="permanent_structures" value="{{ old('permanent_structures', $latestSurvey?->permanent_structures) }}"></div>
                                <div class="form-row"><label class="lbl">Рухсат ҳужжати</label><input class="inp" name="permit" value="{{ old('permit', $latestSurvey?->permit) }}"></div>
                                <div class="form-row mb-0"><label class="lbl">Қўшимча изоҳ</label><textarea class="inp" name="extra_info">{{ old('extra_info', $latestSurvey?->extra_info) }}</textarea></div>
                            </article>
                        </div>

                        <article class="info-card mt-16">
                            <div class="section-title"><span class="section-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span><div><h2>Фото ва ҳужжатлар</h2><p>Расмлар браузерда автоматик оптималлаштирилади</p></div></div>
                            <div class="upload-grid">
                                <div>
                                    <label class="lbl">Объект расмлари — камида 4 та <span class="req">*</span></label>
                                    <div id="photoSlots" class="photo-slots" data-has-existing="{{ $latestSurvey && $latestSurvey->photos ? '1' : '0' }}"></div>
                                    <div class="help" id="photoHint">Камида 4 та расм юкланг · ҳар бири 5 МБ гача</div>
                                    <input class="dz-input" type="file" name="photos[]" id="photoInput" accept="image/jpeg,image/png,image/webp" multiple>
                                    <input class="dz-input" type="file" id="photoPicker" accept="image/jpeg,image/png,image/webp">
                                </div>
                                <div>
                                    <label class="lbl">Қўшимча ҳужжатлар</label>
                                    <label class="dropzone dz-doc" for="docInput"><span class="dz-icon"><i class="fa-solid fa-paperclip"></i></span><span class="dz-text">Файлларни танлаш</span><span class="dz-hint">PDF, Word, Excel ёки расм · 10 МБ гача</span></label>
                                    <input class="dz-input" type="file" name="documents[]" id="docInput" accept=".pdf,.doc,.docx,.xls,.xlsx,image/*" multiple>
                                    <ul id="docPreview" class="doc-list mt-8"></ul>
                                </div>
                            </div>
                        </article>
                    </form>
                @elseif($latestSurvey)
                    <div class="panel-heading"><div><h2>Ўлчов ва харита</h2><p>Масъул ходим киритган маълумотлар</p></div></div>
                    <article class="info-card map-card">
                        <div class="map-toolbar"><div><strong><i class="fa-solid fa-layer-group"></i> Hybrid харита</strong><span>Satellite, hybrid ва оддий харита қатламлари</span></div></div>
                        <div class="map-edit" id="surveyMap" data-lat="{{ $mapLat }}" data-lng="{{ $mapLng }}" data-geo='@json($latestSurvey->geo_area)'></div>
                    </article>
                    <div class="survey-summary mt-16">
                        <div><span>Узунлик</span><strong>{{ $latestSurvey->length_m ?? '—' }} м</strong></div><div><span>Эни</span><strong>{{ $latestSurvey->width_m ?? '—' }} м</strong></div><div class="primary"><span>Майдон</span><strong>{{ $latestSurvey->total_area ?? '—' }} м²</strong></div><div><span>Фасад</span><strong>{{ $latestSurvey->facade_length_m ?? '—' }} м</strong></div><div><span>Йўлгача</span><strong>{{ $latestSurvey->distance_to_road_m ?? '—' }} м</strong></div><div><span>Мақсад</span><strong>{{ $latestSurvey->usage_purpose ?? '—' }}</strong></div>
                    </div>
                @else
                    <div class="empty-state"><i class="fa-solid fa-map-location-dot"></i><h2>Ўлчов ҳали киритилмаган</h2><p>Ариза масъул ходим босқичига ўтганда харита ва ўлчов маълумотлари шу ерда кўринади.</p></div>
                @endif
            </div>

            <div class="tab-panel" data-panel="files">
                <div class="panel-heading"><div><h2>Ариза файллари</h2><p>Фото, ҳужжат ва шаклланган файллар бир жойда</p></div>@if($canEditSurvey)<button type="button" class="btn btn-teal" data-open-tab="survey"><i class="fa-solid fa-plus"></i> Файл қўшиш</button>@endif</div>
                <article class="info-card">
                    <div class="section-title"><span class="section-icon"><i class="fa-regular fa-images"></i></span><div><h2>Объект расмлари</h2><p>{{ count($latestSurvey?->photos ?? []) }} та файл</p></div></div>
                    @if(!empty($latestSurvey?->photos))<div class="photo-gallery">@foreach($latestSurvey->photos as $photo)<a href="{{ asset($photo) }}" target="_blank" class="photo-thumb" style="background-image:url('{{ asset($photo) }}')"><span><i class="fa-solid fa-up-right-from-square"></i></span></a>@endforeach</div>@else<div class="empty compact">Расмлар ҳали юкланмаган</div>@endif
                </article>
                <article class="info-card mt-16">
                    <div class="section-title"><span class="section-icon"><i class="fa-regular fa-file-lines"></i></span><div><h2>Ҳужжатлар</h2><p>{{ count($latestSurvey?->documents ?? []) }} та файл</p></div></div>
                    @if(!empty($latestSurvey?->documents))@include('partials.file-cards', ['files' => $latestSurvey->documents])@else<div class="empty compact">Ҳужжатлар ҳали юкланмаган</div>@endif
                </article>
            </div>

            <div class="tab-panel" data-panel="history">
                <div class="panel-heading"><div><h2>Ариза ҳаракати тарихи</h2><p>Яратилишдан жорий босқичгача бўлган аудит</p></div></div>
                <article class="info-card">
                    <div class="timeline">
                        @foreach($events as $event)
                            @if($event['type'] === 'created')
                                <div class="tl-item"><div class="tl-dot teal"><i class="fa-solid fa-plus"></i></div><div class="tl-time">{{ optional($event['at'])->format('d.m.Y H:i') }}</div><div class="tl-card"><div class="tl-title">Ариза яратилди</div><div class="tl-meta">Тадбиркор: <b>{{ $application->applicant?->displayName() }}</b> · {{ $application->applicant?->phone }}</div><div class="tl-meta">Объект: {{ $application->object?->company_name }} · {{ $application->object?->cadastre_number }}</div></div></div>
                            @elseif($event['type'] === 'survey')
                                @php $survey = $event['survey']; @endphp
                                <div class="tl-item"><div class="tl-dot slate"><i class="fa-solid fa-ruler-combined"></i></div><div class="tl-time">{{ optional($event['at'])->format('d.m.Y H:i') }}</div><div class="tl-card"><div class="tl-title">Объект ўлчови сақланди</div><div class="tl-meta">Масъул: <b>{{ $survey->surveyor?->displayName() }}</b> · Майдон: {{ $survey->total_area }} м²</div></div></div>
                            @else
                                @php $transition = $event['transition']; @endphp
                                <div class="tl-item"><div class="tl-dot {{ $transition->action->color() }}"><i class="fa-solid fa-arrow-right"></i></div><div class="tl-time">{{ optional($event['at'])->format('d.m.Y H:i') }}</div><div class="tl-card"><div class="tl-title">{{ $transition->action->label() }}</div><div class="tl-meta"><b>{{ $transition->performer?->displayName() }}</b> · {{ $transition->performer?->roleType()?->label() }}</div><div class="tl-meta">{{ $transition->from_stage?->label() ?? '—' }} → <b>{{ $transition->to_stage->label() }}</b></div>@if($transition->comment)<div class="tl-comment"><i class="fa-regular fa-comment"></i> {{ $transition->comment }}</div>@endif</div></div>
                            @endif
                        @endforeach
                    </div>
                </article>
            </div>
        </section>

        <aside class="application-rail">
            <div class="sticky-stack">
                <article class="contract-card">
                    <div class="contract-head"><div><span>ШАРТНОМА</span><h2>{{ $application->contract?->contract_number ?: 'Шартнома лойиҳаси' }}</h2></div><i class="fa-solid fa-file-signature"></i></div>
                    @if($application->draft_document_path)
                        <div class="contract-intro">
                            <span class="contract-document-icon"><i class="fa-solid fa-file-contract"></i></span>
                            <h3>Шартнома лойиҳаси тайёр</h3>
                            <p>Ҳужжатни қулай ўлчамда ўқиш, текшириш ва тасдиқлашдан олдин тўлиқ кўриб чиқишингиз мумкин.</p>
                            <div class="contract-meta"><span><i class="fa-regular fa-file-word"></i> DOCX</span><span><i class="fa-regular fa-clock"></i> {{ optional($application->updated_at)->format('d.m.Y H:i') }}</span></div>
                        </div>
                        <div class="contract-actions"><button type="button" class="btn btn-teal btn-block contract-primary" onclick="openDraft()"><i class="fa-solid fa-book-open"></i> Шартнома билан танишиш</button><a href="{{ asset($application->draft_document_path) }}" class="btn btn-outline btn-block" download><i class="fa-solid fa-download"></i> DOCX юклаб олиш</a></div>
                    @elseif($application->contract)
                        <div class="contract-ready"><span><i class="fa-solid fa-circle-check"></i></span><h3>Шартнома расмийлаштирилган</h3><p>{{ optional($application->contract->contract_date)->format('d.m.Y') }}</p></div><div class="contract-actions"><a href="{{ route('contracts.show', $application->contract) }}" class="btn btn-green btn-block">Шартномани очиш</a></div>
                    @else
                        <div class="contract-empty"><i class="fa-solid fa-lock"></i><h3>Ҳали шаклланмаган</h3><p>Раҳбар кўриги босқичида шартнома лойиҳаси автоматик тайёрланади.</p></div>
                    @endif
                </article>

                @if(count($availableActions) > 0)
                    <article class="decision-card">
                        <div class="decision-head"><span><i class="fa-solid fa-bolt"></i></span><div><h2>Қарор</h2><p>Жорий босқич бўйича ҳаракат</p></div></div>
                        <form method="POST" action="{{ route('applications.transition', $application) }}">@csrf<textarea class="inp" name="comment" placeholder="Изоҳ ёки сабаб...">{{ old('comment') }}</textarea><div class="decision-actions">@foreach($availableActions as $action)<button class="btn {{ $btnClass[$action->color()] ?? 'btn-outline' }} btn-block" type="submit" name="action" value="{{ $action->value }}">{{ $action->buttonLabel() }}</button>@endforeach</div></form>
                    </article>
                @endif
            </div>
        </aside>
    </div>

    @if($application->draft_document_path)
        <div class="modal-overlay" id="draftModal" hidden onclick="if(event.target===this) closeDraft()"><div class="modal-panel modal-panel-wide"><div class="modal-head"><div><h2><i class="fa-solid fa-file-contract"></i> Шартнома билан танишиш</h2><p>{{ $application->application_number }} · {{ $application->object?->company_name }}</p></div><div class="modal-head-actions"><a href="{{ asset($application->draft_document_path) }}" class="btn btn-outline btn-sm" download><i class="fa-solid fa-download"></i> DOCX</a><button type="button" class="modal-x" onclick="closeDraft()" title="Ёпиш"><i class="fa-solid fa-xmark"></i></button></div></div><div class="modal-body modal-body-flush"><iframe id="draftFrame" title="Шартнома лойиҳаси"></iframe></div></div></div>
    @endif
@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">
    <style>
        .content { max-width: 1720px; width: 100%; margin: 0 auto; }
        .application-hero { display:flex; align-items:center; justify-content:space-between; gap:20px; margin-bottom:16px; }
        .application-kicker { color:var(--teal); font-size:11px; font-weight:800; letter-spacing:.12em; margin-bottom:5px; }
        .application-number { font-size:23px; font-weight:850; letter-spacing:-.02em; }
        .application-subtitle { color:var(--muted); font-size:12px; margin-top:6px; }
        .stage-shell { background:#fff; border:1px solid var(--line); border-radius:14px; padding:15px 18px; box-shadow:var(--shadow); margin-bottom:16px; overflow-x:auto; }
        .stage-flow { flex-wrap:nowrap; min-width:max-content; }
        .stage-flow .step { padding:7px 11px; }
        .stage-flow .arrow { font-size:9px; }
        .application-workspace { display:grid; grid-template-columns:minmax(0,1fr) 370px; gap:18px; align-items:start; }
        .application-main { min-width:0; }
        .detail-tabs { display:flex; gap:5px; padding:6px; background:#e6eaed; border-radius:13px; margin-bottom:14px; overflow-x:auto; }
        .detail-tab { border:0; background:transparent; color:#64748b; border-radius:9px; padding:10px 14px; font:inherit; font-size:13px; font-weight:700; cursor:pointer; white-space:nowrap; }
        .detail-tab.active { background:#fff; color:var(--teal-dark); box-shadow:0 1px 4px rgba(15,23,42,.09); }
        .tab-count { display:inline-grid; place-items:center; min-width:20px; height:20px; padding:0 6px; border-radius:999px; background:#dce3e7; font-size:10px; margin-left:3px; }
        .detail-tab.active .tab-count { background:var(--teal-light); }
        .tab-panel { display:none; }
        .tab-panel.active { display:block; animation:tabIn .18s ease; }
        @keyframes tabIn { from { opacity:.45; transform:translateY(3px) } to { opacity:1; transform:none } }
        .info-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
        .info-card { background:#fff; border:1px solid var(--line); border-radius:14px; padding:20px; box-shadow:var(--shadow); }
        .section-title { display:flex; align-items:center; gap:11px; margin-bottom:17px; }
        .section-title.compact { margin-bottom:20px; }
        .section-title h2, .panel-heading h2 { margin:0; font-size:15px; }
        .section-title p, .panel-heading p { margin:2px 0 0; color:var(--muted); font-size:12px; }
        .section-icon { width:36px; height:36px; border-radius:10px; display:grid; place-items:center; color:var(--teal-dark); background:var(--teal-light); flex:0 0 auto; }
        .detail-list { margin:0; display:flex; flex-direction:column; gap:0; }
        .detail-list>div { display:grid; grid-template-columns:135px minmax(0,1fr); gap:14px; padding:9px 0; border-bottom:1px solid #f0f2f4; }
        .detail-list>div:last-child { border:0; }
        .detail-list dt { color:var(--muted); font-size:12px; }
        .detail-list dd { margin:0; font-size:13px; font-weight:650; text-align:right; overflow-wrap:anywhere; }
        .metric-row { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
        .metric { padding:14px; border-radius:11px; background:#f7f9fa; border:1px solid #edf0f2; }
        .metric span, .survey-summary span { display:block; color:var(--muted); font-size:11px; margin-bottom:5px; }
        .metric strong { font-size:14px; }
        .metric.accent { background:var(--teal-light); border-color:#cbe5e2; color:var(--teal-dark); }
        .survey-summary { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
        .survey-summary>div { background:#f7f9fa; border:1px solid var(--line); border-radius:11px; padding:13px 14px; }
        .survey-summary strong { display:block; font-size:14px; }
        .survey-summary .primary { color:var(--teal-dark); background:var(--teal-light); border-color:#cbe5e2; }
        .panel-heading { display:flex; align-items:center; justify-content:space-between; gap:14px; margin:4px 2px 14px; }
        .map-card { padding:0; overflow:hidden; }
        .map-toolbar { display:flex; justify-content:space-between; align-items:center; gap:15px; padding:13px 16px; border-bottom:1px solid var(--line); }
        .map-toolbar strong, .map-toolbar span { display:block; }
        .map-toolbar span { color:var(--muted); font-size:11px; margin-top:2px; }
        .map-toolbar .map-action-status { display:inline-flex; align-items:center; min-height:27px; padding:4px 9px; border-radius:999px; background:#eef2f4; color:#64748b; font-size:10px; font-weight:700; white-space:nowrap; }
        .map-toolbar .map-action-status.active { color:var(--teal-dark); background:var(--teal-light); }
        .map-actionbar { display:flex; align-items:center; gap:7px; padding:10px 12px; border-bottom:1px solid var(--line); background:#f8fafb; overflow-x:auto; scrollbar-width:thin; }
        .map-action-btn { min-height:37px; display:inline-flex; align-items:center; justify-content:center; gap:7px; padding:8px 12px; border:1px solid #d8e0e4; border-radius:9px; background:#fff; color:#334155; font:inherit; font-size:12px; font-weight:750; white-space:nowrap; cursor:pointer; transition:.14s ease; }
        .map-action-btn:hover:not(:disabled) { border-color:var(--teal); color:var(--teal-dark); transform:translateY(-1px); box-shadow:0 3px 9px rgba(15,123,123,.10); }
        .map-action-btn.primary { background:var(--teal); border-color:var(--teal); color:#fff; }
        .map-action-btn.primary.active { background:#084f4f; box-shadow:0 0 0 3px rgba(15,123,123,.16); }
        .map-action-btn.draw-attention { position:relative; isolation:isolate; animation:drawButtonPulse 1.65s ease-in-out infinite; }
        .map-action-btn.draw-attention::after { content:""; position:absolute; inset:-5px; z-index:-1; border:2px solid rgba(15,123,123,.52); border-radius:13px; animation:drawButtonRing 1.65s ease-out infinite; pointer-events:none; }
        @keyframes drawButtonPulse { 0%,100%{transform:scale(1);box-shadow:0 3px 9px rgba(15,123,123,.12)} 45%{transform:scale(1.075);box-shadow:0 7px 20px rgba(15,123,123,.32)} }
        @keyframes drawButtonRing { 0%{opacity:.8;transform:scale(.9)} 70%,100%{opacity:0;transform:scale(1.18)} }
        @media (prefers-reduced-motion:reduce) { .map-action-btn.draw-attention,.map-action-btn.draw-attention::after{animation:none} }
        .map-action-btn.danger { color:#c0392b; }
        .map-action-btn:disabled { opacity:.42; cursor:not-allowed; }
        .map-edit { height:430px; background:#dce6ea; }
        .leaflet-container { font:inherit; }
        .total-area { color:var(--teal-dark)!important; background:var(--teal-light)!important; font-weight:800; font-size:17px!important; }
        .upload-grid { display:grid; grid-template-columns:1.15fr .85fr; gap:18px; }
        .upload-guard { padding:11px 14px; border-radius:10px; margin-bottom:12px; background:#fff4e5; border:1px solid #f7d79d; color:#8a5100; font-size:13px; }
        .dz-input { position:absolute; width:1px; height:1px; opacity:0; overflow:hidden; }
        .dropzone { min-height:150px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:5px; padding:18px; border:2px dashed #b6c5cc; border-radius:12px; background:#f8fafb; cursor:pointer; text-align:center; }
        .dropzone:hover { border-color:var(--teal); background:var(--teal-light); }
        .dropzone .dz-icon { font-size:27px; color:var(--teal); }
        .dropzone .dz-text { font-weight:700; }
        .dropzone .dz-hint { color:var(--muted); font-size:11px; }
        .photo-slots { display:grid; grid-template-columns:repeat(4,1fr); gap:9px; }
        .photo-slot { position:relative; aspect-ratio:1/1; border-radius:11px; background-size:cover; background-position:center; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:4px; }
        .photo-slot.empty { border:2px dashed #b8d8d5; background:var(--teal-light); color:var(--teal-dark); cursor:pointer; }
        .photo-slot.filled { border:1px solid var(--line); }
        .slot-x,.doc-x { border:0; cursor:pointer; width:22px; height:22px; display:grid; place-items:center; border-radius:50%; background:#1f2933d9; color:#fff; }
        .slot-x { position:absolute; top:-6px; right:-6px; }
        .slot-plus { font-size:20px; }.slot-n { font-size:10px; }
        .doc-list { list-style:none; padding:0; margin:8px 0 0; display:flex; flex-direction:column; gap:6px; }
        .doc-list li { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:7px 9px; border:1px solid var(--line); border-radius:8px; font-size:12px; }
        .photo-gallery { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; }
        .photo-thumb { min-height:145px; border-radius:11px; background-size:cover; background-position:center; position:relative; overflow:hidden; }
        .photo-thumb span { position:absolute; right:8px; bottom:8px; width:29px; height:29px; border-radius:8px; background:#0f172acc; color:#fff; display:grid; place-items:center; }
        .file-cards { display:flex; flex-wrap:wrap; gap:8px; }.file-card { display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--line); border-radius:10px; min-width:190px; color:inherit; }.file-card:hover { border-color:var(--teal); text-decoration:none; }.fc-icon { font-size:22px; }.fc-body { display:flex; flex-direction:column; }.fc-name { font-weight:700;font-size:12px;}.fc-ext{font-size:10px;color:var(--muted)}.fc-open{margin-left:auto}
        .empty-state { min-height:390px; display:grid; place-items:center; align-content:center; text-align:center; background:#fff; border:1px dashed #bdc8cf; border-radius:14px; color:var(--muted); padding:40px; }
        .empty-state>i { font-size:42px; color:#9bb8b6; }.empty-state h2{color:var(--ink);margin:13px 0 2px}.empty-state p{max-width:440px;margin:0}.empty.compact{padding:24px}
        .application-rail { min-width:0; }.sticky-stack { position:sticky; top:78px; display:flex; flex-direction:column; gap:14px; }
        .contract-card { background:#fff; border:1px solid #cfdadd; border-radius:15px; overflow:hidden; box-shadow:0 12px 30px rgba(15,23,42,.10); }
        .contract-head { padding:15px 17px; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg,#0a5f5f,#0f7b7b); color:#fff; }
        .contract-head span { font-size:9px; font-weight:800; letter-spacing:.13em; opacity:.75; }.contract-head h2 { font-size:14px; margin:2px 0 0; }.contract-head>i{font-size:24px;opacity:.75}
        .contract-intro { text-align:center; padding:28px 22px 24px; background:linear-gradient(180deg,#f8fbfb 0%,#fff 100%); }.contract-document-icon{width:64px;height:64px;border-radius:18px;display:grid;place-items:center;margin:0 auto 14px;background:var(--teal-light);color:var(--teal-dark);font-size:28px;box-shadow:inset 0 0 0 1px #cbe5e2}.contract-intro h3{margin:0 0 7px;font-size:15px}.contract-intro p{margin:0 auto;color:var(--muted);font-size:12px;line-height:1.55;max-width:290px}.contract-meta{display:flex;justify-content:center;gap:8px;flex-wrap:wrap;margin-top:16px}.contract-meta span{padding:5px 8px;border:1px solid var(--line);border-radius:7px;background:#fff;color:#64748b;font-size:10px}.contract-actions { display:grid; gap:8px; padding:12px; border-top:1px solid var(--line); }.contract-primary{min-height:43px;font-size:13px}.contract-ready,.contract-empty{text-align:center;padding:38px 22px}.contract-ready>span,.contract-empty>i{width:52px;height:52px;border-radius:50%;display:grid;place-items:center;margin:0 auto 12px;background:#e7f6ec;color:#1a7f43;font-size:22px}.contract-empty>i{background:#eef2f4;color:#64748b}.contract-ready h3,.contract-empty h3{margin:0 0 4px;font-size:14px}.contract-ready p,.contract-empty p{margin:0;color:var(--muted);font-size:12px}
        .decision-card { background:#fff; border:1px solid var(--line); border-radius:14px; padding:16px; box-shadow:var(--shadow); }.decision-head{display:flex;gap:10px;align-items:center;margin-bottom:12px}.decision-head>span{width:34px;height:34px;display:grid;place-items:center;border-radius:9px;background:#fff4df;color:#b4690e}.decision-head h2{margin:0;font-size:14px}.decision-head p{margin:1px 0 0;color:var(--muted);font-size:11px}.decision-card textarea{min-height:70px}.decision-actions{display:grid;gap:7px;margin-top:9px}
        .modal-overlay[hidden]{display:none}.modal-overlay{position:fixed;inset:0;background:#0f172acc;backdrop-filter:blur(4px);z-index:1000;display:flex;align-items:center;justify-content:center;padding:2.5vh 2vw}.modal-panel{background:#fff;border-radius:16px;width:100%;max-height:95vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 30px 80px rgba(2,8,23,.35)}.modal-panel-wide{width:min(1500px,96vw);height:95vh}.modal-head{min-height:62px;padding:11px 16px 11px 20px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;gap:18px}.modal-head h2{font-size:15px;margin:0}.modal-head p{margin:2px 0 0;color:var(--muted);font-size:11px}.modal-head-actions{display:flex;align-items:center;gap:8px}.modal-x{border:0;background:#eef2f4;border-radius:9px;width:34px;height:34px;cursor:pointer}.modal-x:hover{background:#e1e7eb}.modal-body-flush{flex:1;background:#e9eef1}.modal-body-flush iframe{width:100%;height:100%;border:0;background:#fff}
        @media(max-width:1250px){.application-workspace{grid-template-columns:minmax(0,1fr) 330px}.upload-grid{grid-template-columns:1fr}}
        @media(max-width:980px){.application-workspace{grid-template-columns:1fr}.sticky-stack{position:static}.info-grid{grid-template-columns:1fr}}
        @media(max-width:620px){.application-hero{align-items:flex-start}.application-hero>.btn{display:none}.detail-tabs{border-radius:10px}.metric-row,.survey-summary{grid-template-columns:1fr 1fr}.photo-slots,.photo-gallery{grid-template-columns:repeat(2,1fr)}.map-edit{height:360px}.detail-list>div{grid-template-columns:1fr}.detail-list dd{text-align:left}.panel-heading{align-items:flex-start}.panel-heading>.btn{padding:8px 10px}.modal-overlay{padding:0}.modal-panel-wide{width:100vw;height:100dvh;max-height:none;border-radius:0}.modal-head p{display:none}.modal-head-actions .btn{display:none}}
    </style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = [...document.querySelectorAll('.detail-tab')];
        const panels = [...document.querySelectorAll('.tab-panel')];
        const maps = [];
        const activateTab = name => {
            tabs.forEach(tab => tab.classList.toggle('active', tab.dataset.tab === name));
            panels.forEach(panel => panel.classList.toggle('active', panel.dataset.panel === name));
            history.replaceState(null, '', '#' + name);
            setTimeout(() => maps.forEach(map => map.invalidateSize()), 80);
        };
        tabs.forEach(tab => tab.addEventListener('click', () => activateTab(tab.dataset.tab)));
        document.querySelectorAll('[data-open-tab]').forEach(button => button.addEventListener('click', () => activateTab(button.dataset.openTab)));
        const initialTab = @json($errors->any() ? 'survey' : null) || location.hash.replace('#', '');
        if (['overview', 'survey', 'files', 'history'].includes(initialTab)) activateTab(initialTab);

        window.openDraft = () => { const modal = document.getElementById('draftModal'); const frame = document.getElementById('draftFrame'); if (!modal) return; if (frame && !frame.src) frame.src = @json(route('applications.contract-draft', $application)); modal.hidden = false; document.body.style.overflow = 'hidden'; };
        window.closeDraft = () => { const modal = document.getElementById('draftModal'); if (modal) modal.hidden = true; document.body.style.overflow = ''; };
        document.addEventListener('keydown', event => { if (event.key === 'Escape') window.closeDraft(); });

        const baseLayers = () => {
            const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 20, attribution: '© OpenStreetMap' });
            const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 20, attribution: 'Tiles © Esri' });
            const hybrid = L.layerGroup([
                L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 20, attribution: 'Tiles © Esri' }),
                L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', { maxZoom: 20 })
            ]);
            return { 'Hybrid': hybrid, 'Satellite': satellite, 'OpenStreetMap': osm };
        };

        const addBaseControl = (map, layers) => { layers.Hybrid.addTo(map); L.control.layers(layers, null, { position:'topright' }).addTo(map); maps.push(map); };
        const drawEl = document.getElementById('drawMap');
        if (drawEl && window.L) {
            const map = L.map(drawEl, { zoomControl:true }).setView([parseFloat(drawEl.dataset.lat), parseFloat(drawEl.dataset.lng)], 18);
            addBaseControl(map, baseLayers());
            const drawn = new L.FeatureGroup().addTo(map);
            const polygonDrawer = new L.Draw.Polygon(map, {
                allowIntersection:false,
                showArea:true,
                shapeOptions:{color:'#0f7b7b',weight:3,fillOpacity:.28}
            });
            let locationMarker = null;
            const startButton = document.getElementById('startDraw');
            const finishButton = document.getElementById('finishDraw');
            const undoButton = document.getElementById('undoPoint');
            const actionStatus = document.getElementById('mapActionStatus');
            const setDrawingState = active => {
                startButton?.classList.toggle('active', active);
                if (finishButton) finishButton.disabled = !active;
                if (undoButton) undoButton.disabled = !active;
                if (actionStatus) {
                    actionStatus.textContent = active ? 'Чизиш режими' : 'Тайёр';
                    actionStatus.classList.toggle('active', active);
                }
            };
            const setMapMessage = message => {
                const hint = document.getElementById('drawHint');
                if (hint) hint.textContent = message;
            };
            const syncGeometry = () => {
                const data = drawn.toGeoJSON();
                if (!data.features.length) { document.getElementById('geoArea').value=''; setMapMessage('Майдон чегарасини чизинг ёки таҳрирланг'); return; }
                document.getElementById('geoArea').value = JSON.stringify(data.features[0].geometry);
                const center = drawn.getBounds().getCenter();
                document.getElementById('latInput').value = center.lat.toFixed(7);
                document.getElementById('lngInput').value = center.lng.toFixed(7);
                setMapMessage('Майдон белгиланди ✓');
            };
            map.on(L.Draw.Event.DRAWSTART, () => setDrawingState(true));
            map.on(L.Draw.Event.DRAWSTOP, () => setDrawingState(false));
            map.on(L.Draw.Event.DRAWVERTEX, () => setMapMessage('Кейинги нуқтани белгиланг ёки «Якунлаш»ни босинг'));
            map.on(L.Draw.Event.CREATED, event => { drawn.clearLayers(); drawn.addLayer(event.layer); syncGeometry(); map.fitBounds(drawn.getBounds(), {padding:[35,35],maxZoom:19}); });

            startButton?.addEventListener('click', () => {
                if (polygonDrawer.enabled()) return;
                startButton.classList.remove('draw-attention');
                if (drawn.getLayers().length) drawn.clearLayers();
                syncGeometry();
                polygonDrawer.enable();
                setMapMessage('Харитада камида 3 та нуқтани белгиланг');
            });
            finishButton?.addEventListener('click', () => {
                if (polygonDrawer.enabled()) polygonDrawer.completeShape();
            });
            undoButton?.addEventListener('click', () => {
                if (polygonDrawer.enabled()) {
                    polygonDrawer.deleteLastVertex();
                    setMapMessage('Охирги нуқта олиб ташланди');
                }
            });
            document.getElementById('findLocation')?.addEventListener('click', () => {
                setMapMessage('Жойлашув аниқланмоқда...');
                map.locate({setView:true,maxZoom:19,enableHighAccuracy:true,timeout:12000});
            });
            map.on('locationfound', event => {
                if (locationMarker) locationMarker.remove();
                locationMarker = L.circleMarker(event.latlng, {radius:8,color:'#fff',weight:3,fillColor:'#0f7b7b',fillOpacity:1})
                    .addTo(map).bindTooltip('Сизнинг жойлашувингиз').openTooltip();
                if (!drawn.getLayers().length) {
                    document.getElementById('latInput').value = event.latitude.toFixed(7);
                    document.getElementById('lngInput').value = event.longitude.toFixed(7);
                }
                setMapMessage('Жойлашув топилди ✓');
            });
            map.on('locationerror', () => setMapMessage('Жойлашувни аниқлаб бўлмади. Браузер рухсатини текширинг.'));
            document.getElementById('showArea')?.addEventListener('click', () => {
                if (drawn.getLayers().length) {
                    map.fitBounds(drawn.getBounds(), {padding:[45,45],maxZoom:19});
                    setMapMessage('Белгиланган майдон кўрсатилди');
                } else if (locationMarker) {
                    map.setView(locationMarker.getLatLng(), 19);
                    setMapMessage('Жорий жойлашув кўрсатилди');
                } else {
                    map.setView([parseFloat(drawEl.dataset.lat), parseFloat(drawEl.dataset.lng)], 18);
                    setMapMessage('Бошланғич жойлашув кўрсатилди');
                }
            });
            document.getElementById('clearDraw')?.addEventListener('click', () => {
                if (polygonDrawer.enabled()) polygonDrawer.disable();
                drawn.clearLayers();
                if (locationMarker) { locationMarker.remove(); locationMarker = null; }
                syncGeometry();
                setDrawingState(false);
            });
            const existing = document.getElementById('geoArea').value;
            if (existing) try { const layer=L.geoJSON({type:'Feature',geometry:JSON.parse(existing)},{style:{color:'#0f7b7b',weight:3,fillOpacity:.28}}); layer.eachLayer(item=>drawn.addLayer(item)); map.fitBounds(drawn.getBounds(),{maxZoom:19}); startButton?.classList.remove('draw-attention'); } catch (_) {}
        }

        const viewEl = document.getElementById('surveyMap');
        if (viewEl && window.L) {
            const map = L.map(viewEl, { scrollWheelZoom:false }).setView([parseFloat(viewEl.dataset.lat), parseFloat(viewEl.dataset.lng)], 18);
            addBaseControl(map, baseLayers());
            if (viewEl.dataset.geo && viewEl.dataset.geo !== 'null') try { const geometry=JSON.parse(viewEl.dataset.geo); const layer=L.geoJSON({type:'Feature',geometry},{style:{color:'#00a39b',weight:3,fillOpacity:.3}}).addTo(map); map.fitBounds(layer.getBounds(),{maxZoom:19}); } catch (_) {}
        }

        const lengthInput = document.getElementById('lengthInp');
        const widthInput = document.getElementById('widthInp');
        const totalArea = document.getElementById('totalArea');
        const calculateArea = () => { const length=parseFloat(lengthInput?.value); const width=parseFloat(widthInput?.value); if (totalArea) totalArea.value = length>0 && width>0 ? Math.round(length*width*100)/100 : ''; };
        lengthInput?.addEventListener('input', calculateArea); widthInput?.addEventListener('input', calculateArea);

        const photoInput = document.getElementById('photoInput');
        const photoPicker = document.getElementById('photoPicker');
        const photoSlots = document.getElementById('photoSlots');
        const photoHint = document.getElementById('photoHint');
        const docInput = document.getElementById('docInput');
        const photoStore = new DataTransfer();
        const docStore = new DataTransfer();
        const MIN_PHOTOS=4, MAX_PHOTOS=10, MAX_IMAGE=5*1024*1024, MAX_DOC=10*1024*1024, MAX_TOTAL=120*1024*1024;
        const hasExistingPhotos = photoSlots?.dataset.hasExisting === '1';
        const guard = document.getElementById('uploadGuard');
        const showGuard = message => { if (!guard) return; guard.textContent=message; guard.hidden=!message; };
        const clearStore = store => { while (store.items.length) store.items.remove(0); };
        const totalBytes = () => [...photoStore.files,...docStore.files].reduce((sum,file)=>sum+file.size,0);
        const formatSize = bytes => (bytes/1024/1024).toFixed(1)+' МБ';

        const optimizeImage = file => new Promise(resolve => {
            if (!file.type.startsWith('image/')) return resolve(file);
            const image = new Image(); const url=URL.createObjectURL(file);
            image.onload = () => {
                const scale=Math.min(1,2200/Math.max(image.width,image.height));
                if (scale===1 && file.size<=1.5*1024*1024) { URL.revokeObjectURL(url); return resolve(file); }
                const canvas=document.createElement('canvas'); canvas.width=Math.round(image.width*scale); canvas.height=Math.round(image.height*scale);
                canvas.getContext('2d').drawImage(image,0,0,canvas.width,canvas.height);
                canvas.toBlob(blob => { URL.revokeObjectURL(url); if (!blob) return resolve(file); const optimized=new File([blob],file.name.replace(/\.[^.]+$/,'.jpg'),{type:'image/jpeg',lastModified:Date.now()}); resolve(optimized.size<file.size?optimized:file); },'image/jpeg',.82);
            };
            image.onerror=()=>{URL.revokeObjectURL(url);resolve(file)}; image.src=url;
        });

        const renderPhotos = () => {
            if (!photoSlots) return; const count=photoStore.files.length; const slots=count>=MAX_PHOTOS?MAX_PHOTOS:Math.max(MIN_PHOTOS,count+1); photoSlots.innerHTML='';
            for(let index=0;index<slots;index++) { const cell=document.createElement('div'); cell.className='photo-slot '+(index<count?'filled':'empty');
                if(index<count){cell.style.backgroundImage=`url(${URL.createObjectURL(photoStore.files[index])})`;const remove=document.createElement('button');remove.type='button';remove.className='slot-x';remove.innerHTML='<i class="fa-solid fa-xmark"></i>';remove.onclick=()=>{const keep=[...photoStore.files].filter((_,i)=>i!==index);clearStore(photoStore);keep.forEach(file=>photoStore.items.add(file));renderPhotos()};cell.appendChild(remove)}
                else {cell.innerHTML=`<span class="slot-plus"><i class="fa-solid fa-plus"></i></span><span class="slot-n">${index+1}-расм</span>`;cell.onclick=()=>photoPicker?.click()}
                photoSlots.appendChild(cell);
            }
            photoInput.files=photoStore.files; photoHint.textContent=count?`${count} та расм · ${formatSize(totalBytes())} умумий`:'Камида 4 та расм юкланг · ҳар бири 5 МБ гача';
        };
        renderPhotos();
        photoPicker?.addEventListener('change', async function(){ const source=this.files?.[0]; this.value=''; if(!source||photoStore.files.length>=MAX_PHOTOS)return; showGuard('Расм оптималлаштирилмоқда...'); const file=await optimizeImage(source); if(file.size>MAX_IMAGE){showGuard(`${source.name} оптималлаштирилгандан кейин ҳам 5 МБ дан катта.`);return} if(totalBytes()+file.size>MAX_TOTAL){showGuard('Умумий юклама 120 МБ дан ошмаслиги керак.');return} if(![...photoStore.files].some(item=>item.name===file.name&&item.size===file.size))photoStore.items.add(file); showGuard(source.size>file.size?`Расм ${formatSize(source.size)} дан ${formatSize(file.size)} гача кичрайтирилди.`:''); renderPhotos(); });

        const renderDocs = () => { const list=document.getElementById('docPreview'); if(!list)return; list.innerHTML=''; [...docStore.files].forEach((file,index)=>{const item=document.createElement('li');item.innerHTML=`<span><i class="fa-regular fa-file-lines"></i> ${file.name} · ${formatSize(file.size)}</span>`;const remove=document.createElement('button');remove.type='button';remove.className='doc-x';remove.innerHTML='<i class="fa-solid fa-xmark"></i>';remove.onclick=()=>{const keep=[...docStore.files].filter((_,i)=>i!==index);clearStore(docStore);keep.forEach(entry=>docStore.items.add(entry));renderDocs()};item.appendChild(remove);list.appendChild(item)});docInput.files=docStore.files; };
        docInput?.addEventListener('change', event => { for(const file of event.target.files){if(file.size>MAX_DOC){showGuard(`${file.name} 10 МБ лимитдан катта.`);continue}if(totalBytes()+file.size>MAX_TOTAL){showGuard('Умумий юклама 120 МБ дан ошмаслиги керак.');break}if(![...docStore.files].some(item=>item.name===file.name&&item.size===file.size))docStore.items.add(file)}renderDocs()});

        document.getElementById('surveyForm')?.addEventListener('submit', event => { const count=photoStore.files.length; if(count===0&&hasExistingPhotos)return; if(count<MIN_PHOTOS){event.preventDefault();showGuard(`Камида 4 та расм юкланг. Ҳозир ${count} та.`);window.scrollTo({top:guard.offsetTop-100,behavior:'smooth'})}else if(totalBytes()>MAX_TOTAL){event.preventDefault();showGuard('Умумий юклама 120 МБ дан ошмаслиги керак.')} });
    });
    </script>
@endpush
