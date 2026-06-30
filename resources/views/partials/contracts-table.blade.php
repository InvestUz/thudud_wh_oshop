{{-- $contracts: Contract collection/paginator --}}
<table class="tbl">
    <thead>
        <tr>
            <th>Шартнома №</th>
            <th>Фирма / Объект</th>
            <th>Ойлик сумма</th>
            <th>Жами сумма</th>
            <th>Муддат</th>
            <th>Ҳолат</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($contracts as $c)
            <tr>
                <td class="mono"><b>{{ $c->contract_number }}</b>
                    <div class="tiny muted">{{ optional($c->contract_date)->format('d.m.Y') }}</div>
                </td>
                <td>
                    {{ \Illuminate\Support\Str::limit($c->object?->company_name, 26) }}
                    <div class="tiny muted mono">{{ $c->object?->cadastre_number }}</div>
                </td>
                <td class="num">{{ number_format((float) $c->monthly_amount, 0, '.', ' ') }} сўм</td>
                <td class="num">{{ number_format((float) $c->total_amount, 0, '.', ' ') }} сўм</td>
                <td class="tiny">{{ optional($c->start_date)->format('d.m.y') }} — {{ optional($c->end_date)->format('d.m.y') }}</td>
                <td><x-badge :color="$c->status->color()" :label="$c->status->label()" /></td>
                <td class="text-right">
                    <a href="{{ route('contracts.show', $c) }}" class="btn btn-outline btn-sm">Кўриш</a>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
