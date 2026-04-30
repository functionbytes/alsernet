@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    @include('core::components.alerts')

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Conectar canal de Instagram</h4>
            <p class="text-muted mb-0">Configura la integración con Instagram Business</p>
        </div>
        <a href="{{ route('settings.chat.channels.instagrams.index') }}" class="btn btn-light">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row">
        <!-- Main Connection Card -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm overflow-hidden">
                <!-- Instagram Gradient Header -->
                <div class="card-header border-0" style="background: linear-gradient(135deg, #833ab4 0%, #fd1d1d 50%, #fcb045 100%); min-height: 120px; position: relative;">
                    <div class="position-absolute top-50 start-50 translate-middle text-center text-white">
                        <i class="fab fa-instagram" style="font-size: 3.5rem; opacity: 0.9;"></i>
                        <h5 class="mt-2 mb-0 fw-semibold">Instagram Business</h5>
                    </div>
                </div>

                <div class="card-body text-center py-5 px-4">
                    <h4 class="mb-3 fw-semibold">Conecta tu cuenta de Instagram Business</h4>
                    <p class="text-muted mb-4 mx-auto" style="max-width: 500px;">
                        Autoriza la conexión con tu cuenta de Instagram Business para comenzar a recibir
                        y responder mensajes directos desde tu panel de gestión.
                    </p>

                    <!-- Connection Steps -->
                    <div class="row g-3 mb-5 text-start">
                        <div class="col-md-4">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <span class="fw-bold text-primary">1</span>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">Autorizar</h6>
                                    <p class="text-muted small mb-0">Serás redirigido a Instagram para iniciar sesión</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <span class="fw-bold text-primary">2</span>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">Permisos</h6>
                                    <p class="text-muted small mb-0">Otorga acceso a mensajería de Instagram</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <span class="fw-bold text-primary">3</span>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">Listo</h6>
                                    <p class="text-muted small mb-0">Tu canal estará configurado automáticamente</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CTA Button -->
                    <a href="{{ $oauthUrl }}" class="btn btn-lg px-5 py-3 text-white fw-semibold shadow-sm" style="background: linear-gradient(135deg, #833ab4 0%, #fd1d1d 50%, #fcb045 100%);">
                        <i class="fab fa-instagram me-2"></i> Conectar con Instagram
                    </a>

                    <p class="text-muted small mt-4 mb-0">
                        <i class="fas fa-lock me-1"></i>
                        Conexión segura mediante OAuth 2.0
                    </p>
                </div>
            </div>
        </div>

        <!-- Sidebar Information -->
        <div class="col-lg-4">
            <!-- Requirements Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                            <i class="fas fa-list-check text-info"></i>
                        </div>
                        <h6 class="mb-0 fw-semibold">Requisitos previos</h6>
                    </div>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2 d-flex align-items-start">
                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                            <span>Cuenta de Instagram Business activa</span>
                        </li>
                        <li class="mb-2 d-flex align-items-start">
                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                            <span>Cuenta vinculada a una página de Facebook</span>
                        </li>
                        <li class="mb-2 d-flex align-items-start">
                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                            <span>Permisos de administrador de la página</span>
                        </li>
                        <li class="mb-0 d-flex align-items-start">
                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                            <span>Acceso a mensajería directa habilitado</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Privacy Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                            <i class="fas fa-shield-halved text-success"></i>
                        </div>
                        <h6 class="mb-0 fw-semibold">Seguridad y privacidad</h6>
                    </div>
                    <p class="small text-muted mb-0">
                        Solo solicitamos permisos mínimos necesarios para la mensajería.
                        Tu información está protegida y nunca se comparte con terceros.
                    </p>
                </div>
            </div>

            <!-- Help Card -->
            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(131, 58, 180, 0.05) 0%, rgba(253, 29, 29, 0.05) 50%, rgba(252, 176, 69, 0.05) 100%);">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                            <i class="fas fa-circle-question text-warning"></i>
                        </div>
                        <h6 class="mb-0 fw-semibold">¿Necesitas ayuda?</h6>
                    </div>
                    <p class="small mb-3">
                        Si tienes problemas conectando tu cuenta, verifica que tu cuenta de Instagram esté configurada como Business y vinculada a Facebook.
                    </p>
                    <a href="#" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="fas fa-book me-1"></i> Ver documentación
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
