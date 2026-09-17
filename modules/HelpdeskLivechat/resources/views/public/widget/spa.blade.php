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

    <link rel="stylesheet" href="{{ asset('modules/helpdesklivechat/css/widget-spa-shell.css') }}?v={{ filemtime(public_path('modules/helpdesklivechat/css/widget-spa-shell.css')) }}">
</head>
<body>
    {{-- Always inject config: even in preview (without token) the widget needs reverbHost/Port and baseUrl. --}}
    {{-- $widgetConfig is built server-side in WidgetController::index (connectable Reverb host). --}}
    <script>window.HELPDESK_WIDGET_CONFIG = {!! json_encode($widgetConfig ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};</script>

    <div id="widget-root" data-launcher="false" data-preview="{{ ($isPreview ?? false) ? 'true' : 'false' }}" data-inline="{{ ($isPreview ?? false) ? 'false' : 'true' }}"></div>

    @if(file_exists(public_path('build-helpdesklivechat/widget/main.js')))
        <script type="module" src="{{ asset('build-helpdesklivechat/widget/main.js') }}"></script>
    @else
        <div class="widget-not-built">
            <strong>Helpdesk widget bundle not built yet.</strong>
            <p class="widget-not-built-hint">Run <code>cd modules/HelpdeskLivechat && npm install && npm run widget:build</code> to compile the React bundle.</p>
        </div>
    @endif

    {{-- Engagement bridge: SDK + listener to open chat on trigger:fired --}}
    @if(($engagement_active ?? false) && $websiteToken)
        <script>window.HelpdeskEngagementBridgeConfig = {!! json_encode(['token' => $websiteToken, 'apiUrl' => url('/')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};</script>
        <script src="{{ asset('modules/helpdesklivechat/js/widget-engagement-bridge.js') }}?v={{ filemtime(public_path('modules/helpdesklivechat/js/widget-engagement-bridge.js')) }}"></script>
        <script async src="{{ $engagement_sdk_url }}"></script>
    @endif
</body>
</html>
