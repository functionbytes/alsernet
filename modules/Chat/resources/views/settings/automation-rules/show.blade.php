@extends('layouts.theme')

@section('title', 'Detalle de regla de automatización')

@section('content')

@include('core::components.alerts')

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-info-subtle">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Detalle de regla de automatización</h5>
                        <p class="small mb-0 text-muted">Información completa de la regla</p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <!-- Name -->
                <div class="mb-4 pb-4 border-bottom">
                    <label class="control-label col-form-label text-muted small mb-2">Nombre de la regla</label>
                    <h5 class="mb-0">{{ $Automation->name }}</h5>
                </div>

                <!-- Description -->
                @if($Automation->description)
                    <div class="mb-4 pb-4 border-bottom">
                        <label class="control-label col-form-label text-muted small mb-2">Descripción</label>
                        <p class="mb-0">{{ $Automation->description }}</p>
                    </div>
                @endif

                <!-- Event -->
                <div class="mb-4 pb-4 border-bottom">
                    <label class="control-label col-form-label text-muted small mb-2">Evento disparador</label>
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-bolt text-warning"></i>
                        <code class="bg-light px-3 py-2 rounded">{{ $Automation->event_name }}</code>
                    </div>
                </div>

                <!-- Status -->
                <div class="mb-4 pb-4 border-bottom">
                    <label class="control-label col-form-label text-muted small mb-2">Estado</label>
                    <div>
                        @if($Automation->active)
                            <span class="badge bg-success-subtle text-success fs-6">
                                <i class="fas fa-check-circle"></i> Activa
                            </span>
                            <p class="text-muted small mt-2 mb-0">Esta regla se ejecutará automáticamente cuando se cumplan las condiciones</p>
                        @else
                            <span class="badge bg-info-subtle text-info fs-6">
                                <i class="fas fa-pause-circle"></i> Inactiva
                            </span>
                            <p class="text-muted small mt-2 mb-0">Esta regla no se ejecutará hasta que sea activada</p>
                        @endif
                    </div>
                </div>

                <!-- Conditions -->
                <div class="mb-4 pb-4 border-bottom">
                    <label class="control-label col-form-label text-muted small mb-3">
                        Condiciones ({{ count($Automation->conditions) }})
                    </label>
                    @if(count($Automation->conditions) > 0)
                        <div class="row g-2">
                            @foreach($Automation->conditions as $index => $condition)
                                <div class="col-12">
                                    <div class="card bg-light-subtle border-0">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="badge bg-primary-subtle text-primary">{{ $index + 1 }}</div>
                                                <div class="flex-grow-1">
                                                    <div class="row g-2">
                                                        <div class="col-md-4">
                                                            <small class="text-muted d-block">Atributo</small>
                                                            <strong>{{ ucfirst(str_replace('_', ' ', $condition['attribute'])) }}</strong>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <small class="text-muted d-block">Operador</small>
                                                            <strong>{{ ucfirst(str_replace('_', ' ', $condition['operator'])) }}</strong>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <small class="text-muted d-block">Valor</small>
                                                            <strong>{{ $condition['value'] }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No hay condiciones definidas</p>
                    @endif
                </div>

                <!-- Actions -->
                <div class="mb-4">
                    <label class="control-label col-form-label text-muted small mb-3">
                        Acciones ({{ count($Automation->actions) }})
                    </label>
                    @if(count($Automation->actions) > 0)
                        <div class="row g-2">
                            @foreach($Automation->actions as $index => $action)
                                <div class="col-12">
                                    <div class="card bg-light-subtle border-0">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="badge bg-info-subtle text-info">{{ $index + 1 }}</div>
                                                <div class="flex-grow-1">
                                                    <div class="row g-2">
                                                        <div class="col-md-5">
                                                            <small class="text-muted d-block">Tipo de acción</small>
                                                            <strong>{{ ucfirst(str_replace('_', ' ', $action['action'])) }}</strong>
                                                        </div>
                                                        <div class="col-md-7">
                                                            <small class="text-muted d-block">Valor</small>
                                                            @if(in_array($action['action'], ['resolve_conversation', 'reopen_conversation']))
                                                                <span class="text-muted fst-italic">No requiere valor</span>
                                                            @else
                                                                <strong>{{ $action['value'] }}</strong>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No hay acciones definidas</p>
                    @endif
                </div>
            </div>

            <div class="card-footer bg-white border-top">
                <div class="row g-2">
                    <div class="col-md-6">
                        <a href="{{ route('settings.chat.automation-rules.edit', $Automation->id) }}" class="btn btn-primary w-100">
                            <i class="fas fa-edit"></i> Editar regla
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ route('settings.chat.automation-rules.index') }}" class="btn btn-secondary w-100">
                            <i class="fas fa-arrow-left"></i> Volver al listado
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold">Información del sistema</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5 small">ID:</dt>
                    <dd class="col-sm-7 small">{{ $Automation->id }}</dd>

                    <dt class="col-sm-5 small">Creada:</dt>
                    <dd class="col-sm-7 small">{{ $Automation->created_at->format('d/m/Y H:i') }}</dd>

                    <dt class="col-sm-5 small">Actualizada:</dt>
                    <dd class="col-sm-7 small">{{ $Automation->updated_at->format('d/m/Y H:i') }}</dd>

                    @if($Automation->created_at->ne($Automation->updated_at))
                        <dt class="col-sm-5 small">Modificada hace:</dt>
                        <dd class="col-sm-7 small">{{ $Automation->updated_at->diffForHumans() }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold">Estadísticas</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary-subtle p-3">
                                <i class="fas fa-tasks text-primary"></i>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ count($Automation->conditions) }}</h4>
                                <small class="text-muted">Condiciones</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-info-subtle p-3">
                                <i class="fas fa-play text-info"></i>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ count($Automation->actions) }}</h4>
                                <small class="text-muted">Acciones</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold">Acciones rápidas</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <form action="{{ route('settings.chat.automation-rules.toggle', $Automation->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-{{ $Automation->active ? 'secondary' : 'success' }} w-100">
                            <i class="fas fa-{{ $Automation->active ? 'pause' : 'play' }}"></i>
                            {{ $Automation->active ? 'Desactivar' : 'Activar' }} regla
                        </button>
                    </form>
                    <button type="button"
                            class="btn btn-outline-danger delete-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#delete-modal"
                            data-url="{{ route('settings.chat.automation-rules.destroy', $Automation->id) }}"
                            data-title="Eliminar regla: {{ $Automation->name }}">
                        <i class="fas fa-trash"></i> Eliminar regla
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.delete-btn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const deleteUrl = $(this).data('url');
        const deleteTitle = $(this).data('title');

        $('#delete-modal .modal-title').text(deleteTitle);
        $('#delete-form').attr('action', deleteUrl);
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
