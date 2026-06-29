@extends('layouts.theme')

@section('title', 'Consentimiento — Remarketing')

@section('page_header')
    @include('core::components.card', ['title' => 'Configuración de consentimiento'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <form action="{{ route('settings.remarketing.updateConsent') }}" method="POST">
                @csrf
                @method('PATCH')

                <div class="card mb-3">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Política de privacidad</h5>
                        <small class="text-muted">Versión y textos mostrados en los formularios de consentimiento</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Versión de política <span class="text-danger">*</span></label>
                                <input type="text" name="policy_version" class="form-control @error('policy_version') is-invalid @enderror"
                                       value="{{ old('policy_version', $get('policy_version')) }}"
                                       placeholder="v1.0">
                                @error('policy_version') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-footer d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar configuración
                        </button>
                    </div>
                </div>

            </form>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Sobre el consentimiento</h6>
                    <p class="small text-muted mb-3">Remarketing registra cada evento de consentimiento para cumplir con GDPR y LGPD.</p>
                    <ul class="list-unstyled small text-muted">
                        <li class="mb-1"><i class="fas fa-circle-dot text-primary me-1"></i> Doble opt-in obligatorio</li>
                        <li class="mb-1"><i class="fas fa-circle-dot text-success me-1"></i> Registro de IP y timestamp</li>
                        <li class="mb-1"><i class="fas fa-circle-dot text-warning me-1"></i> Exportación para auditoría</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

@endsection
