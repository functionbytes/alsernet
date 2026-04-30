@extends('layouts.theme')

@section('title', 'Detalles del macro')

@section('content')
    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <div class="card-header border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">{{ $macro->name }}</h5>
                            <p class="mb-0 text-muted small">Detalles del macro</p>
                        </div>
                        <a href="{{ route('settings.chat.macros.edit', $macro) }}" class="btn btn-primary">
                            Editar macro
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @include('core::components.alerts')

                    <!-- Información básica -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3 border-bottom pb-2">Información básica</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="control-label col-form-label text-muted small">Nombre</label>
                                <p class="mb-0 fw-semibold">{{ $macro->name }}</p>
                            </div>

                            @if($macro->description)
                                <div class="col-12">
                                    <label class="control-label col-form-label text-muted small">Descripción</label>
                                    <p class="mb-0">{{ $macro->description }}</p>
                                </div>
                            @endif

                            <div class="col-12 col-md-6">
                                <label class="control-label col-form-label text-muted small">Visibilidad</label>
                                <div>
                                    @php
                                        $visibilityLabels = ['Personal', 'Global', 'Equipo'];
                                        $visibilityColors = ['secondary', 'success', 'info'];
                                    @endphp
                                    <span class="badge bg-{{ $visibilityColors[$macro->visibility] }}-subtle text-{{ $visibilityColors[$macro->visibility] }}">
                                        {{ $visibilityLabels[$macro->visibility] }}
                                    </span>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="control-label col-form-label text-muted small">Estado</label>
                                <div>
                                    @if($macro->enabled)
                                        <span class="badge bg-success-subtle text-success">Activo</span>
                                    @else
                                        <span class="badge bg-info-subtle text-info">Inactivo</span>
                                    @endif
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="control-label col-form-label text-muted small">Creado por</label>
                                <p class="mb-0">{{ $macro->createdBy->name ?? 'Desconocido' }}</p>
                                <small class="text-muted">{{ $macro->created_at->diffForHumans() }}</small>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="control-label col-form-label text-muted small">Última actualización</label>
                                <p class="mb-0">{{ $macro->updated_at->format('d/m/Y H:i') }}</p>
                                <small class="text-muted">{{ $macro->updated_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3 border-bottom pb-2">Acciones ({{ count($macro->actions) }})</h6>
                        @if(count($macro->actions) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%">#</th>
                                            <th style="width: 35%">Acción</th>
                                            <th>Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($macro->actions as $index => $action)
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary rounded-circle" style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;">
                                                        {{ $index + 1 }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @php
                                                        $actionLabels = [
                                                            'assign_agent' => 'Asignar a agente',
                                                            'assign_team' => 'Asignar a equipo',
                                                            'add_label' => 'Agregar etiqueta',
                                                            'remove_label' => 'Quitar etiqueta',
                                                            'change_status' => 'Cambiar estado',
                                                            'change_priority' => 'Cambiar prioridad',
                                                            'send_message' => 'Enviar mensaje',
                                                            'add_private_note' => 'Agregar nota privada',
                                                            'mute_conversation' => 'Silenciar conversación',
                                                            'unmute_conversation' => 'Activar conversación',
                                                            'resolve_conversation' => 'Resolver conversación',
                                                            'reopen_conversation' => 'Reabrir conversación',
                                                        ];
                                                        // Handle both old format (action_name) and new format (action)
                                                        $actionType = $action['action'] ?? $action['action_name'] ?? null;
                                                    @endphp
                                                    <strong>{{ $actionLabels[$actionType] ?? $actionType }}</strong>
                                                </td>
                                                <td>
                                                    @php
                                                        // Extract value from both formats
                                                        $actionValue = $action['value'] ?? null;
                                                        if (!$actionValue && isset($action['action_params'])) {
                                                            $params = $action['action_params'];
                                                            $paramValue = reset($params);
                                                            $actionValue = is_array($paramValue) ? reset($paramValue) : $paramValue;
                                                        }
                                                    @endphp
                                                    @if($actionValue)
                                                        <span class="text-muted">{{ is_array($actionValue) ? json_encode($actionValue) : $actionValue }}</span>
                                                    @else
                                                        <span class="text-muted fst-italic">Sin valor</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fa fa-bolt fs-3 mb-2 d-block"></i>
                                    <p class="mb-0">No hay acciones configuradas para este macro</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Condiciones -->
                    @if($macro->hasConditions())
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Condiciones</h6>
                            @if(isset($macro->conditions['rules']) && count($macro->conditions['rules']) > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 5%">#</th>
                                                <th style="width: 30%">Campo</th>
                                                <th style="width: 30%">Operador</th>
                                                <th>Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($macro->conditions['rules'] as $index => $condition)
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-info rounded-circle" style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;">
                                                            {{ $index + 1 }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <strong>{{ ucfirst($condition['attribute'] ?? 'Desconocido') }}</strong>
                                                    </td>
                                                    <td>
                                                        <span class="text-muted">{{ $condition['operator'] ?? 'equals' }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-primary">{{ $condition['value'] ?? 'N/A' }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fa fa-filter fs-3 mb-2 d-block"></i>
                                        <p class="mb-0">No hay condiciones especificadas</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="card-footer border-top">
                    <a href="{{ route('settings.chat.macros.index') }}" class="btn btn-secondary w-100">
                        Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
