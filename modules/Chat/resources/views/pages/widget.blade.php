<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Widget Demo - {{ config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .demo-container {
            max-width: 1200px;
            width: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .demo-header {
            background: linear-gradient(135deg, {{ $config['widgetColor'] ?? '#1f93ff' }} 0%, {{ $config['widgetColor'] ?? '#1f93ff' }}dd 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .demo-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .demo-header p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        .demo-content {
            padding: 60px 40px;
        }

        .demo-section {
            margin-bottom: 50px;
        }

        .demo-section h2 {
            font-size: 1.8rem;
            color: #2d3748;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .demo-section p {
            font-size: 1.1rem;
            color: #4a5568;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .config-item {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid {{ $config['widgetColor'] ?? '#1f93ff' }};
        }

        .config-item h3 {
            font-size: 0.9rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .config-item p {
            font-size: 1.1rem;
            color: #2d3748;
            margin: 0;
            font-weight: 500;
        }

        .widget-instruction {
            background: #edf2f7;
            padding: 30px;
            border-radius: 8px;
            border: 2px dashed #cbd5e0;
        }

        .widget-instruction h3 {
            color: #2d3748;
            font-size: 1.3rem;
            margin-bottom: 15px;
        }

        .widget-instruction ol {
            margin-left: 20px;
            color: #4a5568;
            line-height: 1.8;
        }

        .widget-instruction li {
            margin-bottom: 10px;
            font-size: 1rem;
        }

        .cta-button {
            display: inline-block;
            background: {{ $config['widgetColor'] ?? '#1f93ff' }};
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 20px;
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .badge {
            display: inline-block;
            background: #48bb78;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 10px;
        }

        @media (max-width: 768px) {
            .demo-header h1 {
                font-size: 1.8rem;
            }

            .demo-content {
                padding: 40px 20px;
            }

            .config-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="demo-header">
            <h1>
                Chat Widget Demo
                @if($websiteToken)
                    <span class="badge">Live</span>
                @endif
            </h1>
            <p>Experience our powerful chat widget in action</p>
        </div>

        <div class="demo-content">
            @if($websiteToken && $webWidget)
                <div class="demo-section">
                    <h2>Widget Configuration</h2>
                    <p>This demo is using your live widget configuration. The widget should appear in the bottom-{{ $config['widgetPosition'] ?? 'right' }} corner of this page.</p>

                    <div class="config-grid">
                        <div class="config-item">
                            <h3>Widget Token</h3>
                            <p>{{ $websiteToken }}</p>
                        </div>
                        <div class="config-item">
                            <h3>Position</h3>
                            <p>{{ ucfirst($config['widgetPosition'] ?? 'right') }}</p>
                        </div>
                        <div class="config-item">
                            <h3>Color</h3>
                            <p style="color: {{ $config['widgetColor'] ?? '#1f93ff' }}">{{ $config['widgetColor'] ?? '#1f93ff' }}</p>
                        </div>
                        <div class="config-item">
                            <h3>Pre-chat Form</h3>
                            <p>{{ $config['preChatFormEnabled'] ? 'Enabled' : 'Disabled' }}</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="demo-section">
                    <h2>Welcome to the Widget Demo</h2>
                    <p>This is a generic demo page. To test your specific widget configuration, use the URL format:</p>
                    <p><code style="background: #edf2f7; padding: 8px 12px; border-radius: 4px; display: inline-block; margin: 10px 0;">{{ url('/pages/widget/YOUR_WEBSITE_TOKEN') }}</code></p>
                </div>
            @endif

            <div class="demo-section">
                <h2>How to Use the Widget</h2>
                <div class="widget-instruction">
                    <h3>Try it out!</h3>
                    <ol>
                        <li>Look for the chat button in the bottom-{{ $config['widgetPosition'] ?? 'right' }} corner of this page</li>
                        <li>Click the button to open the chat widget</li>
                        <li>Start a conversation by typing a message</li>
                        <li>Our team will respond to your message</li>
                    </ol>
                </div>
            </div>

            <div class="demo-section">
                <h2>Integrate on Your Website</h2>
                <p>Add this widget to your website by copying the code snippet below and pasting it before the closing <code>&lt;/body&gt;</code> tag:</p>

                <pre style="background: #2d3748; color: #e2e8f0; padding: 20px; border-radius: 8px; overflow-x: auto; margin-top: 15px; font-size: 0.9rem;"><code>&lt;script&gt;
  (function(w,d,s,o,f,js,fjs){
    w['ChatWidget']=o;w[o] = w[o] || function () { (w[o].q = w[o].q || []).push(arguments) };
    js = d.createElement(s), fjs = d.getElementsByTagName(s)[0];
    js.id = o; js.src = f; js.async = 1; fjs.parentNode.insertBefore(js, fjs);
  }(window, document, 'script', 'chatWidget', '{{ url('/widget.js') }}'));

  chatWidget('init', {
    websiteToken: '{{ $websiteToken ?? 'YOUR_WEBSITE_TOKEN' }}',
    baseUrl: '{{ url('/') }}'
  });
&lt;/script&gt;</code></pre>

                <a href="{{ url('/chats/chat/channels') }}" class="cta-button">
                    Manage Your Widgets
                </a>
            </div>
        </div>
    </div>

    <!-- Widget Script -->
    @if($websiteToken)
    <script>
        (function(w,d,s,o,f,js,fjs){
            w['ChatWidget']=o;w[o] = w[o] || function () { (w[o].q = w[o].q || []).push(arguments) };
            js = d.createElement(s), fjs = d.getElementsByTagName(s)[0];
            js.id = o; js.src = f; js.async = 1; fjs.parentNode.insertBefore(js, fjs);
        }(window, document, 'script', 'chatWidget', '{{ url('/widget.js') }}'));

        chatWidget('init', {
            websiteToken: '{{ $websiteToken }}',
            baseUrl: '{{ url('/') }}',
            @if(isset($config['widgetColor']))
            widgetColor: '{{ $config['widgetColor'] }}',
            @endif
            @if(isset($config['widgetPosition']))
            position: '{{ $config['widgetPosition'] }}',
            @endif
            @if(isset($config['welcomeTitle']))
            welcomeTitle: '{{ $config['welcomeTitle'] }}',
            @endif
            @if(isset($config['welcomeTagline']))
            welcomeTagline: '{{ $config['welcomeTagline'] }}',
            @endif
        });
    </script>
    @endif
</body>
</html>
