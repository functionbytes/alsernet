@extends('layouts.theme')

@section('title', 'Listas de correo')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Listas de correo</h2>
            <a href="{{ route('manager.maillists.create') }}" class="btn btn-primary">Nueva lista</a>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <form method="get" class="mb-3">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Buscar lista…">
        </form>

        <table class="table">
            <thead><tr><th>Nombre</th><th>From</th><th>Suscriptores</th><th>Creada</th><th></th></tr></thead>
            <tbody>
                @forelse ($lists as $l)
                    <tr>
                        <td><a href="{{ route('manager.maillists.show', $l->uid) }}">{{ $l->name }}</a></td>
                        <td><code class="small">{{ $l->from_email }}</code></td>
                        <td>{{ number_format($l->subscribers_count ?? 0) }}</td>
                        <td>{{ $l->created_at?->format('Y-m-d') }}</td>
                        <td><a href="{{ route('manager.maillists.subscribers.index', $l->uid) }}" class="btn btn-sm btn-outline-secondary">Suscriptores</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Sin listas. <a href="{{ route('manager.maillists.create') }}">Crear una</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $lists->links() }}
    </div>
@endsection
