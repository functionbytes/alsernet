@extends('layouts.theme')

@section('title', 'Actividad de correo — Configuración')

{{-- Sin page_header y con content_full_width, igual que el listado: el
     título de la franja del tema repetía el que ya lleva el breadcrumb de
     .evx-toolbar de dentro, y dejaba estas pantallas con un ancho distinto
     al del listado del que cuelgan. --}}
@section('content_full_width', true)

@include('helpdeskemailactivity::settings.partials.css')

@section('content')
    @include('core::components.alerts')

    <div class="emaillog-settings">
        <div class="evx-shell evx-shell-narrow">
            @include('helpdeskemailactivity::settings.partials.subnav', ['current' => 'settings'])

            <form method="POST" action="{{ route('settings.helpdeskemailactivity.update') }}">
                @csrf
                @method('PATCH')

                {{-- Almacenamiento del contenido --}}
                <div class="evx-section-block">
                    <h2 class="evx-section-title">{{ __('helpdeskemailactivity::emaillog.settings.storage') }}</h2>
                    <p class="evx-section-desc">{{ __('helpdeskemailactivity::emaillog.settings.storage_hint') }}</p>

                    <div class="evx-field-grid">
                        <div class="evx-form-field">
                            <label for="store_body" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.store_body') }}</label>
                            <select class="evx-select @error('store_body') is-invalid @enderror" id="store_body" name="store_body">
                                <option value="1" {{ (string) old('store_body', $storeBody ? '1' : '0') === '1' ? 'selected' : '' }}>{{ __('helpdeskemailactivity::emaillog.settings.store_body_yes') }}</option>
                                <option value="0" {{ (string) old('store_body', $storeBody ? '1' : '0') === '0' ? 'selected' : '' }}>{{ __('helpdeskemailactivity::emaillog.settings.store_body_no') }}</option>
                            </select>
                            @error('store_body')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                            <span class="evx-form-hint">{{ __('helpdeskemailactivity::emaillog.settings.store_body_hint') }}</span>
                        </div>

                        <div class="evx-form-field">
                            <label for="max_body_bytes" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.max_body') }}</label>
                            <input type="number" class="evx-input @error('max_body_bytes') is-invalid @enderror"
                                   id="max_body_bytes" name="max_body_bytes"
                                   value="{{ old('max_body_bytes', $maxBodyKb) }}" min="1" max="10240">
                            @error('max_body_bytes')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                            <span class="evx-form-hint">{{ __('helpdeskemailactivity::emaillog.settings.max_body_hint') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Píxel de apertura --}}
                <div class="evx-section-block">
                    <h2 class="evx-section-title">{{ __('helpdeskemailactivity::emaillog.settings.pixel') }}</h2>
                    <p class="evx-section-desc">{{ __('helpdeskemailactivity::emaillog.settings.pixel_section_hint') }}</p>

                    <div class="evx-form-field">
                        <label for="pixel_tracking_enabled" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.pixel_insert') }}</label>
                        <select class="evx-select @error('pixel_tracking_enabled') is-invalid @enderror" id="pixel_tracking_enabled" name="pixel_tracking_enabled">
                            <option value="1" {{ (string) old('pixel_tracking_enabled', $pixelTrackingEnabled ? '1' : '0') === '1' ? 'selected' : '' }}>{{ __('helpdeskemailactivity::emaillog.settings.pixel_yes') }}</option>
                            <option value="0" {{ (string) old('pixel_tracking_enabled', $pixelTrackingEnabled ? '1' : '0') === '0' ? 'selected' : '' }}>{{ __('helpdeskemailactivity::emaillog.settings.pixel_no') }}</option>
                        </select>
                        @error('pixel_tracking_enabled')
                            <span class="evx-invalid">{{ $message }}</span>
                        @enderror
                        <span class="evx-form-hint">{{ __('helpdeskemailactivity::emaillog.settings.pixel_hint') }}</span>
                    </div>

                    {{-- Dominio con el que se generan el píxel y los enlaces del
                         correo. Campo solo en su fila (col-12): la advertencia
                         necesita el ancho para leerse. --}}
                    <div class="evx-form-field">
                        <label for="tracking_base_url" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.tracking_base_url') }}</label>
                        <input type="url" class="evx-input @error('tracking_base_url') is-invalid @enderror"
                               id="tracking_base_url" name="tracking_base_url"
                               value="{{ old('tracking_base_url', $trackingBaseUrl) }}"
                               placeholder="{{ config('app.url') }}">
                        @error('tracking_base_url')
                            <span class="evx-invalid">{{ $message }}</span>
                        @enderror
                        <span class="evx-form-hint">
                            {{ __('helpdeskemailactivity::emaillog.settings.tracking_base_url_hint', ['url' => $trackingBaseEffective]) }}
                        </span>
                        @if($trackingUnreachable)
                            <span class="evx-invalid">
                                {{ __('helpdeskemailactivity::emaillog.tracking_warning.body', ['url' => $trackingBaseEffective]) }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Retención y purga --}}
                <div class="evx-section-block">
                    <h2 class="evx-section-title">{{ __('helpdeskemailactivity::emaillog.settings.retention') }}</h2>
                    <p class="evx-section-desc">{{ __('helpdeskemailactivity::emaillog.settings.retention_section_hint') }}</p>

                    <div class="evx-field-grid">
                        <div class="evx-form-field">
                            <label for="retention_days" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.retention_days') }}</label>
                            <input type="number" class="evx-input @error('retention_days') is-invalid @enderror"
                                   id="retention_days" name="retention_days"
                                   value="{{ old('retention_days', $retentionDays) }}" min="0" max="3650">
                            @error('retention_days')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                            <span class="evx-form-hint">{!! __('helpdeskemailactivity::emaillog.settings.retention_days_hint') !!}</span>
                        </div>

                        <div class="evx-form-field">
                            <label for="stale_queued_hours" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.stale_hours') }}</label>
                            <input type="number" class="evx-input @error('stale_queued_hours') is-invalid @enderror"
                                   id="stale_queued_hours" name="stale_queued_hours"
                                   value="{{ old('stale_queued_hours', $staleQueuedHours) }}" min="0" max="8760">
                            @error('stale_queued_hours')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                            <span class="evx-form-hint">{!! __('helpdeskemailactivity::emaillog.settings.stale_hours_hint') !!}</span>
                        </div>
                    </div>
                </div>

                {{-- Visualización --}}
                <div class="evx-section-block">
                    <h2 class="evx-section-title">{{ __('helpdeskemailactivity::emaillog.settings.display') }}</h2>
                    <p class="evx-section-desc">{{ __('helpdeskemailactivity::emaillog.settings.display_hint') }}</p>

                    <div class="evx-form-field">
                        <label for="per_page" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.per_page') }}</label>
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
                    <h2 class="evx-section-title">{{ __('helpdeskemailactivity::emaillog.settings.domains') }}</h2>
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
                        <label for="reputation_window_days" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.rate_window') }}</label>
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
                    <h2 class="evx-section-title">{{ __('helpdeskemailactivity::emaillog.settings.thresholds') }}</h2>
                    <p class="evx-section-desc">
                        Al cruzar el umbral crítico, <span class="evx-mono">email-logs:check-reputation</span> avisa a
                        manager/super-admin (una sola vez, hasta que se recupere).
                    </p>

                    <div class="evx-field-grid">
                        <div class="evx-form-field">
                            <label for="bounce_rate_warning_pct" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.bounce_warning') }}</label>
                            <input type="number" step="0.1" class="evx-input @error('bounce_rate_warning_pct') is-invalid @enderror"
                                   id="bounce_rate_warning_pct" name="bounce_rate_warning_pct"
                                   value="{{ old('bounce_rate_warning_pct', $bounceRateWarning) }}" min="0" max="100">
                            @error('bounce_rate_warning_pct')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="evx-form-field">
                            <label for="bounce_rate_critical_pct" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.bounce_critical') }}</label>
                            <input type="number" step="0.1" class="evx-input @error('bounce_rate_critical_pct') is-invalid @enderror"
                                   id="bounce_rate_critical_pct" name="bounce_rate_critical_pct"
                                   value="{{ old('bounce_rate_critical_pct', $bounceRateCritical) }}" min="0" max="100">
                            @error('bounce_rate_critical_pct')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="evx-form-field">
                            <label for="complaint_rate_warning_pct" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.complaint_warning') }}</label>
                            <input type="number" step="0.01" class="evx-input @error('complaint_rate_warning_pct') is-invalid @enderror"
                                   id="complaint_rate_warning_pct" name="complaint_rate_warning_pct"
                                   value="{{ old('complaint_rate_warning_pct', $complaintRateWarning) }}" min="0" max="100">
                            @error('complaint_rate_warning_pct')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="evx-form-field">
                            <label for="complaint_rate_critical_pct" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.complaint_critical') }}</label>
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
                    <h2 class="evx-section-title">{{ __('helpdeskemailactivity::emaillog.settings.webhook_connector') }}</h2>
                    <p class="evx-section-desc">
                        Interpreta las notificaciones de rebote/queja que un proveedor de envío
                        (SES, Postmark, Mailgun, Mailrelay) manda por webhook a la URL de abajo.
                    </p>

                    <div class="evx-alert mb-3">
                        {!! __('helpdeskemailactivity::emaillog.settings.connector_notice') !!}
                    </div>

                    <div class="evx-field-grid">
                        <div class="evx-form-field">
                            <label for="provider_webhook_provider" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.provider') }}</label>
                            <select class="evx-select @error('provider_webhook_provider') is-invalid @enderror"
                                    id="provider_webhook_provider" name="provider_webhook_provider">
                                <option value="" {{ (string) old('provider_webhook_provider', $providerWebhookProvider ?? '') === '' ? 'selected' : '' }}>{{ __('helpdeskemailactivity::emaillog.settings.provider_none') }}</option>
                                <option value="mailrelay" {{ (string) old('provider_webhook_provider', $providerWebhookProvider ?? '') === 'mailrelay' ? 'selected' : '' }}>Mailrelay</option>
                                <option value="ses" {{ (string) old('provider_webhook_provider', $providerWebhookProvider ?? '') === 'ses' ? 'selected' : '' }}>{{ __('helpdeskemailactivity::emaillog.settings.provider_ses') }}</option>
                                <option value="postmark" {{ (string) old('provider_webhook_provider', $providerWebhookProvider ?? '') === 'postmark' ? 'selected' : '' }}>Postmark</option>
                                <option value="mailgun" {{ (string) old('provider_webhook_provider', $providerWebhookProvider ?? '') === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                            </select>
                            @error('provider_webhook_provider')
                                <span class="evx-invalid">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="evx-form-field">
                            <label for="provider_webhook_secret" class="evx-form-label">
                                {{ __('helpdeskemailactivity::emaillog.settings.webhook_secret') }}
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
                            <label class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.webhook_url') }}</label>
                            <div class="input-group">
                                <input type="text" class="form-control evx-mono" value="{{ $providerWebhookUrl }}" readonly onclick="this.select()">
                                <button class="btn btn-outline-secondary" type="button" data-copy-webhook-url title="Copiar">
                                    <i class="fa fa-copy"></i>
                                </button>
                            </div>
                            <span class="evx-form-hint">{{ __('helpdeskemailactivity::emaillog.settings.webhook_url_hint') }}</span>
                        </div>
                    @endif

                    <div class="evx-field-grid mt-3">
                        <div class="evx-form-field">
                            <label for="provider_webhook_process_bounces" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.process_bounces') }}</label>
                            <select class="evx-select" id="provider_webhook_process_bounces" name="provider_webhook_process_bounces">
                                <option value="1" {{ (string) old('provider_webhook_process_bounces', $providerWebhookProcessBounces ? '1' : '0') === '1' ? 'selected' : '' }}>{{ __('helpdeskemailactivity::emaillog.settings.yes') }}</option>
                                <option value="0" {{ (string) old('provider_webhook_process_bounces', $providerWebhookProcessBounces ? '1' : '0') === '0' ? 'selected' : '' }}>{{ __('helpdeskemailactivity::emaillog.settings.no') }}</option>
                            </select>
                        </div>
                        <div class="evx-form-field">
                            <label for="provider_webhook_process_complaints" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.process_complaints') }}</label>
                            <select class="evx-select" id="provider_webhook_process_complaints" name="provider_webhook_process_complaints">
                                <option value="1" {{ (string) old('provider_webhook_process_complaints', $providerWebhookProcessComplaints ? '1' : '0') === '1' ? 'selected' : '' }}>{{ __('helpdeskemailactivity::emaillog.settings.yes') }}</option>
                                <option value="0" {{ (string) old('provider_webhook_process_complaints', $providerWebhookProcessComplaints ? '1' : '0') === '0' ? 'selected' : '' }}>{{ __('helpdeskemailactivity::emaillog.settings.no') }}</option>
                            </select>
                        </div>
                        <div class="evx-form-field">
                            <label for="provider_webhook_process_deliveries" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.process_deliveries') }}</label>
                            <select class="evx-select" id="provider_webhook_process_deliveries" name="provider_webhook_process_deliveries">
                                <option value="1" {{ (string) old('provider_webhook_process_deliveries', $providerWebhookProcessDeliveries ? '1' : '0') === '1' ? 'selected' : '' }}>{{ __('helpdeskemailactivity::emaillog.settings.yes') }}</option>
                                <option value="0" {{ (string) old('provider_webhook_process_deliveries', $providerWebhookProcessDeliveries ? '1' : '0') === '0' ? 'selected' : '' }}>{{ __('helpdeskemailactivity::emaillog.settings.no') }}</option>
                            </select>
                            <span class="evx-form-hint">{{ __('helpdeskemailactivity::emaillog.settings.process_deliveries_hint') }}</span>
                        </div>
                        <div class="evx-form-field">
                            <label for="provider_webhook_process_opens" class="evx-form-label">{{ __('helpdeskemailactivity::emaillog.settings.process_opens') }}</label>
                            <select class="evx-select" id="provider_webhook_process_opens" name="provider_webhook_process_opens">
                                <option value="1" {{ (string) old('provider_webhook_process_opens', $providerWebhookProcessOpens ? '1' : '0') === '1' ? 'selected' : '' }}>{{ __('helpdeskemailactivity::emaillog.settings.yes') }}</option>
                                <option value="0" {{ (string) old('provider_webhook_process_opens', $providerWebhookProcessOpens ? '1' : '0') === '0' ? 'selected' : '' }}>{{ __('helpdeskemailactivity::emaillog.settings.no') }}</option>
                            </select>
                            <span class="evx-form-hint">{{ __('helpdeskemailactivity::emaillog.settings.process_opens_hint') }}</span>
                        </div>
                    </div>
                </div>

                <div class="evx-foot">
                    <button type="submit" class="evx-btn evx-btn-primary evx-btn-inline">
                        {{ __('helpdeskemailactivity::emaillog.settings.save') }}
                    </button>
                </div>
            </form>

            {{-- Consejos de uso --}}
            <div class="evx-section-block">
                <h2 class="evx-section-title">{{ __('helpdeskemailactivity::emaillog.settings.tips') }}</h2>
                <ul class="evx-muted mb-0 ps-3">
                    <li class="mb-2">{{ __('helpdeskemailactivity::emaillog.settings.tip_privacy') }}</li>
                    <li class="mb-2">{{ __('helpdeskemailactivity::emaillog.settings.tip_retention') }}</li>
                    {{-- El enlace se compone aquí, no dentro de la traducción: así la
                         cadena traducible no arrastra HTML ni la URL. --}}
                    <li class="mb-2">{!! __('helpdeskemailactivity::emaillog.settings.tip_bounces', [
                        'link' => '<a href="'.route('settings.helpdeskemailactivity.bounce-mailboxes.index').'">'
                            .e(__('helpdeskemailactivity::emaillog.subnav.bounce_mailboxes')).'</a>',
                    ]) !!}</li>
                    <li class="mb-0">{{ __('helpdeskemailactivity::emaillog.settings.tip_schedule') }}</li>
                </ul>
            </div>
        </div>
    </div>
    @include('helpdeskemailactivity::partials.select2')
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
