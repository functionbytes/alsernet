@php
    try {
        $errorPageId = setting('error-page-404');
        $errorPage = $errorPageId
            ? \Modules\Page\Models\Page::find($errorPageId)
            : \Modules\Page\Models\Page::findByPageType('error_404');
    } catch (\Throwable) {
        $errorPage = null;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $errorPage?->title ?? '404 — ' . __('errors.404.title') }}</title>
    <link rel="stylesheet" href="{{ asset('modules/Theme/theme/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('modules/Theme/theme/libs/fontawesome/fontawesome.css') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Nunito Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f6f8;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .error-wrapper {
            background: #fff;
            border-radius: 16px;
            padding: 3.5rem 3rem;
            text-align: center;
            max-width: 520px;
            width: 90%;
            box-shadow: 0 4px 32px rgba(0, 0, 0, .08);
        }
        .error-code {
            font-size: 7rem;
            font-weight: 900;
            color: #b10100;
            line-height: 1;
            letter-spacing: -4px;
        }
        .error-divider {
            width: 56px;
            height: 4px;
            background: #b10100;
            border-radius: 2px;
            margin: 1.25rem auto;
        }
        .error-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: .75rem;
        }
        .error-message {
            color: #6c757d;
            font-size: 1rem;
            line-height: 1.7;
            margin-bottom: 2rem;
        }
        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: #b10100;
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            padding: .65rem 1.5rem;
            text-decoration: none;
            font-size: .95rem;
            transition: background .2s;
        }
        .btn-home:hover { background: #7da010; color: #fff; }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: transparent;
            color: #6c757d;
            font-weight: 500;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: .65rem 1.5rem;
            text-decoration: none;
            font-size: .95rem;
            cursor: pointer;
            transition: border-color .2s, color .2s;
            margin-left: .5rem;
        }
        .btn-back:hover { border-color: #b10100; color: #b10100; }
    </style>
</head>
<body>
    <div class="error-wrapper">
        @if ($errorPage?->content)
            {!! $errorPage->content !!}
        @else
            <div class="error-code">404</div>
            <div class="error-divider"></div>
            <h1 class="error-title">{{ __('errors.404.title') }}</h1>
            <p class="error-message">{{ __('errors.404.message') }}</p>
            <a href="{{ url('/') }}" class="btn-home">
                <i class="fas fa-house"></i>
                {{ __('errors.404.home') }}
            </a>
            <button onclick="history.back()" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                {{ __('errors.404.back') }}
            </button>
        @endif
    </div>
</body>
</html>
