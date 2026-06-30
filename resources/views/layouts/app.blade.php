<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Кабинет') — Tutash Hududlar Reestri</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @stack('styles')
</head>
<body>
@php
    $user = auth()->user();
    $role = $user->roleType();
    $isApplicant = $role === \App\Enums\RoleType::Applicant;
    $isPipeline = $user->isPipelineActor();
    $canContracts = $user->canControlContracts();
    $canMonitoring = $user->canViewMonitoring();
@endphp
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="logo">Т</div>
            <div>
                <div class="title">TUTASH HUDUDLAR</div>
                <div class="subtitle">Кабинет — Реестри</div>
            </div>
        </div>
        <nav class="nav">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="nav-ico"><i class="fa-solid fa-house"></i></span> Бош саҳифа
            </a>
            @if($isApplicant)
                <a href="{{ route('applications.create') }}" class="{{ request()->routeIs('applications.create') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fa-solid fa-plus"></i></span> Ариза яратиш
                </a>
            @endif
            @if($isApplicant || $isPipeline)
                <a href="{{ route('applications.index') }}" class="{{ request()->routeIs('applications.index') || request()->routeIs('applications.show') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fa-solid fa-folder-open"></i></span> {{ $isApplicant ? 'Менинг аризаларим' : 'Аризалар' }}
                </a>
            @endif
            @if($isApplicant || $canContracts)
                <a href="{{ route('contracts.index') }}" class="{{ request()->routeIs('contracts.*') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fa-solid fa-file-contract"></i></span> Шартномалар
                </a>
            @endif
            @if($canMonitoring)
                <a href="{{ route('monitoring') }}" class="{{ request()->routeIs('monitoring') ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fa-solid fa-chart-line"></i></span> Мониторинг
                </a>
            @endif
        </nav>
        <div class="userbox">
            <div class="name">{{ $user->displayName() }}</div>
            <div class="role">{{ $role?->label() }}</div>
            @if($user->district)
                <div class="tiny muted">{{ $user->district->name }}</div>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="mt-8">
                @csrf
                <button class="btn btn-outline btn-sm btn-block" type="submit">Чиқиш</button>
            </form>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="flex items-center gap-12">
                <button class="btn btn-outline btn-sm" onclick="document.getElementById('sidebar').classList.toggle('open')" style="display:none" id="menuBtn"><i class="fa-solid fa-bars"></i></button>
                <h1>@yield('heading', 'Кабинет')</h1>
            </div>
            <div class="right">
                <span class="tiny muted">{{ now()->format('d.m.Y') }}</span>
                <div class="avatar">{{ \Illuminate\Support\Str::of($user->displayName())->substr(0,1)->upper() }}</div>
            </div>
        </header>
        <main class="content">
            @if(request()->boolean('upload_error'))
                <div class="alert alert-error">Файллар ҳажми жуда катта. Расмлар автоматик кичрайтирилади; битта ҳужжат 10 МБ, умумий юклама 128 МБ дан ошмаслиги керак.</div>
            @endif
            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
<script>
    // Mobil menyu tugmasi
    if (window.innerWidth <= 980) document.getElementById('menuBtn').style.display = 'inline-flex';
</script>
@stack('scripts')
</body>
</html>
