{{-- Tags tab partial - loaded via AJAX into #tags-container --}}

@php
    $total = $tags->total();
    $active = $tags->where('is_active', true)->count();
    $inactive = $total - $active;
@endphp

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1 fw-bold">Etiquetas del agente</h5>
        <p class="text-muted mb-0 small">Las etiquetas clasifican conversaciones y afinan el comportamiento del agente</p>
    </div>
    <button type="button" class="btn btn-primary" id="btn-new-tag">
        Nueva etiqueta
    </button>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Total</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($total) }}</h4>
                <small class="text-muted">Etiquetas registradas</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Activos</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($active) }}</h4>
                <small class="text-muted">Disponibles para el agente</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card bg-light-secondary h-100">
            <div class="card-body">
                <h6 class="card-title mb-2">Inactivos</h6>
                <h4 class="mb-1 fw-bold">{{ number_format($inactive) }}</h4>
                <small class="text-muted">Sin efecto en el agente</small>
            </div>
        </div>
    </div>
</div>

{{-- Table --}}
@if($tags->isEmpty())
    <div class="text-center py-5">
        <i class="fas fa-tags fa-3x mb-3 text-muted opacity-50"></i>
        <h5 class="fw-bold mb-2">No hay etiquetas configuradas</h5>
        <p class="text-muted mb-4">Crea la primera etiqueta para clasificar conversaciones y personalizar el comportamiento del agente.</p>
        <button type="button" class="btn btn-primary" id="btn-new-tag-empty">
            Nueva etiqueta
        </button>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle text-nowrap">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tags as $tag)
                    <tr data-count-item>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-circle d-inline-block flex-shrink-0 tag-color-dot"
                                      style="--tag-color: {{ $tag->color ?? '#90bb13' }}"></span>
                                <span class="fw-semibold">{{ $tag->name }}</span>
                            </div>
                        </td>
                        <td>
                            <small class="text-muted">{{ $tag->description ? Str::limit($tag->description, 60) : '—' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">{{ $tag->priority ?? 0 }}</span>
                        </td>
                        <td>
                            @if($tag->is_active)
                                <span class="badge bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                    <i class="fas fa-ellipsis-vertical"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item tag-edit-btn" href="#"
                                           data-id="{{ $tag->id }}"
                                           data-name="{{ $tag->name }}"
                                           data-description="{{ $tag->description }}"
                                           data-color="{{ $tag->color ?? '#90bb13' }}"
                                           data-icon="{{ $tag->icon }}"
                                           data-priority="{{ $tag->priority ?? 0 }}"
                                           data-system-prompt="{{ $tag->system_prompt_addition }}"
                                           data-is-active="{{ $tag->is_active ? '1' : '0' }}">
                                            Editar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item tag-toggle-btn" href="#"
                                           data-id="{{ $tag->id }}"
                                           data-active="{{ $tag->is_active ? '1' : '0' }}">
                                            {{ $tag->is_active ? 'Desactivar' : 'Activar' }}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item tag-delete-btn" href="#"
                                           data-id="{{ $tag->id }}"
                                           data-name="{{ $tag->name }}">
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

    @if($tags->hasPages())
        <div class="d-flex justify-content-end mt-3" data-ajax-pagination>
            {{ $tags->links() }}
        </div>
    @endif
@endif
