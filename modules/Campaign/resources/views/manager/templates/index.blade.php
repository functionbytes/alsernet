@extends('theme::layouts.manager')

@section('title', 'Plantillas')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Plantillas de email</h2>
            <a href="{{ route('manager.campaigns.templates.create') }}" class="btn btn-primary">Nueva plantilla</a>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <form method="get" class="mb-3">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Buscar por nombre…">
        </form>

        <table class="table">
            <thead><tr><th>Nombre</th><th>Asunto</th><th>Layout</th><th>Compartida</th><th></th></tr></thead>
            <tbody>
                @forelse ($templates as $t)
                    <tr>
                        <td><a href="{{ route('manager.campaigns.templates.edit', $t->uid) }}">{{ $t->name }}</a></td>
                        <td class="text-muted">{{ Str::limit($t->subject, 60) }}</td>
                        <td>{{ optional($t->layout)->name ?: '—' }}</td>
                        <td>{{ $t->shared ? 'Sí' : 'No' }}</td>
                        <td>
                            <a href="{{ route('manager.campaigns.templates.preview', $t->uid) }}" target="_blank" class="btn btn-sm btn-outline-info">Preview</a>
                            <a href="{{ route('manager.campaigns.templates.copy', $t->uid) }}" class="btn btn-sm btn-outline-secondary">Duplicar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Sin plantillas. <a href="{{ route('manager.campaigns.templates.create') }}">Crear una</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $templates->links() }}
    </div>
@endsection
