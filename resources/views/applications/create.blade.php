@extends('layouts.app')
@section('title', 'Ариза яратиш')
@section('heading', 'Янги ариза — Туташ ҳудуд')

@section('content')
    @php $hasObjects = $objects->isNotEmpty(); $mode = old('object_mode', $hasObjects ? 'existing' : 'new'); @endphp
    <div class="card" style="max-width:820px">
        <div class="card-head"><h2>Туташ (қўшни) ҳудуддан фойдаланиш аризаси</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ route('applications.store') }}">
                @csrf

                {{-- Объект манбаси: мавжуд ёки янги --}}
                <div class="form-row">
                    <label class="lbl">Объект манбаси</label>
                    <div class="seg">
                        <label class="seg-opt {{ !$hasObjects ? 'is-disabled' : '' }}">
                            <input type="radio" name="object_mode" value="existing" @checked($mode === 'existing') @disabled(!$hasObjects)>
                            <span>Мавжуд объект</span>
                        </label>
                        <label class="seg-opt">
                            <input type="radio" name="object_mode" value="new" @checked($mode === 'new')>
                            <span>Янги объект киритиш</span>
                        </label>
                    </div>
                    @error('object_mode')<div class="err">{{ $message }}</div>@enderror
                </div>

                {{-- Мавжуд объект --}}
                <div id="existing-block" class="obj-block">
                    <div class="form-row">
                        <label class="lbl">Объект <span class="req">*</span></label>
                        <select class="inp @error('object_id') input-err @enderror" name="object_id" id="object_id">
                            <option value="">— Объектни танланг —</option>
                            @foreach($objects as $o)
                                <option value="{{ $o->id }}" @selected(old('object_id') == $o->id)>
                                    {{ $o->company_name }} — {{ $o->cadastre_number }} ({{ $o->district?->name }})
                                </option>
                            @endforeach
                        </select>
                        @error('object_id')<div class="err">{{ $message }}</div>@enderror
                        @unless($hasObjects)
                            <div class="tiny muted mt-8">Сизда рўйхатдан ўтган объект йўқ — «Янги объект киритиш»дан фойдаланинг.</div>
                        @endunless
                    </div>
                </div>

                {{-- Янги объект --}}
                <div id="new-block" class="obj-block">
                    <div class="form-grid">
                        <div class="form-row">
                            <label class="lbl">Объект кадастр рақами <span class="req">*</span></label>
                            <input class="inp @error('cadastre_number') input-err @enderror" type="text" name="cadastre_number" id="cadastre_number" value="{{ old('cadastre_number') }}" placeholder="10:11:01:02:02:0114" maxlength="19" inputmode="numeric">
                            @error('cadastre_number')<div class="err">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-row">
                            <label class="lbl">Фирма номи <span class="req">*</span></label>
                            <input class="inp @error('company_name') input-err @enderror" type="text" name="company_name" value="{{ old('company_name') }}" placeholder="«SARDOR» МЧЖ">
                            @error('company_name')<div class="err">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-row">
                            <label class="lbl">Шаҳар <span class="req">*</span></label>
                            <select class="inp @error('region_id') input-err @enderror" name="region_id" id="geo-region">
                                @if($regions->count() > 1)<option value="">— Танланг —</option>@endif
                                @foreach($regions as $r)
                                    <option value="{{ $r->id }}" @selected(old('region_id', $regions->count() === 1 ? $r->id : null) == $r->id)>{{ $r->name }}</option>
                                @endforeach
                            </select>
                            @error('region_id')<div class="err">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-row">
                            <label class="lbl">Туман <span class="req">*</span></label>
                            <select class="inp @error('district_id') input-err @enderror" name="district_id" id="geo-district">
                                <option value="">— Аввал шаҳарни танланг —</option>
                            </select>
                            @error('district_id')<div class="err">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-row">
                            <label class="lbl">Маҳалла</label>
                            <select class="inp" name="mahalla_id" id="geo-mahalla">
                                <option value="">— Аввал туманни танланг —</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <label class="lbl">Кўча <span class="req">*</span></label>
                            <input class="inp @error('street') input-err @enderror" type="text" name="street" id="geo-street" value="{{ old('street') }}" placeholder="Кўча номини ёзинг">
                            @error('street')<div class="err">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-row">
                            <label class="lbl">Уй рақами</label>
                            <input class="inp" type="text" name="house_number" value="{{ old('house_number') }}" placeholder="55">
                        </div>
                        <div class="form-row">
                            <label class="lbl">СТИР (ихтиёрий)</label>
                            <input class="inp" type="text" name="tin_pinfl" value="{{ old('tin_pinfl') }}" placeholder="Фирма СТИР рақами" maxlength="20" inputmode="numeric">
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

                {{-- Ариза маълумотлари --}}
                <div class="form-grid">
                    <div class="form-row">
                        <label class="lbl">Фаолият тури</label>
                        <input class="inp" type="text" name="activity" value="{{ old('activity') }}" placeholder="Масалан: Савдо / Дўкон">
                    </div>
                    <div class="form-row">
                        <label class="lbl">Туташ ҳудуд майдони (м²) <span class="req">*</span></label>
                        <input class="inp @error('area_m2') input-err @enderror" type="number" step="0.01" name="area_m2" value="{{ old('area_m2') }}" placeholder="44" required>
                        @error('area_m2')<div class="err">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-row">
                    <label class="lbl">Иншоотлар / тузилмалар</label>
                    <input class="inp" type="text" name="structures" value="{{ old('structures') }}" placeholder="Масалан: Терраса, вақтинчалик навес">
                </div>

                <div class="form-row">
                    <label class="lbl">Изоҳ</label>
                    <textarea class="inp" name="comment" placeholder="Қўшимча маълумот...">{{ old('comment') }}</textarea>
                </div>

                <div class="form-row flex items-center gap-8">
                    <input type="checkbox" name="submit_now" id="submit_now" value="1" {{ old('submit_now') ? 'checked' : '' }}>
                    <label for="submit_now" style="margin:0">Дарҳол модерацияга топшириш (акс ҳолда лойиҳа сифатида сақланади)</label>
                </div>

                <div class="flex gap-8 mt-16">
                    <button class="btn btn-teal" type="submit">Сақлаш</button>
                    <a href="{{ route('applications.index') }}" class="btn btn-outline">Бекор қилиш</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .seg { display: inline-flex; gap: 6px; background: #f1f5f9; padding: 4px; border-radius: 10px; }
        .seg-opt { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 7px; cursor: pointer; font-size: 14px; }
        .seg-opt input { accent-color: var(--teal); }
        .seg-opt:has(input:checked) { background: #fff; box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,.08)); font-weight: 600; }
        .seg-opt.is-disabled { opacity: .5; cursor: not-allowed; }
        .obj-block { margin-top: 6px; }
    </style>
@endpush

@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Объект манбаси: мавжуд / янги — блокларни кўрсатиш/яшириш ---
        const existingBlock = document.getElementById('existing-block');
        const newBlock = document.getElementById('new-block');
        const objectSel = document.getElementById('object_id');
        // Янги объект мажбурий майдонлари.
        const newRequired = ['cadastre_number', 'company_name', 'geo-region', 'geo-district', 'geo-street']
            .map(id => document.getElementById(id)).filter(Boolean);
        const newAll = Array.from(newBlock.querySelectorAll('input, select'));

        function setMode(mode) {
            const isNew = mode === 'new';
            existingBlock.style.display = isNew ? 'none' : '';
            newBlock.style.display = isNew ? '' : 'none';

            // Яширин майдонлар submitга кетмаслиги ва required бўлмаслиги учун.
            if (objectSel) { objectSel.disabled = isNew; objectSel.required = !isNew; }
            newAll.forEach(el => { el.disabled = !isNew; });
            newRequired.forEach(el => { el.required = isNew; });
        }

        document.querySelectorAll('input[name="object_mode"]').forEach(r => {
            r.addEventListener('change', () => setMode(r.value));
        });
        const checked = document.querySelector('input[name="object_mode"]:checked');
        setMode(checked ? checked.value : 'existing');

        // --- Каскад: Шаҳар -> Туман -> Маҳалла (AJAX) ---
        const OLD = { district: @json(old('district_id')), mahalla: @json(old('mahalla_id')) };
        const URL_DISTRICTS = id => "{{ route('geo.districts', ['region' => 'RID'], false) }}".replace('RID', id);
        const URL_MAHALLAS = id => "{{ route('geo.mahallas', ['district' => 'DID'], false) }}".replace('DID', id);

        const regionSel = document.getElementById('geo-region');
        const districtSel = document.getElementById('geo-district');
        const mahallaSel = document.getElementById('geo-mahalla');

        function fill(select, items, placeholder, selected) {
            select.innerHTML = '<option value="">' + placeholder + '</option>';
            items.forEach(it => {
                const o = document.createElement('option');
                o.value = it.id; o.textContent = it.name;
                if (selected && String(selected) === String(it.id)) o.selected = true;
                select.appendChild(o);
            });
        }
        async function fetchJson(url) {
            try { const r = await fetch(url, { headers: { 'Accept': 'application/json' } }); return r.ok ? await r.json() : []; }
            catch (e) { return []; }
        }
        async function loadDistricts(selDistrict, selMahalla) {
            if (!regionSel.value) { fill(districtSel, [], '— Аввал шаҳарни танланг —'); fill(mahallaSel, [], '— Аввал туманни танланг —'); return; }
            districtSel.innerHTML = '<option value="">Юкланмоқда...</option>';
            fill(districtSel, await fetchJson(URL_DISTRICTS(regionSel.value)), '— Туманни танланг —', selDistrict);
            await loadMahallas(selMahalla);
        }
        async function loadMahallas(selMahalla) {
            if (!districtSel.value) { fill(mahallaSel, [], '— Аввал туманни танланг —'); return; }
            mahallaSel.innerHTML = '<option value="">Юкланмоқда...</option>';
            fill(mahallaSel, await fetchJson(URL_MAHALLAS(districtSel.value)), '— Маҳаллани танланг —', selMahalla);
        }
        regionSel.addEventListener('change', () => loadDistricts(null, null));
        districtSel.addEventListener('change', () => loadMahallas(null));
        if (regionSel.value) loadDistricts(OLD.district, OLD.mahalla);

        // --- Input масклар: кадастр, телефон ---
        const cadEl = document.getElementById('cadastre_number');
        if (cadEl) cadEl.addEventListener('input', () => {
            const digits = cadEl.value.replace(/\D/g, '').slice(0, 14);
            const groups = [2, 2, 2, 2, 2, 4]; let out = '', i = 0;
            for (const g of groups) { if (i >= digits.length) break; out += (out ? ':' : '') + digits.slice(i, i + g); i += g; }
            cadEl.value = out;
        });
    });
    </script>
@endpush
