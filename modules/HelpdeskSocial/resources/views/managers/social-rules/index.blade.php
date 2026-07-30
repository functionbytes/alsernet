@extends('layouts.theme')

@section('title', 'Reglas automáticas')

@section('page_header')
    @include('core::components.card', ['title' => 'Reglas automáticas'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Reglas automáticas</h5>
                        <p class="small mb-0 text-muted">Automatiza acciones sobre comentarios y mensajes según condiciones.</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('helpdesksocial.rules.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Nueva regla
                        </a>
                    </div>
                </div>
            </div>

            {{-- Tabla --}}
            <div class="card-body">
                @if($rules->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th width="80">Prioridad</th>
                                    <th>Nombre</th>
                                    <th>Plataforma</th>
                                    <th>Condiciones</th>
                                    <th>Acciones</th>
                                    <th>Estado</th>
                                    <th class="text-end">Disparos</th>
                                    <th class="text-center" width="80">Opciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rules as $rule)
                                    <tr>
                                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $rule->priority }}</span></td>
                                        <td>
                                            <div class="small fw-semibold">{{ $rule->name }}</div>
                                            @if($rule->description)
                                                <small class="text-muted d-block text-truncate" style="max-width: 320px;">{{ $rule->description }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($rule->platform)
                                                <span class="badge bg-info-subtle text-info">{{ ucfirst($rule->platform) }}</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Todas</span>
                                            @endif
                                        </td>
                                        <td>
                                            @forelse($rule->conditions ?? [] as $condition)
                                                <span class="badge bg-light text-dark border">
                                                    {{ $condition['field'] ?? '' }} {{ $condition['operator'] ?? '' }} {{ is_scalar($condition['value'] ?? null) ? $condition['value'] : '...' }}
                                                </span>
                                            @empty
                                                <small class="text-muted">—</small>
                                            @endforelse
                                        </td>
                                        <td>
                                            @forelse($rule->actions ?? [] as $action)
                                                <span class="badge bg-success-subtle text-success">{{ $action['type'] ?? '' }}</span>
                                            @empty
                                                <small class="text-muted">—</small>
                                            @endforelse
                                        </td>
                                        <td>
                                            @if($rule->is_active)
                                                <span class="badge bg-success-subtle text-success">Activa</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                                            @endif
                                            @if($rule->stop_processing)
                                                <span class="badge bg-warning-subtle text-warning" title="Detiene procesamiento">Stop</span>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format($rule->trigger_count) }}</td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('helpdesksocial.rules.edit', $rule) }}">Editar</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-url="{{ route('helpdesksocial.rules.destroy', $rule) }}"
                                                           data-title="Eliminar regla {{ $rule->name }}">
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
                        <i class="fas fa-bolt fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">No hay reglas configuradas</h5>
                        <p class="text-muted mb-4">Crea tu primera regla para automatizar respuestas, asignaciones o etiquetado.</p>
                        <a href="{{ route('helpdesksocial.rules.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva regla
                        </a>
                    </div>
                @endif
            </div>

            @if($rules->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $rules->firstItem() }} - {{ $rules->lastItem() }} de {{ $rules->total() }}
                        </div>
                        <div>{{ $rules->links() }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @include('core::components.delete')
@endsection

@push('scripts')
<script>
$(document).on('click', '.delete-btn', function (e) {
    e.preventDefault();
    var url = $(this).data('url');
    var title = $(this).data('title') || 'Confirmar eliminación';
    $('#delete-form').attr('action', url);
    $('#delete-modal .modal-title').text(title);
    new bootstrap.Modal(document.getElementById('delete-modal')).show();
});

@if(session('success'))
    toastr.success(@json(session('success')), 'Éxito');
@endif
@if(session('error'))
    toastr.error(@json(session('error')), 'Error');
@endif
</script>
@endpush
