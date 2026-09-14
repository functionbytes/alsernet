@extends('layouts.theme')

@section('title', 'Chat flows')

@section('page_header')
    @include('core::components.card', ['title' => 'Chat flows'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Chat flows</h5>
                        <p class="small mb-0 text-muted">Flujos de chatbot interactivos activados por eventos en conversaciones</p>
                    </div>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#import-modal">
                            <i class="fas fa-upload me-1"></i> Importar
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#templates-modal">
                            <i class="fas fa-wand-magic-sparkles me-1"></i> Desde plantilla
                        </button>
                        <a href="{{ route('chatflow.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo flow
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($chatFlows->total()) }}</h4>
                                <small class="text-muted">Flows registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active'] ?? 0) }}</h4>
                                <small class="text-muted">En uso actualmente</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Borradores</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['draft'] ?? 0) }}</h4>
                                <small class="text-muted">Sin publicar</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Sesiones hoy</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['sessions_today'] ?? 0) }}</h4>
                                <small class="text-muted">Conversaciones activas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('chatflow.index') }}" id="filterForm">
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <div class="flex-fill chatflow-search-input">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                       placeholder="Buscar por nombre..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <select name="trigger_type" class="form-select w-auto flex-shrink-0">
                            <option value="">Todos los triggers</option>
                            <option value="conversation_start" {{ request('trigger_type') === 'conversation_start' ? 'selected' : '' }}>Inicio conv.</option>
                            <option value="keyword"            {{ request('trigger_type') === 'keyword'            ? 'selected' : '' }}>Keyword</option>
                            <option value="manual"             {{ request('trigger_type') === 'manual'             ? 'selected' : '' }}>Manual</option>
                            <option value="no_agent"           {{ request('trigger_type') === 'no_agent'           ? 'selected' : '' }}>Sin agente</option>
                        </select>
                        <select name="status" class="form-select w-auto flex-shrink-0">
                            <option value="">Todos los estados</option>
                            <option value="draft"    {{ request('status') === 'draft'    ? 'selected' : '' }}>Borrador</option>
                            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Activo</option>
                            <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archivado</option>
                        </select>
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search') || request('trigger_type') || request('status'))
                            <a href="{{ route('chatflow.index') }}" class="btn btn-outline-secondary flex-shrink-0" title="Limpiar filtros">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($chatFlows->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Inbox</th>
                                    <th>Trigger</th>
                                    <th>Estado</th>
                                    <th>Sesiones</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($chatFlows as $chatFlow)
                                    @php
                                        $triggerBadge = match($chatFlow->trigger_type) {
                                            'conversation_start' => ['label' => 'Inicio conv.', 'color' => 'info'],
                                            'keyword'            => ['label' => 'Keyword',      'color' => 'warning'],
                                            'manual'             => ['label' => 'Manual',        'color' => 'secondary'],
                                            'no_agent'           => ['label' => 'Sin agente',    'color' => 'danger'],
                                            default              => ['label' => $chatFlow->trigger_type, 'color' => 'secondary'],
                                        };
                                        $statusColor = match($chatFlow->status) {
                                            'active'   => 'success',
                                            'archived' => 'dark',
                                            default    => 'secondary',
                                        };
                                        $statusLabel = match($chatFlow->status) {
                                            'active'   => 'Activo',
                                            'archived' => 'Archivado',
                                            default    => 'Borrador',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('chatflow.edit', $chatFlow) }}" class="fw-semibold text-dark text-decoration-none">
                                                {{ $chatFlow->name }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="text-muted small">
                                                {{ $chatFlow->inbox?->name ?? 'Todos los inboxes' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $triggerBadge['color'] }}-subtle text-{{ $triggerBadge['color'] }}">
                                                {{ $triggerBadge['label'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted small">{{ number_format($chatFlow->sessions_count ?? 0) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('chatflow.edit', $chatFlow) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('chatflow.sessions', $chatFlow) }}">
                                                            Sesiones
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('chatflow.analytics', $chatFlow) }}">
                                                            Analíticas
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('chatflow.test-cases.index', $chatFlow) }}">
                                                            Escenarios de prueba
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('chatflow.versions', $chatFlow) }}">
                                                            Versiones
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('chatflow.export', $chatFlow) }}">
                                                            Exportar JSON
                                                        </a>
                                                    </li>
                                                    @if($chatFlow->status !== 'active')
                                                        <li>
                                                            <form action="{{ route('chatflow.publish', $chatFlow) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">Publicar</button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                    <li>
                                                        <form action="{{ route('chatflow.duplicate', $chatFlow) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">Duplicar</button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('chatflow.destroy', $chatFlow) }}"
                                                           data-title="Eliminar flow: {{ $chatFlow->name }}">
                                                            Eliminar
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-robot fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request('search') || request('trigger_type') || request('status'))
                                No se encontraron resultados
                            @else
                                No hay flows configurados
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request('search') || request('trigger_type') || request('status'))
                                Prueba ajustando los filtros de busqueda
                            @else
                                Crea tu primer chat flow para automatizar conversaciones del chatbot
                            @endif
                        </p>
                        @if(request('search') || request('trigger_type') || request('status'))
                            <a href="{{ route('chatflow.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('chatflow.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nuevo flow
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($chatFlows->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $chatFlows->firstItem() }}–{{ $chatFlows->lastItem() }} de {{ $chatFlows->total() }}
                        </div>
                        <div>
                            {{ $chatFlows->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @include('core::components.delete')

    {{-- Import flow modal --}}
    <div class="modal fade" id="import-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('chatflow.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-upload me-2 text-primary"></i>Importar flow</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">Sube un archivo <code>.json</code> exportado, o pega su contenido. Se importará como borrador.</p>
                        <div class="mb-3">
                            <label class="form-label">Archivo JSON</label>
                            <input type="file" name="file" accept="application/json,.json" class="form-control">
                        </div>
                        <div class="text-center text-muted small mb-2">— o pega el JSON —</div>
                        <textarea name="json" class="form-control" rows="5" placeholder='{"format":"chatflow/v1","nodes":[...]}'></textarea>
                    </div>
                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-upload me-1"></i> Importar
                        </button>
                        <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Templates gallery modal --}}
    <div class="modal fade" id="templates-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-wand-magic-sparkles me-2 text-primary"></i>Crear desde plantilla
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Empieza con un flujo listo para usar. Podrás editarlo antes de publicarlo.</p>
                    <div class="row g-3">
                        @foreach($templates as $template)
                            <div class="col-12 col-md-6">
                                <form action="{{ route('chatflow.store-template', $template['key']) }}" method="POST" class="h-100">
                                    @csrf
                                    <button type="submit" class="card h-100 w-100 border text-start template-card">
                                        <div class="card-body d-flex gap-3 align-items-start">
                                            <span class="template-icon" data-color="{{ $template['color'] }}">
                                                <i class="{{ $template['icon'] }}"></i>
                                            </span>
                                            <div>
                                                <h6 class="fw-bold mb-1">{{ $template['name'] }}</h6>
                                                <p class="small text-muted mb-0">{{ $template['description'] }}</p>
                                            </div>
                                        </div>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('css')
<style>
.chatflow-search-input { min-width: 200px; }
.template-card { background: #fff; transition: border-color .15s, box-shadow .15s; cursor: pointer; }
.template-card:hover { border-color: #90bb13 !important; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
.template-icon {
    width: 42px; height: 42px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 18px;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success(@json(session('success')), 'Exito');
    @endif
    @if(session('error'))
        toastr.error(@json(session('error')), 'Error');
    @endif

    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Apply each template's accent color from data-color (avoids inline styles)
    $('.template-icon').each(function () {
        $(this).css('background-color', $(this).data('color'));
    });
});
</script>
@endpush
