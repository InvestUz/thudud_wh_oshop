<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Авторизация — Tutash Hududlar Reestri</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body { background: #dfe8ea; }
        .auth-topbar { height: 30px; background: #3a4750; color: #cbd5e1; font-size: 12px; display: flex; align-items: center; padding: 0 16px; }
        .auth-wrap { min-height: calc(100vh - 30px); display: grid; place-items: center; padding: 20px; }
        .auth-card { background: #fff; border-radius: 16px; box-shadow: var(--shadow-lg); padding: 36px 34px; width: 380px; text-align: center; }
        .auth-card .brand-row { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 26px; }
        .auth-card .brand-row .logo { width: 38px; height: 38px; border-radius: 9px; background: var(--teal); color: #fff; display: grid; place-items: center; font-weight: 800; font-size: 20px; }
        .auth-card .brand-row .ttl { text-align: left; font-weight: 800; color: var(--teal-dark); font-size: 15px; line-height: 1.05; }
        .auth-card h2 { font-size: 15px; color: var(--muted); font-weight: 500; margin: 6px 0 22px; }
        .auth-card .form-row { text-align: left; }
        .demo-note { margin-top: 18px; font-size: 12px; color: var(--muted); background: var(--teal-light); border-radius: 9px; padding: 10px 12px; text-align: left; }
        .demo-note b { color: var(--teal-dark); }
    </style>
</head>
<body>
    <div class="auth-topbar">Авторизация</div>
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="brand-row">
                <div class="logo">Т</div>
                <div class="ttl">TUTASH HUDUDLAR<br>REESTRI</div>
            </div>
            <h2>Единая система идентификации</h2>

            @if($errors->any())
                <div class="alert alert-error" style="text-align:left">
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-row">
                    <label class="lbl">Электрон почта</label>
                    <input class="inp" type="email" name="email" value="{{ old('email') }}" placeholder="moderator@test.uz" autofocus required>
                </div>
                <div class="form-row">
                    <label class="lbl">Парол</label>
                    <input class="inp" type="password" name="password" placeholder="password" required>
                </div>
                <div class="form-row flex items-center gap-8">
                    <input type="checkbox" name="remember" id="remember" value="1">
                    <label for="remember" class="tiny muted" style="margin:0">Мени эслаб қол</label>
                </div>
                <button class="btn btn-teal btn-block" type="submit">Кириш (ЕЦП — демо)</button>
            </form>

            <div class="demo-note">
                <b>Демо логинлар</b> (парол: <b>password</b>):<br>
                moderator@test.uz · officer@test.uz · workgroup@test.uz<br>
                deputy@test.uz · head@test.uz · lawyer@test.uz<br>
                compliance@test.uz · applicant@test.uz
            </div>
            <div class="mt-16 tiny"><a href="{{ route('landing') }}">← Лендинг саҳифага қайтиш</a></div>
        </div>
    </div>
</body>
</html>
