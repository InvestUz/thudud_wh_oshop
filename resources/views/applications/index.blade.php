@extends('layouts.app')
@section('title', 'Аризалар')
@section('heading', $role === \App\Enums\RoleType::Applicant ? 'Менинг аризаларим' : 'Кадастр участкалари — Аризалар')

@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Аризалар рўйхати</h2>
            @if($role === \App\Enums\RoleType::Applicant)
                <a href="{{ route('applications.create') }}" class="btn btn-teal btn-sm"><i class="fa-solid fa-plus"></i> Янги ариза</a>
            @endif
        </div>
        <div class="card-body">
            @isset($district)
                @if($district)
                    <div class="alert alert-info flex items-center justify-between">
                        <span>Туман бўйича фильтр: <b>{{ $district->name }}</b></span>
                        <a href="{{ route('applications.index', request()->except('district_id')) }}" class="btn btn-outline btn-sm">✕ Тумандан тозалаш</a>
                    </div>
                @endif
            @endisset

            <form method="GET" class="toolbar mb-16">
                @if(request('district_id'))<input type="hidden" name="district_id" value="{{ request('district_id') }}">@endif
                <input class="inp" style="max-width:260px" type="text" name="q" value="{{ request('q') }}" placeholder="№ / кадастр / фирма бўйича қидириш">
                <select class="inp" style="max-width:200px" name="stage">
                    <option value="">— Барча босқичлар —</option>
                    @foreach($stages as $s)
                        <option value="{{ $s->value }}" @selected(request('stage') === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
                <select class="inp" style="max-width:180px" name="status">
                    <option value="">— Барча ҳолатлар —</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
                @if($role && in_array($role, \App\Enums\RoleType::pipelineRoles()))
                    <select class="inp" style="max-width:200px" name="scope">
                        <option value="queue" @selected($scope === 'queue')>Менинг навбатим</option>
                        <option value="all" @selected($scope === 'all')>Туман бўйича барчаси</option>
                    </select>
                @endif
                <button class="btn btn-teal btn-sm" type="submit">Фильтр</button>
                <a href="{{ route('applications.index') }}" class="btn btn-outline btn-sm">Тозалаш</a>
            </form>

            <div class="table-wrap">
                @if($applications->isEmpty())
                    <div class="empty">Ариза топилмади.</div>
                @else
                    @include('partials.applications-table', ['applications' => $applications])
                @endif
            </div>

            {{ $applications->links('pagination.simple') }}
        </div>
    </div>
@endsection
