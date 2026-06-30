@extends('layouts.app')
@section('title', 'Шартнома '.$contract->contract_number)
@section('heading', 'Шартнома '.$contract->contract_number)

@section('content')
    <div class="page-head">
        <div class="flex items-center gap-12 wrap">
            <span class="mono" style="font-size:18px;font-weight:800">{{ $contract->contract_number }}</span>
            <x-badge :color="$contract->status->color()" :label="$contract->status->label()" />
        </div>
        <a href="{{ route('contracts.index') }}" class="btn btn-outline btn-sm">← Рўйхатга</a>
    </div>

    @if($contract->problem_reason)
        <div class="alert alert-error">Сабаб: {{ $contract->problem_reason }}</div>
    @endif

    <div class="grid-2">
        <div>
            <div class="card mb-16">
                <div class="card-head"><h2>Шартнома маълумотлари</h2></div>
                <div class="card-body">
                    <dl class="dl">
                        <dt>Фирма / Мулкдор</dt><dd>{{ $contract->object?->company_name }} ({{ $contract->owner?->displayName() }})</dd>
                        <dt>Кадастр</dt><dd class="mono">{{ $contract->object?->cadastre_number }}</dd>
                        <dt>Туман</dt><dd>{{ $contract->district?->name }}</dd>
                        <dt>Шартнома санаси</dt><dd>{{ optional($contract->contract_date)->format('d.m.Y') }}</dd>
                        <dt>Амал муддати</dt><dd>{{ optional($contract->start_date)->format('d.m.Y') }} — {{ optional($contract->end_date)->format('d.m.Y') }}</dd>
                        <dt>Ойлик сумма</dt><dd><b>{{ number_format((float)$contract->monthly_amount,0,'.',' ') }} сўм</b></dd>
                        <dt>Жами сумма (12 ой)</dt><dd><b>{{ number_format((float)$contract->total_amount,0,'.',' ') }} сўм</b></dd>
                        <dt>Кунлик пеня ставкаси</dt><dd>{{ $contract->penalty_rate }}%</dd>
                        <dt>Боғлиқ ариза</dt><dd>
                            @if($contract->application)
                                <a href="{{ route('applications.show', $contract->application) }}">{{ $contract->application->application_number }}</a>
                            @else — @endif
                        </dd>
                    </dl>

                    <div class="divider"></div>
                    <div class="grid-3">
                        <div class="stat" style="padding:12px"><div><div class="val" style="font-size:18px;color:#1a7f43">{{ number_format($contract->paidAmount(),0,'.',' ') }}</div><div class="lbl">Тўланган (сўм)</div></div></div>
                        <div class="stat" style="padding:12px"><div><div class="val" style="font-size:18px;color:#c0392b">{{ number_format($contract->overdueAmount(),0,'.',' ') }}</div><div class="lbl">Муддати ўтган</div></div></div>
                        <div class="stat" style="padding:12px"><div><div class="val" style="font-size:18px;color:#c77700">{{ number_format($contract->totalPenalty(),0,'.',' ') }}</div><div class="lbl">Жами пеня</div></div></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2>Тўлов графиги (12 ой)</h2></div>
                <div class="table-wrap">
                    <table class="tbl">
                        <thead>
                            <tr><th>Ой</th><th>Давр</th><th>Тўлов санаси</th><th>Сумма</th><th>Пеня</th><th>Ҳолат</th></tr>
                        </thead>
                        <tbody>
                            @foreach($contract->schedules as $s)
                                <tr>
                                    <td>{{ $s->month_no }}</td>
                                    <td class="mono">{{ $s->period }}</td>
                                    <td class="tiny">{{ optional($s->due_date)->format('d.m.Y') }}</td>
                                    <td class="num">{{ number_format((float)$s->amount,0,'.',' ') }}</td>
                                    <td class="num">{{ (float)$s->penalty_amount > 0 ? number_format((float)$s->penalty_amount,0,'.',' ') : '—' }}</td>
                                    <td><x-badge :color="$s->status->color()" :label="$s->status->label()" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div>
            @if($canControl)
                <div class="card mb-16">
                    <div class="card-head"><h2>Назорат ҳаракатлари</h2></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('contracts.action', $contract) }}">
                            @csrf
                            <div class="form-row">
                                <label class="lbl">Сабаб (тўхтатиш/бекор қилишда мажбурий)</label>
                                <textarea class="inp" name="reason" placeholder="Сабабни киритинг...">{{ old('reason') }}</textarea>
                            </div>
                            <div class="flex gap-8 wrap">
                                @if($contract->status === \App\Enums\ContractStatus::Active)
                                    <button class="btn btn-amber" name="action" value="suspend"><i class="fa-solid fa-pause"></i> Тўхтатиш</button>
                                @endif
                                @if($contract->status === \App\Enums\ContractStatus::Suspended)
                                    <button class="btn btn-green" name="action" value="resume"><i class="fa-solid fa-play"></i> Қайта тиклаш</button>
                                @endif
                                @if($contract->status !== \App\Enums\ContractStatus::Terminated)
                                    <button class="btn btn-red" name="action" value="terminate" onclick="return confirm('Шартномани бекор қилишни тасдиқлайсизми?')"><i class="fa-solid fa-xmark"></i> Бекор қилиш</button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-head"><h2>Ҳаракатлар тарихи</h2></div>
                <div class="card-body">
                    @if($contract->actions->isEmpty())
                        <div class="empty">Ҳаракатлар йўқ.</div>
                    @else
                        <div class="timeline">
                            @foreach($contract->actions as $act)
                                <div class="tl-item">
                                    <div class="tl-dot {{ $act->action->color() === 'green' ? 'green' : ($act->action->color()==='red'?'red':($act->action->color()==='amber'?'amber':'slate')) }}">●</div>
                                    <div class="tl-time">{{ optional($act->created_at)->format('d.m.Y H:i') }}</div>
                                    <div class="tl-card">
                                        <div class="tl-title">{{ $act->action->label() }}</div>
                                        <div class="tl-meta"><b>{{ $act->user?->displayName() }}</b> · {{ $act->user?->roleType()?->label() }}</div>
                                        @if($act->reason)<div class="tl-comment"><i class="fa-solid fa-comment"></i> {{ $act->reason }}</div>@endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
