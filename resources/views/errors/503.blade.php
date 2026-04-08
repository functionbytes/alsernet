@php
    try {
        $errorPageId = setting('error-page-503');
        $errorPage = $errorPageId
            ? \Modules\Page\Models\Page::find($errorPageId)
            : \Modules\Page\Models\Page::findByPageType('error_503');
    } catch (\Throwable) {
        $errorPage = null;
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        @if ($errorPage?->title)
            {{ $errorPage->title }}
        @else
            @php
                try { echo htmlspecialchars(setting('site_title', config('app.name')), ENT_QUOTES, 'UTF-8'); } catch (\Throwable) { echo htmlspecialchars(config('app.name', 'Alsernet'), ENT_QUOTES, 'UTF-8'); }
            @endphp
            &mdash; Mantenimiento
        @endif
    </title>
    <link rel="stylesheet" href="{{ asset('modules/Theme/theme/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('modules/Theme/theme/libs/fontawesome/fontawesome.css') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 48px 40px;
            max-width: 520px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .08);
        }
        .error-card { background: #fff; border-radius: 12px; padding: 3rem 2.5rem; text-align: center; max-width: 480px; width: 90%; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .error-code { font-size: 5rem; font-weight: 800; color: #90bb13; line-height: 1; }
        .error-icon { font-size: 4rem; color: #90bb13; margin-bottom: 1rem; }
        .icon {
            font-size: 56px;
            margin-bottom: 20px;
        }
        .badge {
            display: inline-block;
            background: #90bb13;
            color: #fff;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .4px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 26px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 12px;
            line-height: 1.3;
        }
        p {
            color: #6c757d;
            line-height: 1.7;
            margin-bottom: 28px;
            font-size: 15px;
        }
        .contact {
            font-size: 13px;
            color: #adb5bd;
            border-top: 1px solid #f0f0f0;
            padding-top: 20px;
            margin-top: 4px;
        }
        .contact a {
            color: #90bb13;
            text-decoration: none;
            font-weight: 500;
        }
        .contact a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    @if ($errorPage?->content)
        <div class="error-card">
            {!! $errorPage->content !!}
        </div>
    @else
        <div class="card">
            <div class="icon">&#128295;</div>
            <div class="badge">En mantenimiento</div>

            <h1>
                @php
                    try { echo htmlspecialchars(setting('maintenance_title', 'Volvemos pronto'), ENT_QUOTES, 'UTF-8'); } catch (\Throwable) { echo 'Volvemos pronto'; }
                @endphp
            </h1>

            <p>
                @php
                    try {
                        $msg = setting('maintenance_message', 'Estamos realizando mejoras en el sistema. Por favor, vuelve en unos minutos.');
                        echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
                    } catch (\Throwable) {
                        echo 'Estamos realizando mejoras en el sistema. Por favor, vuelve en unos minutos.';
                    }
                @endphp
            </p>

            <div class="contact">
                ¿Necesitas ayuda?
                <a href="mailto:@php
                    try { echo htmlspecialchars(setting('admin_email', config('mail.from.address', '')), ENT_QUOTES, 'UTF-8'); } catch (\Throwable) { echo htmlspecialchars(config('mail.from.address', ''), ENT_QUOTES, 'UTF-8'); }
                @endphp">Contáctanos</a>
            </div>
        </div>
    @endif
</body>
</html>
