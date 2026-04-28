@extends('theme::layouts.manager')

@section('title', 'Servidores de envío')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Servidores de envío</h2>
            <a href="{{ route('manager.sending-servers.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo servidor
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="get" class="mb-3">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Buscar por nombre / host / from email">
        </form>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>From por defecto</th>
                    <th>Cuota</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($servers as $server)
                    <tr>
                        <td>
                            <a href="{{ route('manager.sending-servers.show', $server->uid) }}">
                                {{ $server->name }}
                            </a>
                        </td>
                        <td>{{ $providers[$server->type]['label'] ?? $server->type }}</td>
                        <td>
                            <span class="badge badge-{{ $server->status === 'active' ? 'success' : 'secondary' }}">
                                {{ $server->status }}
                            </span>
                        </td>
                        <td>{{ $server->default_from_email }}</td>
                        <td>
                            @if ($server->quota_value)
                                {{ $server->quota_value }} / {{ $server->quota_base }} {{ $server->quota_unit }}
                            @else
                                <span class="text-muted">sin límite</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <form method="post" action="{{ route('manager.sending-servers.test', $server->uid) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-info">Probar</button>
                            </form>
                            <a href="{{ route('manager.sending-servers.edit', $server->uid) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No hay servidores configurados aún.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $servers->links() }}
    </div>
@endsection
