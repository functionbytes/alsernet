@extends('theme::layouts.manager')

@section('title', $domain->name)

@section('content')
    <div class="container py-4">
        @if (session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif
        <h2>{{ $domain->name }}</h2>
        <table class="table table-bordered">
            <tr><th>Estado</th><td>{{ $domain->status }}</td></tr>
            <tr><th>Método</th><td>{{ $domain->verification_method }}</td></tr>
            <tr><th>Verificado</th><td>{{ $domain->verified_at?->format('Y-m-d H:i') ?: '—' }}</td></tr>
        </table>

        <form method="post" action="{{ route('manager.sending-servers.tracking-domains.verify', $domain->uid) }}" class="d-inline">@csrf<button class="btn btn-outline-info">Verificar</button></form>
        <form method="post" action="{{ route('manager.sending-servers.tracking-domains.destroy', $domain->uid) }}" class="d-inline" onsubmit="return confirm('¿Seguro?');">@csrf @method('DELETE')<button class="btn btn-outline-danger">Eliminar</button></form>
        <a href="{{ route('manager.sending-servers.tracking-domains.index') }}" class="btn btn-link">Volver</a>
    </div>
@endsection
