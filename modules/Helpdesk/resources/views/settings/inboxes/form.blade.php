@extends('layouts.theme')

@php
    $isEdit     = $inbox->exists;
    $title      = $isEdit ? 'Editar bandeja: '.$inbox->name : 'Nueva bandeja';
    $action     = $isEdit
        ? route('settings.helpdesk.inboxes.update', $inbox)
        : route('settings.helpdesk.inboxes.store');
    $waProvider = ($channel instanceof \Modules\Helpdesk\Models\Channels\Whatsapp)
        ? ($channel->provider ?? 'whatsapp_cloud')
        : 'whatsapp_cloud';

    // Web channel requires HelpdeskLivechat module — gate everything that depends on it.
    $isWebChannel = $inbox->channel_type === 'web';
    $livechatEnabled = \Nwidart\Modules\Facades\Module::find('HelpdeskLivechat')?->isEnabled() === true;
    $showWidgetTab = $isWebChannel && $livechatEnabled;
    $showWidgetPreview = $showWidgetTab && \Illuminate\Support\Facades\Route::has('helpdesk-livechat.widget.spa.index');

    $wh = $inbox->working_hours ?? null;
    $days = [
        'monday'    => ['label' => 'Lunes',      'default_enabled' => true],
        'tuesday'   => ['label' => 'Martes',     'default_enabled' => true],
        'wednesday' => ['label' => 'Miércoles',  'default_enabled' => true],
        'thursday'  => ['label' => 'Jueves',     'default_enabled' => true],
        'friday'    => ['label' => 'Viernes',    'default_enabled' => true],
        'saturday'  => ['label' => 'Sábado',     'default_enabled' => false],
        'sunday'    => ['label' => 'Domingo',    'default_enabled' => false],
    ];
@endphp

@section('title', $title)

@section('page_header')
    @include('core::components.card', ['title' => $title])
@endsection

@push('styles')
<style>.hd-embed-pre { white-space: pre-wrap; word-break: break-all; }</style>
@endpush

@section('content')

@include('core::components.alerts')

@if($showWidgetPreview)
<style>
.inbox-preview-wrap { position: sticky; top: 20px; }
.inbox-phone-frame { background: #1a1a1a; border-radius: 36px; padding: 12px; box-shadow: 0 12px 40px rgba(0,0,0,.35); width: 320px; margin: 0 auto; }
.inbox-phone-notch { background: #333; height: 22px; border-radius: 12px; margin: 0 auto 10px; width: 90px; }
.inbox-phone-screen { border-radius: 24px; overflow: hidden; height: 540px; background: #fff; position: relative; }
.inbox-phone-screen iframe { width: 100%; height: 100%; border: none; display: block; }
.inbox-phone-home-bar { background: #333; height: 4px; border-radius: 2px; width: 90px; margin: 10px auto 0; }
.inbox-preview-tabs .nav-link { font-size: .75rem; padding: .25rem .6rem; color: #666; border-radius: 6px; }
.inbox-preview-tabs .nav-link.active { background: #90bb13; color: #fff; }
</style>
@endif

<div class="row g-4">
    <div class="@if($showWidgetPreview) col-xl-7 col-lg-7 @else col-12 @endif">
<form action="{{ $action }}" method="POST" id="inboxForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- Tab nav --}}
    <ul class="nav nav-tabs nav-fill mb-3" id="inboxTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-general-btn" data-bs-toggle="tab"
                    data-bs-target="#tab-general" type="button" role="tab">
                <i class="fas fa-cog me-1"></i>General
            </button>
        </li>
        @if($isWebChannel)
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-widget-btn" data-bs-toggle="tab"
                    data-bs-target="#tab-widget" type="button" role="tab">
                <i class="far fa-comment-dots me-1"></i>Widget
                @unless($livechatEnabled)
                    <i class="fas fa-triangle-exclamation text-warning ms-1" title="Módulo HelpdeskLivechat deshabilitado"></i>
                @endunless
            </button>
        </li>
        @elseif(! empty($credentialFields) || $inbox->channel_type === 'whatsapp')
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-credentials-btn" data-bs-toggle="tab"
                    data-bs-target="#tab-credentials" type="button" role="tab">
                <i class="fas fa-key me-1"></i>Credenciales
            </button>
        </li>
        @endif
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-behavior-btn" data-bs-toggle="tab"
                    data-bs-target="#tab-behavior" type="button" role="tab">
                <i class="far fa-comment-dots me-1"></i>Comportamiento
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-assignment-btn" data-bs-toggle="tab"
                    data-bs-target="#tab-assignment" type="button" role="tab">
                <i class="fas fa-user-check me-1"></i>Asignación
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-visual-btn" data-bs-toggle="tab"
                    data-bs-target="#tab-visual" type="button" role="tab">
                <i class="fas fa-palette me-1"></i>Identidad visual
            </button>
        </li>
    </ul>

    {{-- Tab content --}}
    <div class="tab-content">

        {{-- ── TAB: GENERAL ───────────────────────────────────────── --}}
        <div class="tab-pane fade show active" id="tab-general" role="tabpanel">

            {{-- Código de instalación (widget existente) --}}
            @if($inbox->channel_type === 'web' && $isEdit && $channel !== null)
                <div class="card mb-3 border-success">
                    <div class="card-header bg-success bg-opacity-10 border-0">
                        <h6 class="mb-0 fw-bold text-success">
                            <i class="fas fa-code me-2"></i>Código de instalación
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-2">
                            Pega este script antes del cierre de <code>&lt;/body&gt;</code> en tu sitio web:
                        </p>
                        <div class="position-relative">
                            <pre id="embed-code" class="hd-embed-pre bg-light border rounded p-3 small mb-1">&lt;script&gt;
  window.helpdeskSettings = { websiteToken: '{{ $channel->website_token }}' };
  (function(d,t) {
    var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
    g.src='{{ asset('build-helpdesklivechat/widget.js') }}';g.defer=true;g.async=true;s.parentNode.insertBefore(g,s);
  })(document,'script');
&lt;/script&gt;</pre>
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-2"
                                    onclick="copyEmbedCode()">
                                <i class="far fa-copy me-1"></i>Copiar
                            </button>
                        </div>
                        <small class="text-muted">Token del widget: <code>{{ $channel->website_token }}</code></small>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header p-4 border-bottom border-light">
                    <h6 class="mb-1 fw-bold">
                        <i class="{{ $channelIcons[$inbox->channel_type] ?? 'fas fa-inbox' }} me-2"></i>
                        Información básica
                    </h6>
                    <p class="small mb-0 text-muted">Datos principales de la bandeja</p>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $inbox->name) }}"
                               required maxlength="120"
                               placeholder="Ej: WhatsApp Soporte General">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Canal <span class="text-danger">*</span></label>
                            <select name="channel_type"
                                    class="form-select @error('channel_type') is-invalid @enderror"
                                    {{ $isEdit ? 'disabled' : '' }} required>
                                @php
                                    $channelOptions = $isEdit
                                        ? \Modules\Helpdesk\Models\Inbox::channelLabels()
                                        : \Modules\Helpdesk\Models\Inbox::availableChannelLabels();
                                @endphp
                                @foreach($channelOptions as $key => $label)
                                    <option value="{{ $key }}" @selected(old('channel_type', $inbox->channel_type) === $key)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @if($isEdit)
                                <input type="hidden" name="channel_type" value="{{ $inbox->channel_type }}">
                                <small class="text-muted">El canal no se puede cambiar tras crear la bandeja.</small>
                            @endif
                            @error('channel_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select name="is_active" class="form-select">
                                <option value="1" @selected(old('is_active', $inbox->is_active))>Activa</option>
                                <option value="0" @selected(! old('is_active', $inbox->is_active))>Inactiva</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" class="form-control" rows="2"
                                  placeholder="Notas internas sobre esta bandeja">{{ old('description', $inbox->description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── TAB: WIDGET (canal web) ────────────────────────────── --}}
        @if($inbox->channel_type === 'web')
        <div class="tab-pane fade" id="tab-widget" role="tabpanel">
            @include('helpdesk::settings.inboxes._partials.widget-tab')
        </div>
        @endif

        {{-- ── TAB: CREDENCIALES ──────────────────────────────────── --}}
        @if($inbox->channel_type !== 'web' && (! empty($credentialFields) || $inbox->channel_type === 'whatsapp'))
        <div class="tab-pane fade" id="tab-credentials" role="tabpanel">
            <div class="card">
                <div class="card-header p-4 border-bottom border-light d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1 fw-bold"><i class="fas fa-key me-2"></i>Credenciales del canal</h6>
                        <p class="small mb-0 text-muted">Estos datos se almacenan encriptados en la base de datos</p>
                    </div>
                    @if($isEdit && $inbox->channel_id && in_array($inbox->channel_type, ['whatsapp', 'email']))
                        <div class="flex-shrink-0 ms-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-test-connection">
                                <i class="fas fa-plug me-1"></i>Probar conexión
                            </button>
                            <div id="test-result" class="small mt-2"></div>
                        </div>
                    @endif
                </div>
                <div class="card-body p-4">

                    {{-- WhatsApp: selector de proveedor + campos por proveedor --}}
                    @if($inbox->channel_type === 'whatsapp')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Proveedor de WhatsApp</label>
                            <select name="wa_provider" id="wa_provider" class="form-select">
                                <option value="whatsapp_cloud" @selected($waProvider === 'whatsapp_cloud')>
                                    Meta Cloud API (oficial)
                                </option>
                                <option value="360dialog" @selected($waProvider === '360dialog')>
                                    360dialog
                                </option>
                                <option value="evolution_api" @selected($waProvider === 'evolution_api')>
                                    Evolution API (self-hosted)
                                </option>
                            </select>
                        </div>

                        {{-- whatsapp_cloud --}}
                        <div class="wa-provider-fields" data-provider="whatsapp_cloud">
                            <div class="mb-3">
                                <label class="form-label">Business Account ID <span class="text-danger">*</span></label>
                                <input type="text" name="credentials[business_account_id]" class="form-control"
                                       value="{{ old('credentials.business_account_id', $inbox->getCredential('business_account_id', '')) }}"
                                       autocomplete="off" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Number ID <span class="text-danger">*</span></label>
                                <input type="text" name="credentials[phone_number_id]" class="form-control"
                                       value="{{ old('credentials.phone_number_id', $inbox->getCredential('phone_number_id', '')) }}"
                                       autocomplete="off" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Access Token <span class="text-danger">*</span></label>
                                @if($isEdit && ! old('credentials.access_token') && $inbox->getCredential('access_token'))
                                    <div class="input-group">
                                        <input type="password" name="credentials[access_token]" class="form-control"
                                               placeholder="Dejar vacío para mantener el token actual"
                                               autocomplete="off" disabled id="cred-access-token">
                                        <button type="button" class="btn btn-outline-secondary btn-change-cred" data-target="cred-access-token">Cambiar</button>
                                    </div>
                                @else
                                    <input type="password" name="credentials[access_token]" class="form-control"
                                           value="{{ old('credentials.access_token') }}"
                                           autocomplete="off" required>
                                @endif
                                <small class="text-muted">Token permanente de Meta Cloud API</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Verify Token <span class="text-danger">*</span></label>
                                <input type="text" name="credentials[verify_token]" class="form-control"
                                       value="{{ old('credentials.verify_token', $inbox->getCredential('verify_token', '')) }}"
                                       autocomplete="off" required>
                                <small class="text-muted">Cualquier string aleatorio que pegarás también en Meta</small>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">App Secret</label>
                                @if($isEdit && ! old('credentials.app_secret') && $inbox->getCredential('app_secret'))
                                    <div class="input-group">
                                        <input type="password" name="credentials[app_secret]" class="form-control"
                                               placeholder="Dejar vacío para mantener el secreto actual"
                                               autocomplete="off" disabled id="cred-app-secret">
                                        <button type="button" class="btn btn-outline-secondary btn-change-cred" data-target="cred-app-secret">Cambiar</button>
                                    </div>
                                @else
                                    <input type="password" name="credentials[app_secret]" class="form-control"
                                           value="{{ old('credentials.app_secret') }}"
                                           autocomplete="off">
                                @endif
                                <small class="text-muted">Para validar firma de webhooks</small>
                            </div>
                        </div>

                        {{-- 360dialog --}}
                        <div class="wa-provider-fields" data-provider="360dialog">
                            <div class="mb-3">
                                <label class="form-label">Número de teléfono <span class="text-danger">*</span></label>
                                <input type="text" name="credentials[phone_number]" class="form-control"
                                       value="{{ old('credentials.phone_number', $inbox->getCredential('phone_number', '')) }}"
                                       autocomplete="off" placeholder="+521234567890">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">API Key <span class="text-danger">*</span></label>
                                @if($isEdit && ! old('credentials.api_key') && $inbox->getCredential('api_key'))
                                    <div class="input-group">
                                        <input type="password" name="credentials[api_key]" class="form-control"
                                               placeholder="Dejar vacío para mantener la clave actual"
                                               autocomplete="off" disabled id="cred-api-key-360">
                                        <button type="button" class="btn btn-outline-secondary btn-change-cred" data-target="cred-api-key-360">Cambiar</button>
                                    </div>
                                @else
                                    <input type="password" name="credentials[api_key]" class="form-control"
                                           value="{{ old('credentials.api_key') }}"
                                           autocomplete="off">
                                @endif
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Verify Token <span class="text-danger">*</span></label>
                                <input type="text" name="credentials[verify_token_360]" class="form-control"
                                       value="{{ old('credentials.verify_token_360', $inbox->getCredential('verify_token_360', '')) }}"
                                       autocomplete="off">
                            </div>
                        </div>

                        {{-- evolution_api --}}
                        <div class="wa-provider-fields" data-provider="evolution_api">
                            <div class="mb-3">
                                <label class="form-label">Nombre de instancia <span class="text-danger">*</span></label>
                                <input type="text" name="credentials[instance_name]" class="form-control"
                                       value="{{ old('credentials.instance_name', $inbox->getCredential('instance_name', '')) }}"
                                       autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">URL de la API <span class="text-danger">*</span></label>
                                <input type="text" name="credentials[api_url]" class="form-control"
                                       value="{{ old('credentials.api_url', $inbox->getCredential('api_url', '')) }}"
                                       autocomplete="off" placeholder="https://evo.tudominio.com">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">API Key <span class="text-danger">*</span></label>
                                @if($isEdit && ! old('credentials.api_key') && $inbox->getCredential('api_key'))
                                    <div class="input-group">
                                        <input type="password" name="credentials[api_key]" class="form-control"
                                               placeholder="Dejar vacío para mantener la clave actual"
                                               autocomplete="off" disabled id="cred-api-key-evo">
                                        <button type="button" class="btn btn-outline-secondary btn-change-cred" data-target="cred-api-key-evo">Cambiar</button>
                                    </div>
                                @else
                                    <input type="password" name="credentials[api_key]" class="form-control"
                                           value="{{ old('credentials.api_key') }}"
                                           autocomplete="off">
                                @endif
                            </div>
                        </div>

                    @else
                        {{-- Resto de canales: loop genérico --}}
                        @foreach($credentialFields as $field)
                            @php
                                $isPassword = ($field['type'] ?? 'text') === 'password';
                                $hasValue   = $isPassword && $isEdit && $inbox->getCredential($field['key']);
                                $fieldId    = 'cred-'.Str::slug($field['key']);
                            @endphp
                            <div class="mb-3">
                                <label class="form-label">
                                    {{ $field['label'] }}
                                    @if(! empty($field['required']))<span class="text-danger">*</span>@endif
                                </label>
                                @if($isPassword && $hasValue && ! old('credentials.'.$field['key']))
                                    <div class="input-group">
                                        <input type="password" name="credentials[{{ $field['key'] }}]"
                                               class="form-control"
                                               placeholder="Dejar vacío para mantener el valor actual"
                                               autocomplete="off" disabled id="{{ $fieldId }}">
                                        <button type="button" class="btn btn-outline-secondary btn-change-cred" data-target="{{ $fieldId }}">Cambiar</button>
                                    </div>
                                @else
                                    <input type="{{ $isPassword ? 'password' : 'text' }}"
                                           name="credentials[{{ $field['key'] }}]"
                                           class="form-control"
                                           value="{{ $isPassword ? old('credentials.'.$field['key']) : old('credentials.'.$field['key'], $inbox->getCredential($field['key'], '')) }}"
                                           autocomplete="off"
                                           @if(! empty($field['required'])) required @endif>
                                @endif
                                @if(! empty($field['help']))
                                    <small class="text-muted">{{ $field['help'] }}</small>
                                @endif
                            </div>
                        @endforeach
                    @endif

                    {{-- Email: secciones IMAP y SMTP colapsables --}}
                    @if($inbox->channel_type === 'email')
                        <div class="mt-3">
                            <details class="mb-2" {{ $channel?->imap_enabled ? 'open' : '' }}>
                                <summary class="fw-semibold small text-muted cursor-pointer mb-2">
                                    <i class="fas fa-inbox me-1"></i>IMAP (recepción)
                                </summary>
                                <div class="row g-3 mt-1">
                                    <div class="col-md-8">
                                        <label class="form-label small">Servidor</label>
                                        <input type="text" name="credentials[imap_address]" class="form-control form-control-sm"
                                               value="{{ old('credentials.imap_address', $channel?->imap_address ?? '') }}"
                                               placeholder="imap.gmail.com">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Puerto</label>
                                        <input type="number" name="credentials[imap_port]" class="form-control form-control-sm"
                                               value="{{ old('credentials.imap_port', $channel?->imap_port ?? 993) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Usuario</label>
                                        <input type="text" name="credentials[imap_login]" class="form-control form-control-sm"
                                               value="{{ old('credentials.imap_login', $channel?->imap_login ?? '') }}"
                                               autocomplete="off">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Contraseña</label>
                                        <input type="password" name="credentials[imap_password]" class="form-control form-control-sm"
                                               autocomplete="off" placeholder="(sin cambios)">
                                    </div>
                                </div>
                            </details>

                            <details {{ $channel?->smtp_enabled ? 'open' : '' }}>
                                <summary class="fw-semibold small text-muted cursor-pointer mb-2">
                                    <i class="fas fa-paper-plane me-1"></i>SMTP (envío)
                                </summary>
                                <div class="row g-3 mt-1">
                                    <div class="col-md-8">
                                        <label class="form-label small">Servidor</label>
                                        <input type="text" name="credentials[smtp_address]" class="form-control form-control-sm"
                                               value="{{ old('credentials.smtp_address', $channel?->smtp_address ?? '') }}"
                                               placeholder="smtp.gmail.com">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Puerto</label>
                                        <input type="number" name="credentials[smtp_port]" class="form-control form-control-sm"
                                               value="{{ old('credentials.smtp_port', $channel?->smtp_port ?? 587) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Usuario</label>
                                        <input type="text" name="credentials[smtp_login]" class="form-control form-control-sm"
                                               value="{{ old('credentials.smtp_login', $channel?->smtp_login ?? '') }}"
                                               autocomplete="off">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Contraseña</label>
                                        <input type="password" name="credentials[smtp_password]" class="form-control form-control-sm"
                                               autocomplete="off" placeholder="(sin cambios)">
                                    </div>
                                </div>
                            </details>
                        </div>
                    @endif

                    {{-- Webhook URL para Meta/Instagram --}}
                    @if($isEdit && in_array($inbox->channel_type, ['whatsapp', 'facebook', 'instagram']))
                        <div class="alert alert-light border mt-3 mb-0">
                            <strong class="d-block mb-1">URL del webhook (pega esto en Meta Business Manager)</strong>
                            <code class="small">{{ $inbox->webhookUrl() }}</code>
                        </div>
                    @endif

                </div>
            </div>
        </div>
        @endif

        {{-- ── TAB: COMPORTAMIENTO ────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-behavior" role="tabpanel">
            <div class="card">
                <div class="card-header p-4 border-bottom border-light">
                    <h6 class="mb-1 fw-bold"><i class="far fa-comment-dots me-2"></i>Comportamiento</h6>
                    <p class="small mb-0 text-muted">Saludo automático y horario de atención</p>
                </div>
                <div class="card-body p-4">

                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="greeting_enabled" value="0">
                        <input class="form-check-input" type="checkbox" id="greeting_enabled"
                               name="greeting_enabled" value="1"
                               @checked(old('greeting_enabled', $inbox->greeting_enabled))>
                        <label class="form-check-label" for="greeting_enabled">Enviar mensaje de bienvenida</label>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Mensaje de bienvenida</label>
                        <textarea name="greeting_message" class="form-control" rows="2"
                                  placeholder="Hola, gracias por contactarnos…">{{ old('greeting_message', $inbox->greeting_message) }}</textarea>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="working_hours_enabled" value="0">
                        <input class="form-check-input" type="checkbox" id="working_hours_enabled"
                               name="working_hours_enabled" value="1"
                               @checked(old('working_hours_enabled', $inbox->working_hours_enabled))>
                        <label class="form-check-label" for="working_hours_enabled">Activar horario laboral</label>
                    </div>

                    <div id="inbox-schedule-block" class="mb-3">
                        <p class="text-muted small mb-3">Define los días y horas en que esta bandeja acepta mensajes</p>
                        @foreach($days as $key => $day)
                            @php
                                $enabled = $wh[$key]['enabled'] ?? $day['default_enabled'];
                                $start   = $wh[$key]['start']   ?? '09:00';
                                $end     = $wh[$key]['end']     ?? '18:00';
                            @endphp
                            <div class="row g-2 align-items-center mb-2 border-bottom pb-2">
                                <div class="col-md-3 d-flex align-items-center gap-2">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input inbox-day-toggle" type="checkbox"
                                               name="working_hours[{{ $key }}][enabled]" value="1"
                                               id="wh_{{ $key }}_enabled"
                                               {{ $enabled ? 'checked' : '' }}>
                                    </div>
                                    <label class="form-check-label fw-semibold" for="wh_{{ $key }}_enabled">
                                        {{ $day['label'] }}
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <input type="time" class="form-control form-control-sm inbox-day-time"
                                           name="working_hours[{{ $key }}][start]"
                                           value="{{ $start }}"
                                           {{ ! $enabled ? 'disabled' : '' }}>
                                </div>
                                <div class="col-auto text-muted">a</div>
                                <div class="col-md-4">
                                    <input type="time" class="form-control form-control-sm inbox-day-time"
                                           name="working_hours[{{ $key }}][end]"
                                           value="{{ $end }}"
                                           {{ ! $enabled ? 'disabled' : '' }}>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Mensaje fuera de horario</label>
                        <textarea name="out_of_office_message" class="form-control" rows="2"
                                  placeholder="Estamos fuera de horario. Te respondemos en cuanto volvamos.">{{ old('out_of_office_message', $inbox->out_of_office_message) }}</textarea>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── TAB: ASIGNACIÓN ───────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-assignment" role="tabpanel">
            <div class="card">
                <div class="card-header p-4 border-bottom border-light">
                    <h6 class="mb-1 fw-bold"><i class="far fa-user me-2"></i>Asignación por defecto</h6>
                    <p class="small mb-0 text-muted">Quién recibe las conversaciones nuevas</p>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Agente</label>
                        <select name="default_assignee_id" class="form-select">
                            <option value="">— Sin asignar —</option>
                            @foreach($agents as $u)
                                <option value="{{ $u->id }}" @selected(old('default_assignee_id', $inbox->default_assignee_id) == $u->id)>
                                    {{ trim(($u->firstname ?? '').' '.($u->lastname ?? '')) ?: $u->email }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Equipo</label>
                        <select name="default_group_id" class="form-select">
                            <option value="">— Ninguno —</option>
                            @foreach($groups as $g)
                                <option value="{{ $g->id }}" @selected(old('default_group_id', $inbox->default_group_id) == $g->id)>
                                    {{ $g->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── TAB: IDENTIDAD VISUAL ─────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-visual" role="tabpanel">
            <div class="card">
                <div class="card-header p-4 border-bottom border-light">
                    <h6 class="mb-1 fw-bold"><i class="fas fa-palette me-2"></i>Identidad visual</h6>
                    <p class="small mb-0 text-muted">Cómo se ve esta bandeja en el inbox</p>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Color</label>
                        <input type="color" name="color" class="form-control form-control-color"
                               value="{{ old('color', $inbox->color ?? '#90bb13') }}">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Icono (Font Awesome)</label>
                        <input type="text" name="icon" class="form-control"
                               value="{{ old('icon', $inbox->icon) }}"
                               placeholder="ej. fab fa-whatsapp">
                        <small class="text-muted">Si vacío, usa el icono por defecto del canal.</small>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /tab-content --}}

    {{-- Footer común --}}
    <div class="d-flex justify-content-between mt-4">
        <a href="{{ route('settings.helpdesk.inboxes.index') }}" class="btn btn-light">
            <i class="fas fa-chevron-left me-1"></i>Volver
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-check me-1"></i>
            {{ $isEdit ? 'Guardar cambios' : 'Crear bandeja' }}
        </button>
    </div>
</form>
    </div>

    @if($showWidgetPreview)
        <div class="col-xl-5 col-lg-5 d-none d-lg-block">
            <div class="inbox-preview-wrap">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-semibold text-muted small">
                        <i class="fas fa-eye me-1"></i>Vista previa en tiempo real
                    </span>
                    <ul class="nav inbox-preview-tabs gap-1" id="previewTabs">
                        <li class="nav-item"><a class="nav-link active" href="#" data-screen="home">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="#" data-screen="conversation">Chat</a></li>
                        <li class="nav-item"><a class="nav-link" href="#" data-screen="help">Ayuda</a></li>
                        <li class="nav-item"><a class="nav-link" href="#" data-screen="messages">Mensajes</a></li>
                    </ul>
                </div>

                @php
                    $previewToken = $channel?->website_token ?? null;
                    $previewUrl = route('helpdesk-livechat.widget.spa.index').'?preview=true'
                        .($previewToken ? '&website_token='.urlencode($previewToken) : '');
                @endphp
                <div class="inbox-phone-frame">
                    <div class="inbox-phone-notch"></div>
                    <div class="inbox-phone-screen">
                        <iframe id="widgetPreviewIframe"
                            src="{{ $previewUrl }}"
                            title="Vista previa del widget"></iframe>
                    </div>
                    <div class="inbox-phone-home-bar"></div>
                </div>

                <p class="text-center text-muted small mt-2 mb-0">
                    <i class="fas fa-circle-info me-1"></i>Los cambios se reflejan al editar los campos
                </p>
            </div>
        </div>
    @elseif($isWebChannel && ! $livechatEnabled)
        <div class="col-12">
            <div class="alert alert-warning d-flex align-items-start gap-2">
                <i class="fas fa-triangle-exclamation mt-1"></i>
                <div>
                    <strong>Módulo HelpdeskLivechat deshabilitado.</strong><br>
                    <span class="small">Para que la bandeja Web funcione (incluido el widget embebible y la vista previa), activa el módulo HelpdeskLivechat desde la gestión de módulos.</span>
                </div>
            </div>
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
$(function () {

    // ── Persistencia de tab en URL hash ──────────────────────────────
    $('#inboxTabs button').on('shown.bs.tab', function (e) {
        window.location.hash = e.target.getAttribute('data-bs-target');
    });

    if (window.location.hash) {
        const tabBtn = document.querySelector('[data-bs-target="' + window.location.hash + '"]');
        if (tabBtn) {
            bootstrap.Tab.getOrCreateInstance(tabBtn).show();
        }
    }

    // ── Autoexpandir tab del primer error de validación ───────────────
    @if($errors->any())
    (function () {
        const firstError = document.querySelector('.is-invalid');
        if (!firstError) { return; }
        const pane = firstError.closest('.tab-pane');
        if (!pane) { return; }
        const btn = document.querySelector('[data-bs-target="#' + pane.id + '"]');
        if (btn) { bootstrap.Tab.getOrCreateInstance(btn).show(); }
    })();
    @endif

    // ── Horario laboral ───────────────────────────────────────────────
    const $whToggle = $('#working_hours_enabled');
    const $whBlock  = $('#inbox-schedule-block');

    function toggleSchedule() {
        $whBlock.toggle($whToggle.is(':checked'));
    }

    toggleSchedule();
    $whToggle.on('change', toggleSchedule);

    $(document).on('change', '.inbox-day-toggle', function () {
        $(this).closest('.row').find('.inbox-day-time').prop('disabled', !this.checked);
    });

    // ── Selector de proveedor WhatsApp ────────────────────────────────
    $('#wa_provider').on('change', function () {
        const val = this.value;
        $('.wa-provider-fields').hide();
        $('[data-provider="' + val + '"]').show();
    }).trigger('change');

    // ── Copiar código de instalación ──────────────────────────────────
    window.copyEmbedCode = function () {
        const code = document.getElementById('embed-code').textContent;
        navigator.clipboard.writeText(code);
    };

    // ── Desbloquear campo de credencial oculto ────────────────────────
    $(document).on('click', '.btn-change-cred', function () {
        const target = $(this).data('target');
        $('#' + target).prop('disabled', false).attr('type', 'password').focus();
        $(this).prop('disabled', true).text('Editando…');
    });

    // ── Probar conexión ───────────────────────────────────────────────
    $('#btn-test-connection').on('click', function () {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Verificando...');

        $.ajax({
            url: @json($isEdit ? route('settings.helpdesk.inboxes.test', $inbox) : ''),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                const cls  = res.ok ? 'text-success' : 'text-danger';
                const icon = res.ok ? 'fa-check-circle' : 'fa-times-circle';
                $('#test-result')
                    .html('<i class="fas ' + icon + ' me-1"></i>' + res.message)
                    .attr('class', 'small mt-2 ' + cls);
            },
            error: function () {
                $('#test-result')
                    .html('<i class="fas fa-times-circle me-1"></i>Error al conectar')
                    .attr('class', 'small mt-2 text-danger');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-plug me-1"></i>Probar conexión');
            },
        });
    });

    @if($showWidgetPreview)
    // ── Widget preview live sync ─────────────────────────────────────
    (function () {
        const iframe = document.getElementById('widgetPreviewIframe');
        if (!iframe) return;

        let iframeReady = false;
        let pendingSettings = null;

        window.addEventListener('message', function (event) {
            if (event.origin !== window.location.origin) return;
            if (event.data?.source === 'be-backups-preview' && event.data?.type === 'appLoaded') {
                iframeReady = true;
                if (pendingSettings) {
                    postSettings(pendingSettings);
                    pendingSettings = null;
                }
            }
        });

        function getVal(name) {
            const el = document.querySelector('[name="widget['+name+']"]:not([type=hidden])');
            return el ? el.value : '';
        }
        function isChecked(name) {
            const el = document.querySelector('input[type=checkbox][name="widget['+name+']"]');
            return el ? el.checked : false;
        }

        function collectSettings() {
            return {
                primary_color:        getVal('widget_color') || '#90bb13',
                secondary_color:      getVal('secondary_color') || '#ffffff',
                header_title:         getVal('header_title'),
                welcome_message:      getVal('welcome_message'),
                input_placeholder:    getVal('input_placeholder'),
                show_avatars:         isChecked('show_avatars'),
                show_help_center:     isChecked('show_help_center'),
                typing_indicator:     isChecked('typing_indicator'),
                sound_notifications:  isChecked('sound_notifications'),
                show_timestamps:      isChecked('show_timestamps'),
                enable_email_transcripts: isChecked('enable_email_transcripts'),
                hide_launcher:        isChecked('hide_launcher'),
                position:             getVal('widget_position') || 'bottom-right',
                side_spacing:         parseInt(getVal('side_spacing')) || 16,
                bottom_spacing:       parseInt(getVal('bottom_spacing')) || 16,
            };
        }

        function postSettings(s) {
            if (!iframe.contentWindow) return;
            iframe.contentWindow.postMessage({
                source: 'be-backups-editor',
                type:   'setValues',
                values: s,
            }, window.location.origin);
        }

        function syncPreview() {
            const s = collectSettings();
            if (iframeReady) postSettings(s);
            else pendingSettings = s;
        }

        // Sync on any change inside #tab-widget
        $('#tab-widget').on('change input', 'input, select, textarea', syncPreview);

        // Switch preview screen tab
        const previewToken = @json($previewToken);
        $('#previewTabs').on('click', 'a.nav-link', function (e) {
            e.preventDefault();
            $('#previewTabs a.nav-link').removeClass('active');
            $(this).addClass('active');
            iframeReady = false;
            try {
                let url = iframe.src.split('?')[0] + '?preview=true&screen=' + $(this).data('screen');
                if (previewToken) url += '&website_token=' + encodeURIComponent(previewToken);
                iframe.src = url;
            } catch (err) {}
            pendingSettings = collectSettings();
        });

        // Initial sync
        pendingSettings = collectSettings();
    })();
    @endif

});
</script>
@endpush
