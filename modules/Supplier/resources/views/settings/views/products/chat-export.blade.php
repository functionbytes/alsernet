<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>{{ $chat->title ?? 'Conversación' }} — {{ $chat->product->code }}</title>
<style>
body { font-family: system-ui, -apple-system, sans-serif; max-width: 800px; margin: 2rem auto; padding: 0 1rem; color: #2a3547; line-height: 1.55; }
h1 { font-size: 1.75rem; margin-bottom: 0.5rem; }
.meta { background: #f5f6f8; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; font-size: 0.9rem; }
.meta dl { display: grid; grid-template-columns: auto 1fr; gap: 0.25rem 1rem; margin: 0; }
.meta dt { font-weight: 600; color: #5a6a85; }
.msg { border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1rem; }
.msg.user { background: #90bb13; color: #fff; margin-left: 20%; }
.msg.assistant { background: #fff; border: 1px solid #e5e7eb; }
.msg-role { font-size: 0.8rem; font-weight: 700; opacity: 0.85; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px; }
.msg-meta { font-size: 0.75rem; color: #5a6a85; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(0,0,0,0.06); }
.msg.user .msg-meta { border-top-color: rgba(255,255,255,0.3); color: rgba(255,255,255,0.85); }
.sources { font-size: 0.8rem; margin-top: 0.5rem; padding: 0.5rem; background: rgba(0,0,0,0.03); border-radius: 6px; }
.sources a { color: #0c7a64; word-break: break-all; }
table { border-collapse: collapse; width: 100%; margin: 0.5rem 0; }
th, td { padding: 0.35rem 0.6rem; border: 1px solid #e5e7eb; font-size: 0.9rem; }
th { background: #f5f6f8; }
code { background: rgba(0,0,0,0.06); padding: 0.1rem 0.35rem; border-radius: 4px; font-size: 0.85em; }
pre { background: #000; color: #fff; padding: 0.75rem; border-radius: 6px; overflow-x: auto; }
</style>
</head>
<body>
<h1>{{ $chat->title ?? 'Conversación' }}</h1>
<div class="meta">
<dl>
<dt>Producto:</dt><dd>{{ $chat->product->name }} ({{ $chat->product->code }})</dd>
<dt>Proveedor:</dt><dd>{{ $chat->product->supplier?->label ?? '-' }}</dd>
<dt>Modelo IA:</dt><dd>{{ $chat->model }}</dd>
<dt>Fecha:</dt><dd>{{ $chat->created_at->format('d/m/Y H:i') }}</dd>
<dt>Coste total:</dt><dd>${{ number_format($chat->total_cost, 6) }}</dd>
<dt>Tokens:</dt><dd>{{ number_format($chat->total_tokens) }}</dd>
</dl>
</div>

@foreach($chat->conversation as $msg)
    <div class="msg {{ $msg->role }}">
        <div class="msg-role">{{ $msg->role === 'user' ? '👤 Usuario' : '🤖 Asistente' }}</div>
        <div>{!! $msg->role === 'user' ? nl2br(e($msg->content)) : \Modules\Supplier\Helpers\HtmlSanitizer::clean($msg->content) !!}</div>
        @if(! empty($msg->sources))
            <div class="sources">
                <strong>Fuentes:</strong>
                <ul style="margin: 0.25rem 0 0 1rem;">
                    @foreach($msg->sources as $src)
                        <li><a href="{{ $src['url'] ?? '#' }}">{{ $src['title'] ?? $src['url'] ?? 'Fuente' }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="msg-meta">
            {{ $msg->created_at->format('d/m/Y H:i') }}
            @if($msg->model) · {{ $msg->model }} @endif
            @if($msg->cost > 0) · ${{ number_format($msg->cost, 6) }} @endif
            @if($msg->total_tokens > 0) · {{ number_format($msg->total_tokens) }} tokens @endif
        </div>
    </div>
@endforeach
</body>
</html>
