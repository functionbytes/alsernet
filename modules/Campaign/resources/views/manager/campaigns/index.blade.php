@extends('layouts.theme')

@section('title', 'Campañas')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Campañas</h2>
            @hasanypermission('campaigns.manage.all')
                <a href="{{ route('manager.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva campaña
                </a>
            @endhasanypermission
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <form method="get" class="row g-2 mb-3">
            <div class="col-md-6"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Buscar por nombre o asunto…"></div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Todos los estados</option>
                    @foreach ($statuses as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3"><button class="btn btn-outline-secondary w-100">Filtrar</button></div>
        </form>

        <table class="table table-striped">
            <thead><tr>
                <th>Nombre</th><th>Asunto</th><th>Estado</th><th>Programada</th><th>Creada</th><th></th>
            </tr></thead>
            <tbody>
                @forelse ($campaigns as $c)
                    <tr>
                        <td><a href="{{ route('manager.show', $c->uid) }}">{{ $c->name }}</a></td>
                        <td class="text-muted">{{ Str::limit($c->subject, 60) }}</td>
                        <td><span class="badge bg-secondary">{{ $statuses[$c->status] ?? $c->status }}</span></td>
                        <td>{{ $c->run_at?->format('Y-m-d H:i') ?: '—' }}</td>
                        <td>{{ $c->created_at?->format('Y-m-d') }}</td>
                        <td class="text-end">
                            <a href="{{ route('manager.edit', $c->uid) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay campañas. <a href="{{ route('manager.create') }}">Crear una</a>.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{ $campaigns->links() }}
    </div>
@endsection
