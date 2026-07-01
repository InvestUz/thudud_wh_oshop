@extends('layouts.app')
@section('title', 'Бош саҳифа')
@section('heading', 'Бош саҳифа')

@php
    $iconMap = ['file'=>'fa-folder-open','clock'=>'fa-clock','check'=>'fa-circle-check','doc'=>'fa-file-contract','inbox'=>'fa-inbox','x'=>'fa-circle-xmark','pause'=>'fa-circle-pause','alert'=>'fa-triangle-exclamation'];
    $bgMap = ['teal'=>'badge-teal','amber'=>'badge-amber','green'=>'badge-green','red'=>'badge-red','blue'=>'badge-blue','violet'=>'badge-violet','slate'=>'badge-slate'];
    $showsContracts = $recent->first() instanceof \App\Models\Contract;
@endphp

@section('content')
    @if($signatureRequests->isNotEmpty())
        <div class="alert alert-success" style="display:flex;justify-content:space-between;align-items:center;gap:16px">
            <div><strong><i class="fa-solid fa-bell"></i> Раҳбар тасдиқлаган ариза бор</strong><div class="tiny mt-8">Ариза ҳали якуний тасдиқланмаган. Шартнома билан танишиб, ўз тасдиғингизни беринг.</div></div>
            <div class="flex gap-8 wrap">
                @foreach($signatureRequests as $item)
                    <a class="btn btn-outline btn-sm" href="{{ route('applications.show', $item) }}">{{ $item->application_number }} — кўриш</a>
                    <form method="POST" action="{{ route('applications.transition', $item) }}" onsubmit="return confirm('Аризани якуний тасдиқлайсизми? Бу амалдан кейин ариза тасдиқланган ҳисобланади.')">@csrf<input type="hidden" name="action" value="sign"><button class="btn btn-green btn-sm">Тасдиқлайман</button></form>
                @endforeach
            </div>
        </div>
    @endif
    @if($optionalReviewApplications->isNotEmpty())
        <div class="alert alert-success"><strong><i class="fa-solid fa-scale-balanced"></i> Ихтиёрий хулоса кутаётган аризалар</strong><div class="flex gap-8 wrap mt-8">@foreach($optionalReviewApplications as $item)<a class="btn btn-outline btn-sm" href="{{ route('applications.show', $item) }}">{{ $item->application_number }} — хулоса бериш</a>@endforeach</div></div>
    @endif
    <div class="stats">
        @foreach($cards as $c)
            <div class="stat">
                <div class="ic {{ $bgMap[$c['color']] ?? 'badge-slate' }}"><i class="fa-solid {{ $iconMap[$c['icon']] ?? 'fa-circle-dot' }}"></i></div>
                <div>
                    <div class="val">{{ $c['value'] }}</div>
                    <div class="lbl">{{ $c['label'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-head">
            <h2>
                @if($role === \App\Enums\RoleType::Applicant) Сўнгги аризаларим
                @elseif($showsContracts) Сўнгги шартномалар
                @else Кутаётган аризалар
                @endif
            </h2>
            <div class="flex gap-8">
                @if($role === \App\Enums\RoleType::Applicant)
                    <a href="{{ route('applications.create') }}" class="btn btn-teal btn-sm"><i class="fa-solid fa-plus"></i> Янги ариза</a>
                @endif
                @if($showsContracts)
                    <a href="{{ route('contracts.index') }}" class="btn btn-outline btn-sm">Барчаси →</a>
                @else
                    <a href="{{ route('applications.index') }}" class="btn btn-outline btn-sm">Барчаси →</a>
                @endif
            </div>
        </div>
        <div class="table-wrap">
            @if($recent->isEmpty())
                <div class="empty">Маълумот йўқ.</div>
            @elseif($showsContracts)
                @include('partials.contracts-table', ['contracts' => $recent])
            @else
                @include('partials.applications-table', ['applications' => $recent])
            @endif
        </div>
    </div>
@endsection
