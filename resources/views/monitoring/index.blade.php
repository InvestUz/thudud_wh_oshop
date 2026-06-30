@extends('layouts.app')
@section('title', 'Мониторинг')
@section('heading', 'Мониторинг ва ҳисобот')

@section('content')
    @if($districtFilterEnabled)
        <div class="card mb-16">
            <div class="card-body">
                <form method="GET" class="toolbar">
                    <label class="lbl mb-0" for="district_id">Туман бўйича:</label>
                    <select class="inp" style="max-width:340px" name="district_id" id="district_id" onchange="this.form.submit()">
                        <option value="">— Барча туманлар —</option>
                        @foreach($districtsByRegion as $regionName => $districts)
                            <optgroup label="{{ $regionName }}">
                                @foreach($districts as $district)
                                    <option value="{{ $district->id }}" @selected((int) $selectedDistrictId === $district->id)>{{ $district->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <noscript><button class="btn btn-teal btn-sm" type="submit">Кўрсатиш</button></noscript>
                    @if($selectedDistrictId)
                        <a href="{{ route('monitoring') }}" class="btn btn-outline btn-sm">Тозалаш</a>
                    @endif
                </form>
            </div>
        </div>
    @endif

    {{-- Тошкент шаҳри — "24/7 кўчалари" ижара шартномалари бўйича ойлик тўлов мониторинги (Excel шаклида) --}}
    @if(!empty($monRows))
        @php
            $num = fn ($v) => number_format((float) $v, 0, '.', ' ');
            $pct = fn ($v) => number_format((float) $v, 0).'%';
        @endphp
        <div class="card mb-16">
            <div class="report-title">
                <div class="rt-main">24/7 кўчалари бўйича ижара шартномаларини расмийлаштириш бўйича мониторинги ҳолати</div>
                <div class="rt-sub">Тошкент шаҳри туманлари кесимида (сўм)</div>
            </div>
            <div class="report-scroll">
                <table class="report-tbl mon-tbl">
                    <thead>
                        <tr>
                            <th rowspan="3">T/R</th>
                            <th rowspan="3" class="tl">Туман номи</th>
                            <th rowspan="3">Туташ ҳудуд майдони (кв.м)</th>
                            <th rowspan="3">Шартномалар сони</th>
                            <th rowspan="3">Шартнома суммаси</th>
                            <th rowspan="3">Жами тушум</th>
                            <th rowspan="3">Қолдиқ</th>
                            <th rowspan="3">Фоизда</th>
                            @foreach($monYears as $year => $cnt)
                                <th colspan="{{ $cnt * 5 }}" class="grp">{{ $year }}-йил</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($monPeriods as $p)
                                <th colspan="5" class="grp">{{ $p['label'] }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($monPeriods as $p)
                                <th>Режа</th><th>Амалда</th><th>Қолдиқ</th><th>Ортиқча</th><th>Фоиз</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $renderMonths = function ($months) use ($monPeriods, $num, $pct) {
                                $html = '';
                                foreach ($monPeriods as $p) {
                                    $m = $months[$p['key']];
                                    $html .= '<td>'.$num($m['reja']).'</td>'
                                        .'<td class="pos">'.$num($m['amalda']).'</td>'
                                        .'<td class="neg">'.$num($m['qoldiq']).'</td>'
                                        .'<td class="warn">'.$num($m['ortiqcha']).'</td>'
                                        .'<td>'.$pct($m['foiz']).'</td>';
                                }
                                return $html;
                            };
                        @endphp
                        <tr class="total-row">
                            <td>A</td>
                            <td class="tl">Jami</td>
                            <td>{{ $num($monTotal['area']) }}</td>
                            <td>{{ $num($monTotal['count']) }}</td>
                            <td>{{ $num($monTotal['summa']) }}</td>
                            <td class="pos">{{ $num($monTotal['tushum']) }}</td>
                            <td class="neg">{{ $num($monTotal['qoldiq']) }}</td>
                            <td>{{ $pct($monTotal['foiz']) }}</td>
                            {!! $renderMonths($monTotal['months']) !!}
                        </tr>
                        @foreach($monRows as $row)
                            <tr>
                                <td>{{ $row['n'] }}</td>
                                <td class="tl"><a href="{{ route('applications.index', ['district_id' => $row['district_id'], 'scope' => 'all']) }}">{{ $row['name'] }}</a></td>
                                <td>{{ $num($row['area']) }}</td>
                                <td>{{ $num($row['count']) }}</td>
                                <td>{{ $num($row['summa']) }}</td>
                                <td class="pos">{{ $num($row['tushum']) }}</td>
                                <td class="neg">{{ $num($row['qoldiq']) }}</td>
                                <td>{{ $pct($row['foiz']) }}</td>
                                {!! $renderMonths($row['months']) !!}
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @php
        $districtQuery = $selectedDistrictId ? ['district_id' => $selectedDistrictId] : [];
        $appsUrl = route('applications.index', $districtQuery + ['scope' => 'all']);
        $contractsUrl = route('contracts.index', $districtQuery);
        $paidUrl = route('contracts.index', $districtQuery + ['payment' => 'paid']);
        $overdueUrl = route('contracts.index', $districtQuery + ['payment' => 'overdue']);
    @endphp
    <div class="stats">
        <a class="stat" href="{{ $appsUrl }}" title="Аризалар рўйхатини кўриш"><div class="ic badge-teal"><i class="fa-solid fa-folder-open"></i></div><div><div class="val">{{ $totals['applications'] }}</div><div class="lbl">Жами аризалар</div></div></a>
        <a class="stat" href="{{ $contractsUrl }}" title="Шартномалар рўйхатини кўриш"><div class="ic badge-blue"><i class="fa-solid fa-file-contract"></i></div><div><div class="val">{{ $totals['contracts'] }}</div><div class="lbl">Жами шартномалар</div></div></a>
        <a class="stat" href="{{ $paidUrl }}" title="Тўлов қилинган шартномаларни кўриш"><div class="ic badge-green"><i class="fa-solid fa-money-bill-wave"></i></div><div><div class="val" style="font-size:20px">{{ number_format($totals['paidSum'],0,'.',' ') }}</div><div class="lbl">Тўланган (сўм)</div></div></a>
        <a class="stat" href="{{ $overdueUrl }}" title="Муддати ўтган (тўланмаган) шартномаларни кўриш"><div class="ic badge-red"><i class="fa-solid fa-triangle-exclamation"></i></div><div><div class="val" style="font-size:20px">{{ number_format($totals['overdueSum'],0,'.',' ') }}</div><div class="lbl">Муддати ўтган (сўм)</div></div></a>
    </div>

    <div class="card mb-16">
        <div class="card-head">
            <h2><i class="fa-solid fa-map-location-dot"></i> Шартнома майдонлари — харита</h2>
            <div class="map-legend tiny muted">
                <span><i class="dot dot-green"></i> Тўланган (қарзсиз)</span>
                <span><i class="dot dot-amber"></i> Тўлов кутилмоқда</span>
                <span><i class="dot dot-red"></i> Муддати ўтган</span>
            </div>
        </div>
        <div class="card-body">
            @if($mapContracts->isEmpty())
                <div class="empty">Харитада кўрсатиш учун белгиланган майдон топилмади.</div>
            @else
                <div id="contractsMap" class="monitor-map"
                     data-contracts='@json($mapContracts, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'></div>
            @endif
        </div>
    </div>

    <div class="grid-2">
        <div class="card mb-16">
            <div class="card-head"><h2>Аризалар — ҳолат бўйича</h2></div>
            <div class="card-body">
                @foreach($appsByStatus as $row)
                    <div class="flex items-center justify-between" style="padding:8px 0;border-bottom:1px solid #f1f3f5">
                        <x-badge :color="$row['color']" :label="$row['label']" />
                        <b>{{ $row['count'] }}</b>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card mb-16">
            <div class="card-head"><h2>Шартномалар — ҳолат бўйича</h2></div>
            <div class="card-body">
                @foreach($contractsByStatus as $row)
                    <div class="flex items-center justify-between" style="padding:8px 0;border-bottom:1px solid #f1f3f5">
                        <x-badge :color="$row['color']" :label="$row['label']" />
                        <b>{{ $row['count'] }}</b>
                    </div>
                @endforeach
                <div class="divider"></div>
                <div class="flex items-center justify-between"><span class="muted">Кутилаётган тўловлар</span><b>{{ number_format($totals['pendingSum'],0,'.',' ') }} сўм</b></div>
                <div class="flex items-center justify-between mt-8"><span class="muted">Жами ҳисобланган пеня</span><b style="color:#c77700">{{ number_format($totals['penaltySum'],0,'.',' ') }} сўм</b></div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .monitor-map { height: 440px; border-radius: 10px; border: 1px solid var(--line); overflow: hidden; }
        .leaflet-container { font: inherit; }
        .map-legend { display: flex; gap: 16px; flex-wrap: wrap; align-items: center; }
        .map-legend .dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 5px; vertical-align: middle; }
        .dot-green { background: #1a7f43; } .dot-amber { background: #c77700; } .dot-red { background: #c0392b; }

        /* Ҳудудлар кесими ҳисобот жадвали (расмдагидек) */
        .report-title { text-align: center; padding: 16px 14px 10px; border-bottom: 1px solid var(--line); }
        .report-title .rt-main { font-weight: 700; color: #1f5f9e; font-size: 15px; line-height: 1.4; }
        .report-title .rt-sub { font-weight: 800; color: #c0392b; letter-spacing: .5px; margin-top: 4px; }
        .report-title .rt-note { font-size: 13px; color: var(--muted); margin-top: 6px; }
        .report-scroll { overflow-x: auto; }
        .report-tbl { border-collapse: collapse; width: 100%; font-size: 13px; min-width: 920px; }
        .report-tbl th, .report-tbl td { border: 1px solid #d4dde4; padding: 6px 8px; text-align: center; white-space: nowrap; }
        .report-tbl thead th { background: #eef3f7; color: #1f3a52; font-weight: 700; vertical-align: middle; }
        .report-tbl td.tl, .report-tbl th.tl { text-align: left; }
        .report-tbl td.tl a { color: var(--teal-dark); text-decoration: none; }
        .report-tbl td.tl a:hover { text-decoration: underline; }
        .report-tbl tbody tr:nth-child(even) { background: #fafbfc; }
        .report-tbl tbody tr:hover { background: #f0f7f5; }
        .report-tbl .total-row { background: #e8f3ef !important; font-weight: 700; }
        .report-tbl .total-row td { border-top: 2px solid var(--teal); border-bottom: 2px solid var(--teal); }
        .report-tbl td.pos { color: #1a7f43; font-weight: 600; }
        .report-tbl td.neg { color: #c0392b; font-weight: 600; }
        .report-tbl td.warn { color: #c77700; font-weight: 600; }
        /* Кенг ойлик мониторинг жадвали */
        .mon-tbl { font-size: 11px; min-width: 100%; }
        .mon-tbl th, .mon-tbl td { padding: 3px 5px; }
        .mon-tbl thead th.grp { background: #dbe7f0; color: #163a57; }
        .mon-tbl tbody td:nth-child(-n+8), .mon-tbl thead th:nth-child(-n+8) { background-clip: padding-box; }
        .mon-tbl tbody tr td:nth-child(2) { min-width: 110px; }
        .report-scroll { max-height: 78vh; }
    </style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('contractsMap');
        if (!el || !window.L) return;

        const TILES = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        const ATTR = '© OpenStreetMap';
        const COLORS = { paid: '#1a7f43', pending: '#c77700', overdue: '#c0392b' };
        const LABELS = { paid: 'Тўланган (қарзсиз)', pending: 'Тўлов кутилмоқда', overdue: 'Муддати ўтган' };

        let data = [];
        try { data = JSON.parse(el.dataset.contracts) || []; } catch (e) { data = []; }

        const map = L.map('contractsMap', { scrollWheelZoom: false });
        L.tileLayer(TILES, { maxZoom: 19, attribution: ATTR }).addTo(map);
        const group = L.featureGroup().addTo(map);

        data.forEach(function (c) {
            const color = COLORS[c.state] || '#475569';
            const popup = '<b>' + (c.number || '') + '</b>'
                + (c.company ? '<br>' + c.company : '')
                + '<br><span style="color:' + color + '">● ' + (LABELS[c.state] || '') + '</span>'
                + (c.url ? '<br><a href="' + c.url + '">Шартномани кўриш →</a>' : '');

            let layer = null;
            if (c.geo) {
                try {
                    layer = L.geoJSON({ type: 'Feature', geometry: c.geo },
                        { style: { color: color, weight: 2, fillColor: color, fillOpacity: 0.35 } });
                } catch (e) { layer = null; }
            }
            if (!layer && c.lat != null && c.lng != null) {
                layer = L.circleMarker([c.lat, c.lng],
                    { radius: 8, color: color, fillColor: color, fillOpacity: 0.7, weight: 2 });
            }
            if (layer) { layer.bindPopup(popup); group.addLayer(layer); }
        });

        if (group.getLayers().length) {
            map.fitBounds(group.getBounds(), { maxZoom: 17, padding: [24, 24] });
        } else {
            map.setView([41.311, 69.279], 12);
        }
        setTimeout(function () { map.invalidateSize(); }, 200);
    });
    </script>
@endpush
