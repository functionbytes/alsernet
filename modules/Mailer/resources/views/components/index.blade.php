@extends('layouts.theme')

@section('page_title', 'Componentes de Email')

@section('content')


    @include('core::components.card', ['title' => 'Componentes de Email'])

    <div class="widget-content searchable-container list">

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Componentes de email</h5>
                        <p class="small mb-0 text-muted">Gestiona header, footer y otros componentes reutilizables para tus plantillas de email</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('mailers.templates.index') }}" class="btn btn-secondary">
                            Ver plantillas
                        </a>
                        <a href="{{ route('mailers.components.create') }}" class="btn btn-primary">
                            Nuevo componente
                        </a>
                    </div>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="card-body border-bottom">
                <div class="alert alert-info border-0 mb-0" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-2">¿Qué son los componentes de email?</h6>
                            <p class="mb-0">
                                Los componentes son partes reutilizables de HTML que se aplican automáticamente a todas las plantillas de email.
                                Por ejemplo, el <strong>header</strong> y <strong>footer</strong> se insertan en cada email que envías.
                                Edítalos aquí y los cambios se reflejarán en todas las plantillas.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Alerts --}}
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

            @if (session('error'))
                <div class="card-body border-bottom">
                    <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-triangle-exclamation fs-4 me-2"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('mailers.components.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-7">
                            <label for="search" class="form-label fw-semibold">Búsqueda</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="Buscar por alias, nombre o código..."
                                   value="{{ $search }}">
                        </div>

                        @if (!empty($types))
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="type" class="form-label fw-semibold">Tipo</label>
                                <select id="type" name="type" class="form-select">
                                    <option value="">Todos los tipos</option>
                                    @foreach ($types as $t)
                                        <option value="{{ $t }}" @if($type === $t) selected @endif>
                                            {{ ucfirst($t) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-12 col-sm-6 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fa fa-magnifying-glass me-2"></i>Buscar
                            </button>
                            @if ($search || $type)
                                <a href="{{ route('mailers.components.index') }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Components Table --}}
            @if ($components->count() > 0)
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="25%">Componente</th>
                                <th width="16%">Código</th>
                                <th width="10%">Tipo</th>
                                <th width="18%">Traducciones</th>
                                <th width="13%">Estado</th>
                                <th width="18%" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($components as $component)
                                @php
                                    $isCritical = in_array($component->alias, ['email_template_header', 'email_template_footer', 'email_template_wrapper']);
                                @endphp
                                <tr>
                                    <td>
                                        <div>
                                            <strong class="d-block">{{ $component->subject }}</strong>
                                            <small class="text-muted d-block">{{ $component->alias }}</small>
                                            @if ($isCritical)
                                                <span class="badge bg-warning-subtle text-warning mt-1">
                                                    <i class="fa fa-star"></i> Sistema
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <code class="text-muted">{{ $component->code }}</code>
                                    </td>
                                    <td>
                                        @if ($component->type === 'partial')
                                            <span class="badge bg-info-subtle text-info">Parcial</span>
                                        @elseif ($component->type === 'layout')
                                            <span class="badge bg-success-subtle text-success">Layout</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($component->type) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $totalLangs = $langs->count();
                                            $completedTranslations = $component->translations->filter(function($t) {
                                                return !empty($t->subject) && !empty($t->content);
                                            })->count();
                                            $pendingTranslations = $totalLangs - $completedTranslations;
                                        @endphp
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="flex-grow-1">
                                                <small class="d-block fw-semibold">
                                                    {{ $completedTranslations }}/{{ $totalLangs }} completas
                                                </small>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-success" role="progressbar"
                                                         style="width: {{ ($completedTranslations / $totalLangs) * 100 }}%"
                                                         aria-valuenow="{{ $completedTranslations }}" aria-valuemin="0"
                                                         aria-valuemax="{{ $totalLangs }}">
                                                    </div>
                                                </div>
                                            </div>
                                            @if ($completedTranslations === $totalLangs)
                                                <i class="fas fa-check-circle text-success" data-bs-toggle="tooltip"
                                                   title="Todas las traducciones completas"></i>
                                            @elseif ($completedTranslations === 0)
                                                <i class="fas fa-circle-exclamation text-danger" data-bs-toggle="tooltip"
                                                   title="Sin traducciones completadas"></i>
                                            @else
                                                <i class="fas fa-exclamation-circle text-warning" data-bs-toggle="tooltip"
                                                   title="{{ $pendingTranslations }} traducción(es) pendiente(s)"></i>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if ($isCritical)
                                            <span class="badge bg-warning-subtle text-warning">
                                                Protegido
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Personalizado</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('mailers.components.edit', $component->uid) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('mailers.components.preview', $component->uid) }}" target="_blank">
                                                        Vista previa
                                                    </a>
                                                </li>
                                                <li>
                                                    <form method="POST"
                                                          action="{{ route('mailers.components.duplicate', $component->uid) }}">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">
                                                            Duplicar
                                                        </button>
                                                    </form>
                                                </li>
                                                @if (!$isCritical)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST"
                                                              action="{{ route('mailers.components.destroy', $component->uid) }}"
                                                              onsubmit="return confirm('¿Estás seguro de que deseas eliminar este componente?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                Eliminar
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fa fa-inbox fa-3x mb-3 text-muted opacity-50"></i>
                    <h5 class="fw-bold mb-2">No hay componentes</h5>
                    <p class="text-muted mb-4">
                        @if ($search || $type)
                            No se encontraron resultados con los filtros aplicados.
                        @else
                            Comienza creando tu primer componente reutilizable.
                        @endif
                    </p>
                    @if ($search || $type)
                        <a href="{{ route('mailers.components.index') }}" class="btn btn-secondary">
                            Ver todos
                        </a>
                    @else
                        <a href="{{ route('mailers.components.create') }}" class="btn btn-primary">
                            + Crear ahora
                        </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- System Components Info Section --}}
            <div class="card-body border-top">
                <h5 class="fw-bold mb-1">Componentes del sistema</h5>
                <p class="text-muted mb-4">
                    Estos componentes son esenciales para el funcionamiento del sistema de emails.
                    Son protegidos y se aplican automáticamente a todas las plantillas.
                </p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle bg-light-subtle d-flex align-items-center justify-content-center me-3"
                                         style="width: 48px; height: 48px;">
                                        <i class="fa fa-arrow-up text-primary"></i>
                                    </div>
                                    <h6 class="fw-bold mb-0">Header</h6>
                                </div>
                                <p class="text-muted mb-2 small">
                                    Se inserta al inicio de cada email. Incluye logo, estilos CSS y apertura de HTML.
                                </p>
                                <code class="small text-primary">email_template_header</code>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle bg-light-subtle d-flex align-items-center justify-content-center me-3"
                                         style="width: 48px; height: 48px;">
                                        <i class="fa fa-arrow-down text-success"></i>
                                    </div>
                                    <h6 class="fw-bold mb-0">Footer</h6>
                                </div>
                                <p class="text-muted mb-2 small">
                                    Se inserta al final de cada email. Incluye información de la empresa y cierre de HTML.
                                </p>
                                <code class="small text-success">email_template_footer</code>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="rounded-circle bg-light-subtle d-flex align-items-center justify-content-center me-3"
                                         style="width: 48px; height: 48px;">
                                        <i class="fa fa-layer-group text-info"></i>
                                    </div>
                                    <h6 class="fw-bold mb-0">Wrapper</h6>
                                </div>
                                <p class="text-muted mb-2 small">
                                    Layout completo que combina header + contenido + footer.
                                </p>
                                <code class="small text-info">email_template_wrapper</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

</div>

@push('scripts')
<script>
    // Inicializar tooltips de Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
@endpush

@endsection
