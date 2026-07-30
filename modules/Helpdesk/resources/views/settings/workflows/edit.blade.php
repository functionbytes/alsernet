@extends('layouts.theme')

@section('title', 'Editar workflow')

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.workflows.update', $workflow) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar workflow</h5>
                        <small class="text-muted">{{ $workflow->name }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.workflows._form', compact('workflow'))
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                        <a href="{{ route('settings.helpdesk.workflows.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Estadisticas del workflow</h6>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Estado</span>
                        <span>
                            @if($workflow->is_active)
                                <span class="badge bg-success-subtle text-success">Activo</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                            @endif
                        </span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Total ejecuciones</span>
                        <span class="fw-semibold">{{ number_format($workflow->total_runs) }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Ultima ejecucion</span>
                        <span class="small">
                            {{ $workflow->last_run_at ? $workflow->last_run_at->diffForHumans() : 'Nunca' }}
                        </span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Creado</span>
                        <span class="small">{{ $workflow->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Ultima actualizacion</span>
                        <span class="small">{{ $workflow->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
