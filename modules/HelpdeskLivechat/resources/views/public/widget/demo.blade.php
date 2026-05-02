<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helpdesk Widget Demo · {{ config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .demo-container {
            max-width: 1100px;
            width: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .demo-header {
            background: linear-gradient(135deg, {{ $config['widgetColor'] ?? '#b10100' }} 0%, {{ $config['widgetColor'] ?? '#b10100' }}dd 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .demo-header h1 { font-size: 2.4rem; margin-bottom: 8px; }
        .demo-header p { font-size: 1.05rem; opacity: 0.95; }
        .demo-content { padding: 50px 40px; }
        .demo-section { margin-bottom: 36px; }
        .demo-section h2 { color: #2d3748; margin-bottom: 16px; }
        .demo-section p { color: #4a5568; line-height: 1.7; margin-bottom: 12px; }
        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 22px;
        }
        .config-item {
            background: #f7fafc;
            padding: 18px;
            border-radius: 8px;
            border-left: 4px solid {{ $config['widgetColor'] ?? '#b10100' }};
        }
        .config-item h3 {
            font-size: 0.85rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .config-item p { margin: 0; font-weight: 500; color: #2d3748; }
        .badge {
            display: inline-block;
            background: #48bb78;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 10px;
        }
        pre {
            background: #2d3748;
            color: #e2e8f0;
            padding: 18px;
            border-radius: 8px;
            overflow-x: auto;
            margin-top: 12px;
            font-size: 0.88rem;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="demo-header">
            <h1>
                Helpdesk Widget Demo
                @if($websiteToken)<span class="badge">Live</span>@endif
            </h1>
            <p>Multi-channel customer support widget</p>
        </div>

        <div class="demo-content">
            @if($websiteToken && $webWidget)
                <div class="demo-section">
                    <h2>Configuration</h2>
                    <p>Widget settings for token <code>{{ $websiteToken }}</code>:</p>
                    <div class="config-grid">
                        <div class="config-item">
                            <h3>Token</h3>
                            <p>{{ $websiteToken }}</p>
                        </div>
                        <div class="config-item">
                            <h3>Position</h3>
                            <p>{{ ucfirst($config['position'] ?? 'right') }}</p>
                        </div>
                        <div class="config-item">
                            <h3>Color</h3>
                            <p style="color: {{ $config['widgetColor'] ?? '#b10100' }}">{{ $config['widgetColor'] ?? '#b10100' }}</p>
                        </div>
                        <div class="config-item">
                            <h3>Pre-chat form</h3>
                            <p>{{ ($config['preChatFormEnabled'] ?? false) ? 'Enabled' : 'Disabled' }}</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="demo-section">
                    <h2>How to test this widget</h2>
                    <p>Append a website token to the URL: <code>{{ url('/pages/helpdesk-widget/YOUR_TOKEN') }}</code></p>
                </div>
            @endif

            <div class="demo-section">
                <h2>Install on your site</h2>
                <p>Paste this snippet before the closing <code>&lt;/body&gt;</code> tag of your website:</p>
                <pre><code>&lt;script async src="{{ url('/widget/helpdesk/script/'.($websiteToken ?? 'YOUR_TOKEN')) }}"&gt;&lt;/script&gt;</code></pre>
                <p style="margin-top: 18px; font-size: 0.95rem; color: #718096;">
                    The widget loader auto-injects <code>window.HELPDESK_WIDGET_CONFIG</code> with the tenant's color, welcome text, pre-chat form and offline message.
                </p>
            </div>
        </div>
    </div>

    @if($websiteToken)
        <script async src="{{ url('/widget/helpdesk/script/'.$websiteToken) }}"></script>
    @endif
</body>
</html>
