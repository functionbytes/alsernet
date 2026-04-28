@extends('theme::layouts.manager')

@section('title', 'Dominios de envío')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Dominios de envío</h2>
            <a href="{{ route('manager.sending-servers.domains.create') }}" class="btn btn-primary">Nuevo dominio</a>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <table class="table">
            <thead>
                <tr><th>Dominio</th><th>Servidor</th><th>Estado</th><th>DKIM</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($domains as $domain)
                    <tr>
                        <td><a href="{{ route('manager.sending-servers.domains.show', $domain->uid) }}">{{ $domain->name }}</a></td>
                        <td>{{ optional($domain->sendingServer)->name ?: '—' }}</td>
                        <td>{{ $domain->status }}</td>
                        <td>{{ $domain->signing_enabled ? 'sí' : 'no' }}</td>
                        <td>
                            <form method="post" action="{{ route('manager.sending-servers.domains.verify', $domain->uid) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-info">Verificar</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">Sin dominios.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $domains->links() }}
    </div>
@endsection
