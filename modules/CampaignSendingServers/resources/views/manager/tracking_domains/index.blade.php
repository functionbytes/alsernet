@extends('theme::layouts.manager')

@section('title', 'Dominios de tracking')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Dominios de tracking</h2>
            <a href="{{ route('manager.sending-servers.tracking-domains.create') }}" class="btn btn-primary">Nuevo</a>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <table class="table">
            <thead><tr><th>Dominio</th><th>Estado</th><th>Método</th><th>Verificado</th><th></th></tr></thead>
            <tbody>
                @forelse ($domains as $d)
                    <tr>
                        <td><a href="{{ route('manager.sending-servers.tracking-domains.show', $d->uid) }}">{{ $d->name }}</a></td>
                        <td>{{ $d->status }}</td>
                        <td>{{ $d->verification_method }}</td>
                        <td>{{ $d->verified_at?->format('Y-m-d H:i') ?: '—' }}</td>
                        <td>
                            <form method="post" action="{{ route('manager.sending-servers.tracking-domains.verify', $d->uid) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-info">Verificar</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">Sin dominios de tracking.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $domains->links() }}
    </div>
@endsection
