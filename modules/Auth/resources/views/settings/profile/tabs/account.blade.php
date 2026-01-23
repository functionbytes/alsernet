<div class="row g-4">
    <div class="col-12">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1 fw-bold">Información de cuenta</h6>
                <p class="text-muted small mb-0">Detalles principales y estado de tu cuenta</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card border">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Datos personales</h6>

                        <div class="mb-3 pb-3 border-bottom">
                            <small class="text-muted d-block mb-1">Email</small>
                            <p class="mb-0"><strong>{{ $user->email }}</strong></p>
                        </div>

                        <div class="mb-3 pb-3 border-bottom">
                            <small class="text-muted d-block mb-1">Nombre completo</small>
                            <p class="mb-0"><strong>{{ $user->firstname }} {{ $user->lastname }}</strong></p>
                        </div>

                        <div class="mb-3 pb-3 border-bottom">
                            <small class="text-muted d-block mb-1">Rol asignado</small>
                            <p class="mb-0"><span class="badge {{ $badgeClass }}">{{ Str::title(str_replace('-', ' ', $roleName)) }}</span></p>
                        </div>

                        <div>
                            <small class="text-muted d-block mb-1">UID</small>
                            <p class="mb-0"><code class="small">{{ $user->uid }}</code></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card border">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Estado de la cuenta</h6>

                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-0 small">Activo/Inactivo</h6>
                                <small class="text-muted">{{ $user->available ? 'Cuenta activa' : 'Cuenta desactivada' }}</small>
                            </div>
                            <span class="badge bg-{{ $user->available ? 'success' : 'danger' }}">{{ $user->available ? 'Activo' : 'Inactivo' }}</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-0 small">Email verificado</h6>
                                <small class="text-muted">
                                    @if($user->email_verified_at)
                                        {{ $user->email_verified_at->format('d/m/Y H:i') }}
                                    @else
                                        Pendiente
                                    @endif
                                </small>
                            </div>
                            <span class="badge bg-{{ $user->email_verified_at ? 'success' : 'warning' }}">{{ $user->email_verified_at ? 'Verificado' : 'Pendiente' }}</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-0 small">Empresa</h6>
                                <small class="text-muted">{{ $user->company ?: 'No especificada' }}</small>
                            </div>
                        </div>

                        <div>
                            <h6 class="mb-0 small">Última actualización</h6>
                            <small class="text-muted">{{ $user->updated_at->diffForHumans() }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
