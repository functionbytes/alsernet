@extends('layouts.theme')

@section('title', 'Nueva conexión Google')

@section('content')

    @include('core::components.card', ['title' => 'Conectar con Google My Business'])

    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- Encabezado -->
                    <div class="mb-4 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 google-icon-circle">
                            <i class="fab fa-google fa-3x"></i>
                        </div>
                        <h5 class="fw-bold. ">Conectar cuenta de Google</h5>
                        <p class="text-muted mb-0">
                            Autoriza el acceso a tu cuenta de Google My Business para sincronizar reseñas automáticamente.
                        </p>
                    </div>

                    <hr class="my-4">

                    <!-- Formulario -->
                    <form id="connection-form" method="POST" action="{{ route('settings.reviews.connections.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="name" class="form-label fw-semibold">Nombre descriptivo de la conexión</label>
                            <input type="text"
                                   class="form-control form-control-lg"
                                   id="name"
                                   name="name"
                                   placeholder="Ej: Cuenta principal, Sucursal norte..."
                                   required>
                            <small class="form-text text-muted">
                                Este nombre te ayudará a identificar la conexión en el panel.
                            </small>
                        </div>

                        <!-- Permisos solicitados -->
                        <div class="bg-light rounded-3 p-4 mb-4">
                            <div class="d-flex">
                                <div>
                                    <h6 class="fw-bold mb-2">¿Qué permisos se solicitan?</h6>
                                    <ul class="mb-0">
                                        <li class="mb-1">Lectura de ubicaciones de Google My Business</li>
                                        <li class="mb-1">Lectura de reseñas de clientes</li>
                                        <li class="mb-0">Capacidad de responder a reseñas</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Pasos siguientes -->
                        <div class="bg-light rounded-3 p-4 mb-4">
                            <h6 class="fw-bold mb-3">
                                Pasos siguientes
                            </h6>
                            <div class="timeline-steps">
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center step-badge">1</span>
                                    </div>
                                    <div>
                                        <strong class="d-block">Autorización</strong>
                                        <small class="text-muted">Serás redirigido a Google para autorizar el acceso.</small>
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center step-badge">2</span>
                                    </div>
                                    <div>
                                        <strong class="d-block">Selección de ubicaciones</strong>
                                        <small class="text-muted">Podrás elegir qué ubicaciones sincronizar.</small>
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center step-badge">3</span>
                                    </div>
                                    <div>
                                        <strong class="d-block">Configuración</strong>
                                        <small class="text-muted">Configura el intervalo de sincronización y opciones de moderación.</small>
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <div class="me-3">
                                        <span class="badge bg-success rounded-circle d-flex align-items-center justify-content-center step-badge">
                                            <i class="fas fa-check"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <strong class="d-block ">Listo</strong>
                                        <small class="text-muted">Las reseñas comenzarán a sincronizarse automáticamente.</small>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </form>
                </div>

                <div class="card-footer gap-2">
                    <button type="submit" id="connect-google" class="btn btn-primary w-100 py-2">
                        Conectar
                    </button>
                    <a href="{{ route('settings.reviews.connections.index') }}"  class="btn btn-light border w-100 py-2">
                        Cancelar
                    </a>
                </div>

            </div>
        </div>
    </div>

@endsection

@push('css')
<style>
.google-icon-circle { width: 80px; height: 80px; background: rgba(66, 133, 244, 0.1); }
.step-badge { width: 32px; height: 32px; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('modules/reviews/js/oauth-wizard.js') }}"></script>
@endpush
