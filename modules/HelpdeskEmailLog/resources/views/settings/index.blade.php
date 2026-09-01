@extends('layouts.theme')

@section('title', 'Log de emails — Configuración')

@section('page_header')
    @include('core::components.card', ['title' => 'Log de emails — Configuración'])
@endsection

@section('content')
    @include('core::components.alerts')

    <div class="row g-4 align-items-start">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('settings.helpdeskemaillog.update') }}">
                @csrf
                @method('PATCH')

                <div class="card">

                    <div class="card-header p-4 border-bottom">
                        <h5 class="mb-1 fw-bold">Configuración del log de emails</h5>
                        <p class="small mb-0 text-muted">Controla cómo se almacenan, purgan y muestran los registros de auditoría de emails</p>
                    </div>

                    <div class="card-body">

                        {{-- Almacenamiento del contenido --}}
                        <div class="mb-4">
                            <h6 class="fw-bold mb-1">Almacenamiento del contenido</h6>
                            <p class="text-muted mb-3">Define si se guarda el cuerpo del email y su tamaño máximo.</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="store_body" class="form-label fw-semibold">Guardar cuerpo HTML/texto del email</label>
                                    <select class="form-select @error('store_body') is-invalid @enderror" id="store_body" name="store_body">
                                        <option value="1" {{ (string) old('store_body', $storeBody ? '1' : '0') === '1' ? 'selected' : '' }}>Sí, guardar contenido</option>
                                        <option value="0" {{ (string) old('store_body', $storeBody ? '1' : '0') === '0' ? 'selected' : '' }}>No, solo metadatos</option>
                                    </select>
                                    @error('store_body')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Si se desactiva, solo se almacenan metadatos (asunto, destinatarios, estado). Recomendado desactivar por privacidad o espacio en disco.</small>
                                </div>

                                <div class="col-md-6">
                                    <label for="max_body_bytes" class="form-label fw-semibold">Tamaño máximo del cuerpo (KB)</label>
                                    <input type="number" class="form-control @error('max_body_bytes') is-invalid @enderror"
                                           id="max_body_bytes" name="max_body_bytes"
                                           value="{{ old('max_body_bytes', $maxBodyKb) }}" min="1" max="10240">
                                    @error('max_body_bytes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Si el cuerpo supera este límite, se trunca. Máximo 10.240 KB.</small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Píxel de apertura --}}
                        <div class="mb-4">
                            <h6 class="fw-bold mb-1">Píxel de apertura</h6>
                            <p class="text-muted mb-3">Controla si los envíos con seguimiento (hoy, "Emails enviados" de HelpdeskTickets) incluyen el píxel que registra la apertura.</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="pixel_tracking_enabled" class="form-label fw-semibold">Insertar píxel de seguimiento</label>
                                    <select class="form-select @error('pixel_tracking_enabled') is-invalid @enderror" id="pixel_tracking_enabled" name="pixel_tracking_enabled">
                                        <option value="1" {{ (string) old('pixel_tracking_enabled', $pixelTrackingEnabled ? '1' : '0') === '1' ? 'selected' : '' }}>Sí, insertar píxel</option>
                                        <option value="0" {{ (string) old('pixel_tracking_enabled', $pixelTrackingEnabled ? '1' : '0') === '0' ? 'selected' : '' }}>No, no insertar píxel</option>
                                    </select>
                                    @error('pixel_tracking_enabled')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Desactivarlo detiene la inserción del píxel en nuevos envíos. No borra las aperturas ya registradas ni afecta a la redirección de clics.</small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Retención y purga --}}
                        <div class="mb-4">
                            <h6 class="fw-bold mb-1">Retención y purga automática</h6>
                            <p class="text-muted mb-3">Antigüedad máxima de los registros y tratamiento de los envíos que quedan en cola.</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="retention_days" class="form-label fw-semibold">Días de retención</label>
                                    <input type="number" class="form-control @error('retention_days') is-invalid @enderror"
                                           id="retention_days" name="retention_days"
                                           value="{{ old('retention_days', $retentionDays) }}" min="0" max="3650">
                                    @error('retention_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Los registros más antiguos se eliminan durante la purga diaria. <strong>0</strong> desactiva la purga.</small>
                                </div>

                                <div class="col-md-6">
                                    <label for="stale_queued_hours" class="form-label fw-semibold">Horas para marcar cola obsoleta</label>
                                    <input type="number" class="form-control @error('stale_queued_hours') is-invalid @enderror"
                                           id="stale_queued_hours" name="stale_queued_hours"
                                           value="{{ old('stale_queued_hours', $staleQueuedHours) }}" min="0" max="8760">
                                    @error('stale_queued_hours')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Registros en estado <em>queued</em> que nunca se confirmaron se marcan como <em>failed</em> tras estas horas. <strong>0</strong> lo desactiva.</small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Visualización --}}
                        <div>
                            <h6 class="fw-bold mb-1">Visualización</h6>
                            <p class="text-muted mb-3">Preferencias por defecto del listado de emails.</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="per_page" class="form-label fw-semibold">Registros por página (por defecto)</label>
                                    <select class="form-select @error('per_page') is-invalid @enderror" id="per_page" name="per_page">
                                        @foreach($perPageOptions as $option)
                                            <option value="{{ $option }}" {{ (int) old('per_page', $perPage) === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    @error('per_page')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Dominios monitorizados (reputación) --}}
                        <div class="mb-4">
                            <h6 class="fw-bold mb-1">Dominios monitorizados (SPF/DKIM/DMARC)</h6>
                            <p class="text-muted mb-3">
                                Un dominio por línea. Opcionalmente, selectores DKIM conocidos separados por coma:
                                <code>midominio.com:selector1,selector2</code>. Sin selector se prueban algunos comunes,
                                pero un DKIM no encontrado se reporta como "no verificable", nunca como "ausente".
                            </p>
                            <textarea class="form-control @error('reputation_domains') is-invalid @enderror"
                                      name="reputation_domains" rows="4"
                                      placeholder="a-alvarez.com&#10;otro-dominio.com:selector1,google">{{ old('reputation_domains', $reputationDomainsText) }}</textarea>
                            @error('reputation_domains')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label for="reputation_window_days" class="form-label fw-semibold">Ventana de días para las tasas</label>
                                    <input type="number" class="form-control @error('reputation_window_days') is-invalid @enderror"
                                           id="reputation_window_days" name="reputation_window_days"
                                           value="{{ old('reputation_window_days', $reputationWindowDays) }}" min="1" max="365">
                                    @error('reputation_window_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Umbrales de rebote y quejas --}}
                        <div>
                            <h6 class="fw-bold mb-1">Umbrales de rebote y quejas</h6>
                            <p class="text-muted mb-3">
                                Al cruzar el umbral crítico, <code>email-logs:check-reputation</code> avisa a
                                manager/super-admin (una sola vez, hasta que se recupere).
                            </p>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="bounce_rate_warning_pct" class="form-label fw-semibold">Rebote — aviso (%)</label>
                                    <input type="number" step="0.1" class="form-control @error('bounce_rate_warning_pct') is-invalid @enderror"
                                           id="bounce_rate_warning_pct" name="bounce_rate_warning_pct"
                                           value="{{ old('bounce_rate_warning_pct', $bounceRateWarning) }}" min="0" max="100">
                                    @error('bounce_rate_warning_pct')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="bounce_rate_critical_pct" class="form-label fw-semibold">Rebote — crítico (%)</label>
                                    <input type="number" step="0.1" class="form-control @error('bounce_rate_critical_pct') is-invalid @enderror"
                                           id="bounce_rate_critical_pct" name="bounce_rate_critical_pct"
                                           value="{{ old('bounce_rate_critical_pct', $bounceRateCritical) }}" min="0" max="100">
                                    @error('bounce_rate_critical_pct')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="complaint_rate_warning_pct" class="form-label fw-semibold">Quejas — aviso (%)</label>
                                    <input type="number" step="0.01" class="form-control @error('complaint_rate_warning_pct') is-invalid @enderror"
                                           id="complaint_rate_warning_pct" name="complaint_rate_warning_pct"
                                           value="{{ old('complaint_rate_warning_pct', $complaintRateWarning) }}" min="0" max="100">
                                    @error('complaint_rate_warning_pct')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="complaint_rate_critical_pct" class="form-label fw-semibold">Quejas — crítico (%)</label>
                                    <input type="number" step="0.01" class="form-control @error('complaint_rate_critical_pct') is-invalid @enderror"
                                           id="complaint_rate_critical_pct" name="complaint_rate_critical_pct"
                                           value="{{ old('complaint_rate_critical_pct', $complaintRateCritical) }}" min="0" max="100">
                                    @error('complaint_rate_critical_pct')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Conector de webhooks de proveedor --}}
                        <div>
                            <h6 class="fw-bold mb-1">Conector de webhooks de proveedor</h6>
                            <p class="text-muted mb-3">
                                Interpreta las notificaciones de rebote/queja que un proveedor de envío
                                (SES, Postmark, Mailgun, Mailrelay) manda por webhook a la URL de abajo.
                            </p>

                            <div class="border rounded p-3 mb-3 bg-light">
                                <p class="mb-0 small">
                                    <strong>Importante:</strong> elegir un proveedor aquí solo determina
                                    cómo se <em>interpretan</em> los webhooks entrantes — no cambia por
                                    dónde sale el correo real. Mientras el envío siga siendo SMTP genérico
                                    (no un proveedor con webhooks), ningún proveedor de estos va a mandar
                                    ninguna notificación, porque nunca procesa correo nuestro. Migrar el
                                    envío de verdad a un proveedor requiere instalar su SDK y cambiar la
                                    configuración de correo — eso es aparte de este panel.
                                </p>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="provider_webhook_provider" class="form-label fw-semibold">Proveedor</label>
                                    <select class="form-select @error('provider_webhook_provider') is-invalid @enderror"
                                            id="provider_webhook_provider" name="provider_webhook_provider">
                                        <option value="" {{ (string) old('provider_webhook_provider', $providerWebhookProvider ?? '') === '' ? 'selected' : '' }}>Ninguno (desactivado)</option>
                                        <option value="mailrelay" {{ (string) old('provider_webhook_provider', $providerWebhookProvider ?? '') === 'mailrelay' ? 'selected' : '' }}>Mailrelay</option>
                                        <option value="ses" {{ (string) old('provider_webhook_provider', $providerWebhookProvider ?? '') === 'ses' ? 'selected' : '' }}>Amazon SES (vía SNS)</option>
                                        <option value="postmark" {{ (string) old('provider_webhook_provider', $providerWebhookProvider ?? '') === 'postmark' ? 'selected' : '' }}>Postmark</option>
                                        <option value="mailgun" {{ (string) old('provider_webhook_provider', $providerWebhookProvider ?? '') === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                                    </select>
                                    @error('provider_webhook_provider')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="provider_webhook_secret" class="form-label fw-semibold">
                                        Secreto de verificación
                                        @if($providerWebhookHasSecret ?? false)
                                            <span class="text-muted fw-normal">(ya configurado)</span>
                                        @endif
                                    </label>
                                    <input type="password" class="form-control @error('provider_webhook_secret') is-invalid @enderror"
                                           id="provider_webhook_secret" name="provider_webhook_secret"
                                           placeholder="{{ ($providerWebhookHasSecret ?? false) ? 'Dejar en blanco para conservar el actual' : 'Token o clave de firma del proveedor' }}"
                                           autocomplete="new-password">
                                    @error('provider_webhook_secret')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">
                                        Mailrelay/Postmark: token compartido que el proveedor debe enviar en cabecera. Mailgun: clave de firma HMAC de la cuenta. SES/SNS: no se usa (la firma la valida SNS con su propio certificado).
                                    </small>
                                </div>

                                @if($providerWebhookUrl ?? null)
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">URL del webhook</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="{{ $providerWebhookUrl }}" readonly onclick="this.select()">
                                            <button class="btn btn-outline-secondary" type="button" data-copy-webhook-url title="Copiar">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Configúrala en el panel del proveedor como destino de sus notificaciones de rebote/queja.</small>
                                    </div>
                                @endif

                                <div class="col-md-3">
                                    <label for="provider_webhook_process_bounces" class="form-label fw-semibold">Procesar rebotes</label>
                                    <select class="form-select" id="provider_webhook_process_bounces" name="provider_webhook_process_bounces">
                                        <option value="1" {{ (string) old('provider_webhook_process_bounces', $providerWebhookProcessBounces ? '1' : '0') === '1' ? 'selected' : '' }}>Sí</option>
                                        <option value="0" {{ (string) old('provider_webhook_process_bounces', $providerWebhookProcessBounces ? '1' : '0') === '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="provider_webhook_process_complaints" class="form-label fw-semibold">Procesar quejas</label>
                                    <select class="form-select" id="provider_webhook_process_complaints" name="provider_webhook_process_complaints">
                                        <option value="1" {{ (string) old('provider_webhook_process_complaints', $providerWebhookProcessComplaints ? '1' : '0') === '1' ? 'selected' : '' }}>Sí</option>
                                        <option value="0" {{ (string) old('provider_webhook_process_complaints', $providerWebhookProcessComplaints ? '1' : '0') === '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="provider_webhook_process_deliveries" class="form-label fw-semibold">Procesar entregas</label>
                                    <select class="form-select" id="provider_webhook_process_deliveries" name="provider_webhook_process_deliveries">
                                        <option value="1" {{ (string) old('provider_webhook_process_deliveries', $providerWebhookProcessDeliveries ? '1' : '0') === '1' ? 'selected' : '' }}>Sí</option>
                                        <option value="0" {{ (string) old('provider_webhook_process_deliveries', $providerWebhookProcessDeliveries ? '1' : '0') === '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                    <small class="text-muted">Aún no se usa (no hay pantalla de confirmaciones de entrega); reservado para cuando la haya.</small>
                                </div>
                                <div class="col-md-3">
                                    <label for="provider_webhook_process_opens" class="form-label fw-semibold">Procesar aperturas</label>
                                    <select class="form-select" id="provider_webhook_process_opens" name="provider_webhook_process_opens">
                                        <option value="1" {{ (string) old('provider_webhook_process_opens', $providerWebhookProcessOpens ? '1' : '0') === '1' ? 'selected' : '' }}>Sí</option>
                                        <option value="0" {{ (string) old('provider_webhook_process_opens', $providerWebhookProcessOpens ? '1' : '0') === '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                    <small class="text-muted">Aún no se usa (las aperturas ya se registran por píxel propio); reservado a futuro.</small>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="card-footer p-4">
                        <button type="submit" class="btn btn-primary w-100">
                            Guardar configuración
                        </button>
                    </div>

                </div>
            </form>
        </div>

        {{-- Columna derecha: sidebar informativo --}}
        <div class="col-lg-4">

            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre esta configuración</h6>
                </div>
                <div class="card-body">
                    <h6 class="fw-semibold mb-1">Almacenamiento del contenido</h6>
                    <p class="text-muted mb-3">Si desactivas el guardado del cuerpo, solo se registran metadatos (asunto, destinatarios, estado). Reduce el uso de disco y el riesgo de exponer datos sensibles.</p>

                    <hr class="my-3">

                    <h6 class="fw-semibold mb-1">Píxel de apertura</h6>
                    <p class="text-muted mb-3">Desactívalo si no quieres que los envíos con seguimiento incluyan el píxel de apertura, por ejemplo por preferencia del cliente o auditoría de privacidad.</p>

                    <hr class="my-3">

                    <h6 class="fw-semibold mb-1">Retención y purga</h6>
                    <p class="text-muted mb-3">Los registros más antiguos que los días configurados se eliminan en la purga diaria. Los envíos que quedan en cola sin confirmarse se marcan como fallidos tras las horas indicadas.</p>

                    <hr class="my-3">

                    <h6 class="fw-semibold mb-1">Visualización</h6>
                    <p class="text-muted mb-0">Define cuántos registros se muestran por página de forma predeterminada en el listado de emails.</p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div>
                        <h6 class="mb-1 fw-semibold">Buzones de rebote</h6>
                        <p class="text-muted mb-0">
                            Configura las cuentas IMAP que procesan los correos devueltos para marcar automáticamente los envíos fallidos.
                        </p>
                    </div>
                    <a href="{{ route('settings.helpdeskemaillog.bounce-mailboxes.index') }}" class="btn btn-primary w-100 mt-2">Configurar</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Consejos de uso</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Desactiva el guardado del cuerpo si manejas datos sensibles o necesitas cumplir políticas de privacidad más estrictas.</li>
                        <li class="mb-2">Una retención de 90 días suele ser un buen equilibrio entre auditoría y espacio en disco.</li>
                        <li class="mb-2">Revisa periódicamente los buzones de rebote para detectar direcciones inválidas.</li>
                        <li class="mb-0">Los cambios se aplican en la siguiente ejecución de la purga programada.</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    $('.form-select').select2({ width: '100%' });

    $('[data-copy-webhook-url]').on('click', function () {
        var $input = $(this).closest('.input-group').find('input');
        $input.select();
        navigator.clipboard?.writeText($input.val());
    });
});
</script>
@endpush
