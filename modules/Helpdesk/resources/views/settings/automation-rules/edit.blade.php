@extends('layouts.theme')

@section('title', 'Editar regla de automatizacion')


@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.rules.update', $automationRule) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar regla</h5>
                        <small class="text-muted">{{ $automationRule->name }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.automation-rules._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                        <a href="{{ route('settings.helpdesk.rules.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Estadisticas de esta regla</h6>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Veces ejecutada</span>
                        <span class="fw-semibold">{{ number_format($automationRule->triggered_count) }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Evento</span>
                        <span class="small">{{ \Modules\Helpdesk\Models\AutomationRule::EVENTS[$automationRule->event_name] ?? $automationRule->event_name }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Creada</span>
                        <span class="small">{{ $automationRule->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Ultima actualizacion</span>
                        <span class="small">{{ $automationRule->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
