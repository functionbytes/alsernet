@php
    try {
        $errorPageId = setting('error-page-503');
        $errorPage = $errorPageId
            ? \Modules\Page\Models\Page::find($errorPageId)
            : \Modules\Page\Models\Page::findByPageType('error_503');
        $siteTitle  = setting('site_title', config('app.name', 'Alsernet'));
        $maintTitle = setting('maintenance_title', __('errors.503.title'));
        $maintMsg   = setting('maintenance_message', __('errors.503.message'));
        $adminEmail = setting('admin_email', config('mail.from.address', ''));
    } catch (\Throwable) {
        $errorPage  = null;
        $siteTitle  = config('app.name', 'Alsernet');
        $maintTitle = __('errors.503.title');
        $maintMsg   = __('errors.503.message');
        $adminEmail = config('mail.from.address', '');
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $errorPage?->title ?? ($siteTitle . ' — ' . __('errors.503.title')) }}</title>
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
        .maintenance-wrapper {
            background: #fff;
            border-radius: 16px;
            padding: 3.5rem 3rem;
            max-width: 520px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 32px rgba(0, 0, 0, .08);
        }
        .maintenance-icon {
            font-size: 3.5rem;
            color: #90bb13;
            margin-bottom: 1.25rem;
        }
        .maintenance-badge {
            display: inline-block;
            background: #90bb13;
            color: #fff;
            padding: .35rem 1.1rem;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
        }
        .maintenance-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: .75rem;
            line-height: 1.3;
        }
        .maintenance-message {
            color: #6c757d;
            font-size: 1rem;
            line-height: 1.7;
            margin-bottom: 2rem;
        }
        .maintenance-contact {
            font-size: .85rem;
            color: #adb5bd;
            border-top: 1px solid #f0f0f0;
            padding-top: 1.25rem;
            margin-top: .25rem;
        }
        .maintenance-contact a {
            color: #90bb13;
            text-decoration: none;
            font-weight: 600;
        }
        .maintenance-contact a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    @if ($errorPage?->content)
        <div class="maintenance-wrapper">
            {!! $errorPage->content !!}
        </div>
    @else
        <div class="maintenance-wrapper">
            <div class="maintenance-icon">
                <i class="fas fa-wrench"></i>
            </div>
            <div class="maintenance-badge">{{ __('errors.503.title') }}</div>
            <h1 class="maintenance-title">{{ htmlspecialchars($maintTitle, ENT_QUOTES, 'UTF-8') }}</h1>
            <p class="maintenance-message">{{ htmlspecialchars($maintMsg, ENT_QUOTES, 'UTF-8') }}</p>
            @if ($adminEmail)
                <div class="maintenance-contact">
                    {{ __('errors.503.contact') }}
                    <a href="mailto:{{ htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8') }}">
                        {{ __('errors.503.contact_link') }}
                    </a>
                </div>
            @endif
        </div>
    @endif
</body>
</html>
