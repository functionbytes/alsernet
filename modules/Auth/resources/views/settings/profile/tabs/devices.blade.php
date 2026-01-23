<div class="row g-4">
    <div class="col-12">
        <div class="mb-4">
            <h6 class="mb-1 fw-bold">Dispositivos conectados</h6>
            <p class="text-muted small mb-0">Gestiona los dispositivos que han accedido a tu cuenta</p>
        </div>

        <div class="card border">
            <div class="card-body p-4">
                {{-- Current Device --}}
                <div class="d-flex align-items-start justify-content-between py-3 border-bottom">
                    <div class="d-flex align-items-start gap-3 flex-grow-1">
                        <div class="bg-light-primary rounded-1 p-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-desktop text-primary fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <h6 class="mb-0">Dispositivo actual</h6>
                                <span class="badge bg-success">En uso</span>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted d-block">
                                    <i class="fas fa-laptop me-1"></i>
                                    <strong>Tipo:</strong>
                                    @php
                                        $userAgent = request()->userAgent();
                                        if (str_contains($userAgent, 'Mobile')) {
                                            echo 'Móvil';
                                        } elseif (str_contains($userAgent, 'Tablet')) {
                                            echo 'Tablet';
                                        } else {
                                            echo 'Escritorio';
                                        }
                                    @endphp
                                </small>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-globe me-1"></i>
                                    <strong>Navegador:</strong>
                                    @php
                                        if (str_contains($userAgent, 'Chrome')) {
                                            echo 'Google Chrome';
                                        } elseif (str_contains($userAgent, 'Firefox')) {
                                            echo 'Mozilla Firefox';
                                        } elseif (str_contains($userAgent, 'Safari')) {
                                            echo 'Safari';
                                        } elseif (str_contains($userAgent, 'Edge')) {
                                            echo 'Microsoft Edge';
                                        } else {
                                            echo 'Otro navegador';
                                        }
                                    @endphp
                                </small>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-map-marker-alt me-1"></i>
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

                {{-- Previous Devices Placeholder --}}
                <div class="text-center py-5">
                    <i class="fas fa-mobile-screen fa-3x mb-3 text-muted opacity-50"></i>
                    <h6 class="fw-bold mb-2">Sin dispositivos previos</h6>
                    <p class="text-muted mb-0 small">
                        No se han registrado otros dispositivos. Este es tu único dispositivo activo.
                    </p>
                </div>

                {{-- Info Alert --}}
                <div class="alert alert-info border-0 mt-4" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-2">Control de dispositivos</h6>
                            <p class="mb-0 small">
                                Revisa regularmente los dispositivos que han accedido a tu cuenta.
                                Si encuentras algún dispositivo desconocido, puedes revocarlo y cambiar tu contraseña inmediatamente.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
