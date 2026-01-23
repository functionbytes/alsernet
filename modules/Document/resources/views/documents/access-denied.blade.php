@extends('layouts.theme')

@section('title', 'Acceso Denegado')

@section('content')



                <!-- Error Card -->
                <div class="card border-0 shadow-lg">

                    <!-- Header with Icon -->
                    <div class="card-header bg-danger border-0 text-white d-flex align-items-center justify-content-center p-5"
                         style="height: 120px;">
                        <div class="text-center">
                            <i class="fas fa-lock fa-3x"></i>
                        </div>
                    </div>

                    <!-- Body Content -->
                    <div class="card-body text-center p-5">

                        <!-- Error Code -->
                        <h1 class="display-4 fw-bold text-danger mb-2">403</h1>

                        <!-- Error Title -->
                        <h3 class="fw-bold text-dark mb-3">Acceso Denegado</h3>

                        <!-- Error Message -->
                        <p class="text-muted fs-6 mb-4">
                            @if(isset($message))
                                {{ $message }}
                            @else
                                No tienes permiso para acceder a esta sección.
                            @endif
                        </p>

                        <!-- Permission Details -->
                        <div class="alert alert-warning alert-sm mb-4" role="alert">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fas fa-info-circle fs-5"></i>
                                <div class="text-start">
                                    <strong>Permisos requeridos:</strong>
                                    @if(isset($action))
                                        <div class="small mt-2">
                                            <span class="badge bg-secondary">{{ $action }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Help Text -->
                        <p class="text-muted small mb-4">
                            Si crees que deberías tener acceso a esta sección, contacta con tu administrador
                            para que revise tus permisos en el panel de control.
                        </p>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <a href="{{ route('documents.index') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>Volver a documentos
                            </a>
                            <a href="" class="btn btn-secondary btn-lg">
                                <i class="fas fa-home me-2"></i>Ir al inicio
                            </a>
                        </div>

                    </div>

                    <!-- Footer Info -->
                    <div class="card-footer bg-light border-top">
                        <small class="text-muted d-flex align-items-center justify-content-center gap-2">
                            <i class="fas fa-question-circle"></i>
                            ¿Necesitas ayuda? Contacta al administrador del sistema
                        </small>
                    </div>

                </div>

                <!-- Additional Info Card -->
                <div class="card mt-4 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-shield-alt text-info me-2"></i>
                            Información de seguridad
                        </h6>
                        <ul class="list-unstyled small text-muted">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>Acceso registrado:</strong> Tu intento ha sido registrado para auditoría
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-user-shield text-info me-2"></i>
                                <strong>Cuenta segura:</strong> Tu sesión es válida y está protegida
                            </li>
                            <li>
                                <i class="fas fa-clock text-warning me-2"></i>
                                <strong>Soporte disponible:</strong> Contacta al equipo de administración
                            </li>
                        </ul>
                    </div>
                </div>


@endsection

@push('styles')
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .card {
            border-radius: 12px;
        }

        .card-header {
            border-radius: 12px 12px 0 0;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3e99 100%);
        }

        .display-4 {
            font-size: 4rem;
            line-height: 1;
        }
    </style>
@endpush
