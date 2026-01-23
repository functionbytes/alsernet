<div class="row g-4">
    <div class="col-12">
        <div class="mb-4">
            <h6 class="mb-1 fw-bold">Sesiones activas</h6>
            <p class="text-muted small mb-0">Gestiona tus sesiones activas en diferentes dispositivos y navegadores</p>
        </div>

        <div class="card border">
            <div class="card-body p-4">
                {{-- Current Session --}}
                <div class="d-flex align-items-start justify-content-between py-3 border-bottom">
                    <div class="d-flex align-items-start gap-3 flex-grow-1">
                        <div class="bg-light-success rounded-1 p-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-circle-check text-success fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <h6 class="mb-0">Sesión actual</h6>
                                <span class="badge bg-success">Activa</span>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted d-block">
                                    <i class="fas fa-desktop me-1"></i>
                                    <strong>Navegador:</strong> {{ request()->userAgent() }}
                                </small>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-globe me-1"></i>
                                    <strong>IP:</strong> {{ request()->ip() }}
                                </small>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-clock me-1"></i>
                                    <strong>Última actividad:</strong> Ahora mismo
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Previous Sessions Placeholder --}}
                <div class="text-center py-5">
                    <i class="fas fa-history fa-3x mb-3 text-muted opacity-50"></i>
                    <h6 class="fw-bold mb-2">Sin sesiones previas</h6>
                    <p class="text-muted mb-0 small">
                        No hay otras sesiones activas en este momento. Esta es tu única sesión activa.
                    </p>
                </div>

                {{-- Info Alert --}}
                <div class="alert alert-info border-0 mt-4" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-2">Control de sesiones</h6>
                            <p class="mb-0 small">
                                Si detectas actividad sospechosa, puedes revocar sesiones individuales o cerrar todas las sesiones excepto la actual.
                                Esto cerrará tu cuenta en otros dispositivos y navegadores.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
