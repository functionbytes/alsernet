@extends('theme::layouts.manager')

@section('title', $domain->name)

@section('content')
    <div class="container py-4">
        @if (session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif
        <h2>{{ $domain->name }}</h2>
        <p class="text-muted">uid <code>{{ $domain->uid }}</code></p>

        <table class="table table-bordered">
            <tr><th>Estado</th><td>{{ $domain->status }}</td></tr>
            <tr><th>Servidor</th><td>{{ optional($domain->sendingServer)->name ?: '—' }}</td></tr>
            <tr><th>Firma DKIM</th><td>{{ $domain->signing_enabled ? 'Activa' : 'Inactiva' }}</td></tr>
            <tr><th>Selector</th><td>{{ $domain->dkim_selector ?: '—' }}</td></tr>
            <tr><th>Verificado</th><td>{{ $domain->verified_at?->format('Y-m-d H:i') ?: '—' }}</td></tr>
        </table>

        @if ($domain->dkim_public_key)
            <h5>Registro DKIM (DNS TXT)</h5>
            <pre class="bg-light p-3 small">{{ $domain->dkim_selector }}._domainkey.{{ $domain->name }}  IN  TXT  "v=DKIM1; k=rsa; p={{ $domain->dkim_public_key }}"</pre>
        @endif

        <form method="post" action="{{ route('manager.sending-servers.domains.verify', $domain->uid) }}" class="d-inline">@csrf<button class="btn btn-outline-info">Verificar</button></form>
        <form method="post" action="{{ route('manager.sending-servers.domains.destroy', $domain->uid) }}" class="d-inline" onsubmit="return confirm('¿Seguro?');">@csrf @method('DELETE')<button class="btn btn-outline-danger">Eliminar</button></form>
        <a href="{{ route('manager.sending-servers.domains.index') }}" class="btn btn-link">Volver</a>
    </div>
@endsection
