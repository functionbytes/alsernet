@extends('layouts.theme')

@section('title', 'Plantillas')

@section('content')
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Plantillas de email</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('manager.templates.gallery') }}" class="btn btn-outline-secondary">Galería</a>
                <a href="{{ route('manager.templates.create') }}" class="btn btn-primary">Nueva plantilla</a>
            </div>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <form method="get" class="mb-3">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Buscar por nombre…">
        </form>

        <table class="table align-middle">
            <thead><tr><th>Nombre</th><th>Asunto</th><th>Layout</th><th>Compartida</th><th class="text-end" style="min-width:280px">Acciones</th></tr></thead>
            <tbody>
                @forelse ($templates as $t)
                    <tr>
                        <td><a href="{{ route('manager.templates.builder', $t->uid) }}" class="fw-semibold text-decoration-none">{{ $t->name }}</a></td>
                        <td class="text-muted">{{ Str::limit($t->subject, 60) }}</td>
                        <td>{{ optional($t->layout)->name ?: '—' }}</td>
                        <td>{{ $t->shared ? 'Sí' : 'No' }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('manager.templates.builder', $t->uid) }}" class="btn btn-primary" title="Abrir en el builder visual">
                                    <i class="fa fa-pen-to-square me-1"></i> Editar
                                </a>
                                <a href="{{ route('manager.templates.edit', $t->uid) }}" class="btn btn-outline-secondary" title="Editar metadatos (nombre, asunto, layout)">
                                    <i class="fa fa-cog"></i>
                                </a>
                                <a href="{{ route('manager.templates.preview', $t->uid) }}" target="_blank" class="btn btn-outline-info" title="Vista previa en pestaña nueva">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <form method="post" action="{{ route('manager.templates.copy', $t->uid) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary" title="Duplicar">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </form>
                                <form method="post" action="{{ route('manager.templates.destroy', $t->uid) }}" class="d-inline" onsubmit="return confirm('¿Eliminar la plantilla &quot;{{ addslashes($t->name) }}&quot;?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Sin plantillas. <a href="{{ route('manager.templates.gallery') }}">Empieza desde la galería</a> o <a href="{{ route('manager.templates.create') }}">crea una en blanco</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $templates->links() }}
@endsection
