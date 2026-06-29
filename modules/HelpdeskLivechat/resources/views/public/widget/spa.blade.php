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
            'reverbKey' => config('broadcasting.connections.reverb.key', 'local-key'),
            'reverbHost' => config('broadcasting.connections.reverb.options.host', request()->getHost()),
            'reverbPort' => (int) config('broadcasting.connections.reverb.options.port', 8080),
            'reverbScheme' => config('broadcasting.connections.reverb.options.scheme', request()->isSecure() ? 'https' : 'http'),
            'channelPrefix' => 'helpdesk-widget-conversation',
            'eventName' => '.message.received',
            'baseUrl' => url('/'),
        ]);
    @endphp
    {{-- Always inject config: even in preview (without token) the widget needs reverbHost/Port and baseUrl. --}}
    <script>window.HELPDESK_WIDGET_CONFIG = {!! json_encode($widgetConfig) !!};</script>

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
            var action = (e && e.action) ? e.action : {};
            var w = window.HelpdeskWidget;
            if (!w) return;

            switch (action.type) {
                case 'open_chat':
                    w.open();
                    break;
                case 'show_message_in_chat':
                    w.botMessage(action.text || action.message || '');
                    w.open();
                    break;
                case 'prefill_form':
                    w.prefill(action.fields || {});
                    w.open();
                    break;
                case 'request_rating':
                    w.requestRating();
                    w.open();
                    break;
                case 'inject_recommendations':
                    w.showRecommendations(action.products || []);
                    w.open();
                    break;
            }
        });
        </script>
    @endif
</body>
</html>
