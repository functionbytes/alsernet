@extends('layouts.theme')

@section('page_title', 'Email Templates')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Plantillas de email',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => ''],
            ['label' => 'Plantillas de correo', 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container list">

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Plantillas de email</h5>
                        <p class="small mb-0 text-muted">Gestiona plantillas de email para documentos, órdenes y notificaciones</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('mailers.components.index') }}" class="btn btn-secondary">
                            Ver componentes
                        </a>
                        <a href="{{ route('mailers.templates.create') }}" class="btn btn-primary">
                            Nuevo template
                        </a>
                    </div>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="card-body border-bottom">
                <div class="alert alert-info border-0 mb-0" role="alert">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                            <div>
                                <h6 class="fw-bold mb-2">¿Necesitas editar el Header o Footer?</h6>
                                <p class="mb-0">
                                    Los componentes como header, footer y otros elementos reutilizables se gestionan por separado.
                                    Edítalos una vez y se aplicarán automáticamente a todas las plantillas.
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('mailers.components.index') }}" class="btn btn-info flex-shrink-0">
                            <i class="fa fa-arrow-right me-2"></i>Ver
                        </a>
                    </div>
                </div>
            </div>

            {{-- Alerts --}}
            @if ($errors->any())
                <div class="card-body border-bottom">
                    <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-exclamation-circle fs-4 me-2 mt-1"></i>
                            <div>
                                <h6 class="alert-heading fw-bold mb-2">Errores de Validación</h6>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            @if (session('success'))
                <div class="card-body border-bottom">
                    <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-check fs-4 me-2"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('mailers.templates.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-7">
                            <label for="search" class="form-label fw-semibold">Búsqueda</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="Buscar por nombre, key o descripción..."
                                   value="{{ $search }}">
                        </div>

                        @if (!empty($modules))
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="module" class="form-label fw-semibold">Módulo</label>
                                <select id="module" name="module" class="form-select">
                                    <option value="">Todos los módulos</option>
                                    @foreach ($modules as $mod)
                                        <option value="{{ $mod }}" @if($module === $mod) selected @endif>
                                            {{ ucfirst($mod) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-12 col-sm-6 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fa fa-magnifying-glass me-2"></i>Buscar
                            </button>
                            @if ($search || $module)
                                <a href="{{ route('mailers.templates.index') }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Templates Table --}}
            @if ($templates->count() > 0)
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="22%">Nombre</th>
                                <th width="16%">Clave (Key)</th>
                                <th width="12%">Módulo</th>
                                <th width="13%">Estado</th>
                                <th width="25%" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($templates as $template)
                                <tr>
                                    <td>
                                        <div>
                                            <strong class="d-block">{{ $template->name }}</strong>
                                            @if ($template->description)
                                                <small class="text-muted d-block">{{ Str::limit($template->description, 50) }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <code class="text-muted">{{ $template->key }}</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info">{{ ucfirst($template->module) }}</span>
                                    </td>
                                    <td>
                                        @if ($template->is_enabled)
                                            <span class="badge bg-success-subtle text-success">
                                               Activo
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle">
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('mailers.templates.edit', $template->uid) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('mailers.templates.preview', $template->uid) }}" target="_blank">
                                                        Vista previa
                                                    </a>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#modalSendTest{{ $template->uid }}">
                                                        Enviar prueba
                                                    </button>
                                                </li>
                                                <li>
                                                    <form method="POST"
                                                          action="{{ route('mailers.templates.toggle-status', $template->uid) }}">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">
                                                            {{ $template->is_enabled ? 'Desactivar' : 'Activar' }}
                                                        </button>
                                                    </form>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST"
                                                          action="{{ route('mailers.templates.destroy', $template->uid) }}"
                                                          onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta plantilla?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item">
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Modal: Send Test Email --}}
                                <div class="modal fade" id="modalSendTest{{ $template->uid }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form method="POST" action="{{ route('mailers.templates.send-test', $template->uid) }}">
                                            @csrf
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">
                                                        <i class="fa fa-envelope me-2"></i>Enviar Email de Prueba
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="alert alert-info border-0">
                                                        <strong>Plantilla:</strong> {{ $template->name }}<br>
                                                        <strong>Asunto:</strong> {{ $template->subject }}
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="test_email_{{ $template->uid }}" class="form-label fw-semibold">Email de Destino</label>
                                                        <input type="email" class="form-control form-control-lg"
                                                               id="test_email_{{ $template->uid }}"
                                                               name="test_email" placeholder="tu@email.com" required>
                                                        <small class="form-text text-muted">
                                                            Se enviará un email con variables de ejemplo
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fa fa-paper-plane me-2"></i>Enviar Ahora
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fa fa-inbox fa-3x mb-3 text-muted opacity-50"></i>
                    <h5 class="fw-bold mb-2">No hay plantillas</h5>
                    <p class="text-muted mb-4">
                        @if ($search || $module || $langId)
                            No se encontraron resultados con los filtros aplicados.
                        @else
                            Comienza creando tu primera plantilla de email.
                        @endif
                    </p>
                    @if ($search || $module || $langId)
                        <a href="{{ route('mailers.templates.index') }}" class="btn btn-secondary">
                            Ver todas
                        </a>
                    @else
                        <a href="{{ route('mailers.templates.create') }}" class="btn btn-primary">
                            + Crear ahora
                        </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Pagination --}}
            @if($templates->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $templates->firstItem() }} - {{ $templates->lastItem() }} de {{ $templates->total() }} plantillas
                        </div>
                        <div>
                            {{ $templates->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>


</div>

@endsection
