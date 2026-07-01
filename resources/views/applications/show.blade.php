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
    $fileCount = count($latestSurvey?->photos ?? [])
        + count($latestSurvey?->documents ?? [])
        + ($latestSurvey?->study_report_path ? 1 : 0);
    $mapLat = old('latitude', $latestSurvey?->latitude ?: 41.311081);
    $mapLng = old('longitude', $latestSurvey?->longitude ?: 69.279737);
@endphp

@section('content')
    <a href="{{ route('applications.index') }}" class="show-back"><i class="fa-solid fa-arrow-left"></i> Аризалар рўйхатига қайтиш</a>
    <div class="application-hero">
        <div>
            <div class="flex items-center gap-12 wrap">
                <span class="application-number">Ариза {{ $application->application_number }}</span>
                <x-badge :color="$application->current_stage->color()" :label="$application->current_stage->label()" />
                <x-badge :color="$application->status->color()" :label="$application->status->label()" />
            </div>
            <div class="application-subtitle">
                {{ $application->object?->company_name }} · {{ $application->district?->name }} · {{ optional($application->created_at)->format('d.m.Y') }}
            </div>
        </div>
        <button type="button" class="btn btn-outline" onclick="window.print()"><i class="fa-solid fa-print"></i> Чоп этиш</button>
    </div>

    <div class="application-facts">
        <div class="fact-card"><span>Ариза рақами</span><strong>{{ $application->application_number }}</strong><i class="fa-regular fa-file-lines"></i></div>
        <div class="fact-card"><span>Майдон</span><strong>{{ $latestSurvey?->total_area ?? $application->adjacentAreas->first()?->area_m2 ?? '—' }} м²</strong><i class="fa-solid fa-vector-square"></i></div>
        <div class="fact-card"><span>Жойлашув</span><strong>{{ $application->district?->name }}</strong><i class="fa-solid fa-location-dot"></i></div>
        <div class="fact-card fact-status"><span>Ҳолати</span><strong>{{ $application->status->label() }}</strong><i class="fa-regular fa-circle-check"></i></div>
        <div class="fact-card"><span>Ариза яратилган сана</span><strong>{{ optional($application->created_at)->format('d.m.Y H:i') }}</strong><i class="fa-regular fa-calendar"></i></div>
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
                                <div class="form-row"><label class="lbl">Фаолият тури <span class="req">*</span></label><select class="inp" name="activity_type" required><option value="">— Танланг —</option>@foreach(\App\Models\ApplicationSurvey::ACTIVITY_TYPES as $type)<option value="{{ $type }}" @selected(old('activity_type', $latestSurvey?->activity_type) === $type)>{{ $type }}</option>@endforeach</select></div>
                                <div class="form-row"><label class="lbl">Терраса иншоотлари</label><input class="inp" name="terrace_structures" value="{{ old('terrace_structures', $latestSurvey?->terrace_structures) }}"></div>
                                <div class="form-row"><label class="lbl">Доимий иншоотлар</label><input class="inp" name="permanent_structures" value="{{ old('permanent_structures', $latestSurvey?->permanent_structures) }}"></div>
                                <div class="form-row"><label class="lbl">Рухсат ҳужжати <span class="req">*</span></label><select class="inp" name="permit" required><option value="">— Танланг —</option>@foreach(\App\Models\ApplicationSurvey::PERMIT_STATUSES as $status)<option value="{{ $status }}" @selected(old('permit', $latestSurvey?->permit) === $status)>{{ $status }}</option>@endforeach</select></div>
                                <div class="form-row mb-0"><label class="lbl">Қўшимча изоҳ</label><textarea class="inp" name="extra_info">{{ old('extra_info', $latestSurvey?->extra_info) }}</textarea></div>
                            </article>
                        </div>

                        <article class="info-card mt-16">
                            <div class="section-title"><span class="section-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span><div><h2>Фото ва ҳужжатлар</h2><p>Расмлар браузерда автоматик оптималлаштирилади</p></div></div>
                            <div class="upload-grid">
                                <div>
                                    <label class="lbl">Объект расмлари — камида 4 та <span class="req">*</span></label>
                                    <div class="help mb-8">1 — Олди, 2 — Орқа, 3 — Чап, 4 — Ўнг томони. Ҳар бир ракурс учун алоҳида расм юкланг.</div>
                                    @if(!empty($latestSurvey?->photos))
                                        <div class="photo-gallery saved-photo-gallery mb-8">
                                            @foreach($latestSurvey->photos as $index => $photo)
                                                <a href="{{ asset($photo) }}" target="_blank" class="photo-thumb" style="background-image:url('{{ asset($photo) }}')">
                                                    <b class="saved-view-label">{{ ['Олди','Орқа','Чап','Ўнг'][$index] ?? ($index + 1).'-расм' }}</b>
                                                    <span><i class="fa-solid fa-up-right-from-square"></i></span>
                                                </a>
                                            @endforeach
                                        </div>
                                        <div class="help mb-8">Сақланган расмлар. Алмаштириш учун пастда 4 та янги расм танланг.</div>
                                    @endif
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
                                    <label class="lbl mt-16">Ўрганиш далолатномаси <span class="req">*</span></label>
                                    <input class="inp" type="file" name="study_report" accept=".pdf,.doc,.docx" @required(!$latestSurvey?->study_report_path)>
                                    @if($latestSurvey?->study_report_path)
                                        <div class="help"><i class="fa-solid fa-check-circle"></i> Далолатнома юкланган. Янги файл танланмаса, мавжуди сақланади.</div>
                                        @include('partials.file-cards', ['files' => [$latestSurvey->study_report_path], 'labels' => ['Ўрганиш далолатномаси']])
                                    @endif
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
                <article class="info-card mt-16">
                    <div class="section-title"><span class="section-icon"><i class="fa-solid fa-file-signature"></i></span><div><h2>Ўрганиш далолатномаси</h2><p>{{ $latestSurvey?->study_report_path ? '1 та файл' : '0 та файл' }}</p></div></div>
                    @if($latestSurvey?->study_report_path)
                        @include('partials.file-cards', ['files' => [$latestSurvey->study_report_path], 'labels' => ['Ўрганиш далолатномаси']])
                    @else
                        <div class="empty compact">Далолатнома ҳали юкланмаган</div>
                    @endif
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

                <div id="historyRailMount"></div>

                @if($application->reviews->isNotEmpty() || $canOptionalReview)
                    <article class="decision-card">
                        <div class="decision-head"><span><i class="fa-solid fa-scale-balanced"></i></span><div><h2>Юрист / комплаенс хулосаси</h2><p>Ихтиёрий, жараённи тўхтатмайди</p></div></div>
                        @foreach($application->reviews as $review)
                            <div class="conclusion mb-8"><strong>{{ $review->reviewer?->displayName() }}</strong> · {{ $review->decision === 'approved' ? 'Тасдиқлади' : 'Рад этишни тавсия қилди' }}@if($review->comment)<div class="tiny muted mt-8">{{ $review->comment }}</div>@endif</div>
                        @endforeach
                        @if($canOptionalReview)
                            <form method="POST" action="{{ route('applications.review', $application) }}">@csrf
                                <textarea class="inp" name="comment" placeholder="Хулоса ёки рад этиш сабаби..."></textarea>
                                <div class="decision-actions"><button class="btn btn-green btn-block" name="decision" value="approved">Тасдиқлаш</button><button class="btn btn-red btn-block" name="decision" value="rejected">Рад этишни тавсия қилиш</button></div>
                            </form>
                        @endif
                    </article>
                @endif

                @if(count($availableActions) > 0)
                    <article class="decision-card">
                        <div class="decision-head"><span><i class="fa-solid fa-bolt"></i></span><div><h2>Қарор</h2><p>Жорий босқич бўйича ҳаракат</p></div></div>
                        <form method="POST" action="{{ route('applications.transition', $application) }}" onsubmit="if(event.submitter?.value==='sign') return confirm('Аризани якуний тасдиқлайсизми?')">@csrf<textarea class="inp" name="comment" placeholder="Изоҳ ёки сабаб...">{{ old('comment') }}</textarea><div class="decision-actions">@foreach($availableActions as $action)<button class="btn {{ $btnClass[$action->color()] ?? 'btn-outline' }} btn-block" type="submit" name="action" value="{{ $action->value }}">{{ $action->buttonLabel() }}</button>@endforeach</div></form>
                    </article>
                @endif

                <article class="rail-note">
                    <h2>Изоҳ</h2>
                    <p>{{ $application->transitions->last()?->comment ?: 'Ариза бўйича қўшимча изоҳ киритилмаган.' }}</p>
                    <i class="fa-solid fa-quote-right"></i>
                </article>
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
        .map-action-btn.draw-attention { position:relative; isolation:isolate; }
        .map-action-btn.draw-attention:not(.active) { animation:drawButtonGlow 1.35s ease-in-out infinite; }
        .map-action-btn.draw-attention:not(.active)::after { content:""; position:absolute; inset:-5px; z-index:-1; border:2px solid rgba(20,184,166,.76); border-radius:13px; animation:drawButtonRing 1.35s ease-out infinite; pointer-events:none; }
        .map-action-btn.draw-attention:not(.active) i { animation:drawIconSpark 1.35s ease-in-out infinite; }
        @keyframes drawButtonGlow { 0%,100%{transform:scale(1);background:#0f7b7b;box-shadow:0 0 0 0 rgba(20,184,166,0),0 3px 8px rgba(15,123,123,.14)} 50%{transform:scale(1.09);background:#13a19d;box-shadow:0 0 10px 3px rgba(45,212,191,.72),0 8px 24px rgba(15,123,123,.48)} }
        @keyframes drawButtonRing { 0%{opacity:.95;transform:scale(.92)} 75%,100%{opacity:0;transform:scale(1.28)} }
        @keyframes drawIconSpark { 0%,100%{transform:rotate(0) scale(1);filter:brightness(1)} 50%{transform:rotate(-8deg) scale(1.22);filter:brightness(1.8) drop-shadow(0 0 4px #fff)} }
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
        .slot-view-label { position:absolute; left:7px; bottom:7px; z-index:2; padding:4px 7px; border-radius:6px; background:rgba(15,23,42,.84); color:#fff; font-size:9px; font-weight:800; letter-spacing:.02em; box-shadow:0 2px 6px rgba(15,23,42,.18); }
        .photo-slot.empty .slot-view-label { position:static; color:var(--teal-dark); background:#fff; box-shadow:none; }
        .slot-x,.doc-x { border:0; cursor:pointer; width:22px; height:22px; display:grid; place-items:center; border-radius:50%; background:#1f2933d9; color:#fff; }
        .slot-x { position:absolute; top:-6px; right:-6px; }
        .slot-plus { font-size:20px; }.slot-n { font-size:10px; }
        .doc-list { list-style:none; padding:0; margin:8px 0 0; display:flex; flex-direction:column; gap:6px; }
        .doc-list li { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:7px 9px; border:1px solid var(--line); border-radius:8px; font-size:12px; }
        .photo-gallery { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; }
        .photo-thumb { min-height:145px; border-radius:11px; background-size:cover; background-position:center; position:relative; overflow:hidden; }
        .photo-thumb span { position:absolute; right:8px; bottom:8px; width:29px; height:29px; border-radius:8px; background:#0f172acc; color:#fff; display:grid; place-items:center; }
        .saved-view-label { position:absolute; left:8px; bottom:8px; padding:5px 8px; border-radius:6px; color:#fff; background:rgba(0,100,102,.9); font-size:10px; }
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

        /* Single-page application record — reference palette and rhythm */
        :root { --teal:#172033; --teal-dark:#101827; --teal-light:#f1f4f7; --line:#e3e7ec; --shadow:0 1px 3px rgba(15,23,42,.035); }
        .content { width:auto; max-width:none; margin:0; padding-top:0; }
        .show-back { display:inline-flex; align-items:center; gap:12px; min-height:58px; color:#273244; font-size:12px; font-weight:700; text-decoration:none; }
        .show-back:hover { color:#0f172a; }
        .application-hero { margin:0 -8px 14px; padding:14px 8px 2px; border-top:1px solid var(--line); }
        .application-number { color:#111827; font-size:26px; }
        .application-subtitle { color:#536071; }
        .application-facts { display:grid; grid-template-columns:1.05fr 1fr 1.05fr .9fr 1.25fr; gap:12px; margin:18px 0 14px; }
        .fact-card { position:relative; min-width:0; min-height:74px; padding:15px 50px 13px 16px; background:#fff; border:1px solid var(--line); border-radius:9px; box-shadow:var(--shadow); }
        .fact-card span { display:block; margin-bottom:8px; color:#647184; font-size:10px; }
        .fact-card strong { display:block; overflow:hidden; color:#151d2d; font-size:13px; text-overflow:ellipsis; white-space:nowrap; }
        .fact-card>i { position:absolute; top:17px; right:14px; width:34px; height:34px; display:grid; place-items:center; color:#172033; background:#f5f7f9; border-radius:10px; }
        .fact-status strong,.fact-status>i { color:#159a61; }
        .stage-shell { display:none; }
        .application-workspace { grid-template-columns:minmax(0,1fr) 360px; gap:14px; }
        .application-main { display:flex; flex-direction:column; }
        .tab-panel[data-panel="overview"] { display:contents!important; }
        .tab-panel[data-panel="overview"]>.info-grid { order:1; }
        .tab-panel[data-panel="overview"]>.info-card { order:3; margin-top:0; margin-bottom:14px; }
        .tab-panel[data-panel="survey"] { order:2; }
        .tab-panel[data-panel="files"] { order:4; }
        .tab-panel,.tab-panel.active { display:block; animation:none; margin-bottom:14px; }
        .tab-panel + .tab-panel { padding-top:0; }
        .info-grid { gap:14px; }
        .info-card,.decision-card { border-radius:9px; box-shadow:var(--shadow); }
        .info-card { padding:17px; }
        .section-icon { color:#172033; background:#f4f6f8; border-radius:9px; }
        .metric.accent,.survey-summary .primary,.total-area { color:#151d2d!important; background:#f4f6f8!important; border-color:#e5e9ee!important; }
        .panel-heading { margin:18px 2px 10px; }
        .contract-card { border-color:var(--line); border-radius:9px; box-shadow:var(--shadow); }
        .contract-head { color:#172033; background:#fff; border-bottom:1px solid var(--line); }
        .contract-head span { color:#6b7687; }
        .contract-document-icon { color:#172033; background:#f4f6f8; box-shadow:inset 0 0 0 1px #e1e6eb; }
        .contract-primary,.btn-teal { color:#fff; background:#172033; border-color:#172033; }
        .contract-primary:hover,.btn-teal:hover { background:#0c1322; border-color:#0c1322; }
        .sticky-stack { top:72px; }
        .tl-dot.teal { background:#159a61; }
        /* Dense 12-column dashboard */
        .application-hero { margin-bottom:6px; padding-top:8px; }
        .application-facts { gap:8px; margin:9px 0 10px; }
        .fact-card { min-height:58px; padding:9px 42px 8px 11px; }
        .fact-card span { margin-bottom:4px; font-size:9px; }
        .fact-card strong { font-size:11px; }
        .fact-card>i { top:11px; right:9px; width:29px; height:29px; border-radius:8px; }
        .application-workspace { grid-template-columns:minmax(0,1fr) 310px; gap:10px; }
        .application-main { display:grid; grid-template-columns:repeat(12,minmax(0,1fr)); gap:10px; align-items:start; }
        .tab-panel[data-panel="overview"]>.info-grid { grid-column:span 8; grid-template-columns:1fr 1fr; gap:8px; }
        .tab-panel[data-panel="overview"]>.info-card { grid-column:span 6; margin:0; }
        .tab-panel[data-panel="survey"] { grid-column:span 4; margin:0; }
        .tab-panel[data-panel="survey"]:has(form) { grid-column:1/-1; }
        .tab-panel[data-panel="files"] { grid-column:1/-1; margin:0; }
        .tab-panel,.tab-panel.active { margin-bottom:0; }
        .info-card { padding:11px; }
        .section-title { gap:7px; margin-bottom:7px; }
        .section-title p,.panel-heading p { display:none; }
        .section-title h2,.panel-heading h2 { font-size:12px; }
        .section-icon { width:28px; height:28px; border-radius:7px; }
        .detail-list>div { grid-template-columns:105px minmax(0,1fr); gap:8px; padding:5px 0; }
        .detail-list dt { font-size:9px; }
        .detail-list dd { font-size:10px; }
        .panel-heading { margin:0 2px 5px; }
        .metric-row,.survey-summary { gap:6px; }
        .metric,.survey-summary>div { padding:7px 9px; }
        .metric span,.survey-summary span { margin-bottom:2px; font-size:8px; }
        .metric strong,.survey-summary strong { font-size:10px; }
        .map-toolbar { padding:7px 9px; }
        .map-toolbar span { display:none; }
        .map-edit { height:180px; }
        .contract-head { padding:10px 12px; }
        .contract-intro { padding:14px 12px 12px; }
        .contract-document-icon { width:38px; height:38px; margin-bottom:7px; border-radius:10px; font-size:17px; }
        .contract-intro h3 { margin-bottom:3px; font-size:12px; }
        .contract-intro p { display:none; }
        .contract-meta { margin-top:8px; }
        .contract-actions { gap:5px; padding:7px; }
        .contract-primary { min-height:34px; font-size:10px; }
        .sticky-stack { gap:9px; }
        .rail-note { min-height:82px; padding:10px; }
        .rail-note h2 { margin-bottom:6px; font-size:12px; }
        .rail-note p { padding:8px; font-size:9px; }
        /* Final reference composition */
        .application-workspace { grid-template-columns:minmax(0,2fr) 360px; gap:14px; }
        .application-main { display:flex; flex-direction:column; align-items:stretch; gap:0; width:100%; }
        .application-main>.tab-panel,
        .tab-panel[data-panel="overview"]>.info-grid,
        .tab-panel[data-panel="overview"]>.info-card { width:100%; min-width:0; }
        .tab-panel[data-panel="overview"]>.info-grid { order:1; display:grid; grid-template-columns:5fr 7fr; gap:14px; }
        .tab-panel[data-panel="survey"] { order:2; display:grid!important; grid-template-columns:minmax(0,3fr) minmax(150px,1fr); gap:12px; margin:14px 0; }
        .tab-panel[data-panel="survey"]>.panel-heading { grid-column:1/-1; }
        .tab-panel[data-panel="survey"]>.map-card { grid-column:1; }
        .tab-panel[data-panel="survey"]>.survey-summary { grid-column:2; display:flex; flex-direction:column; gap:0; margin:0; border-left:1px solid var(--line); }
        .tab-panel[data-panel="survey"]>.survey-summary>div { flex:1; padding:8px 12px; background:#fff!important; border:0; border-bottom:1px solid var(--line); border-radius:0; }
        .tab-panel[data-panel="survey"]>.survey-summary>div:last-child { border-bottom:0; }
        .tab-panel[data-panel="survey"]:has(form) { display:block!important; }
        .tab-panel[data-panel="overview"]>.info-card { order:3; margin:0 0 14px; }
        .tab-panel[data-panel="overview"]>.info-card:first-of-type { display:none; }
        .tab-panel[data-panel="files"] { order:4; }
        .info-card { padding:16px; }
        .section-title { margin-bottom:12px; }
        .section-title p { display:block; }
        .section-title h2,.panel-heading h2 { font-size:14px; }
        .section-icon { width:34px; height:34px; }
        .detail-list>div { grid-template-columns:125px minmax(0,1fr); padding:7px 0; }
        .detail-list dt { font-size:11px; }
        .detail-list dd { font-size:12px; }
        .map-toolbar { padding:10px 12px; }
        .map-edit { height:245px; }
        .survey-summary span { font-size:9px; }
        .survey-summary strong { font-size:11px; }
        .tab-panel[data-panel="overview"]>.info-card .survey-summary { grid-template-columns:repeat(6,1fr); }
        .tab-panel[data-panel="overview"]>.info-card .survey-summary>div { padding:10px; }
        .photo-thumb { min-height:105px; }
        .contract-intro { padding:18px 15px 15px; }
        .contract-intro p { display:block; font-size:10px; }
        .contract-document-icon { width:48px; height:48px; font-size:20px; }
        .contract-primary { min-height:38px; font-size:11px; }
        @media(max-width:1150px){
            .application-workspace{grid-template-columns:1fr}
            .application-rail{grid-row:auto}.sticky-stack{position:static}
        }
        @media(max-width:760px){
            .tab-panel[data-panel="overview"]>.info-grid{grid-template-columns:1fr}
            .tab-panel[data-panel="survey"]{grid-template-columns:1fr}
            .tab-panel[data-panel="survey"]>.map-card,.tab-panel[data-panel="survey"]>.survey-summary{grid-column:1}
            .tab-panel[data-panel="survey"]>.survey-summary{display:grid;grid-template-columns:repeat(2,1fr);border-left:0}
            .tab-panel[data-panel="overview"]>.info-card .survey-summary{grid-template-columns:repeat(2,1fr)}
        }
        /* Dashboard teal palette */
        :root {
            --teal:#128889;
            --teal-dark:#006466;
            --teal-light:#e3f2f2;
        }
        .show-back:hover,.section-icon,.application-kicker { color:var(--teal-dark); }
        .section-icon,.fact-card>i { color:var(--teal-dark); background:var(--teal-light); }
        .contract-head>i { color:var(--teal-dark); }
        .contract-document-icon { color:var(--teal-dark); background:var(--teal-light); box-shadow:inset 0 0 0 1px #c5e3e3; }
        .contract-primary,.btn-teal { color:#fff; background:var(--teal); border-color:var(--teal); }
        .contract-primary:hover,.btn-teal:hover { background:var(--teal-dark); border-color:var(--teal-dark); }
        .map-action-btn.primary { background:var(--teal); border-color:var(--teal); }
        .map-action-btn:hover:not(:disabled) { color:var(--teal-dark); border-color:var(--teal); }
        .metric.accent,.survey-summary .primary,.total-area { color:var(--teal-dark)!important; background:var(--teal-light)!important; border-color:#c5e3e3!important; }
        .file-card:hover { border-color:var(--teal); }
        #historyRailMount .tl-dot.teal,.tl-dot.teal { background:var(--teal); }
        #historyRailMount>.tab-panel { margin:0; }
        #historyRailMount .panel-heading { margin:0; padding:15px 16px 10px; background:#fff; border:1px solid var(--line); border-bottom:0; border-radius:9px 9px 0 0; }
        #historyRailMount .panel-heading p { display:none; }
        #historyRailMount .info-card { padding:13px 14px; border-radius:0 0 9px 9px; }
        #historyRailMount .timeline { padding-left:25px; }
        #historyRailMount .timeline:before { left:9px; }
        #historyRailMount .tl-item { padding-bottom:16px; }
        #historyRailMount .tl-dot { left:-25px; width:20px; height:20px; font-size:8px; }
        #historyRailMount .tl-time { position:absolute; top:2px; right:0; font-size:9px; }
        #historyRailMount .tl-card { padding:0 82px 0 0; background:transparent; border:0; }
        #historyRailMount .tl-title { font-size:11px; }
        #historyRailMount .tl-meta { font-size:9px; }
        #historyRailMount .tl-comment { margin-top:4px; font-size:9px; }
        .rail-note { position:relative; min-height:112px; padding:15px 16px; overflow:hidden; background:#fff; border:1px solid var(--line); border-radius:9px; box-shadow:var(--shadow); }
        .rail-note h2 { margin:0 0 13px; font-size:14px; }
        .rail-note p { position:relative; z-index:1; margin:0; padding:14px; color:#4c5869; background:#fafbfc; border-radius:8px; font-size:11px; line-height:1.55; }
        .rail-note>i { position:absolute; right:20px; bottom:12px; color:#e1e6eb; font-size:38px; }
        @media(max-width:1200px){.application-facts{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:1250px){.application-workspace{grid-template-columns:minmax(0,1fr) 330px}.upload-grid{grid-template-columns:1fr}}
        @media(max-width:980px){.application-workspace{grid-template-columns:1fr}.sticky-stack{position:static}.info-grid{grid-template-columns:1fr}}
        @media(max-width:620px){.application-facts{grid-template-columns:1fr 1fr}.application-hero{align-items:flex-start}.application-hero>.btn{display:none}.metric-row,.survey-summary{grid-template-columns:1fr 1fr}.photo-slots,.photo-gallery{grid-template-columns:repeat(2,1fr)}.map-edit{height:360px}.detail-list>div{grid-template-columns:1fr}.detail-list dd{text-align:left}.panel-heading{align-items:flex-start}.panel-heading>.btn{padding:8px 10px}.modal-overlay{padding:0}.modal-panel-wide{width:100vw;height:100dvh;max-height:none;border-radius:0}.modal-head p{display:none}.modal-head-actions .btn{display:none}}
    </style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const historyPanel = document.querySelector('.tab-panel[data-panel="history"]');
        const historyRailMount = document.getElementById('historyRailMount');
        if (historyPanel && historyRailMount) historyRailMount.appendChild(historyPanel);
        const tabs = [...document.querySelectorAll('.detail-tab')];
        const panels = [...document.querySelectorAll('.tab-panel')];
        const maps = [];
        const mapObservers = [];
        const focusSavedMap = @json((bool) session('focus_survey_map', false));
        let refreshSequence = 0;
        const refreshMaps = (focusContent = false) => {
            const sequence = ++refreshSequence;
            [0, 80, 240, 520].forEach(delay => setTimeout(() => {
                if (sequence !== refreshSequence) return;
                maps.forEach(map => {
                    const container = map.getContainer();
                    if (!container.offsetWidth || !container.offsetHeight) return;
                    map.invalidateSize({pan:false, debounceMoveend:true});
                    map.eachLayer(layer => { if (typeof layer.redraw === 'function') layer.redraw(); });
                    if (focusContent && typeof map.__focusContent === 'function') map.__focusContent();
                });
            }, delay));
        };
        const activateTab = name => {
            tabs.forEach(tab => tab.classList.toggle('active', tab.dataset.tab === name));
            panels.forEach(panel => panel.classList.toggle('active', panel.dataset.panel === name));
            history.replaceState(null, '', '#' + name);
            refreshMaps(name === 'survey');
        };
        tabs.forEach(tab => tab.addEventListener('click', () => activateTab(tab.dataset.tab)));
        document.querySelectorAll('[data-open-tab]').forEach(button => button.addEventListener('click', () => activateTab(button.dataset.openTab)));
        const initialTab = @json($errors->any() ? 'survey' : null) || location.hash.replace('#', '');
        if (['overview', 'survey', 'files', 'history'].includes(initialTab)) activateTab(initialTab);
        window.addEventListener('hashchange', () => {
            const tab = location.hash.replace('#', '');
            if (['overview', 'survey', 'files', 'history'].includes(tab)) activateTab(tab);
        });
        window.addEventListener('pageshow', () => {
            const tab = location.hash.replace('#', '');
            if (['overview', 'survey', 'files', 'history'].includes(tab)) activateTab(tab);
            else refreshMaps(false);
        });
        window.addEventListener('load', () => refreshMaps(location.hash === '#survey'));
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) refreshMaps(location.hash === '#survey');
        });

        window.openDraft = () => { const modal = document.getElementById('draftModal'); const frame = document.getElementById('draftFrame'); if (!modal) return; if (frame && !frame.src) frame.src = @json(route('applications.contract-draft', $application)); modal.hidden = false; document.body.style.overflow = 'hidden'; };
        window.closeDraft = () => { const modal = document.getElementById('draftModal'); if (modal) modal.hidden = true; document.body.style.overflow = ''; };
        document.addEventListener('keydown', event => { if (event.key === 'Escape') window.closeDraft(); });

        const baseLayers = () => {
            const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 20, attribution: '© OpenStreetMap' });
            const googleOptions = {
                maxZoom: 21,
                subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                attribution: '© Google'
            };
            const satellite = L.tileLayer('https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', googleOptions);
            const hybrid = L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', googleOptions);
            return { 'Hybrid': hybrid, 'Satellite': satellite, 'OpenStreetMap': osm };
        };

        const addBaseControl = (map, layers) => {
            layers.Hybrid.addTo(map);
            L.control.layers(layers, null, { position:'topright' }).addTo(map);
            maps.push(map);
            if (window.ResizeObserver) {
                const observer = new ResizeObserver(entries => {
                    const box = entries[0]?.contentRect;
                    if (box?.width > 0 && box?.height > 0) refreshMaps(location.hash === '#survey');
                });
                observer.observe(map.getContainer());
                mapObservers.push(observer);
            }
        };
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
            if (existing) try {
                const layer=L.geoJSON({type:'Feature',geometry:JSON.parse(existing)},{style:{color:'#0f7b7b',weight:3,fillOpacity:.28}});
                layer.eachLayer(item=>drawn.addLayer(item));
                const focusPolygon = () => {
                    map.invalidateSize();
                    map.fitBounds(drawn.getBounds(), {padding:[65,65],maxZoom:20,animate:true,duration:.65});
                    drawn.bringToFront();
                    if (focusSavedMap) setMapMessage('Сақланган майдон яқинлаштириб кўрсатилди ✓');
                };
                map.__focusContent = focusPolygon;
                setTimeout(focusPolygon, focusSavedMap ? 280 : 100);
            } catch (_) {}
        }

        const viewEl = document.getElementById('surveyMap');
        if (viewEl && window.L) {
            const map = L.map(viewEl, { scrollWheelZoom:false }).setView([parseFloat(viewEl.dataset.lat), parseFloat(viewEl.dataset.lng)], 18);
            addBaseControl(map, baseLayers());
            if (viewEl.dataset.geo && viewEl.dataset.geo !== 'null') try { const geometry=JSON.parse(viewEl.dataset.geo); const layer=L.geoJSON({type:'Feature',geometry},{style:{color:'#00a39b',weight:3,fillOpacity:.3}}).addTo(map); map.__focusContent=()=>map.fitBounds(layer.getBounds(),{padding:[65,65],maxZoom:20}); map.__focusContent(); } catch (_) {}
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

        const requiredViews = ['Олди', 'Орқа', 'Чап', 'Ўнг'];
        const renderPhotos = () => {
            if (!photoSlots) return; const count=photoStore.files.length; const slots=count>=MAX_PHOTOS?MAX_PHOTOS:Math.max(MIN_PHOTOS,count+1); photoSlots.innerHTML='';
            for(let index=0;index<slots;index++) { const cell=document.createElement('div'); cell.className='photo-slot '+(index<count?'filled':'empty');
                const viewLabel = requiredViews[index] || `${index+1}-расм`;
                if(index<count){cell.style.backgroundImage=`url(${URL.createObjectURL(photoStore.files[index])})`;const remove=document.createElement('button');remove.type='button';remove.className='slot-x';remove.innerHTML='<i class="fa-solid fa-xmark"></i>';remove.onclick=()=>{const keep=[...photoStore.files].filter((_,i)=>i!==index);clearStore(photoStore);keep.forEach(file=>photoStore.items.add(file));renderPhotos()};cell.appendChild(remove);cell.insertAdjacentHTML('beforeend',`<span class="slot-view-label">${viewLabel}</span>`)}
                else {cell.innerHTML=`<span class="slot-plus"><i class="fa-solid fa-plus"></i></span><span class="slot-view-label">${viewLabel}</span>`;cell.onclick=()=>photoPicker?.click()}
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
