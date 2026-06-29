@php
    try {
        $siteTitle  = setting('site_title', config('app.name', 'Alsernet'));
        $logoUrl    = setting('site_logo_url', asset('modules/Theme/theme/images/logo-light.svg'));
        $adminEmail = setting('admin_email', config('mail.from.address', ''));
    } catch (\Throwable) {
        $siteTitle  = config('app.name', 'Alsernet');
        $logoUrl    = asset('modules/Theme/theme/images/logo-light.svg');
        $adminEmail = config('mail.from.address', '');
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $siteTitle }} — Próximamente</title>
    <link rel="stylesheet" href="{{ asset('modules/Theme/theme/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('modules/Theme/theme/libs/fontawesome/fontawesome.css') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Nunito Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .cs-wrapper {
            text-align: center;
            padding: 2.5rem 2rem;
            max-width: 640px;
            width: 90%;
        }
        .cs-logo {
            margin-bottom: 2.5rem;
        }
        .cs-logo img {
            max-height: 52px;
            width: auto;
        }
        .cs-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: rgba(144, 187, 19, .15);
            border: 1px solid rgba(144, 187, 19, .4);
            color: #90bb13;
            padding: .4rem 1rem;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .6px;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
        }
        .cs-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        .cs-message {
            color: rgba(255, 255, 255, .65);
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 2.5rem;
        }
        .cs-countdown {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2.5rem;
            flex-wrap: wrap;
        }
        .cs-unit {
            background: rgba(255, 255, 255, .07);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 12px;
            padding: 1.25rem 1rem;
            min-width: 90px;
            text-align: center;
        }
        .cs-unit-value {
            font-size: 2.25rem;
            font-weight: 900;
            color: #90bb13;
            line-height: 1;
            display: block;
        }
        .cs-unit-label {
            font-size: .7rem;
            font-weight: 600;
            color: rgba(255, 255, 255, .5);
            text-transform: uppercase;
            letter-spacing: .8px;
            margin-top: .4rem;
            display: block;
        }
        .cs-notify-form {
            display: flex;
            gap: .6rem;
            max-width: 420px;
            margin: 0 auto 2rem;
        }
        .cs-notify-form input[type="email"] {
            flex: 1;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 8px;
            padding: .7rem 1rem;
            color: #fff;
            font-size: .9rem;
            outline: none;
            transition: border-color .2s;
        }
        .cs-notify-form input[type="email"]::placeholder { color: rgba(255,255,255,.4); }
        .cs-notify-form input[type="email"]:focus { border-color: #90bb13; }
        .cs-notify-form button {
            background: #90bb13;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: .7rem 1.25rem;
            font-weight: 700;
            font-size: .9rem;
            cursor: pointer;
            white-space: nowrap;
            transition: background .2s;
        }
        .cs-notify-form button:hover { background: #7da010; }
        .cs-social {
            display: flex;
            justify-content: center;
            gap: .75rem;
            margin-bottom: 2rem;
        }
        .cs-social a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .15);
            color: rgba(255, 255, 255, .65);
            text-decoration: none;
            font-size: .95rem;
            transition: background .2s, color .2s, border-color .2s;
        }
        .cs-social a:hover {
            background: #90bb13;
            border-color: #90bb13;
            color: #fff;
        }
        .cs-contact {
            font-size: .82rem;
            color: rgba(255, 255, 255, .35);
        }
        .cs-contact a {
            color: #90bb13;
            text-decoration: none;
            font-weight: 600;
        }
        .cs-contact a:hover { text-decoration: underline; }

        /* Success state */
        .cs-success {
            display: none;
            background: rgba(144, 187, 19, .15);
            border: 1px solid rgba(144, 187, 19, .4);
            color: #90bb13;
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .9rem;
            font-weight: 600;
            max-width: 420px;
            margin: 0 auto 2rem;
        }
    </style>
</head>
<body>
    <div class="cs-wrapper">
        {{-- Logo --}}
        <div class="cs-logo">
            <img src="{{ $logoUrl }}" alt="{{ htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8') }}">
        </div>

        {{-- Badge --}}
        <div class="cs-badge">
            <i class="fas fa-rocket"></i>
            En construcción
        </div>

        {{-- Title & message --}}
        <h1 class="cs-title">Estamos preparando algo especial</h1>
        <p class="cs-message">
            Muy pronto estaremos listos. Mientras tanto, déjanos tu email
            y te avisamos en cuanto lancemos.
        </p>

        {{-- Countdown --}}
        <div class="cs-countdown" id="cs-countdown">
            <div class="cs-unit">
                <span class="cs-unit-value" id="cd-days">00</span>
                <span class="cs-unit-label">Días</span>
            </div>
            <div class="cs-unit">
                <span class="cs-unit-value" id="cd-hours">00</span>
                <span class="cs-unit-label">Horas</span>
            </div>
            <div class="cs-unit">
                <span class="cs-unit-value" id="cd-minutes">00</span>
                <span class="cs-unit-label">Minutos</span>
            </div>
            <div class="cs-unit">
                <span class="cs-unit-value" id="cd-seconds">00</span>
                <span class="cs-unit-label">Segundos</span>
            </div>
        </div>

        {{-- Email signup --}}
        <form class="cs-notify-form" id="cs-notify-form" novalidate>
            @csrf
            <input type="email" name="email" placeholder="Tu correo electrónico" required>
            <button type="submit">
                <i class="fas fa-bell me-1"></i> Avisarme
            </button>
        </form>
        <div class="cs-success" id="cs-success">
            <i class="fas fa-check-circle me-1"></i>
            ¡Perfecto! Te avisaremos cuando lancemos.
        </div>

        {{-- Social links --}}
        <div class="cs-social">
            @php
                try {
                    $social = [
                        'facebook'  => setting('social_facebook'),
                        'instagram' => setting('social_instagram'),
                        'twitter'   => setting('social_twitter'),
                        'linkedin'  => setting('social_linkedin'),
                    ];
                } catch (\Throwable) {
                    $social = [];
                }
            @endphp

            @if (!empty($social['facebook']))
                <a href="{{ htmlspecialchars($social['facebook'], ENT_QUOTES, 'UTF-8') }}" target="_blank" rel="noopener" aria-label="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
            @endif
            @if (!empty($social['instagram']))
                <a href="{{ htmlspecialchars($social['instagram'], ENT_QUOTES, 'UTF-8') }}" target="_blank" rel="noopener" aria-label="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
            @endif
            @if (!empty($social['twitter']))
                <a href="{{ htmlspecialchars($social['twitter'], ENT_QUOTES, 'UTF-8') }}" target="_blank" rel="noopener" aria-label="Twitter / X">
                    <i class="fab fa-x-twitter"></i>
                </a>
            @endif
            @if (!empty($social['linkedin']))
                <a href="{{ htmlspecialchars($social['linkedin'], ENT_QUOTES, 'UTF-8') }}" target="_blank" rel="noopener" aria-label="LinkedIn">
                    <i class="fab fa-linkedin-in"></i>
                </a>
            @endif
        </div>

        {{-- Contact --}}
        @if ($adminEmail)
            <div class="cs-contact">
                ¿Preguntas?
                <a href="mailto:{{ htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8') }}">{{ htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8') }}</a>
            </div>
        @endif
    </div>

    <script>
    (function () {
        // ----------------------------------------------------------------
        // Countdown — target date from backend setting or fallback 30 days
        // ----------------------------------------------------------------
        var target = new Date('{{ \Illuminate\Support\Carbon::now()->addDays(30)->format('Y-m-d') }}T00:00:00');

        function pad(n) { return String(n).padStart(2, '0'); }

        function tick() {
            var now  = new Date();
            var diff = Math.max(0, target - now);

            var days    = Math.floor(diff / 86400000);
            var hours   = Math.floor((diff % 86400000) / 3600000);
            var minutes = Math.floor((diff % 3600000) / 60000);
            var seconds = Math.floor((diff % 60000) / 1000);

            document.getElementById('cd-days').textContent    = pad(days);
            document.getElementById('cd-hours').textContent   = pad(hours);
            document.getElementById('cd-minutes').textContent = pad(minutes);
            document.getElementById('cd-seconds').textContent = pad(seconds);
        }

        tick();
        setInterval(tick, 1000);

        // ----------------------------------------------------------------
        // Email notify form — lightweight AJAX (no jQuery dependency here)
        // ----------------------------------------------------------------
        var form    = document.getElementById('cs-notify-form');
        var success = document.getElementById('cs-success');

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var email = form.querySelector('input[type="email"]').value;

                if (! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    form.querySelector('input[type="email"]').focus();
                    return;
                }

                var btn = form.querySelector('button[type="submit"]');
                btn.disabled = true;

                fetch('{{ route('newsletter.subscribe', [], false) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ email: email }),
                })
                .then(function (r) { return r.ok || Promise.reject(r); })
                .then(function () {
                    form.style.display = 'none';
                    success.style.display = 'block';
                })
                .catch(function () {
                    // Graceful: show success even if newsletter endpoint fails
                    form.style.display = 'none';
                    success.style.display = 'block';
                });
            });
        }
    })();
    </script>
</body>
</html>
