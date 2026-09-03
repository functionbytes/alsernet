@extends('layouts.theme')

@section('title', 'Actividad de correo — Configuración')

@section('page_header')
    @include('core::components.card', ['title' => 'Log de emails — Configuración'])
@endsection

@include('helpdeskemaillog::settings.partials.css')

@section('content')
    @include('core::components.alerts')

    <div class="emaillog-settings">
        <div class="evx-shell evx-shell-narrow">
            @include('helpdeskemaillog::settings.partials.subnav', ['current' => 'settings'])

            <form method="POST" action="{{ route('settings.helpdeskemaillog.update') }}">
                @csrf
                @method('PATCH')

                {{-- Almacenamiento del contenido --}}
                <div class="evx-section-block">
                    <h2 class="evx-section-title">Almacenamiento del contenido</h2>
                    <p class="evx-section-desc">Define si se guarda el cuerpo del email y su tamaño máximo.</p>

                    <div class="evx-field-grid">
                        <div class="evx-form-field">
                            <label for="store_body" class="evx-form-label">Guardar cuerpo HTML/texto del email</label>
                            <select class="evx-select @error('store_body') is-invalid @enderror" id="store_body" name="store_body">
                                <option value="1" {{ (string) old('store_body', $storeBody ? '1' : '0') === '1' ? 'selected' : '' }}>Sí, guardar contenido</option>
                                <option value="0" {{ (string) old('store_body', $storeBody ? '1' : '0') === '0' ? 'selected' : '' }}>No, solo metadatos</option>
                            </select>
                            @error('store_body')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                            <span class="evx-form-hint">Si se desactiva, solo se almacenan metadatos (asunto, destinatarios, estado). Recomendado desactivar por privacidad o espacio en disco.</span>
                        </div>

                        <div class="evx-form-field">
                            <label for="max_body_bytes" class="evx-form-label">Tamaño máximo del cuerpo (KB)</label>
                            <input type="number" class="evx-input @error('max_body_bytes') is-invalid @enderror"
                                   id="max_body_bytes" name="max_body_bytes"
                                   value="{{ old('max_body_bytes', $maxBodyKb) }}" min="1" max="10240">
                            @error('max_body_bytes')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                            <span class="evx-form-hint">Si el cuerpo supera este límite, se trunca. Máximo 10.240 KB.</span>
                        </div>
                    </div>
                </div>

                {{-- Píxel de apertura --}}
                <div class="evx-section-block">
                    <h2 class="evx-section-title">Píxel de apertura</h2>
                    <p class="evx-section-desc">Controla si los envíos con seguimiento (hoy, "Emails enviados" de HelpdeskTickets) incluyen el píxel que registra la apertura.</p>

                    <div class="evx-form-field">
                        <label for="pixel_tracking_enabled" class="evx-form-label">Insertar píxel de seguimiento</label>
                        <select class="evx-select @error('pixel_tracking_enabled') is-invalid @enderror" id="pixel_tracking_enabled" name="pixel_tracking_enabled">
                            <option value="1" {{ (string) old('pixel_tracking_enabled', $pixelTrackingEnabled ? '1' : '0') === '1' ? 'selected' : '' }}>Sí, insertar píxel</option>
                            <option value="0" {{ (string) old('pixel_tracking_enabled', $pixelTrackingEnabled ? '1' : '0') === '0' ? 'selected' : '' }}>No, no insertar píxel</option>
                        </select>
                        @error('pixel_tracking_enabled')
                            <span class="evx-invalid">{{ $message }}</span>
                        @enderror
                        <span class="evx-form-hint">Desactivarlo detiene la inserción del píxel en nuevos envíos. No borra las aperturas ya registradas ni afecta a la redirección de clics.</span>
                    </div>
                </div>

                {{-- Retención y purga --}}
                <div class="evx-section-block">
                    <h2 class="evx-section-title">Retención y purga automática</h2>
                    <p class="evx-section-desc">Antigüedad máxima de los registros y tratamiento de los envíos que quedan en cola. Los cambios se aplican en la siguiente ejecución de la purga programada.</p>

                    <div class="evx-field-grid">
                        <div class="evx-form-field">
                            <label for="retention_days" class="evx-form-label">Días de retención</label>
                            <input type="number" class="evx-input @error('retention_days') is-invalid @enderror"
                                   id="retention_days" name="retention_days"
                                   value="{{ old('retention_days', $retentionDays) }}" min="0" max="3650">
                            @error('retention_days')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                            <span class="evx-form-hint">Los registros más antiguos se eliminan durante la purga diaria. <strong>0</strong> desactiva la purga.</span>
                        </div>

                        <div class="evx-form-field">
                            <label for="stale_queued_hours" class="evx-form-label">Horas para marcar cola obsoleta</label>
                            <input type="number" class="evx-input @error('stale_queued_hours') is-invalid @enderror"
                                   id="stale_queued_hours" name="stale_queued_hours"
                                   value="{{ old('stale_queued_hours', $staleQueuedHours) }}" min="0" max="8760">
                            @error('stale_queued_hours')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                            <span class="evx-form-hint">Registros en estado <em>queued</em> que nunca se confirmaron se marcan como <em>failed</em> tras estas horas. <strong>0</strong> lo desactiva.</span>
                        </div>
                    </div>
                </div>

                {{-- Visualización --}}
                <div class="evx-section-block">
                    <h2 class="evx-section-title">Visualización</h2>
                    <p class="evx-section-desc">Preferencias por defecto del listado de emails.</p>

                    <div class="evx-form-field">
                        <label for="per_page" class="evx-form-label">Registros por página (por defecto)</label>
                        <select class="evx-select @error('per_page') is-invalid @enderror" id="per_page" name="per_page">
                            @foreach($perPageOptions as $option)
                                <option value="{{ $option }}" {{ (int) old('per_page', $perPage) === $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('per_page')
                            <span class="evx-invalid">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Dominios monitorizados (reputación) --}}
                <div class="evx-section-block">
                    <h2 class="evx-section-title">Dominios monitorizados (SPF/DKIM/DMARC)</h2>
                    <p class="evx-section-desc">
                        Un dominio por línea. Opcionalmente, selectores DKIM conocidos separados por coma:
                        <span class="evx-mono">midominio.com:selector1,selector2</span>. Sin selector se prueban algunos
                        comunes, pero un DKIM no encontrado se reporta como "no verificable", nunca como "ausente".
                    </p>

                    <div class="evx-form-field mb-3">
                        <textarea class="evx-input @error('reputation_domains') is-invalid @enderror"
                                  name="reputation_domains" rows="4"
                                  placeholder="a-alvarez.com&#10;otro-dominio.com:selector1,google">{{ old('reputation_domains', $reputationDomainsText) }}</textarea>
                        @error('reputation_domains')
                            <span class="evx-invalid">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="evx-form-field">
                        <label for="reputation_window_days" class="evx-form-label">Ventana de días para las tasas</label>
                        <input type="number" class="evx-input @error('reputation_window_days') is-invalid @enderror"
                               id="reputation_window_days" name="reputation_window_days"
                               value="{{ old('reputation_window_days', $reputationWindowDays) }}" min="1" max="365">
                        @error('reputation_window_days')
                            <span class="evx-invalid">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Umbrales de rebote y quejas --}}
                <div class="evx-section-block">
                    <h2 class="evx-section-title">Umbrales de rebote y quejas</h2>
                    <p class="evx-section-desc">
                        Al cruzar el umbral crítico, <span class="evx-mono">email-logs:check-reputation</span> avisa a
                        manager/super-admin (una sola vez, hasta que se recupere).
                    </p>

                    <div class="evx-field-grid">
                        <div class="evx-form-field">
                            <label for="bounce_rate_warning_pct" class="evx-form-label">Rebote — aviso (%)</label>
                            <input type="number" step="0.1" class="evx-input @error('bounce_rate_warning_pct') is-invalid @enderror"
                                   id="bounce_rate_warning_pct" name="bounce_rate_warning_pct"
                                   value="{{ old('bounce_rate_warning_pct', $bounceRateWarning) }}" min="0" max="100">
                            @error('bounce_rate_warning_pct')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="evx-form-field">
                            <label for="bounce_rate_critical_pct" class="evx-form-label">Rebote — crítico (%)</label>
                            <input type="number" step="0.1" class="evx-input @error('bounce_rate_critical_pct') is-invalid @enderror"
                                   id="bounce_rate_critical_pct" name="bounce_rate_critical_pct"
                                   value="{{ old('bounce_rate_critical_pct', $bounceRateCritical) }}" min="0" max="100">
                            @error('bounce_rate_critical_pct')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="evx-form-field">
                            <label for="complaint_rate_warning_pct" class="evx-form-label">Quejas — aviso (%)</label>
                            <input type="number" step="0.01" class="evx-input @error('complaint_rate_warning_pct') is-invalid @enderror"
                                   id="complaint_rate_warning_pct" name="complaint_rate_warning_pct"
                                   value="{{ old('complaint_rate_warning_pct', $complaintRateWarning) }}" min="0" max="100">
                            @error('complaint_rate_warning_pct')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="evx-form-field">
                            <label for="complaint_rate_critical_pct" class="evx-form-label">Quejas — crítico (%)</label>
                            <input type="number" step="0.01" class="evx-input @error('complaint_rate_critical_pct') is-invalid @enderror"
                                   id="complaint_rate_critical_pct" name="complaint_rate_critical_pct"
                                   value="{{ old('complaint_rate_critical_pct', $complaintRateCritical) }}" min="0" max="100">
                            @error('complaint_rate_critical_pct')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Conector de webhooks de proveedor --}}
                <div class="evx-section-block">
                    <h2 class="evx-section-title">Conector de webhooks de proveedor</h2>
                    <p class="evx-section-desc">
                        Interpreta las notificaciones de rebote/queja que un proveedor de envío
                        (SES, Postmark, Mailgun, Mailrelay) manda por webhook a la URL de abajo.
                    </p>

                    <div class="evx-alert mb-3">
                        <strong>Importante:</strong> elegir un proveedor aquí solo determina cómo se
                        <em>interpretan</em> los webhooks entrantes — no cambia por dónde sale el correo real.
                        Mientras el envío siga siendo SMTP genérico (no un proveedor con webhooks), ningún
                        proveedor de estos va a mandar ninguna notificación, porque nunca procesa correo nuestro.
                        Migrar el envío de verdad a un proveedor requiere instalar su SDK y cambiar la configuración
                        de correo — eso es aparte de este panel.
                    </div>

                    <div class="evx-field-grid">
                        <div class="evx-form-field">
                            <label for="provider_webhook_provider" class="evx-form-label">Proveedor</label>
                            <select class="evx-select @error('provider_webhook_provider') is-invalid @enderror"
                                    id="provider_webhook_provider" name="provider_webhook_provider">
                                <option value="" {{ (string) old('provider_webhook_provider', $providerWebhookProvider ?? '') === '' ? 'selected' : '' }}>Ninguno (desactivado)</option>
                                <option value="mailrelay" {{ (string) old('provider_webhook_provider', $providerWebhookProvider ?? '') === 'mailrelay' ? 'selected' : '' }}>Mailrelay</option>
                                <option value="ses" {{ (string) old('provider_webhook_provider', $providerWebhookProvider ?? '') === 'ses' ? 'selected' : '' }}>Amazon SES (vía SNS)</option>
                                <option value="postmark" {{ (string) old('provider_webhook_provider', $providerWebhookProvider ?? '') === 'postmark' ? 'selected' : '' }}>Postmark</option>
                                <option value="mailgun" {{ (string) old('provider_webhook_provider', $providerWebhookProvider ?? '') === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                            </select>
                            @error('provider_webhook_provider')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="evx-form-field">
                            <label for="provider_webhook_secret" class="evx-form-label">
                                Secreto de verificación
                                @if($providerWebhookHasSecret ?? false)
                                    <span class="evx-muted fw-normal">(ya configurado)</span>
                                @endif
                            </label>
                            <input type="password" class="evx-input @error('provider_webhook_secret') is-invalid @enderror"
                                   id="provider_webhook_secret" name="provider_webhook_secret"
                                   placeholder="{{ ($providerWebhookHasSecret ?? false) ? 'Dejar en blanco para conservar el actual' : 'Token o clave de firma del proveedor' }}"
                                   autocomplete="new-password">
                            @error('provider_webhook_secret')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                            <span class="evx-form-hint">
                                Mailrelay/Postmark: token compartido que el proveedor debe enviar en cabecera. Mailgun: clave
                                de firma HMAC de la cuenta. SES/SNS: no se usa (la firma la valida SNS con su propio certificado).
                            </span>
                        </div>
                    </div>

                    @if($providerWebhookUrl ?? null)
                        <div class="evx-form-field mt-3">
                            <label class="evx-form-label">URL del webhook</label>
                            <div class="input-group">
                                <input type="text" class="form-control evx-mono" value="{{ $providerWebhookUrl }}" readonly onclick="this.select()">
                                <button class="btn btn-outline-secondary" type="button" data-copy-webhook-url title="Copiar">
                                    <i class="fa fa-copy"></i>
                                </button>
                            </div>
                            <span class="evx-form-hint">Configúrala en el panel del proveedor como destino de sus notificaciones de rebote/queja.</span>
                        </div>
                    @endif

                    <div class="evx-field-grid mt-3">
                        <div class="evx-form-field">
                            <label for="provider_webhook_process_bounces" class="evx-form-label">Procesar rebotes</label>
                            <select class="evx-select" id="provider_webhook_process_bounces" name="provider_webhook_process_bounces">
                                <option value="1" {{ (string) old('provider_webhook_process_bounces', $providerWebhookProcessBounces ? '1' : '0') === '1' ? 'selected' : '' }}>Sí</option>
                                <option value="0" {{ (string) old('provider_webhook_process_bounces', $providerWebhookProcessBounces ? '1' : '0') === '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="evx-form-field">
                            <label for="provider_webhook_process_complaints" class="evx-form-label">Procesar quejas</label>
                            <select class="evx-select" id="provider_webhook_process_complaints" name="provider_webhook_process_complaints">
                                <option value="1" {{ (string) old('provider_webhook_process_complaints', $providerWebhookProcessComplaints ? '1' : '0') === '1' ? 'selected' : '' }}>Sí</option>
                                <option value="0" {{ (string) old('provider_webhook_process_complaints', $providerWebhookProcessComplaints ? '1' : '0') === '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="evx-form-field">
                            <label for="provider_webhook_process_deliveries" class="evx-form-label">Procesar entregas</label>
                            <select class="evx-select" id="provider_webhook_process_deliveries" name="provider_webhook_process_deliveries">
                                <option value="1" {{ (string) old('provider_webhook_process_deliveries', $providerWebhookProcessDeliveries ? '1' : '0') === '1' ? 'selected' : '' }}>Sí</option>
                                <option value="0" {{ (string) old('provider_webhook_process_deliveries', $providerWebhookProcessDeliveries ? '1' : '0') === '0' ? 'selected' : '' }}>No</option>
                            </select>
                            <span class="evx-form-hint">Aún no se usa (no hay pantalla de confirmaciones de entrega); reservado para cuando la haya.</span>
                        </div>
                        <div class="evx-form-field">
                            <label for="provider_webhook_process_opens" class="evx-form-label">Procesar aperturas</label>
                            <select class="evx-select" id="provider_webhook_process_opens" name="provider_webhook_process_opens">
                                <option value="1" {{ (string) old('provider_webhook_process_opens', $providerWebhookProcessOpens ? '1' : '0') === '1' ? 'selected' : '' }}>Sí</option>
                                <option value="0" {{ (string) old('provider_webhook_process_opens', $providerWebhookProcessOpens ? '1' : '0') === '0' ? 'selected' : '' }}>No</option>
                            </select>
                            <span class="evx-form-hint">Aún no se usa (las aperturas ya se registran por píxel propio); reservado a futuro.</span>
                        </div>
                    </div>
                </div>

                <div class="evx-foot">
                    <button type="submit" class="evx-btn evx-btn-primary evx-btn-inline">
                        Guardar configuración
                    </button>
                </div>
            </form>

            {{-- Consejos de uso --}}
            <div class="evx-section-block">
                <h2 class="evx-section-title">Consejos de uso</h2>
                <ul class="evx-muted mb-0 ps-3">
                    <li class="mb-2">Desactiva el guardado del cuerpo si manejas datos sensibles o necesitas cumplir políticas de privacidad más estrictas.</li>
                    <li class="mb-2">Una retención de 90 días suele ser un buen equilibrio entre auditoría y espacio en disco.</li>
                    <li class="mb-2">Revisa periódicamente los <a href="{{ route('settings.helpdeskemaillog.bounce-mailboxes.index') }}">buzones de rebote</a> para detectar direcciones inválidas.</li>
                    <li class="mb-0">Los cambios se aplican en la siguiente ejecución de la purga programada.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    $('[data-copy-webhook-url]').on('click', function () {
        var $input = $(this).closest('.input-group').find('input');
        $input.select();
        navigator.clipboard?.writeText($input.val());
    });
});
</script>
@endpush
