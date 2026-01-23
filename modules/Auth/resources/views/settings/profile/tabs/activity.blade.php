<div class="row g-4">
    <div class="col-12">
        <div class="mb-4">
            <h6 class="mb-1 fw-bold">Historial de actividad</h6>
            <p class="text-muted small mb-0">Registro de eventos y acciones en tu cuenta</p>
        </div>

        <div class="card border">
            <div class="card-body p-4">
                <div class="timeline-widget">
                    {{-- Login Event --}}
                    <div class="timeline-item d-flex position-relative pb-4">
                        <div class="timeline-time text-muted small me-3" style="min-width: 120px;">
                            {{ now()->format('d M, H:i') }}
                        </div>
                        <div class="timeline-badge-wrap d-flex flex-column align-items-center">
                            <div class="timeline-badge bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 12px; height: 12px;"></div>
                            <div class="timeline-badge-border bg-light" style="width: 2px; height: 100%;"></div>
                        </div>
                        <div class="timeline-desc fs-3 text-dark ms-3 flex-grow-1">
                            <h6 class="mb-1">Inicio de sesión</h6>
                            <p class="mb-1 small text-muted">Acceso desde {{ request()->ip() }}</p>
                            <small class="text-muted">
                                <i class="fas fa-desktop me-1"></i>
                                @php
                                    $userAgent = request()->userAgent();
                                    if (str_contains($userAgent, 'Chrome')) {
                                        echo 'Chrome';
                                    } elseif (str_contains($userAgent, 'Firefox')) {
                                        echo 'Firefox';
                                    } elseif (str_contains($userAgent, 'Safari')) {
                                        echo 'Safari';
                                    } else {
                                        echo 'Otro navegador';
                                    }
                                @endphp
                            </small>
                        </div>
                    </div>

                    {{-- Profile Updated Event --}}
                    @if($user->updated_at->diffInDays($user->created_at) > 0)
                    <div class="timeline-item d-flex position-relative pb-4">
                        <div class="timeline-time text-muted small me-3" style="min-width: 120px;">
                            {{ $user->updated_at->format('d M, H:i') }}
                        </div>
                        <div class="timeline-badge-wrap d-flex flex-column align-items-center">
                            <div class="timeline-badge bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 12px; height: 12px;"></div>
                            <div class="timeline-badge-border bg-light" style="width: 2px; height: 100%;"></div>
                        </div>
                        <div class="timeline-desc fs-3 text-dark ms-3 flex-grow-1">
                            <h6 class="mb-1">Perfil actualizado</h6>
                            <p class="mb-0 small text-muted">Se actualizó la información del perfil</p>
                        </div>
                    </div>
                    @endif

                    {{-- Email Verified Event --}}
                    @if($user->email_verified_at)
                    <div class="timeline-item d-flex position-relative pb-4">
                        <div class="timeline-time text-muted small me-3" style="min-width: 120px;">
                            {{ $user->email_verified_at->format('d M, H:i') }}
                        </div>
                        <div class="timeline-badge-wrap d-flex flex-column align-items-center">
                            <div class="timeline-badge bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 12px; height: 12px;"></div>
                            <div class="timeline-badge-border bg-light" style="width: 2px; height: 100%;"></div>
                        </div>
                        <div class="timeline-desc fs-3 text-dark ms-3 flex-grow-1">
                            <h6 class="mb-1">Email verificado</h6>
                            <p class="mb-0 small text-muted">Verificación exitosa del correo electrónico</p>
                        </div>
                    </div>
                    @endif

                    {{-- Account Created Event --}}
                    <div class="timeline-item d-flex position-relative">
                        <div class="timeline-time text-muted small me-3" style="min-width: 120px;">
                            {{ $user->created_at->format('d M, H:i') }}
                        </div>
                        <div class="timeline-badge-wrap d-flex flex-column align-items-center">
                            <div class="timeline-badge bg-info rounded-circle d-flex align-items-center justify-content-center" style="width: 12px; height: 12px;"></div>
                        </div>
                        <div class="timeline-desc fs-3 text-dark ms-3 flex-grow-1">
                            <h6 class="mb-1">Cuenta creada</h6>
                            <p class="mb-0 small text-muted">Registro en el sistema completado</p>
                        </div>
                    </div>
                </div>

                {{-- Info Alert --}}
                <div class="alert alert-info border-0 mt-4" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-2">Historial de eventos</h6>
                            <p class="mb-0 small">
                                Este es un resumen básico de tu actividad en el sistema.
                                Para un registro detallado de todas las acciones, contacta al administrador.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
