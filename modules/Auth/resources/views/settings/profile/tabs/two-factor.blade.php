<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Autenticación de dos factores (2FA)</h6>
                <p class="text-muted small mb-4">Agrega una capa extra de seguridad a tu cuenta requiriendo un código además de tu contraseña</p>

                {{-- 2FA Status --}}
                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white rounded-1 p-3">
                            <i class="fas fa-shield-halved text-warning fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Estado de 2FA</h6>
                            <small class="text-muted">La autenticación de dos factores está desactivada</small>
                        </div>
                    </div>
                    <span class="badge bg-black">Desactivada</span>
                </div>

                {{-- Setup Instructions --}}
                <div class="mb-4">
                    <h6 class="fw-bold mb-3">Cómo activar 2FA</h6>
                    <ol class="small text-muted ps-3">
                        <li class="mb-2">Descarga una aplicación de autenticación como Google Authenticator o Authy</li>
                        <li class="mb-2">Haz clic en "Activar 2FA" para generar un código QR</li>
                        <li class="mb-2">Escanea el código QR con tu aplicación de autenticación</li>
                        <li class="mb-2">Ingresa el código de 6 dígitos que aparece en tu aplicación</li>
                        <li>Guarda tus códigos de recuperación en un lugar seguro</li>
                    </ol>
                </div>

                {{-- Action Button --}}
                <div class="d-grid">
                    <button type="button" class="btn btn-primary" disabled>
                        <i class="fas fa-qrcode me-2"></i>Activar 2FA (Próximamente)
                    </button>
                    <small class="text-muted text-center mt-2">Esta funcionalidad estará disponible próximamente</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border bg-light">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <i class="fas fa-mobile-screen fa-3x text-primary mb-3"></i>
                    <h6 class="fw-bold">Aplicaciones recomendadas</h6>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="fab fa-google text-primary"></i>
                        <strong class="small">Google Authenticator</strong>
                    </div>
                    <small class="text-muted d-block">Aplicación oficial de Google para 2FA</small>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="fas fa-shield text-success"></i>
                        <strong class="small">Authy</strong>
                    </div>
                    <small class="text-muted d-block">Respaldo en la nube y multi-dispositivo</small>
                </div>

                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="fas fa-key text-warning"></i>
                        <strong class="small">Microsoft Authenticator</strong>
                    </div>
                    <small class="text-muted d-block">Aplicación de Microsoft con 2FA</small>
                </div>
            </div>
        </div>

        <div class="card border mt-3">
            <div class="card-body p-4">
                <div class="text-center">
                    <i class="fas fa-info-circle text-info fa-2x mb-2"></i>
                    <h6 class="fw-bold mb-2">¿Por qué 2FA?</h6>
                    <p class="small text-muted mb-0">
                        La autenticación de dos factores añade una capa extra de seguridad,
                        protegiendo tu cuenta incluso si alguien conoce tu contraseña.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
