{{-- Kutadi: $files (yo'llar massivi), ixtiyoriy $labels (ko'rinadigan nomlar). --}}
@php
    $fcIcon = function ($path) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf' => ['fa-file-pdf', '#e53935'],
            'doc', 'docx' => ['fa-file-word', '#2b579a'],
            'xls', 'xlsx' => ['fa-file-excel', '#1d7044'],
            'jpg', 'jpeg', 'png', 'webp' => ['fa-file-image', '#8e44ad'],
            default => ['fa-file-lines', '#607d8b'],
        };
    };
@endphp
<div class="file-cards mt-8">
    @foreach($files as $i => $f)
        @php([$fcI, $fcC] = $fcIcon($f))
        <a href="{{ asset($f) }}" target="_blank" class="file-card" title="{{ basename($f) }}">
            <span class="fc-icon" style="color:{{ $fcC }}"><i class="fa-solid {{ $fcI }}"></i></span>
            <span class="fc-body">
                <span class="fc-name">{{ $labels[$i] ?? 'Ҳужжат '.($i + 1) }}</span>
                <span class="fc-ext">{{ strtoupper(pathinfo($f, PATHINFO_EXTENSION)) }} файл</span>
            </span>
            <span class="fc-open"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
        </a>
    @endforeach
</div>
