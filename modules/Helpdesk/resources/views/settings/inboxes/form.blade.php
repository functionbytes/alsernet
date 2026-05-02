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

@section('content')

<div class="widget-content searchable-container list">
    @include('core::components.alerts')

    <form action="{{ $action }}" method="POST">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="row g-3">

            {{-- ========== COLUMNA PRINCIPAL ========== --}}
            <div class="col-lg-8">

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
                                <pre id="embed-code" class="bg-light border rounded p-3 small mb-1" style="white-space:pre-wrap;word-break:break-all;">&lt;script&gt;
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

                {{-- Información básica --}}
                <div class="card mb-3">
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
                                        // Para edit: mantenemos todas las opciones para no perder el canal actual
                                        // si el módulo del canal se deshabilitó. Para create: solo canales disponibles.
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

                {{-- Credenciales por canal --}}
                @if(! empty($credentialFields) || $inbox->channel_type === 'whatsapp')
                    <div class="card mb-3">
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
                                        <input type="password" name="credentials[access_token]" class="form-control"
                                               value="{{ old('credentials.access_token', $inbox->getCredential('access_token', '')) }}"
                                               autocomplete="off" required>
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
                                        <input type="password" name="credentials[app_secret]" class="form-control"
                                               value="{{ old('credentials.app_secret', $inbox->getCredential('app_secret', '')) }}"
                                               autocomplete="off">
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
                                        <input type="password" name="credentials[api_key]" class="form-control"
                                               value="{{ old('credentials.api_key', $inbox->getCredential('api_key', '')) }}"
                                               autocomplete="off">
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
                                        <input type="password" name="credentials[api_key]" class="form-control"
                                               value="{{ old('credentials.api_key', $inbox->getCredential('api_key', '')) }}"
                                               autocomplete="off">
                                    </div>
                                </div>

                            @else
                                {{-- Resto de canales: loop genérico --}}
                                @foreach($credentialFields as $field)
                                    @php
                                        $cur        = old('credentials.'.$field['key'], $inbox->getCredential($field['key'], ''));
                                        $isPassword = ($field['type'] ?? 'text') === 'password';
                                    @endphp
                                    <div class="mb-3">
                                        <label class="form-label">
                                            {{ $field['label'] }}
                                            @if(! empty($field['required']))<span class="text-danger">*</span>@endif
                                        </label>
                                        <input type="{{ $isPassword ? 'password' : 'text' }}"
                                               name="credentials[{{ $field['key'] }}]"
                                               class="form-control"
                                               value="{{ $cur }}"
                                               autocomplete="off"
                                               @if(! empty($field['required'])) required @endif>
                                        @if(! empty($field['help']))
                                            <small class="text-muted">{{ $field['help'] }}</small>
                                        @endif
                                    </div>
                                @endforeach
                            @endif

                            {{-- Email: secciones IMAP y SMTP colapsables --}}
                            @if($inbox->channel_type === 'email')
                                <div class="mt-3">
                                    {{-- IMAP --}}
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

                                    {{-- SMTP --}}
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
                @endif

                {{-- Comportamiento: saludo + horario laboral --}}
                <div class="card mb-3">
                    <div class="card-header p-4 border-bottom border-light">
                        <h6 class="mb-1 fw-bold"><i class="far fa-comment-dots me-2"></i>Comportamiento</h6>
                        <p class="small mb-0 text-muted">Saludo automático y horario de atención</p>
                    </div>
                    <div class="card-body p-4">

                        {{-- Saludo --}}
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

                        {{-- Horario laboral --}}
                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="working_hours_enabled" value="0">
                            <input class="form-check-input" type="checkbox" id="working_hours_enabled"
                                   name="working_hours_enabled" value="1"
                                   @checked(old('working_hours_enabled', $inbox->working_hours_enabled))>
                            <label class="form-check-label" for="working_hours_enabled">Activar horario laboral</label>
                        </div>

                        {{-- Grilla semanal --}}
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

                        {{-- Mensaje fuera de horario --}}
                        <div class="mb-0">
                            <label class="form-label">Mensaje fuera de horario</label>
                            <textarea name="out_of_office_message" class="form-control" rows="2"
                                      placeholder="Estamos fuera de horario. Te respondemos en cuanto volvamos.">{{ old('out_of_office_message', $inbox->out_of_office_message) }}</textarea>
                        </div>

                    </div>
                </div>

            </div>

            {{-- ========== COLUMNA LATERAL ========== --}}
            <div class="col-lg-4">

                <div class="card mb-3">
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

                <div class="card mb-3">
                    <div class="card-header p-4 border-bottom border-light">
                        <h6 class="mb-1 fw-bold"><i class="fas fa-palette me-2"></i>Identidad visual</h6>
                        <p class="small mb-0 text-muted">Cómo se ve esta bandeja en el inbox</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label">Color</label>
                            <input type="color" name="color" class="form-control form-control-color"
                                   value="{{ old('color', $inbox->color ?? '#b10100') }}">
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
        </div>

        {{-- Footer acciones --}}
        <div class="d-flex justify-content-between mt-3">
            <a href="{{ route('settings.helpdesk.inboxes.index') }}" class="btn btn-light">
                <i class="fas fa-chevron-left me-1"></i> Volver
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-check me-1"></i>
                {{ $isEdit ? 'Guardar cambios' : 'Crear bandeja' }}
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
$(function () {

    // ── Horario laboral ────────────────────────────────────────────────
    const $whToggle = $('#working_hours_enabled');
    const $whBlock  = $('#inbox-schedule-block');

    function toggleSchedule() {
        $whBlock.toggle($whToggle.is(':checked'));
    }

    toggleSchedule();
    $whToggle.on('change', toggleSchedule);

    // Deshabilitar inputs de tiempo cuando el día está desactivado
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
        navigator.clipboard.writeText(code).then(function () {
        });
    };

    // ── Probar conexión ───────────────────────────────────────────────
    $('#btn-test-connection').on('click', function () {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Verificando...');

        $.ajax({
            url: window.location.pathname + '/test',
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

});
</script>
@endpush
