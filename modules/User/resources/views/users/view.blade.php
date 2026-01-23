@extends('layouts.theme')

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        @php
                            $roleName = $user->getRoleNames()->first() ?? 'Sin rol';
                            $badgeClass = match($roleName) {
                                'super-admin' => 'bg-light-danger text-danger',
                                'manager' => 'bg-light-success text-success',
                                'customer' => 'bg-light-info text-info',
                                default => 'bg-light-secondary text-dark'
                            };
                        @endphp
                        <h5 class="mb-1 fw-bold">Detalle del usuario : {{ $user->firstname }} {{ $user->lastname }}</h5>
                        <p class="small mb-0 text-muted">Perfil y detalles del usuario en el sistema</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.users.edit', $user->uid) }}" class="btn btn-primary" title="Editar información del usuario">
                            Editar
                        </a>
                        <a href="{{ route('settings.users.index') }}" class="btn btn-light">
                            Volver
                        </a>
                    </div>
                </div>
            </div>


            {{-- Navigation Pills --}}
            <ul class="nav nav-pills user-profile-tab" id="user-profile-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 active"
                            id="account-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#account"
                            type="button"
                            role="tab"
                            aria-controls="account"
                            aria-selected="true">
                        <span class="d-none d-md-block">Cuenta</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="details-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#details"
                            type="button"
                            role="tab"
                            aria-controls="details"
                            aria-selected="false">
                        <span class="d-none d-md-block">Detalles</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="security-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#security"
                            type="button"
                            role="tab"
                            aria-controls="security"
                            aria-selected="false">
                        <span class="d-none d-md-block">Seguridad</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="activity-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#activity"
                            type="button"
                            role="tab"
                            aria-controls="activity"
                            aria-selected="false">
                        <span class="d-none d-md-block">Actividad</span>
                    </button>
                </li>
            </ul>

            {{-- Tab Content --}}
            <div class="card-body">
                <div class="tab-content" id="user-profile-content">

                    {{-- Tab: Cuenta --}}
                    <div class="tab-pane fade show active" id="account" role="tabpanel" aria-labelledby="account-tab">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="mb-4 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1 fw-bold">Información de cuenta</h6>
                                        <p class="text-muted small mb-0">Detalles principales y estado del usuario</p>
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
                    </div>

                    {{-- Tab: Detalles --}}
                    <div class="tab-pane fade" id="details" role="tabpanel" aria-labelledby="details-tab">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-bold">Información completa del usuario</h6>
                                    <p class="text-muted small mb-0">Todos los detalles registrados en el sistema</p>
                                </div>

                                <div class="card border">
                                    <div class="card-body p-4">
                                        <div class="row">
                                            <div class="col-md-12 mb-4">
                                                <h6 class="text-muted small mb-3 fw-bold">INFORMACIÓN PERSONAL</h6>
                                                <table class="table table-sm table-borderless">
                                                    <tbody>
                                                        <tr>
                                                            <td class="text-muted" width="150">Nombre:</td>
                                                            <td><strong>{{ $user->firstname }}</strong></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-muted">Apellido:</td>
                                                            <td><strong>{{ $user->lastname }}</strong></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-muted">Email:</td>
                                                            <td><strong>{{ $user->email }}</strong></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-muted">Identificación:</td>
                                                            <td>{{ $user->identification ?: 'No especificado' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-muted">Teléfono:</td>
                                                            <td>{{ $user->cellphone ?: 'No especificado' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-muted">Dirección:</td>
                                                            <td>{{ $user->address ?: 'No especificada' }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="col-md-12 mb-4">
                                                <h6 class="text-muted small mb-3 fw-bold">INFORMACIÓN ADICIONAL</h6>
                                                <table class="table table-sm table-borderless">
                                                    <tbody>
                                                        <tr>
                                                            <td class="text-muted" width="150">Empresa:</td>
                                                            <td>{{ $user->company ?: 'No especificada' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-muted">Zona horaria:</td>
                                                            <td>{{ $user->timezone ?: 'No configurada' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-muted">Rol:</td>
                                                            <td><span class="badge {{ $badgeClass }}">{{ Str::title(str_replace('-', ' ', $roleName)) }}</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-muted">UID:</td>
                                                            <td><code class="small">{{ $user->uid }}</code></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-muted">Estado:</td>
                                                            <td><span class="badge bg-{{ $user->available ? 'success' : 'danger' }}">{{ $user->available ? 'Activo' : 'Inactivo' }}</span></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="col-md-12">
                                                <h6 class="text-muted small mb-3 fw-bold">AUDITORÍA</h6>
                                                <table class="table table-sm table-borderless mb-0">
                                                    <tbody>
                                                        <tr>
                                                            <td class="text-muted" width="150">Creado:</td>
                                                            <td>{{ $user->created_at->format('d/m/Y H:i') }} <span class="text-muted small">({{ $user->created_at->diffForHumans() }})</span></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-muted">Actualizado:</td>
                                                            <td>{{ $user->updated_at->format('d/m/Y H:i') }} <span class="text-muted small">({{ $user->updated_at->diffForHumans() }})</span></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab: Seguridad --}}
                    <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="card border">
                                    <div class="card-body p-4">
                                        <h6 class="fw-bold mb-3">Configuración de seguridad</h6>
                                        <p class="text-muted small mb-4">Gestiona las opciones de seguridad de la cuenta</p>

                                        <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-light-{{ $user->email_verified_at ? 'success' : 'warning' }} rounded-1 p-3 d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-envelope-circle-check text-{{ $user->email_verified_at ? 'success' : 'warning' }} fs-5"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">Verificación de email</h6>
                                                    <p class="mb-0 small text-muted">Estado de verificación del correo electrónico</p>
                                                </div>
                                            </div>
                                            @if($user->email_verified_at)
                                                <span class="badge bg-success">Verificado</span>
                                            @else
                                                <span class="badge bg-warning">Pendiente</span>
                                            @endif
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-light-secondary rounded-1 p-3 d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-key text-dark fs-5"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">Contraseña</h6>
                                                    <p class="mb-0 small text-muted">Última actualización: {{ $user->updated_at->format('d/m/Y') }}</p>
                                                </div>
                                            </div>
                                            <a href="{{ route('settings.users.edit', $user->uid) }}" class="btn btn-sm btn-outline-primary">Cambiar</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="card border bg-light">
                                    <div class="card-body p-4">
                                        <div class="text-center mb-3">
                                            <i class="fas fa-shield-halved fa-3x text-primary mb-3"></i>
                                            <h6 class="fw-bold">Nivel de seguridad</h6>
                                        </div>

                                        @php
                                            $securityScore = 0;
                                            if ($user->email_verified_at) $securityScore += 50;
                                            if (strlen($user->password) > 8) $securityScore += 25;
                                            if ($user->updated_at->diffInDays() < 90) $securityScore += 25;

                                            $securityLevel = $securityScore >= 75 ? 'Alto' : ($securityScore >= 50 ? 'Medio' : 'Bajo');
                                            $securityColor = $securityScore >= 75 ? 'success' : ($securityScore >= 50 ? 'warning' : 'danger');
                                        @endphp

                                        <div class="progress mb-3" style="height: 10px;">
                                            <div class="progress-bar bg-{{ $securityColor }}" role="progressbar" style="width: {{ $securityScore }}%" aria-valuenow="{{ $securityScore }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>

                                        <div class="text-center">
                                            <h5 class="mb-0 text-{{ $securityColor }}">{{ $securityLevel }}</h5>
                                            <small class="text-muted">{{ $securityScore }}% seguro</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab: Actividad --}}
                    <div class="tab-pane fade" id="activity" role="tabpanel" aria-labelledby="activity-tab">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-bold">Historial de actividad</h6>
                                    <p class="text-muted small mb-0">Registro de eventos del usuario en el sistema</p>
                                </div>

                                <div class="card border">
                                    <div class="card-body p-4">
                                        <div class="timeline-widget">
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
                                                    <p class="mb-0 small text-muted">Se actualizó la información del usuario</p>
                                                </div>
                                            </div>

                                            <div class="timeline-item d-flex position-relative">
                                                <div class="timeline-time text-muted small me-3" style="min-width: 120px;">
                                                    {{ $user->created_at->format('d M, H:i') }}
                                                </div>
                                                <div class="timeline-badge-wrap d-flex flex-column align-items-center">
                                                    <div class="timeline-badge bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 12px; height: 12px;"></div>
                                                </div>
                                                <div class="timeline-desc fs-3 text-dark ms-3 flex-grow-1">
                                                    <h6 class="mb-1">Usuario creado</h6>
                                                    <p class="mb-0 small text-muted">Cuenta registrada en el sistema</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Ensure Bootstrap pills work correctly
    const tabs = document.querySelectorAll('[data-bs-toggle="pill"]');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Update active state
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
});
</script>
@endpush
