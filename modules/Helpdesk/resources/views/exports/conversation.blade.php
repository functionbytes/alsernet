<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #222; }
    h1 { font-size: 16px; margin: 0 0 4px; }
    .meta { color: #666; font-size: 10px; margin-bottom: 16px; border-bottom: 2px solid #90bb13; padding-bottom: 8px; }
    .msg { border-bottom: 1px solid #eee; padding: 7px 0; page-break-inside: avoid; }
    .msg.internal { background: #fffbe6; padding-left: 6px; }
    .who { font-weight: bold; font-size: 10px; }
    .when { color: #999; font-size: 9px; margin-left: 6px; }
    .tag-internal { color: #b45309; font-size: 9px; margin-left: 6px; }
    .body { margin-top: 3px; }
    .extra { font-size: 9px; color: #888; margin-top: 2px; }
</style>
</head>
<body>
    <h1>{{ $conversation->subject ?: 'Conversación #'.$conversation->id }}</h1>
    @if($includeHeader ?? true)
    <div class="meta">
        Cliente: {{ $conversation->customer?->name ?: '—' }}
        @if($conversation->customer?->email) ({{ $conversation->customer->email }}) @endif
        &middot; Canal: {{ $conversation->channel }}
        &middot; Exportado: {{ $generatedAt }}
    </div>
    @endif

    @forelse($rows as $r)
        <div class="msg {{ $r['internal'] ? 'internal' : '' }}">
            <span class="who">{{ $r['sender'] }}</span>
            <span class="when">{{ $r['date'] ? \Illuminate\Support\Carbon::parse($r['date'])->format('d/m/Y H:i') : '' }}</span>
            @if($r['internal'])<span class="tag-internal">[nota interna]</span>@endif
            <div class="body">{{ $r['body'] }}</div>
            @if($includeAttachments && count($r['attachments']))
                <div class="extra">Adjuntos: {{ implode(', ', $r['attachments']) }}</div>
            @endif
            @if($includeMeta && count($r['metadata']))
                <div class="extra">{{ json_encode($r['metadata'], JSON_UNESCAPED_UNICODE) }}</div>
            @endif
        </div>
    @empty
        <p>Sin mensajes para exportar.</p>
    @endforelse
</body>
</html>
