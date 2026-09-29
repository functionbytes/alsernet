{{-- Excepción de acceso remoto en la ficha de usuario (29-sep-2026). Solo super-admin. --}}
@if (auth()->user()?->hasRole('super-admin') && \Modules\Auth\Services\StaffIpAllowlist::remoteColumnsExist())
    @php
        $ipFilter = app(\Modules\Auth\Services\StaffIpAllowlist::class);
        $remoteOn = (bool) $user->remote_access_enabled;
        $remoteUntil = $user->remote_access_until ? \Illuminate\Support\Carbon::parse($user->remote_access_until) : null;
        $remoteEffective = $ipFilter->remoteExceptionUsable($user);
    @endphp
    <div class="py-3 border-top">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-light-{{ $remoteEffective ? 'success' : 'secondary' }} rounded-1 p-3 d-flex align-items-center justify-content-center">
                    <i class="fas fa-globe text-{{ $remoteEffective ? 'success' : 'dark' }} fs-5"></i>
                </div>
                <div>
                    <h6 class="mb-0">Acceso remoto (fuera de las redes permitidas)</h6>
                    <p class="mb-0 text-muted small">
                        @if ($remoteOn)
                            Concedido{{ $remoteUntil ? ' hasta '.$remoteUntil->format('d/m/Y H:i') : ' sin límite' }}.
                            {{ $remoteEffective ? 'Activo (requiere verificar el 2FA en cada sesión).' : 'Sin efecto: '.($user->hasTwoFactorEnabled() ? 'caducado.' : 'el usuario no tiene 2FA activado.') }}
                        @else
                            No concedido. Solo surte efecto con 2FA activado y verificado.
                        @endif
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('settings.auth.ip-filter.remote-access') }}" class="d-flex gap-2 align-items-center">
                @csrf
                <input type="hidden" name="user_id" value="{{ $user->id }}">
                @if ($remoteOn)
                    <input type="hidden" name="enabled" value="0">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Retirar</button>
                @else
                    <input type="hidden" name="enabled" value="1">
                    <input type="datetime-local" name="until" class="form-control form-control-sm" title="Hasta (opcional)">
                    <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">Conceder</button>
                @endif
            </form>
        </div>
    </div>
@endif
