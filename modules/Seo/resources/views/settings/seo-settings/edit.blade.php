@extends('layouts.theme')

@section('page_title', 'Configuración SEO')

@section('content')
    @include('core::components.alerts')

    <form method="POST" action="{{ route('setting.seo.settings.update') }}">
        @csrf

        <div class="row g-4">
            <div class="col-lg-8">
                {{-- IndexNow --}}
                <div class="card mb-3">
                    <div class="card-header p-4 border-bottom">
                        <h6 class="mb-1 fw-bold">IndexNow</h6>
                        <p class="small mb-0 text-muted">Notifica a Bing/Yandex/Seznam cuando publicas o actualizas contenido.</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="indexnow_enabled" class="form-label fw-semibold">Activar IndexNow</label>
                            <select class="form-select" id="indexnow_enabled" name="indexnow_enabled">
                                <option value="1" @selected($settings['indexnow_enabled'])>Activo</option>
                                <option value="0" @selected(!$settings['indexnow_enabled'])>Inactivo</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="indexnow_key" class="form-label fw-semibold">Key</label>
                            <input type="text" id="indexnow_key" name="indexnow_key"
                                   class="form-control font-monospace @error('indexnow_key') is-invalid @enderror"
                                   value="{{ old('indexnow_key', $settings['indexnow_key']) }}" placeholder="8-128 caracteres alfanuméricos">
                            <small class="text-muted">Archivo de verificación se sirve en <code>/&lt;key&gt;.txt</code>.</small>
                            @error('indexnow_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="indexnow_auto_submit" class="form-label fw-semibold">Auto-submit al publicar/actualizar</label>
                            <select class="form-select" id="indexnow_auto_submit" name="indexnow_auto_submit">
                                <option value="1" @selected($settings['indexnow_auto_submit'])>Activo</option>
                                <option value="0" @selected(!$settings['indexnow_auto_submit'])>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Webhooks --}}
                <div class="card mb-3">
                    <div class="card-header p-4 border-bottom">
                        <h6 class="mb-1 fw-bold">Webhooks &amp; Notificaciones</h6>
                        <p class="small mb-0 text-muted">Alertas a Slack/Discord y email para eventos SEO relevantes.</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="notifications_email" class="form-label fw-semibold">Email de notificaciones</label>
                            <input type="email" id="notifications_email" name="notifications_email"
                                   class="form-control @error('notifications_email') is-invalid @enderror"
                                   value="{{ old('notifications_email', $settings['notifications_email']) }}">
                            @error('notifications_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="slack_webhook_url" class="form-label fw-semibold">Slack Webhook URL</label>
                            <input type="url" id="slack_webhook_url" name="slack_webhook_url"
                                   class="form-control @error('slack_webhook_url') is-invalid @enderror"
                                   value="{{ old('slack_webhook_url', $settings['slack_webhook_url']) }}" placeholder="https://hooks.slack.com/services/...">
                            @error('slack_webhook_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="discord_webhook_url" class="form-label fw-semibold">Discord Webhook URL</label>
                            <input type="url" id="discord_webhook_url" name="discord_webhook_url"
                                   class="form-control @error('discord_webhook_url') is-invalid @enderror"
                                   value="{{ old('discord_webhook_url', $settings['discord_webhook_url']) }}">
                            @error('discord_webhook_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="webhook_signing_secret" class="form-label fw-semibold">Signing Secret (HMAC-SHA256)</label>
                            <input type="text" id="webhook_signing_secret" name="webhook_signing_secret"
                                   class="form-control font-monospace @error('webhook_signing_secret') is-invalid @enderror"
                                   value="{{ old('webhook_signing_secret', $settings['webhook_signing_secret']) }}" placeholder="Mínimo 16 caracteres">
                            <small class="text-muted">Añade <code>X-Seo-Signature</code> a cada webhook saliente para verificación.</small>
                            @error('webhook_signing_secret')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="score_drop_threshold" class="form-label fw-semibold">Umbral de caída de score</label>
                            <input type="number" id="score_drop_threshold" name="score_drop_threshold" min="1" max="100"
                                   class="form-control @error('score_drop_threshold') is-invalid @enderror"
                                   value="{{ old('score_drop_threshold', $settings['score_drop_threshold']) }}">
                            <small class="text-muted">Puntos. Notifica si el SEO score cae más de este valor.</small>
                            @error('score_drop_threshold')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="notify_score_drop" class="form-label fw-semibold">Notificar caídas de score</label>
                            <select class="form-select" id="notify_score_drop" name="notify_score_drop">
                                <option value="1" @selected($settings['notify_score_drop'])>Activo</option>
                                <option value="0" @selected(!$settings['notify_score_drop'])>Inactivo</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notify_redirect_chain" class="form-label fw-semibold">Notificar cadenas de redirects</label>
                            <select class="form-select" id="notify_redirect_chain" name="notify_redirect_chain">
                                <option value="1" @selected($settings['notify_redirect_chain'])>Activo</option>
                                <option value="0" @selected(!$settings['notify_redirect_chain'])>Inactivo</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notify_orphans" class="form-label fw-semibold">Notificar contenido huérfano</label>
                            <select class="form-select" id="notify_orphans" name="notify_orphans">
                                <option value="1" @selected($settings['notify_orphans'])>Activo</option>
                                <option value="0" @selected(!$settings['notify_orphans'])>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Performance --}}
                <div class="card mb-3">
                    <div class="card-header p-4 border-bottom">
                        <h6 class="mb-1 fw-bold">Performance Hints</h6>
                        <p class="small mb-0 text-muted">Preconnect / dns-prefetch / cache headers para subir PageSpeed score.</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="preconnect" class="form-label fw-semibold">Preconnect</label>
                            <input type="text" id="preconnect" name="preconnect"
                                   class="form-control font-monospace @error('preconnect') is-invalid @enderror"
                                   value="{{ old('preconnect', $settings['preconnect']) }}"
                                   placeholder="https://fonts.googleapis.com,https://fonts.gstatic.com">
                            <small class="text-muted">Dominios separados por coma. Se inyectan como <code>&lt;link rel="preconnect"&gt;</code>.</small>
                            @error('preconnect')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="dns_prefetch" class="form-label fw-semibold">DNS Prefetch</label>
                            <input type="text" id="dns_prefetch" name="dns_prefetch"
                                   class="form-control font-monospace @error('dns_prefetch') is-invalid @enderror"
                                   value="{{ old('dns_prefetch', $settings['dns_prefetch']) }}"
                                   placeholder="https://cdn.example.com">
                            <small class="text-muted">Dominios secundarios. Más barato que preconnect.</small>
                            @error('dns_prefetch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="html_cache_seconds" class="form-label fw-semibold">Cache HTML (segundos)</label>
                            <input type="number" id="html_cache_seconds" name="html_cache_seconds" min="0" max="86400"
                                   class="form-control @error('html_cache_seconds') is-invalid @enderror"
                                   value="{{ old('html_cache_seconds', $settings['html_cache_seconds']) }}">
                            <small class="text-muted">0 = sin Cache-Control. Solo afecta a usuarios anónimos.</small>
                            @error('html_cache_seconds')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="enable_security_headers" name="enable_security_headers" value="1" @checked($settings['enable_security_headers'])>
                            <label class="form-check-label" for="enable_security_headers">Enviar security headers (X-Content-Type-Options, Referrer-Policy, etc.)</label>
                        </div>
                    </div>
                </div>

                {{-- Web Vitals --}}
                <div class="card mb-3">
                    <div class="card-header p-4 border-bottom">
                        <h6 class="mb-1 fw-bold">Core Web Vitals</h6>
                        <p class="small mb-0 text-muted">Recolección de métricas reales desde el navegador del usuario.</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="web_vitals_enabled" name="web_vitals_enabled" value="1" @checked($settings['web_vitals_enabled'])>
                            <label class="form-check-label" for="web_vitals_enabled">Activar beacon de Web Vitals</label>
                        </div>
                        <div class="mb-3">
                            <label for="web_vitals_sample_rate" class="form-label fw-semibold">Sample rate</label>
                            <input type="number" id="web_vitals_sample_rate" name="web_vitals_sample_rate"
                                   step="0.01" min="0" max="1"
                                   class="form-control @error('web_vitals_sample_rate') is-invalid @enderror"
                                   value="{{ old('web_vitals_sample_rate', $settings['web_vitals_sample_rate']) }}">
                            <small class="text-muted">0.0-1.0. Ej: 0.1 = 10% de las páginas reportan métricas.</small>
                            @error('web_vitals_sample_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="web_vitals_retention_days" class="form-label fw-semibold">Retención (días)</label>
                            <input type="number" id="web_vitals_retention_days" name="web_vitals_retention_days" min="1" max="365"
                                   class="form-control @error('web_vitals_retention_days') is-invalid @enderror"
                                   value="{{ old('web_vitals_retention_days', $settings['web_vitals_retention_days']) }}">
                            @error('web_vitals_retention_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- PageSpeed + llms.txt --}}
                <div class="card mb-3">
                    <div class="card-header p-4 border-bottom">
                        <h6 class="mb-1 fw-bold">Integraciones externas</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="pagespeed_api_key" class="form-label fw-semibold">Google PageSpeed Insights API key</label>
                            <input type="text" id="pagespeed_api_key" name="pagespeed_api_key"
                                   class="form-control font-monospace @error('pagespeed_api_key') is-invalid @enderror"
                                   value="{{ old('pagespeed_api_key', $settings['pagespeed_api_key']) }}">
                            <small class="text-muted">Opcional. Sin key, requests al API están rate-limited.</small>
                            @error('pagespeed_api_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="llms_txt_enabled" name="llms_txt_enabled" value="1" @checked($settings['llms_txt_enabled'])>
                            <label class="form-check-label" for="llms_txt_enabled">Servir <code>/llms.txt</code> a crawlers de IA</label>
                        </div>

                        <div>
                            <label for="gtm_container_id" class="form-label fw-semibold">Google Tag Manager ID</label>
                            <input type="text" id="gtm_container_id" name="gtm_container_id"
                                   class="form-control font-monospace @error('gtm_container_id') is-invalid @enderror"
                                   value="{{ old('gtm_container_id', $settings['gtm_container_id'] ?? '') }}" placeholder="GTM-XXXXXXX">
                            <small class="text-muted">Formato <code>GTM-XXXXXXX</code>. Se inyecta vía <code>@seoGtmHead</code> y <code>@seoGtmBody</code>.</small>
                            @error('gtm_container_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <div>
                        <a href="{{ route('setting.seo.settings.export') }}" class="btn btn-outline-secondary">
                            Exportar JSON
                        </a>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importConfigModal">
                            Importar JSON
                        </button>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar configuración</button>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-2 border-bottom pb-2">Sobre esta página</h6>
                        <p class="small text-muted mb-2">
                            Centraliza toda la configuración SEO antes dispersa en <code>.env</code>.
                            Los cambios se aplican inmediatamente — no hace falta reiniciar el servidor.
                        </p>
                        <p class="small text-muted mb-2">
                            <strong>Orden de precedencia:</strong> DB (esta página) &gt; <code>.env</code> &gt; defaults.
                        </p>
                        <p class="small text-muted mb-0">
                            Si dejas un campo vacío, se usará el valor de <code>.env</code> o el default.
                        </p>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-2 border-bottom pb-2">Accesos rápidos</h6>
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-1"><a href="{{ route('setting.seo.dashboard') }}">Dashboard SEO</a></li>
                            <li class="mb-1"><a href="{{ route('setting.seo.robots.edit') }}">robots.txt</a></li>
                            <li class="mb-1"><a href="{{ route('setting.seo.llms.edit') }}">llms.txt</a></li>
                            <li class="mb-1"><a href="{{ route('setting.seo.indexnow.index') }}">IndexNow</a></li>
                            <li class="mb-1"><a href="{{ route('setting.seo.web-vitals.index') }}">Core Web Vitals</a></li>
                            <li><a href="{{ route('setting.seo.verification.index') }}">Códigos de verificación</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Import config modal --}}
    <div class="modal fade" id="importConfigModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('setting.seo.settings.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Importar configuración SEO</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">Sube un archivo JSON generado con "Exportar JSON". Sobreescribirá la configuración actual.</p>
                        <input type="file" name="config_file" accept=".json" class="form-control" required>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Importar</button>
                        <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
