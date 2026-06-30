<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tutash Hududlar Reestri — тадбиркорлар учун</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body { background: #fff; }
        .lp-nav { position: sticky; top: 0; z-index: 30; background: #fff; border-bottom: 1px solid var(--line); }
        .lp-container { max-width: 1080px; margin: 0 auto; padding: 0 20px; }
        .lp-nav-inner { display: flex; align-items: center; justify-content: space-between; height: 64px; }
        .lp-brand { display: flex; align-items: center; gap: 10px; }
        .lp-brand .logo { width: 38px; height: 38px; border-radius: 9px; background: var(--teal); color: #fff; display: grid; place-items: center; font-weight: 800; font-size: 20px; }
        .lp-brand b { color: var(--teal-dark); font-size: 16px; }
        .lp-hero { background: linear-gradient(135deg, #0f7b7b 0%, #0a5f5f 100%); color: #fff; padding: 76px 0 90px; }
        .lp-hero h1 { font-size: 40px; margin: 0 0 16px; font-weight: 800; line-height: 1.12; }
        .lp-hero p { font-size: 18px; opacity: .92; max-width: 640px; margin: 0 0 28px; }
        .lp-hero .btn-row { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn-white { background: #fff; color: var(--teal-dark); }
        .btn-ghost { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.4); }
        .lp-section { padding: 64px 0; }
        .lp-section h2 { font-size: 28px; text-align: center; margin: 0 0 8px; }
        .lp-section .sub { text-align: center; color: var(--muted); margin: 0 auto 40px; max-width: 560px; }
        .steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
        .step-card { text-align: center; padding: 26px 18px; border: 1px solid var(--line); border-radius: var(--radius); background: #fff; box-shadow: var(--shadow); }
        .step-card .n { width: 46px; height: 46px; border-radius: 50%; background: var(--teal-light); color: var(--teal-dark); font-weight: 800; font-size: 20px; display: grid; place-items: center; margin: 0 auto 14px; }
        .step-card h3 { margin: 0 0 6px; font-size: 16px; }
        .step-card p { margin: 0; color: var(--muted); font-size: 13px; }
        .feat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
        .feat { padding: 24px; border: 1px solid var(--line); border-radius: var(--radius); background: #fff; }
        .feat .ic { font-size: 26px; margin-bottom: 10px; }
        .feat h3 { margin: 0 0 6px; font-size: 17px; }
        .feat p { margin: 0; color: var(--muted); font-size: 14px; }
        .form-section { background: var(--teal-light); }
        .form-box { max-width: 720px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: var(--shadow-lg); padding: 36px; }
        .lp-footer { background: #0a5f5f; color: #cfe8e6; padding: 30px 0; text-align: center; font-size: 14px; }
        @media (max-width: 860px) { .steps, .feat-grid { grid-template-columns: 1fr; } .lp-hero h1 { font-size: 30px; } }
    </style>
</head>
<body>
    <nav class="lp-nav">
        <div class="lp-container lp-nav-inner">
            <div class="lp-brand">
                <div class="logo">Т</div>
                <b>TUTASH HUDUDLAR REESTRI</b>
            </div>
            <div class="flex gap-8">
                <a href="#ariza" class="btn btn-outline btn-sm">Ариза топшириш</a>
                <a href="{{ route('login') }}" class="btn btn-teal btn-sm">Тизимга кириш</a>
            </div>
        </div>
    </nav>

    <header class="lp-hero">
        <div class="lp-container">
            <h1>Туташ ҳудуддан фойдаланиш — <br>энди онлайн ва шаффоф</h1>
            <p>Тижорат объекти эгалари ўз объектига туташ (қўшни) ҳудуддан фойдаланиш учун ариза топширади, ариза босқичма-босқич кўриб чиқилади ва тасдиқлангач — шартнома ҳамда тўлов графиги автоматик шакллантирилади.</p>
            <div class="btn-row">
                <a href="#ariza" class="btn btn-white">Ариза топшириш →</a>
                <a href="#qadamlar" class="btn btn-ghost">Қандай ишлайди?</a>
            </div>
        </div>
    </header>

    <section class="lp-section" id="qadamlar">
        <div class="lp-container">
            <h2>Қандай ишлайди</h2>
            <p class="sub">Ариза 4 та асосий босқичдан ўтади — ҳар бирида мас'ул ходим текширади ва тасдиқлайди.</p>
            <div class="steps">
                <div class="step-card"><div class="n">1</div><h3>Ариза</h3><p>Тадбиркор объект ва туташ ҳудуд маълумоти билан ариза топширади.</p></div>
                <div class="step-card"><div class="n">2</div><h3>Текширув</h3><p>Модератор ва мас'ул ходим жойни ўлчайди, ишчи гуруҳ хулоса беради.</p></div>
                <div class="step-card"><div class="n">3</div><h3>Тасдиқ</h3><p>Раҳбарият якуний қарор қабул қилади — тасдиқ ёки рад этиш.</p></div>
                <div class="step-card"><div class="n">4</div><h3>Шартнома</h3><p>Тасдиқлангач шартнома + 12 ойлик тўлов графиги яратилади.</p></div>
            </div>
        </div>
    </section>

    <section class="lp-section" style="background:#f8fafa">
        <div class="lp-container">
            <h2>Афзалликлари</h2>
            <p class="sub">Тадбиркорлар учун қулай, давлат органлари учун шаффоф назорат.</p>
            <div class="feat-grid">
                <div class="feat"><div class="ic"><i class="fa-solid fa-bolt"></i></div><h3>Тезкор</h3><p>Ариза онлайн топширилади, ҳар бир босқич реал вақтда кузатилади.</p></div>
                <div class="feat"><div class="ic"><i class="fa-solid fa-magnifying-glass"></i></div><h3>Шаффоф</h3><p>Ариза кимдан кимга ўтгани, изоҳлар ва ўлчовлар — барчаси тарихда сақланади.</p></div>
                <div class="feat"><div class="ic"><i class="fa-solid fa-file-signature"></i></div><h3>Автоматик шартнома</h3><p>Тасдиқлангач шартнома, тўлов графиги ва ҳисоб-фактуралар автоматик.</p></div>
                <div class="feat"><div class="ic"><i class="fa-solid fa-chart-line"></i></div><h3>Мониторинг</h3><p>Тўловлар, пеня ва шартномалар ҳолати бўйича доимий назорат.</p></div>
                <div class="feat"><div class="ic"><i class="fa-solid fa-shield-halved"></i></div><h3>Назорат</h3><p>Юрист ва комплаенс шартномаларни кузатади, зарур бўлса тўхтатади.</p></div>
                <div class="feat"><div class="ic"><i class="fa-solid fa-city"></i></div><h3>Ҳудудий</h3><p>Ҳар бир туман ўз аризаларини алоҳида бошқаради.</p></div>
            </div>
        </div>
    </section>

    <section class="lp-section form-section" id="ariza">
        <div class="lp-container">
            <h2>Ариза топшириш</h2>
            <p class="sub">Қуйидаги маълумотларни тўлдиринг — аризангиз модераторга юборилади.</p>

            <div class="form-box">
                @if(session('public_app_number'))
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i> Аризангиз қабул қилинди! Ариза рақами: <b>{{ session('public_app_number') }}</b>.
                        Ариза модерацияга юборилди.
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-error">
                        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('public.applications.submit') }}#ariza">
                    @csrf
                    <div class="form-grid">
                        <div class="form-row">
                            <label class="lbl">Исм <span class="req">*</span></label>
                            <input class="inp" type="text" name="first_name" value="{{ old('first_name') }}" placeholder="Алишер" required>
                        </div>
                        <div class="form-row">
                            <label class="lbl">Фамилия <span class="req">*</span></label>
                            <input class="inp" type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Каримов" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <label class="lbl">ПИНФЛ <span class="req">*</span></label>
                        <input class="inp" type="text" name="pinfl" value="{{ old('pinfl') }}" placeholder="14 та рақам" maxlength="14" inputmode="numeric" required>
                    </div>
                    <div class="form-grid">
                        <div class="form-row">
                            <label class="lbl">Объект кадастр рақами <span class="req">*</span></label>
                            <input class="inp" type="text" name="cadastre_number" value="{{ old('cadastre_number') }}" placeholder="10:11:01:02:02:0114" maxlength="19" inputmode="numeric" required>
                        </div>
                        <div class="form-row">
                            <label class="lbl">Фирма номи <span class="req">*</span></label>
                            <input class="inp" type="text" name="company_name" value="{{ old('company_name') }}" placeholder="«SARDOR» МЧЖ" required>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-row">
                            <label class="lbl">Шаҳар <span class="req">*</span></label>
                            <select class="inp" name="region_id" id="geo-region" required>
                                @if($regions->count() > 1)
                                    <option value="">— Танланг —</option>
                                @endif
                                @foreach($regions as $r)
                                    <option value="{{ $r->id }}" @selected(old('region_id', $regions->count() === 1 ? $r->id : null) == $r->id)>{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-row">
                            <label class="lbl">Туман <span class="req">*</span></label>
                            <select class="inp" name="district_id" id="geo-district" required>
                                <option value="">— Аввал шаҳарни танланг —</option>
                            </select>
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
                            <input class="inp" type="text" name="street" id="geo-street" value="{{ old('street') }}" placeholder="Кўча номини ёзинг" required>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-row">
                            <label class="lbl">Уй рақами</label>
                            <input class="inp" type="text" name="house_number" value="{{ old('house_number') }}" placeholder="55">
                        </div>
                        <div class="form-row">
                            <label class="lbl">Телефон</label>
                            <input class="inp" type="text" name="phone" value="{{ old('phone') }}" placeholder="+998 90 123 45 67" inputmode="tel">
                        </div>
                    </div>
                    <button class="btn btn-teal btn-block" type="submit">Аризани юбориш</button>
                    <p class="tiny muted mt-16" style="text-align:center">Юбориш орқали сиз маълумотларингиз қайта ишланишига розилик берасиз (демо).</p>
                </form>
            </div>
        </div>
    </section>

    <footer class="lp-footer">
        <div class="lp-container">
            © {{ date('Y') }} Tutash Hududlar Reestri — демо версия. Барча ҳуқуқлар ҳимояланган.
        </div>
    </footer>

    <script>
        // Каскад: Шаҳар -> Туман -> Маҳалла (AJAX орқали). Кўча қўлда ёзилади.
        const OLD = {
            district: @json(old('district_id')),
            mahalla: @json(old('mahalla_id')),
        };

        // Илдизга-нисбий (relative) URL — ҳар қандай host/домендан ишлайди.
        const URL_DISTRICTS = id => "{{ route('geo.districts', ['region' => 'RID'], false) }}".replace('RID', id);
        const URL_MAHALLAS = id => "{{ route('geo.mahallas', ['district' => 'DID'], false) }}".replace('DID', id);

        const regionSel = document.getElementById('geo-region');
        const districtSel = document.getElementById('geo-district');
        const mahallaSel = document.getElementById('geo-mahalla');

        // id'ни value сифатида (туман/маҳалла учун).
        function fill(select, items, placeholder, selected) {
            select.innerHTML = '<option value="">' + placeholder + '</option>';
            items.forEach(it => {
                const o = document.createElement('option');
                o.value = it.id;
                o.textContent = it.name;
                if (selected && String(selected) === String(it.id)) o.selected = true;
                select.appendChild(o);
            });
        }

        async function fetchJson(url) {
            try {
                const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                return r.ok ? await r.json() : [];
            } catch (e) { return []; }
        }

        async function loadDistricts(selDistrict, selMahalla) {
            if (!regionSel.value) {
                fill(districtSel, [], '— Аввал шаҳарни танланг —');
                fill(mahallaSel, [], '— Аввал туманни танланг —');
                return;
            }
            districtSel.innerHTML = '<option value="">Юкланмоқда...</option>';
            const districts = await fetchJson(URL_DISTRICTS(regionSel.value));
            fill(districtSel, districts, '— Туманни танланг —', selDistrict);
            await loadMahallas(selMahalla);
        }

        async function loadMahallas(selMahalla) {
            if (!districtSel.value) {
                fill(mahallaSel, [], '— Аввал туманни танланг —');
                return;
            }
            mahallaSel.innerHTML = '<option value="">Юкланмоқда...</option>';
            const mahallas = await fetchJson(URL_MAHALLAS(districtSel.value));
            fill(mahallaSel, mahallas, '— Маҳаллани танланг —', selMahalla);
        }

        regionSel.addEventListener('change', () => loadDistricts(null, null));
        districtSel.addEventListener('change', () => loadMahallas(null));

        // Дастлабки юклашда old() қийматларни тиклаш (валидация хатоси бўлса).
        if (regionSel.value) loadDistricts(OLD.district, OLD.mahalla);

        // --- Input масклар ---------------------------------------------------

        // ПИНФЛ: фақат рақам, кўпи 14 та.
        const pinflEl = document.querySelector('input[name="pinfl"]');
        if (pinflEl) {
            pinflEl.addEventListener('input', () => {
                pinflEl.value = pinflEl.value.replace(/\D/g, '').slice(0, 14);
            });
        }

        // Кадастр: NN:NN:NN:NN:NN:NNNN (14 рақам, ":" билан автоформат).
        const cadEl = document.querySelector('input[name="cadastre_number"]');
        if (cadEl) {
            cadEl.addEventListener('input', () => {
                const digits = cadEl.value.replace(/\D/g, '').slice(0, 14);
                const groups = [2, 2, 2, 2, 2, 4];
                let out = '', i = 0;
                for (const g of groups) {
                    if (i >= digits.length) break;
                    out += (out ? ':' : '') + digits.slice(i, i + g);
                    i += g;
                }
                cadEl.value = out;
            });
        }

        // Телефон: +998 XX XXX XX XX.
        const phoneEl = document.querySelector('input[name="phone"]');
        if (phoneEl) {
            phoneEl.addEventListener('input', () => {
                let d = phoneEl.value.replace(/\D/g, '');
                if (d.startsWith('998')) d = d.slice(3);
                d = d.slice(0, 9);
                if (d.length === 0) { phoneEl.value = ''; return; }
                let out = '+998';
                if (d.length > 0) out += ' ' + d.slice(0, 2);
                if (d.length > 2) out += ' ' + d.slice(2, 5);
                if (d.length > 5) out += ' ' + d.slice(5, 7);
                if (d.length > 7) out += ' ' + d.slice(7, 9);
                phoneEl.value = out;
            });
        }
    </script>
</body>
</html>
