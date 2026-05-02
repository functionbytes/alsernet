@extends('theme::layouts.admin')

@section('title', 'Plantillas de respuesta')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Plantillas de respuesta</h1>
        <a href="{{ route('helpdesksocial.templates.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nueva plantilla
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Plataforma</th>
                            <th>Categoría</th>
                            <th>Contenido</th>
                            <th>Uso</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $template->name }}</div>
                                <small class="text-muted">{{ $template->description }}</small>
                            </td>
                            <td>
                                @if($template->platform)
                                <span class="badge bg-secondary">{{ ucfirst($template->platform) }}</span>
                                @else
                                <span class="badge bg-light text-dark">Todas</span>
                                @endif
                            </td>
                            <td>{{ $template->category ?? '-' }}</td>
                            <td>
                                <div class="text-truncate" style="max-width: 300px;">
                                    {{ $template->body }}
                                </div>
                            </td>
                            <td>{{ $template->usage_count }}</td>
                            <td>
                                @if($template->is_active)
                                <span class="badge bg-success">Activa</span>
                                @else
                                <span class="badge bg-secondary">Inactiva</span>
                                @endif
                                @if($template->is_default)
                                <span class="badge bg-primary">Default</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('helpdesksocial.templates.edit', $template) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No hay plantillas configuradas
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $templates->links() }}
        </div>
    </div>
</div>
@endsection
