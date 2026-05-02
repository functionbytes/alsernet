<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — Helpdesk</title>

    @if(file_exists(public_path('build-helpdesklivechat/widget/main.css')))
        <link rel="stylesheet" href="{{ asset('build-helpdesklivechat/widget/main.css') }}">
    @endif

    <style>
        html, body { margin: 0; padding: 0; height: 100%; }
        body { background: #f8f9fa; }
        #widget-root { width: 100%; height: 100vh; }
        .widget-not-built {
            max-width: 480px;
            margin: 80px auto;
            padding: 24px;
            border: 1px solid #f5c2c7;
            border-radius: 8px;
            background: #f8d7da;
            color: #842029;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .widget-not-built code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    @php
        $widgetConfig = array_merge($config ?? [], [
            'reverbKey' => env('REVERB_APP_KEY', 'local-key'),
            'reverbHost' => env('REVERB_HOST', request()->getHost()),
            'reverbPort' => (int) env('REVERB_PORT', 8090),
            'reverbScheme' => env('REVERB_SCHEME', request()->isSecure() ? 'https' : 'http'),
            'channelPrefix' => 'helpdesk-widget-conversation',
            'eventName' => '.message.received',
        ]);
    @endphp
    @if($websiteToken && !empty($config))
        <script>window.HELPDESK_WIDGET_CONFIG = {!! json_encode($widgetConfig) !!};</script>
    @endif

    <div id="widget-root" data-launcher="false" data-preview="{{ ($isPreview ?? false) ? 'true' : 'false' }}" data-inline="{{ ($isPreview ?? false) ? 'false' : 'true' }}"></div>

    @if(file_exists(public_path('build-helpdesklivechat/widget/main.js')))
        <script type="module" src="{{ asset('build-helpdesklivechat/widget/main.js') }}"></script>
    @else
        <div class="widget-not-built">
            <strong>Helpdesk widget bundle not built yet.</strong>
            <p style="margin: 8px 0 0;">Run <code>cd modules/HelpdeskLivechat && npm install && npm run widget:build</code> to compile the React bundle.</p>
        </div>
    @endif

    {{-- Engagement bridge: SDK + listener to open chat on trigger:fired --}}
    @if(($engagement_active ?? false) && $websiteToken)
        <script>
        (function (w, d) {
            w.chat = w.chat || function () { (w.chat.q = w.chat.q || []).push(arguments); };
        })(window, document);
        </script>
        <script async src="{{ $engagement_sdk_url }}"></script>
        <script>
        window.chat('init', {
            token: @json($websiteToken),
            apiUrl: @json(url('/')),
            consent: true,
        });
        window.chat('on', 'trigger:fired', function (e) {
            if (e && e.action && e.action.type === 'open_chat' && window.HelpdeskWidget && typeof window.HelpdeskWidget.open === 'function') {
                window.HelpdeskWidget.open();
            }
        });
        </script>
    @endif
</body>
</html>
