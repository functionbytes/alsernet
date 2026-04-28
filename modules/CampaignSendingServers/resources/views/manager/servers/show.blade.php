@extends('theme::layouts.manager')

@section('title', $server->name)

@section('content')
    <div class="container py-4">
        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if (session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif

        <h2>{{ $server->name }}</h2>
        <p class="text-muted">{{ $server->getTypeName() }} · uid <code>{{ $server->uid }}</code></p>

        <table class="table table-bordered">
            <tr><th>Estado</th><td>{{ $server->status }}</td></tr>
            <tr><th>Host</th><td>{{ $server->host ?: '—' }}</td></tr>
            <tr><th>Puerto / Protocolo</th><td>{{ $server->smtp_port }} / {{ $server->smtp_protocol }}</td></tr>
            <tr><th>From por defecto</th><td>{{ $server->default_from_email ?: '—' }}</td></tr>
            <tr><th>Cuota</th><td>{{ $server->quota_value ? $server->quota_value.' / '.$server->quota_base.' '.$server->quota_unit : 'sin límite' }}</td></tr>
            <tr><th>Bounce handler</th><td>{{ optional($server->bounceHandler)->name ?: '—' }}</td></tr>
            <tr><th>Feedback handler</th><td>{{ optional($server->feedbackLoopHandler)->name ?: '—' }}</td></tr>
        </table>

        <form method="post" action="{{ route('manager.sending-servers.test', $server->uid) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-info">Probar conexión</button>
        </form>
        <a href="{{ route('manager.sending-servers.edit', $server->uid) }}" class="btn btn-outline-secondary">Editar</a>
        <form method="post" action="{{ route('manager.sending-servers.destroy', $server->uid) }}" class="d-inline" onsubmit="return confirm('¿Seguro?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">Eliminar</button>
        </form>
        <a href="{{ route('manager.sending-servers.index') }}" class="btn btn-link">Volver</a>
    </div>
@endsection
