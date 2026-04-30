<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: {{ $campaign->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f7fa;
            padding: 0;
            margin: 0;
        }

        .preview-header {
            background: #fff;
            border-bottom: 2px solid #e0e6ed;
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .preview-header .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .preview-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
        }

        .preview-title small {
            display: block;
            font-size: 0.875rem;
            font-weight: 400;
            color: #718096;
            margin-top: 0.25rem;
        }

        .btn-close-preview {
            background: #e0e6ed;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            color: #4a5568;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-close-preview:hover {
            background: #cbd5e0;
        }

        .preview-info {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin: 1rem 2rem;
            border-radius: 0.375rem;
            max-width: 1200px;
            margin: 1rem auto;
        }

        .preview-info-title {
            font-weight: 600;
            color: #856404;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }

        .preview-info-title i {
            margin-right: 0.5rem;
        }

        .preview-info-text {
            font-size: 0.875rem;
            color: #856404;
            margin: 0;
        }

        .email-container {
            max-width: 1200px;
            margin: 2rem auto;
            background: #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .email-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e0e6ed;
            padding: 1.5rem;
        }

        .email-subject {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .email-meta {
            font-size: 0.875rem;
            color: #718096;
        }

        .email-meta div {
            margin-bottom: 0.25rem;
        }

        .email-body {
            padding: 0;
            background: #fff;
        }

        .email-body iframe {
            width: 100%;
            min-height: 600px;
            border: none;
            display: block;
        }

        .device-selector {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }

        .device-btn {
            background: #e0e6ed;
            border: 2px solid transparent;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            color: #4a5568;
            transition: all 0.2s;
        }

        .device-btn:hover {
            background: #cbd5e0;
        }

        .device-btn.active {
            background: #b10100;
            color: #fff;
            border-color: #7a9f11;
        }

        @media (max-width: 768px) {
            .preview-header .container {
                flex-direction: column;
                gap: 1rem;
            }

            .device-selector {
                width: 100%;
                justify-content: center;
            }

            .email-container {
                margin: 1rem;
            }
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="preview-header">
        <div class="container">
            <div class="preview-title">
                Preview de Campaign
                <small>{{ $campaign->name }}</small>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div class="device-selector">
                    <button class="device-btn active" onclick="setDevice('desktop')">
                        🖥️ Desktop
                    </button>
                    <button class="device-btn" onclick="setDevice('mobile')">
                        📱 Mobile
                    </button>
                </div>
                <a href="{{ route('managers.mailrelay.campaigns.show', $campaign->id) }}" class="btn-close-preview">
                    ← Volver
                </a>
            </div>
        </div>
    </div>

    {{-- Info Banner --}}
    <div class="preview-info">
        <div class="preview-info-title">
            ℹ️ Vista previa con datos de ejemplo
        </div>
        <p class="preview-info-text">
            Esta es una vista previa del email usando variables de ejemplo. El email real se personalizará para cada destinatario.
        </p>
    </div>

    {{-- Email Container --}}
    <div class="email-container" id="emailContainer">
        <div class="email-header">
            <div class="email-subject">{{ $campaign->subject }}</div>
            <div class="email-meta">
                <div>
                    <strong>De:</strong>
                    @if($campaign->mailProvider)
                        {{ $campaign->mailProvider->credentials['from_name'] ?? 'Sistema' }}
                        &lt;{{ $campaign->mailProvider->credentials['from_email'] ?? 'noreply@example.com' }}&gt;
                    @endif
                </div>
                <div>
                    <strong>Para:</strong> test@example.com
                </div>
                @if($campaign->mailerTemplate)
                    <div>
                        <strong>Plantilla:</strong> {{ $campaign->mailerTemplate->name }}
                    </div>
                @endif
                @if($campaign->mailProvider)
                    <div>
                        <strong>Provider:</strong> {{ $campaign->mailProvider->name }} ({{ $campaign->mailProvider->driver }})
                    </div>
                @endif
            </div>
        </div>
        <div class="email-body">
            <iframe id="emailFrame" srcdoc="{{ htmlspecialchars($html) }}"></iframe>
        </div>
    </div>

    <script>
        function setDevice(device) {
            const container = document.getElementById('emailContainer');
            const btns = document.querySelectorAll('.device-btn');

            // Update active button
            btns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            // Update container width
            if (device === 'mobile') {
                container.style.maxWidth = '375px';
                container.style.margin = '2rem auto';
            } else {
                container.style.maxWidth = '1200px';
                container.style.margin = '2rem auto';
            }
        }

        // Auto-resize iframe
        const iframe = document.getElementById('emailFrame');
        iframe.onload = function() {
            try {
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                const height = Math.max(
                    iframeDoc.body.scrollHeight,
                    iframeDoc.documentElement.scrollHeight,
                    600
                );
                iframe.style.height = height + 'px';
            } catch (e) {
                console.error('Cannot access iframe content:', e);
                iframe.style.height = '600px';
            }
        };
    </script>
</body>
</html>
