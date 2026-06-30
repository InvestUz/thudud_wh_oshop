{{-- $applications: Application collection/paginator --}}
<table class="tbl">
    <thead>
        <tr>
            <th>№</th>
            <th>Объект манзили</th>
            <th>Фирма</th>
            <th>Майдон</th>
            <th>Босқич</th>
            <th>Ҳолат</th>
            <th>Сана</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($applications as $app)
            <tr>
                <td class="mono"><b>{{ $app->application_number }}</b></td>
                <td>
                    {{ $app->object?->fullAddress() ?: '—' }}
                    <div class="tiny muted mono">{{ $app->object?->cadastre_number }}</div>
                </td>
                <td>{{ \Illuminate\Support\Str::limit($app->object?->company_name, 28) }}</td>
                <td class="num">{{ rtrim(rtrim(number_format((float) $app->adjacentAreas->sum('area_m2'), 2, '.', ' '), '0'), '.') }} м²</td>
                <td><x-badge :color="$app->current_stage->color()" :label="$app->current_stage->label()" /></td>
                <td><x-badge :color="$app->status->color()" :label="$app->status->label()" /></td>
                <td class="tiny muted">{{ optional($app->submitted_at ?? $app->created_at)->format('d.m.y H:i') }}</td>
                <td class="text-right">
                    <a href="{{ route('applications.show', $app) }}" class="btn btn-outline btn-sm">Кўриш</a>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
