@extends('layouts.admin')

@section('title', 'Plantillas')

@section('content')

    <div class="widget-content searchable-container list">

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Main Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-file-code me-2"></i>Plantillas de Contenido
                        </h5>
                        <p class="small mb-0 text-muted">Crea plantillas reutilizables con variables dinámicas</p>
                    </div>
                    <a href="{{ route('admin.social.templates.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva Plantilla
                    </a>
                </div>
            </div>

            <!-- Templates Table -->
            <div class="card-body">
                @if($templates->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Plantilla</th>
                                    <th>Categoría</th>
                                    <th>Variables</th>
                                    <th>Redes</th>
                                    <th>Uso</th>
                                    <th width="150">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($templates as $template)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $template->name }}</div>
                                            <small class="text-muted">{{ Str::limit($template->content, 60) }}</small>
                                        </td>
                                        <td>
                                            @if($template->category)
                                                <span class="badge bg-light-primary text-primary">
                                                    {{ ucfirst($template->category) }}
                                                </span>
                                            @else
                                                <span class="text-muted">Sin categoría</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($template->variables && count($template->variables) > 0)
                                                <div class="d-flex gap-1 flex-wrap">
                                                    @foreach(array_slice($template->variables, 0, 3) as $var)
                                                        <span class="badge bg-light text-dark">
                                                            <code>{{ '{' . $var . '}' }}</code>
                                                        </span>
                                                    @endforeach
                                                    @if(count($template->variables) > 3)
                                                        <span class="badge bg-light text-muted">
                                                            +{{ count($template->variables) - 3 }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">Sin variables</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($template->networks && count($template->networks) > 0)
                                                <div class="d-flex gap-1">
                                                    @foreach(array_slice($template->networks, 0, 3) as $network)
                                                        <span class="badge bg-light">
                                                            {{ ucfirst($network) }}
                                                        </span>
                                                    @endforeach
                                                    @if(count($template->networks) > 3)
                                                        <span class="badge bg-light text-muted">
                                                            +{{ count($template->networks) - 3 }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">Todas</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-copy text-muted"></i>
                                                <span class="fw-semibold">{{ $template->usage_count }}</span>
                                                @if($template->last_used_at)
                                                    <small class="text-muted">
                                                        ({{ $template->last_used_at->diffForHumans() }})
                                                    </small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('admin.social.templates.edit', $template) }}"
                                                   class="btn btn-sm btn-light"
                                                   data-bs-toggle="tooltip"
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.social.templates.destroy', $template) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('¿Eliminar esta plantilla?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-sm btn-light-danger"
                                                            data-bs-toggle="tooltip"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-file-code fs-1 text-muted"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay plantillas creadas</h5>
                        <p class="text-muted mb-4">Crea plantillas reutilizables con variables dinámicas para agilizar tu trabajo</p>
                        <a href="{{ route('admin.social.templates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Crear Primera Plantilla
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
