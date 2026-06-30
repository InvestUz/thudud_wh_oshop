@extends('layouts.app')
@section('title', 'Шартномалар')
@section('heading', 'Шартномалар')

@section('content')
    <div class="card">
        <div class="card-head"><h2>Шартномалар рўйхати</h2></div>
        <div class="card-body">
            @php
                $paymentLabels = ['paid' => 'Тўланган (тўлов қилинган) шартномалар', 'overdue' => 'Муддати ўтган (тўланмаган) шартномалар'];
            @endphp
            @if(($district ?? null) || ($payment ?? null))
                <div class="alert alert-info flex items-center justify-between wrap gap-8">
                    <span>
                        Фильтр:
                        @if($payment ?? null)<b>{{ $paymentLabels[$payment] ?? $payment }}</b>@endif
                        @if(($district ?? null))@if($payment ?? null) — @endif<b>{{ $district->name }}</b>@endif
                    </span>
                    <a href="{{ route('contracts.index') }}" class="btn btn-outline btn-sm">✕ Фильтрни тозалаш</a>
                </div>
            @endif

            <form method="GET" class="toolbar mb-16">
                @if(request('district_id'))<input type="hidden" name="district_id" value="{{ request('district_id') }}">@endif
                @if($payment ?? null)<input type="hidden" name="payment" value="{{ $payment }}">@endif
                <input class="inp" style="max-width:280px" type="text" name="q" value="{{ request('q') }}" placeholder="№ / фирма / кадастр">
                <select class="inp" style="max-width:200px" name="status">
                    <option value="">— Барча ҳолатлар —</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
                <button class="btn btn-teal btn-sm" type="submit">Фильтр</button>
                <a href="{{ route('contracts.index') }}" class="btn btn-outline btn-sm">Тозалаш</a>
            </form>

            <div class="table-wrap">
                @if($contracts->isEmpty())
                    <div class="empty">Шартнома топилмади.</div>
                @else
                    @include('partials.contracts-table', ['contracts' => $contracts])
                @endif
            </div>

            {{ $contracts->links('pagination.simple') }}
        </div>
    </div>
@endsection
