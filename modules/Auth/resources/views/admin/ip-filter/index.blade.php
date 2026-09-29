@extends('layouts.theme')

@section('title', 'Redes permitidas')

@section('page_header')
    @include('core::components.card', ['title' => 'Redes permitidas'])
@endsection

@section('content')
<div class="px-3">

    @include('core::components.alerts')

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Redes permitidas para el login y el panel</h4>
            <p class="text-muted mb-0">Filtro por IP de las rutas del personal (login, recuperación de contraseña, 2FA, /panel). No afecta al portal de clientes, al widget, a los webhooks ni a la subida pública de documentos.</p>
        </div>
        <div>
            <a href="{{ route('settings.auth.audit.login-attempts', ['status' => 'ip_not_allowed']) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-list me-1"></i> Accesos de fuera de la lista
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Estado --}}
    <div class="card border mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <small class="text-muted d-block">Modo efectivo</small>
                    @php $modeBadge = ['off' => 'bg-secondary', 'monitor' => 'bg-info', 'enforce' => 'bg-danger'][$mode] ?? 'bg-secondary'; @endphp
                    <span class="badge {{ $modeBadge }} fs-3">{{ $mode }}</span>
                    <small class="text-muted ms-1">(origen: {{ $modeSource }})</small>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Tu IP actual</small>
                    <span class="font-monospace">{{ $currentIp }}</span>
                    <span class="badge {{ $currentIpAllowed ? 'bg-success' : 'bg-warning' }}">{{ $currentIpAllowed ? 'en la lista' : 'fuera de la lista' }}</span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Usuarios identificados fuera de la lista (7 días)</small>
                    <strong>{{ $outsideUsers7d }}</strong> registros
                </div>
            </div>
            @if ($forcedOff)
                <div class="alert alert-warning mt-3 mb-0">AUTH_STAFF_IP_FILTER_FORCE_OFF está activo en el servidor: el filtro está desactivado sea cual sea el modo guardado.</div>
            @endif
        </div>
    </div>

    <div class="row g-4">
        {{-- Modo --}}
        <div class="col-lg-4">
            <div class="card border h-100">
                <div class="card-body p-3">
                    <h6 class="fw-bold">Modo</h6>
                    <ul class="small text-muted ps-3">
                        <li><strong>off</strong>: sin filtro.</li>
                        <li><strong>monitor</strong>: deja pasar y registra los accesos de fuera de la lista en la auditoría.</li>
                        <li><strong>enforce</strong>: bloquea con 403 y cierra la sesión si la había.</li>
                    </ul>
                    <form method="POST" action="{{ route('settings.auth.ip-filter.mode') }}">
                        @csrf
                        <select name="mode" class="form-select form-select-sm mb-2">
                            @foreach (['off', 'monitor', 'enforce'] as $m)
                                <option value="{{ $m }}" {{ $mode === $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Guardar modo</button>
                    </form>
                    <p class="small text-muted mt-3 mb-0">Emergencia desde el servidor: <code>php artisan auth:ip-filter off</code></p>
                </div>
            </div>
        </div>

        {{-- Lista --}}
        <div class="col-lg-8">
            <div class="card border h-100">
                <div class="card-body p-3">
                    <h6 class="fw-bold">Lista de IPs y rangos</h6>
                    <p class="small text-muted">Una entrada por línea: IP o rango CIDR (IPv4 o IPv6) seguido de una descripción. Siempre permitidas, además: {{ implode(', ', $alwaysAllowed) }}. No se guarda una lista que deje fuera tu IP actual.</p>
                    <form method="POST" action="{{ route('settings.auth.ip-filter.allowlist') }}">
                        @csrf
                        <textarea name="allowlist" rows="8" class="form-control font-monospace mb-2" placeholder="203.0.113.10  Oficina&#10;10.0.0.0/24  LAN">{{ old('allowlist', $entriesText) }}</textarea>
                        <button type="submit" class="btn btn-primary btn-sm">Guardar lista</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Excepciones remotas --}}
    <div class="card border mt-4">
        <div class="card-body p-3">
            <h6 class="fw-bold">Excepciones de acceso remoto</h6>
            <p class="small text-muted">Permiten entrar desde cualquier IP, pero solo si el usuario tiene el 2FA activado y lo ha verificado en esa sesión. Las sesiones remotas caducan a las {{ (int) config('auth.auth-policy.staff_ip_filter.remote_session_hours', 8) }} horas. También se gestionan desde la ficha del usuario (pestaña Seguridad).</p>

            @if (! $remoteColumns)
                <div class="alert alert-warning">Falta ejecutar la migración de acceso remoto en la tabla users.</div>
            @else
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light">
                            <tr><th>Usuario</th><th>Hasta</th><th>2FA</th><th>Efectiva</th><th></th></tr>
                        </thead>
                        <tbody>
                            @forelse ($remoteUsers as $u)
                                @php
                                    $until = $u->remote_access_until ? \Illuminate\Support\Carbon::parse($u->remote_access_until) : null;
                                    $effective = app(\Modules\Auth\Services\StaffIpAllowlist::class)->remoteExceptionUsable($u);
                                @endphp
                                <tr>
                                    <td>{{ $u->firstname }} {{ $u->lastname }} <small class="text-muted">{{ $u->email }}</small></td>
                                    <td><small>{{ $until ? $until->format('d/m/Y H:i') : 'sin límite' }}</small></td>
                                    <td><span class="badge {{ $u->hasTwoFactorEnabled() ? 'bg-success' : 'bg-warning' }}">{{ $u->hasTwoFactorEnabled() ? 'activado' : 'no' }}</span></td>
                                    <td><span class="badge {{ $effective ? 'bg-success' : 'bg-secondary' }}">{{ $effective ? 'sí' : 'no' }}</span></td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('settings.auth.ip-filter.remote-access') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $u->id }}">
                                            <input type="hidden" name="enabled" value="0">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Retirar</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-3">Ningún usuario tiene excepción de acceso remoto.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <form method="POST" action="{{ route('settings.auth.ip-filter.remote-access') }}" class="row g-2 align-items-end">
                    @csrf
                    <input type="hidden" name="enabled" value="1">
                    <div class="col-md-5">
                        <label class="form-label small">Correo del usuario</label>
                        <input type="email" name="email" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Hasta (opcional)</label>
                        <input type="datetime-local" name="until" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Conceder acceso remoto</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
