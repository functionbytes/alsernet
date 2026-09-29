@extends('layouts.theme')

@section('title', 'Auditoría: intentos de inicio de sesión')

@section('page_header')
    @include('core::components.card', ['title' => 'Auditoría: intentos de inicio de sesión'])
@endsection

@section('content')
<div class="px-3">

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Auditoría: intentos de inicio de sesión</h4>
            <p class="text-muted mb-0">Registro completo de accesos exitosos y fallidos del sistema</p>
        </div>
        <div>
            <a href="{{ route('settings.auth.audit.impersonations') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-user-secret me-1"></i> Ver impersonaciones
            </a>
            @if (auth()->user()?->hasRole('super-admin'))
                <a href="{{ route('settings.auth.ip-filter') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-network-wired me-1"></i> Redes permitidas
                </a>
            @endif
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card border mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('settings.auth.audit.login-attempts') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small">Correo</label>
                    <input type="text" name="email" value="{{ $filters['email'] ?? '' }}"
                           class="form-control form-control-sm" placeholder="Buscar por correo...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Estado</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach (['success' => 'Éxito', 'failed' => 'Fallido', 'lockout' => 'Bloqueado', '2fa_success' => '2FA éxito', '2fa_failed' => '2FA fallido', 'ip_not_allowed' => 'IP fuera de la lista', 'ip_remote_access' => 'Acceso remoto (excepción)'] as $val => $label)
                            <option value="{{ $val }}" {{ ($filters['status'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">IP</label>
                    <input type="text" name="ip" value="{{ $filters['ip'] ?? '' }}"
                           class="form-control form-control-sm" placeholder="Filtrar por IP...">
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" name="only_users" value="1" id="only_users" {{ ! empty($filters['only_users']) ? 'checked' : '' }}>
                        <label class="form-check-label small" for="only_users">Solo usuarios identificados</label>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('settings.auth.audit.login-attempts') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-xmark"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card border">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Estado</th>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>IP</th>
                        <th>Razón</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attempts as $a)
                        <tr>
                            <td>
                                @php
                                    $badges = [
                                        'success' => 'bg-success',
                                        'failed' => 'bg-danger',
                                        'lockout' => 'bg-warning',
                                        '2fa_success' => 'bg-success',
                                        '2fa_failed' => 'bg-danger',
                                        'ip_not_allowed' => 'bg-warning',
                                        'ip_remote_access' => 'bg-info',
                                    ];
                                @endphp
                                <span class="badge {{ $badges[$a->status] ?? 'bg-secondary' }}">{{ $a->status }}</span>
                            </td>
                            <td>
                                @if ($a->user)
                                    {{ $a->user->firstname }} {{ $a->user->lastname }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><small>{{ $a->email ?? '—' }}</small></td>
                            <td><small class="font-monospace">{{ $a->ip_address ?? '—' }}</small></td>
                            <td><small class="text-muted">{{ $a->reason ?? '—' }}</small></td>
                            <td><small>{{ $a->attempted_at?->format('d M Y, H:i:s') ?? '—' }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No hay intentos registrados con esos filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($attempts->hasPages())
            <div class="card-footer">
                {{ $attempts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
