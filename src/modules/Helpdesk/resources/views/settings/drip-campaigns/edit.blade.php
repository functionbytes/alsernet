@extends('layouts.theme')

@section('title', 'Editar drip campaign')

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.helpdesk.drip-campaigns.update', $dripCampaign) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar drip campaign</h5>
                        <small class="text-muted">{{ $dripCampaign->name }}</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')
                        @include('helpdesk::settings.drip-campaigns._form')
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                        <a href="{{ route('settings.helpdesk.drip-campaigns.index') }}" class="btn btn-light w-100">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Estadisticas de la campaña</h6>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Pasos configurados</span>
                        <span class="fw-semibold">{{ $dripCampaign->steps->count() }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Ejecuciones totales</span>
                        <span class="fw-semibold">{{ number_format($dripCampaign->executions()->count()) }}</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Estado</span>
                        <span>
                            @if($dripCampaign->is_active)
                                <span class="badge bg-success-subtle text-success">Activa</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                            @endif
                        </span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Creada</span>
                        <span class="small">{{ $dripCampaign->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Ultima actualizacion</span>
                        <span class="small">{{ $dripCampaign->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
