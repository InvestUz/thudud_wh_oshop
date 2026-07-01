<!DOCTYPE html>
<html lang="uz-Cyrl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Шартнома лойиҳаси — {{ $application->application_number }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            background: #e9edf0;
            font-family: 'Times New Roman', Georgia, serif;
            color: #111;
            padding: 24px 12px;
        }
        .cd-paper {
            background: #fff;
            max-width: 794px; /* A4 eni ~21cm @96dpi */
            margin: 0 auto;
            padding: 48px 56px 56px;
            box-shadow: 0 6px 24px rgba(15,23,42,.18);
            border-radius: 4px;
            font-size: 15px;
            line-height: 1.55;
        }
        .cd-badge {
            display: inline-block;
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            padding: 4px 10px;
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin-bottom: 18px;
        }
        .cd-h {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            margin: 12px 0 4px;
            line-height: 1.4;
        }
        .cd-meta {
            font-weight: bold;
            white-space: pre-wrap;
            margin: 14px 0;
        }
        .cd-p {
            text-align: justify;
            text-indent: 2.2em;
            margin: 0 0 6px;
        }
        .cd-sig {
            display: flex;
            gap: 32px;
            margin-top: 24px;
        }
        .cd-sig-col { flex: 1; text-align: center; }
        .cd-sig-row { min-height: 1.6em; }
        .cd-sig-title { font-weight: bold; margin-bottom: 4px; }
        /* Имзо QR (имзоланганда — устун пастида) */
        .cd-sig-qr { margin-top: 8px; }
        .cd-sig-qr svg { width: 96px; height: 96px; display: block; margin: 0 auto; }
        .cd-appendix { break-before:page; page-break-before:always; padding-top:32px; text-align:center; }
        .cd-appendix h3 { margin:12px 0; line-height:1.25; }
        .cd-appendix table { width:100%; margin:18px 0 54px; border-collapse:collapse; }
        .cd-appendix th { border:1px solid #111; padding:7px 10px; }
        .cd-appendix th:first-child { width:10%; }.cd-appendix th:nth-child(2){width:28%}
        .cd-appendix-sign { display:grid; grid-template-columns:1fr 1fr; border:1px solid #999; }
        .cd-appendix-sign>div { min-height:150px; padding:10px; border-right:1px solid #999; }
        .cd-appendix-sign>div:last-child { border-right:0; }
        @media print {
            body { background: #fff; padding: 0; }
            .cd-paper { box-shadow: none; border-radius: 0; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="cd-paper">
        <div class="cd-badge">Лойиҳа — раҳбарият тасдиқлагандан сўнг расмий шартнома кучга киради</div>
        {!! $documentHtml !!}
    </div>
</body>
</html>
