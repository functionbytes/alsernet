@extends('layouts.theme')

@section('title', 'Configuración de seguridad')

@section('page_header')
    @include('core::components.card', ['title' => 'Configuración de seguridad'])
@endsection

@php
    $fmtAge = function (?int $seconds) {
        if ($seconds === null) {
            return '—';
        }
        $d = intdiv($seconds, 86400);
        $h = intdiv($seconds % 86400, 3600);
        $m = intdiv($seconds % 3600, 60);

        return ($d ? $d.' d ' : '').($d || $h ? $h.' h ' : '').$m.' min';
    };
    $yesNo = fn ($v) => $v ? 'sí' : 'no';
@endphp

@section('content')
<div class="px-3">

    @include('core::components.alerts')

    <div class="mb-4">
        <h4 class="fw-bold mb-1">Configuración de seguridad</h4>
        <p class="text-muted mb-0">
            Opciones que antes solo se cambiaban en <code>.env</code>/config. Si una opción no se ha tocado aquí, manda el valor de <code>.env</code> (se indica como "origen: .env").
            Al guardar un valor se aplica en la siguiente petición y en el siguiente job de los workers. Cada cambio queda en el registro de actividad.
            Solo super-admin.
        </p>
    </div>

    <nav class="mb-4 d-flex flex-wrap gap-2 small">
        <a href="#access" class="btn btn-outline-secondary btn-sm">1. Acceso</a>
        <a href="#headers" class="btn btn-outline-secondary btn-sm">2. Cabeceras</a>
        <a href="#watch" class="btn btn-outline-secondary btn-sm">3. Vigilancia</a>
        <a href="#erp" class="btn btn-outline-secondary btn-sm">4. API ERP</a>
        <a href="#documents" class="btn btn-outline-secondary btn-sm">5. Documentos</a>
        <a href="#helpdesk" class="btn btn-outline-secondary btn-sm">6. Helpdesk</a>
        <a href="#ai" class="btn btn-outline-secondary btn-sm">7. IA</a>
        <a href="#supplier" class="btn btn-outline-secondary btn-sm">8. Proveedores</a>
        <a href="#system" class="btn btn-outline-secondary btn-sm">9. Estado del sistema</a>
    </nav>

    {{-- Pie de cada opción --}}
    @php
        $foot = function (string $id) use ($settings) {
            $s = $settings[$id];
            $html = '<div class="form-text">Variable <code>'.e($s['def']['env']).'</code> · ';
            if ($s['override']) {
                $html .= '<span class="badge bg-warning text-dark">origen: panel</span> (en .env: '.e($s['env_display']).') ';
            } else {
                $html .= '<span class="badge bg-light text-dark border">origen: .env</span>';
            }
            $html .= '</div>';

            return $html;
        };
    @endphp

    {{-- ============ 1. ACCESO ============ --}}
    <div class="card border mb-4" id="access">
        <div class="card-body p-3">
            <h5 class="fw-bold mb-3">1. Acceso</h5>

            <div class="row g-4">
                <div class="col-lg-4">
                    <h6 class="fw-bold">Filtro por IP del personal</h6>
                    @if ($ipFilter['available'] ?? false)
                        @php $modeBadge = ['off' => 'bg-secondary', 'monitor' => 'bg-info', 'enforce' => 'bg-danger'][$ipFilter['mode']] ?? 'bg-secondary'; @endphp
                        <p class="mb-1">Modo: <span class="badge {{ $modeBadge }}">{{ $ipFilter['mode'] }}</span> <small class="text-muted">(origen: {{ $ipFilter['source'] }})</small></p>
                        <p class="mb-1 small">Entradas en la lista: {{ $ipFilter['entries'] }}</p>
                    @else
                        <p class="text-muted small">No disponible.</p>
                    @endif
                    @if ($forceOff)
                        <div class="alert alert-warning small py-2">AUTH_STAFF_IP_FILTER_FORCE_OFF está activo en el .env: el filtro está apagado y tiene prioridad sobre cualquier ajuste.</div>
                    @endif
                    @if (Route::has('settings.auth.ip-filter'))
                        <a href="{{ route('settings.auth.ip-filter') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-network-wired me-1"></i> Gestionar redes permitidas</a>
                    @endif

                    <h6 class="fw-bold mt-4">Política de contraseña <small class="text-muted fw-normal">(solo lectura)</small></h6>
                    @php $p = $policy['password']; $l = $policy['lockout']; @endphp
                    <ul class="small mb-0 ps-3">
                        <li>Longitud mínima: <strong>{{ $p['min_length'] ?? '—' }}</strong></li>
                        <li>Mayúsculas / minúsculas / números / símbolos: {{ $yesNo($p['require_uppercase'] ?? false) }} / {{ $yesNo($p['require_lowercase'] ?? false) }} / {{ $yesNo($p['require_numbers'] ?? false) }} / {{ $yesNo($p['require_symbols'] ?? false) }}</li>
                        <li>Rechazar contraseñas filtradas: {{ $yesNo($p['reject_compromised'] ?? false) }}</li>
                        <li>Historial (no reutilizar): {{ $p['history_count'] ?? '—' }}</li>
                        <li>Caducidad: {{ ($p['expires_in_days'] ?? 0) ? $p['expires_in_days'].' días' : 'no caduca' }}</li>
                        <li>Bloqueo de cuenta: {{ $yesNo($l['enabled'] ?? false) }} ({{ $l['max_attempts'] ?? '—' }} intentos, {{ $l['duration_minutes'] ?? '—' }} min)</li>
                    </ul>
                    <p class="form-text">Se cambia en <code>.env</code> (AUTH_PWD_*, AUTH_LOCKOUT_*).</p>
                </div>

                <div class="col-lg-8">
                    <h6 class="fw-bold">2FA obligatorio por rol</h6>
                    <form method="POST" action="{{ route('settings.security.config.update', 'access') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small">Estado</label>
                            <select name="two_factor_enabled" class="form-select form-select-sm w-auto">
                                <option value="0" @selected(! old('two_factor_enabled', $settings['two_factor_enabled']['value']))>Desactivado</option>
                                <option value="1" @selected(old('two_factor_enabled', $settings['two_factor_enabled']['value']))>Activado</option>
                            </select>
                            {!! $foot('two_factor_enabled') !!}
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Roles afectados</label>
                            @php $selectedRoles = (array) old('two_factor_roles', $settings['two_factor_roles']['value']); @endphp
                            <div class="d-flex flex-wrap gap-3">
                                @foreach ($roles as $role)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="two_factor_roles[]" value="{{ $role }}" id="r-{{ $role }}" @checked(in_array($role, $selectedRoles, true))>
                                        <label class="form-check-label small" for="r-{{ $role }}">{{ $role }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('two_factor_roles')<div class="text-danger small">{{ $message }}</div>@enderror
                            {!! $foot('two_factor_roles') !!}
                        </div>

                        @if (($without2fa['total'] ?? 0) > 0)
                            <div class="alert {{ $settings['two_factor_enabled']['value'] ? 'alert-danger' : 'alert-warning' }} small">
                                <strong>{{ $without2fa['total'] }} usuario(s) de esos roles no tienen 2FA.</strong>
                                {{ $settings['two_factor_enabled']['value'] ? 'Ahora mismo' : 'Si lo activas,' }} solo podrán ir a Perfil &gt; Doble factor hasta activarlo (no podrán usar el resto del panel).
                                <ul class="mb-0 mt-2">
                                    @foreach ($without2fa['users'] as $u)
                                        <li>{{ $u['name'] ?: '—' }} &lt;{{ $u['email'] }}&gt; <small class="text-muted">{{ implode(', ', $u['roles']) }}</small></li>
                                    @endforeach
                                </ul>
                                @if ($without2fa['total'] > count($without2fa['users']))
                                    <div class="mt-1">… y {{ $without2fa['total'] - count($without2fa['users']) }} más.</div>
                                @endif
                            </div>
                        @elseif (($without2fa['total'] ?? null) === 0)
                            <div class="alert alert-success small py-2">Todos los usuarios de esos roles tienen 2FA activado.</div>
                        @endif

                        <button type="submit" class="btn btn-primary btn-sm">Guardar acceso</button>
                    </form>
                    <div class="d-flex gap-2 mt-2">
                        @foreach (['two_factor_enabled', 'two_factor_roles'] as $rid)
                            @if ($settings[$rid]['override'])
                                <form method="POST" action="{{ route('settings.security.config.reset', $rid) }}">@csrf<button class="btn btn-link btn-sm p-0">Volver al .env: {{ $settings[$rid]['def']['label'] }}</button></form>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ 2. CABECERAS ============ --}}
    <div class="card border mb-4" id="headers">
        <div class="card-body p-3">
            <h5 class="fw-bold mb-3">2. Cabeceras y navegador</h5>
            @php
                $cspMode = ! $settings['csp_enabled']['value'] ? 'off' : ($settings['csp_report_only']['value'] ? 'report' : 'enforce');
            @endphp
            <div class="row g-4">
                <div class="col-lg-7">
                    <form method="POST" action="{{ route('settings.security.config.update', 'headers') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small">Content-Security-Policy</label>
                            <select name="csp_mode" class="form-select form-select-sm w-auto">
                                <option value="off" @selected(old('csp_mode', $cspMode) === 'off')>Desactivada</option>
                                <option value="report" @selected(old('csp_mode', $cspMode) === 'report')>Solo informe (Report-Only, no bloquea)</option>
                                <option value="enforce" @selected(old('csp_mode', $cspMode) === 'enforce')>Bloqueante</option>
                            </select>
                            <div class="form-text text-danger">Bloqueante: revisa antes los reportes. Un recurso no incluido en la política dejará de cargar en el panel.</div>
                            {!! $foot('csp_enabled') !!}
                            {!! $foot('csp_report_only') !!}
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">X-Robots-Tag (noindex en todas las respuestas)</label>
                            <select name="robots_tag" class="form-select form-select-sm w-auto">
                                <option value="1" @selected(filled($settings['robots_tag']['value']))>Activado</option>
                                <option value="0" @selected(! filled($settings['robots_tag']['value']))>Desactivado</option>
                            </select>
                            <div class="form-text">Valor actual: <code>{{ $settings['robots_tag']['display'] }}</code></div>
                            {!! $foot('robots_tag') !!}
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Guardar cabeceras</button>
                    </form>
                    <div class="d-flex gap-3 mt-2">
                        @foreach (['csp_enabled', 'csp_report_only', 'robots_tag'] as $rid)
                            @if ($settings[$rid]['override'])
                                <form method="POST" action="{{ route('settings.security.config.reset', $rid) }}">@csrf<button class="btn btn-link btn-sm p-0">Volver al .env: {{ $settings[$rid]['def']['label'] }}</button></form>
                            @endif
                        @endforeach
                    </div>
                </div>
                <div class="col-lg-5">
                    <h6 class="fw-bold">Reportes CSP (últimos 7 días)</h6>
                    <p class="mb-1"><strong>{{ $csp['total'] }}</strong> reporte(s) en <code>storage/logs/csp-*.log</code></p>
                    <table class="table table-sm small mb-3 w-auto">
                        @foreach ($csp['days'] as $day => $n)
                            <tr><td>{{ $day }}</td><td class="text-end">{{ $n }}</td></tr>
                        @endforeach
                    </table>
                    <h6 class="fw-bold">HSTS <small class="text-muted fw-normal">(solo lectura)</small></h6>
                    <p class="small mb-0">Lo envía Apache (<code>Strict-Transport-Security: max-age=63072000</code>). La app {{ $system['hsts_app'] ? 'también lo envía (SECURITY_HSTS=true)' : 'no lo duplica (SECURITY_HSTS=false)' }}.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ 3. VIGILANCIA ============ --}}
    <div class="card border mb-4" id="watch">
        <div class="card-body p-3">
            <h5 class="fw-bold mb-3">3. Vigilancia (<code>security:watch</code>)</h5>
            <div class="row g-4">
                <div class="col-lg-7">
                    <p class="small text-muted">Umbrales dentro de una ventana de {{ $watch['window_minutes'] }} min. Al superarse se avisa al rol <code>{{ $watch['notify_role'] }}</code>.</p>
                    <form method="POST" action="{{ route('settings.security.config.update', 'watch') }}">
                        @csrf
                        <div class="row g-3">
                            @foreach (['watch_failed_logins', 'watch_failed_logins_ip', 'watch_failed_logins_email', 'watch_erp_403', 'watch_erp_401'] as $wid)
                                <div class="col-md-6">
                                    <label class="form-label small">{{ $settings[$wid]['def']['label'] }}</label>
                                    <input type="number" name="{{ $wid }}" class="form-control form-control-sm" min="{{ $settings[$wid]['def']['min'] }}" max="{{ $settings[$wid]['def']['max'] }}" value="{{ old($wid, $settings[$wid]['value']) }}" required>
                                    @error($wid)<div class="text-danger small">{{ $message }}</div>@enderror
                                    {!! $foot($wid) !!}
                                    @if ($settings[$wid]['override'])
                                        <button form="reset-{{ $wid }}" class="btn btn-link btn-sm p-0">Volver al .env</button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm mt-3">Guardar umbrales</button>
                    </form>
                    @foreach (['watch_failed_logins', 'watch_failed_logins_ip', 'watch_failed_logins_email', 'watch_erp_403', 'watch_erp_401'] as $wid)
                        @if ($settings[$wid]['override'])
                            <form method="POST" id="reset-{{ $wid }}" action="{{ route('settings.security.config.reset', $wid) }}">@csrf</form>
                        @endif
                    @endforeach
                </div>
                <div class="col-lg-5">
                    <h6 class="fw-bold">Estado</h6>
                    <ul class="small ps-3">
                        <li>Última ejecución de security:watch: <strong>{{ $watch['watch_last_run'] ? $watch['watch_last_run']->format('d/m/Y H:i:s').' ('.$watch['watch_last_run']->diffForHumans().')' : 'sin datos' }}</strong>
                            @if ($watch['watch_duration_ms'] !== null) <span class="text-muted">· {{ $watch['watch_duration_ms'] }} ms</span>@endif
                            @if ($watch['watch_last_run'] && $watch['watch_last_run']->lt(now()->subMinutes(15)))
                                <span class="badge bg-danger">hace más de 15 min: ¿cron parado?</span>
                            @endif
                        </li>
                        <li>Línea base creada: {{ $watch['watch_initialized_at']?->format('d/m/Y H:i') ?? 'sin datos' }}</li>
                        @if ($watch['watch_quiet_until'] && $watch['watch_quiet_until']->isFuture())
                            <li>Ventana de instalación (cambios de ficheros sin alerta) hasta {{ $watch['watch_quiet_until']->format('d/m/Y H:i') }}</li>
                        @endif
                        <li>Aviso por email: <strong>{{ $watch['email'] ? 'sí' : 'no (solo notificación en el panel)' }}</strong> <span class="text-muted">(mailer {{ $watch['mailer'] ?: '—' }})</span></li>
                        <li>Última ejecución de security:audit: <strong>sin datos</strong> <span class="text-muted">(no deja registro)</span>
                            @if ($watch['audit_baseline_at']) · línea base del {{ $watch['audit_baseline_at']->format('d/m/Y H:i') }}@endif
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ 4. API ERP ============ --}}
    <div class="card border mb-4" id="erp">
        <div class="card-body p-3">
            <h5 class="fw-bold mb-3">4. API ERP</h5>
            <form method="POST" action="{{ route('settings.security.config.update', 'erp') }}">
                @csrf
                <label class="form-label small">IPs / rangos CIDR que pueden usar <code>/api/erp/*</code> mientras la autenticación de lectura esté desactivada</label>
                <textarea name="erp_allowed_ips" rows="4" class="form-control form-control-sm font-monospace" required>{{ old('erp_allowed_ips', implode("\n", array_filter(array_map('trim', explode(',', (string) $settings['erp_allowed_ips']['value']))))) }}</textarea>
                @error('erp_allowed_ips')<div class="text-danger small">{{ $message }}</div>@enderror
                <div class="form-text">Una por línea (o separadas por comas). Mantén <code>127.0.0.1</code>, <code>::1</code> y la IP del NAT por la que la propia app se llama a sí misma (Supplier, HelpdeskErp, cumpleaños) además de la tienda.</div>
                {!! $foot('erp_allowed_ips') !!}
                <button type="submit" class="btn btn-primary btn-sm mt-2">Guardar IPs</button>
                @if (Route::has('settings.erp.api-security.edit'))
                    <a href="{{ route('settings.erp.api-security.edit') }}" class="btn btn-outline-secondary btn-sm mt-2 ms-2"><i class="fas fa-key me-1"></i> Autenticación y tokens de la API ERP</a>
                @endif
            </form>
            @if ($settings['erp_allowed_ips']['override'])
                <form method="POST" action="{{ route('settings.security.config.reset', 'erp_allowed_ips') }}" class="mt-2">@csrf<button class="btn btn-link btn-sm p-0">Volver al .env</button></form>
            @endif
        </div>
    </div>

    {{-- ============ 5. DOCUMENTOS ============ --}}
    <div class="card border mb-4" id="documents">
        <div class="card-body p-3">
            <h5 class="fw-bold mb-3">5. Documentos</h5>

            @if ($generatedSecret)
                <div class="alert alert-warning">
                    <strong>Secreto nuevo (se muestra solo esta vez):</strong>
                    <div class="font-monospace user-select-all bg-white border rounded p-2 my-2" style="word-break: break-all;">{{ $generatedSecret }}</div>
                    Configúralo en la tienda (módulo que llama a <code>/api/documents</code> y al webhook order-paid). Hasta entonces, las llamadas firmadas con el secreto anterior fallarán.
                </div>
            @endif

            <div class="row g-4">
                <div class="col-lg-7">
                    <form method="POST" action="{{ route('settings.security.config.update', 'documents') }}" autocomplete="off">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small">Exigir firma HMAC en las llamadas de la tienda (POST /api/documents, /create, /process)</label>
                            <select name="documents_require_signed" class="form-select form-select-sm w-auto">
                                <option value="0" @selected(! old('documents_require_signed', $settings['documents_require_signed']['value']))>No</option>
                                <option value="1" @selected(old('documents_require_signed', $settings['documents_require_signed']['value']))>Sí</option>
                            </select>
                            @error('documents_require_signed')<div class="text-danger small">{{ $message }}</div>@enderror
                            <div class="form-text text-danger">Atención: si la tienda PrestaShop no firma sus llamadas, activar esto rompe la creación de documentos desde la tienda.</div>
                            {!! $foot('documents_require_signed') !!}
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Secreto HMAC de PrestaShop</label>
                            <div class="mb-1">
                                @if ($settings['documents_prestashop_secret']['configured'])
                                    <span class="badge bg-success">configurado</span>
                                @else
                                    <span class="badge bg-secondary">no configurado</span> <small class="text-muted">(sin secreto, el webhook order-paid y las rutas firmadas responden 503)</small>
                                @endif
                            </div>
                            <input type="password" name="documents_prestashop_secret" class="form-control form-control-sm font-monospace" autocomplete="new-password" placeholder="Vacío = sin cambios (mínimo 32 caracteres)">
                            @error('documents_prestashop_secret')<div class="text-danger small">{{ $message }}</div>@enderror
                            <div class="form-text">Solo escritura: el valor guardado nunca se muestra. Se guarda cifrado.</div>
                            {!! $foot('documents_prestashop_secret') !!}
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Validez de las URLs firmadas de ficheros (minutos, 5-1440)</label>
                            <input type="number" name="documents_signed_url_minutes" min="5" max="1440" class="form-control form-control-sm w-auto" value="{{ old('documents_signed_url_minutes', $settings['documents_signed_url_minutes']['value']) }}" required>
                            @error('documents_signed_url_minutes')<div class="text-danger small">{{ $message }}</div>@enderror
                            {!! $foot('documents_signed_url_minutes') !!}
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Guardar documentos</button>
                    </form>
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        @foreach (['documents_require_signed', 'documents_prestashop_secret', 'documents_signed_url_minutes'] as $rid)
                            @if ($settings[$rid]['override'])
                                <form method="POST" action="{{ route('settings.security.config.reset', $rid) }}">@csrf<button class="btn btn-link btn-sm p-0">Volver al .env: {{ $settings[$rid]['def']['label'] }}</button></form>
                            @endif
                        @endforeach
                    </div>
                </div>
                <div class="col-lg-5">
                    <h6 class="fw-bold">Generar secreto aleatorio</h6>
                    <p class="small text-muted">Genera 64 caracteres hexadecimales, los guarda cifrados y los muestra una sola vez para copiarlos en la tienda.</p>
                    <form method="POST" action="{{ route('settings.security.config.generate-secret') }}">
                        @csrf
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="confirm" value="1" id="confirm-secret">
                            <label class="form-check-label small" for="confirm-secret">Entiendo que tengo que poner el secreto nuevo en la tienda; si ya usaba otro, sus llamadas firmadas fallarán hasta entonces.</label>
                        </div>
                        @error('confirm')<div class="text-danger small">{{ $message }}</div>@enderror
                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-key me-1"></i> Generar secreto nuevo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ 6. HELPDESK ============ --}}
    <div class="card border mb-4" id="helpdesk">
        <div class="card-body p-3">
            <h5 class="fw-bold mb-3">6. Helpdesk</h5>
            <form method="POST" action="{{ route('settings.security.config.update', 'helpdesk') }}">
                @csrf
                <label class="form-label small">Disco de adjuntos (conversaciones y tickets)</label>
                <select name="helpdesk_attachments_disk" class="form-select form-select-sm w-auto">
                    @foreach (['public' => 'public (URL directa /storage)', 'local' => 'local (privado, URL firmada)'] as $v => $label)
                        <option value="{{ $v }}" @selected(old('helpdesk_attachments_disk', $settings['helpdesk_attachments_disk']['value']) === $v)>{{ $label }}</option>
                    @endforeach
                </select>
                {!! $foot('helpdesk_attachments_disk') !!}
                <div class="alert alert-danger small mt-2 mb-2">
                    <strong>Rotar APP_KEY antes de pasar a local.</strong> Con <code>local</code> los adjuntos se sirven con URLs firmadas permanentes que se guardan en la BD y se firman con APP_KEY: si se rota después, todas esas URLs dejan de funcionar.
                    Además, HelpdeskTickets comparte este disco: sus adjuntos existentes están en <code>public</code> y no se mueven solos.
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="confirm_local" value="1" id="confirm-local">
                    <label class="form-check-label small" for="confirm-local">Confirmo que APP_KEY ya se ha rotado (necesario para pasar a <code>local</code>).</label>
                </div>
                @error('confirm_local')<div class="text-danger small">{{ $message }}</div>@enderror
                @error('helpdesk_attachments_disk')<div class="text-danger small">{{ $message }}</div>@enderror
                <button type="submit" class="btn btn-primary btn-sm">Guardar disco</button>
            </form>
            @if ($settings['helpdesk_attachments_disk']['override'])
                <form method="POST" action="{{ route('settings.security.config.reset', 'helpdesk_attachments_disk') }}" class="mt-2">@csrf<button class="btn btn-link btn-sm p-0">Volver al .env</button></form>
            @endif
            <hr>
            <p class="small mb-0">Simulador público de conversaciones (<code>/helpdesk/sim</code>): <strong>{{ $system['simulator_public'] ? 'activado' : 'desactivado' }}</strong> <span class="text-muted">(solo lectura; en producción está forzado a desactivado)</span></p>
        </div>
    </div>

    {{-- ============ 7. IA ============ --}}
    <div class="card border mb-4" id="ai">
        <div class="card-body p-3">
            <h5 class="fw-bold mb-3">7. IA</h5>
            <form method="POST" action="{{ route('settings.security.config.update', 'ai') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label small">Servidor MCP HTTP para clientes externos</label>
                    <select name="mcp_server_enabled" class="form-select form-select-sm w-auto">
                        <option value="0" @selected(! old('mcp_server_enabled', $settings['mcp_server_enabled']['value']))>Desactivado</option>
                        <option value="1" @selected(old('mcp_server_enabled', $settings['mcp_server_enabled']['value']))>Activado</option>
                    </select>
                    <div class="form-text">Sirve datos de clientes reales (autenticado, permiso helpdesk.ai.use). No lo actives sin restringirlo a la red interna.@if ($system['routes_cached']) Las rutas están cacheadas: el cambio se aplica al regenerar la caché de rutas.@endif</div>
                    {!! $foot('mcp_server_enabled') !!}
                </div>
                <div class="mb-3">
                    <label class="form-label small">Hosts permitidos del LLM local (Ollama)</label>
                    <textarea name="local_llm_allowed_hosts" rows="2" class="form-control form-control-sm font-monospace" placeholder="localhost">{{ old('local_llm_allowed_hosts', implode("\n", (array) $settings['local_llm_allowed_hosts']['value'])) }}</textarea>
                    @error('local_llm_allowed_hosts')<div class="text-danger small">{{ $message }}</div>@enderror
                    <div class="form-text">Uno por línea, solo nombre o IP. Vacío = el proveedor local queda desactivado.</div>
                    {!! $foot('local_llm_allowed_hosts') !!}
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Guardar IA</button>
            </form>
            <div class="d-flex gap-3 mt-2">
                @foreach (['mcp_server_enabled', 'local_llm_allowed_hosts'] as $rid)
                    @if ($settings[$rid]['override'])
                        <form method="POST" action="{{ route('settings.security.config.reset', $rid) }}">@csrf<button class="btn btn-link btn-sm p-0">Volver al .env: {{ $settings[$rid]['def']['label'] }}</button></form>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- ============ 8. PROVEEDORES ============ --}}
    <div class="card border mb-4" id="supplier">
        <div class="card-body p-3">
            <h5 class="fw-bold mb-3">8. Proveedores</h5>
            <form method="POST" action="{{ route('settings.security.config.update', 'supplier') }}">
                @csrf
                <label class="form-label small">Hosts ERP permitidos en los endpoints configurables de Proveedores</label>
                <textarea name="supplier_erp_allowed_hosts" rows="3" class="form-control form-control-sm font-monospace" required>{{ old('supplier_erp_allowed_hosts', implode("\n", (array) $settings['supplier_erp_allowed_hosts']['value'])) }}</textarea>
                @error('supplier_erp_allowed_hosts')<div class="text-danger small">{{ $message }}</div>@enderror
                <div class="form-text">Uno por línea, solo nombre o IP. El host de ERP_INTERNAL_URL se añade siempre.</div>
                {!! $foot('supplier_erp_allowed_hosts') !!}
                <button type="submit" class="btn btn-primary btn-sm mt-2">Guardar hosts</button>
            </form>
            @if ($settings['supplier_erp_allowed_hosts']['override'])
                <form method="POST" action="{{ route('settings.security.config.reset', 'supplier_erp_allowed_hosts') }}" class="mt-2">@csrf<button class="btn btn-link btn-sm p-0">Volver al .env</button></form>
            @endif
        </div>
    </div>

    {{-- ============ 9. ESTADO DEL SISTEMA ============ --}}
    <div class="card border mb-4" id="system">
        <div class="card-body p-3">
            <h5 class="fw-bold mb-3">9. Estado del sistema <small class="text-muted fw-normal">(solo lectura)</small></h5>
            <div class="table-responsive">
                <table class="table table-sm small align-middle mb-0">
                    <tbody>
                        <tr>
                            <th class="w-25">APP_DEBUG</th>
                            <td>@if ($system['debug'])<span class="badge bg-danger">activado</span> ¡No debe estar activado en producción!@else<span class="badge bg-success">desactivado</span>@endif</td>
                        </tr>
                        <tr>
                            <th>APP_ENV</th>
                            <td><span class="badge {{ $system['env'] === 'production' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $system['env'] }}</span></td>
                        </tr>
                        <tr>
                            <th>Modo mantenimiento</th>
                            <td>{{ $system['maintenance'] ? 'activado' : 'no' }}</td>
                        </tr>
                        <tr>
                            <th>Workers de Horizon</th>
                            <td>
                                @php $w = $system['workers']; @endphp
                                @if (! ($w['available'] ?? false))
                                    sin datos
                                @elseif (($w['count'] ?? 0) === 0)
                                    <span class="badge bg-warning text-dark">ningún horizon:work de esta instalación</span>
                                @else
                                    {{ $w['count'] }} proceso(s). El más antiguo arrancó hace <strong>{{ $fmtAge($w['oldest_seconds']) }}</strong> ({{ $w['started_at']->format('d/m/Y H:i') }}).
                                    @if ($w['oldest_seconds'] > 3600)
                                        <div class="text-danger">Los workers siguen con el código y el .env de cuando arrancaron: tras desplegar cambios hay que ejecutar <code>php artisan horizon:terminate</code>. Los ajustes de esta pantalla sí les llegan (se reaplican en cada job).</div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>ClamAV</th>
                            <td>
                                @if ($system['clamav']['binary'])
                                    <span class="badge bg-success">disponible</span> <code>{{ $system['clamav']['binary'] }}</code>
                                @else
                                    <span class="badge bg-secondary">no instalado</span>
                                @endif
                                <span class="text-muted">· escaneo Media: {{ $yesNo($system['clamav']['media_enabled']) }} · Helpdesk: {{ $yesNo($system['clamav']['helpdesk_enabled']) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>PHP / Laravel</th>
                            <td>{{ $system['php'] }} / {{ $system['laravel'] }}</td>
                        </tr>
                        <tr>
                            <th>Cachés</th>
                            <td>config: {{ $system['config_cached'] ? 'cacheada' : 'no' }} · rutas: {{ $system['routes_cached'] ? 'cacheadas' : 'no' }}</td>
                        </tr>
                        <tr>
                            <th>composer audit</th>
                            <td>
                                @php $ca = $system['composer_audit']; @endphp
                                @if ($ca === null)
                                    sin datos <span class="text-muted">(desde la web no se ejecuta composer; se muestra si existe <code>storage/app/security/composer-audit.json</code>)</span>
                                @elseif ($ca['advisories'] > 0)
                                    <span class="badge bg-danger">{{ $ca['advisories'] }} aviso(s) de seguridad</span> · {{ $ca['abandoned'] }} paquete(s) abandonado(s) · {{ $ca['generated_at']->format('d/m/Y H:i') }}
                                @else
                                    <span class="badge bg-success">sin avisos</span> · {{ $ca['abandoned'] }} paquete(s) abandonado(s) · {{ $ca['generated_at']->format('d/m/Y H:i') }}
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
