@extends('layouts.theme')

@section('title', 'Editar integracion de Slack')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar integracion de Slack'])
@endsection

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.slack-integrations.update', $integration) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar integracion de Slack</h5>
                        <small class="text-muted">#{{ $integration->channel_name }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.slack-integrations._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                        <a href="{{ route('settings.helpdesk.slack-integrations.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Informacion de la integracion</h6>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Estado</span>
                        @if($integration->is_active)
                            <span class="badge bg-success-subtle text-success">Activa</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                        @endif
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Eventos activos</span>
                        <span class="fw-semibold">{{ count($integration->events ?? []) }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Creada</span>
                        <span class="small">{{ $integration->created_at->format('d/m/Y') }}</span>
                    </div>
                    <hr>
                    <p class="small text-muted mb-0">
                        <i class="fas fa-lock text-muted me-1"></i>
                        La URL del webhook esta cifrada. Haz clic en "Cambiar" para actualizarla.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
