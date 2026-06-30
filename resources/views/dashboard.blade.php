@extends('layouts.app')
@section('title', 'Бош саҳифа')
@section('heading', 'Бош саҳифа')

@php
    $iconMap = ['file'=>'fa-folder-open','clock'=>'fa-clock','check'=>'fa-circle-check','doc'=>'fa-file-contract','inbox'=>'fa-inbox','x'=>'fa-circle-xmark','pause'=>'fa-circle-pause','alert'=>'fa-triangle-exclamation'];
    $bgMap = ['teal'=>'badge-teal','amber'=>'badge-amber','green'=>'badge-green','red'=>'badge-red','blue'=>'badge-blue','violet'=>'badge-violet','slate'=>'badge-slate'];
    $showsContracts = $recent->first() instanceof \App\Models\Contract;
@endphp

@section('content')
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
