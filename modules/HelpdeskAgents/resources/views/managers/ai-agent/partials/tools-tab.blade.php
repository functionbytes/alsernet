{{-- Tools tab partial - loaded via AJAX into #tools-container --}}

@php
    $total = $tools->total();
    $active = $tools->where('is_active', true)->count();
    $inactive = $total - $active;
@endphp

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1 fw-bold">Herramientas del agente</h5>
        <p class="text-muted mb-0 small">Funciones y APIs que el agente puede invocar para realizar acciones</p>
    </div>
    <button type="button" class="btn btn-primary" id="btn-new-tool">
        Nueva herramienta
    </button>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Total</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($total) }}</h4>
                <small class="text-muted">Herramientas registradas</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Activas</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($active) }}</h4>
                <small class="text-muted">Disponibles para el agente</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Inactivas</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($inactive) }}</h4>
                <small class="text-muted">No disponibles para el agente</small>
            </div>
        </div>
    </div>
</div>

{{-- Table --}}
@if($tools->isEmpty())
    <div class="text-center py-5">
        <i class="fas fa-wrench fa-3x mb-3 text-muted opacity-50"></i>
        <h5 class="fw-bold mb-2">No hay herramientas configuradas</h5>
        <p class="text-muted mb-4">Crea herramientas para que el agente pueda consultar APIs, bases de datos o ejecutar funciones.</p>
        <button type="button" class="btn btn-primary" id="btn-new-tool-empty">
            Nueva herramienta
        </button>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle text-nowrap">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Usos</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tools as $tool)
                    <tr data-count-item>
                        <td>
                            <span class="fw-semibold">{{ $tool->name }}</span>
                            @if($tool->requires_approval)
                                <span class="badge bg-warning-subtle text-warning ms-1">Aprobación</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $typeLabels = ['function' => 'Función', 'api' => 'API', 'database' => 'Base de datos', 'custom' => 'Personalizado'];
                                $typeBadges = ['function' => 'primary', 'api' => 'info', 'database' => 'warning', 'custom' => 'secondary'];
                            @endphp
                            <span class="badge bg-{{ $typeBadges[$tool->type] ?? 'secondary' }}-subtle text-{{ $typeBadges[$tool->type] ?? 'secondary' }}">
                                {{ $typeLabels[$tool->type] ?? $tool->type }}
                            </span>
                        </td>
                        <td>
                            <small class="text-muted">{{ Str::limit($tool->description, 60) }}</small>
                        </td>
                        <td>
                            <small class="text-muted">{{ $tool->usage_count ?? 0 }} veces</small>
                        </td>
                        <td>
                            @if($tool->is_active)
                                <span class="badge bg-success-subtle text-success">Activa</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                    <i class="fas fa-ellipsis-vertical"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item tool-edit-btn" href="#" data-id="{{ $tool->id }}">
                                            Editar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item tool-toggle-btn" href="#"
                                           data-id="{{ $tool->id }}"
                                           data-active="{{ $tool->is_active ? '1' : '0' }}">
                                            {{ $tool->is_active ? 'Desactivar' : 'Activar' }}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item tool-delete-btn" href="#"
                                           data-id="{{ $tool->id }}"
                                           data-name="{{ $tool->name }}">
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

    @if($tools->hasPages())
        <div class="d-flex justify-content-end mt-3" data-ajax-pagination>
            {{ $tools->links() }}
        </div>
    @endif
@endif
