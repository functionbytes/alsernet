@extends('campaign::refactor.layout')

@section('title', 'Editar servidor: ' . $server->name)

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ $server->name }}</h1>
            <p class="mc-page-subtitle">{{ $server->getTypeName() }} &middot; <code style="font-size:var(--text-xs);">{{ $server->uid }}</code></p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.sending-servers.show', $server->uid) }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                Volver al detalle
            </a>
        </div>
    </div>
@endsection

@section('content')

<div style="display:grid;grid-template-columns:1fr 340px;gap:var(--space-5);align-items:start;">

    {{-- Main form --}}
    <div class="mc-card">
        <form method="post" action="{{ route('manager.sending-servers.update', $server->uid) }}">
            @csrf
            @method('PUT')

            <div style="padding:var(--space-5);border-bottom:1px solid var(--color-border);">
                <p style="font-weight:var(--font-semibold);font-size:var(--text-base);margin:0 0 var(--space-1);">Editar servidor</p>
                <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin:0;">Actualiza la configuración del servidor de envío</p>
            </div>

            @if ($errors->any())
                <div class="mc-alert mc-alert-danger" style="margin:var(--space-5) var(--space-5) 0;">
                    <div class="mc-alert-icon">
                        @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'info', 'size' => 16])
                    </div>
                    <div class="mc-alert-content">
                        <ul style="margin:0;padding-left:var(--space-4);">
                            @foreach ($errors->all() as $error)
                                <li style="font-size:var(--text-sm);">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div style="padding:var(--space-5);display:grid;gap:var(--space-4);">

                {{-- Nombre --}}
                <div class="mc-form-field">
                    <label class="mc-label" for="name">Nombre <span class="mc-required">*</span></label>
                    <input type="text" name="name" id="name"
                           class="mc-form-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                           value="{{ old('name', $server->name) }}"
                           required>
                    @error('name')
                        <p class="mc-form-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Estado --}}
                <div class="mc-form-field">
                    <label class="mc-label" for="status">Estado</label>
                    <select name="status" id="status" class="mc-form-input mc-form-select">
                        <option value="active" @selected(old('status', $server->status) === 'active')>
                            {{ trans('campaign::sending-servers.status.active') }}
                        </option>
                        <option value="inactive" @selected(old('status', $server->status) === 'inactive')>
                            {{ trans('campaign::sending-servers.status.inactive') }}
                        </option>
                    </select>
                </div>

                {{-- Email por defecto --}}
                <div class="mc-form-field">
                    <label class="mc-label" for="default_from_email">Email de envío por defecto</label>
                    <input type="email" name="default_from_email" id="default_from_email"
                           class="mc-form-input"
                           value="{{ old('default_from_email', $server->default_from_email) }}"
                           placeholder="noreply@example.com">
                </div>

                <hr style="border:none;border-top:1px solid var(--color-border);margin:0;">

                <p style="font-weight:var(--font-semibold);font-size:var(--text-sm);margin:0;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;">SMTP / Conexión</p>

                <div style="display:grid;grid-template-columns:1fr 120px 130px;gap:var(--space-3);">
                    <div class="mc-form-field">
                        <label class="mc-label" for="host">Host SMTP</label>
                        <input type="text" name="host" id="host"
                               class="mc-form-input"
                               value="{{ old('host', $server->host) }}"
                               placeholder="smtp.example.com">
                    </div>
                    <div class="mc-form-field">
                        <label class="mc-label" for="smtp_port">Puerto</label>
                        <input type="number" name="smtp_port" id="smtp_port"
                               class="mc-form-input"
                               value="{{ old('smtp_port', $server->smtp_port) }}">
                    </div>
                    <div class="mc-form-field">
                        <label class="mc-label" for="smtp_protocol">Protocolo</label>
                        <select name="smtp_protocol" id="smtp_protocol" class="mc-form-input mc-form-select">
                            @foreach (['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'Ninguno'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('smtp_protocol', $server->smtp_protocol) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);">
                    <div class="mc-form-field">
                        <label class="mc-label" for="smtp_username">Usuario SMTP</label>
                        <input type="text" name="smtp_username" id="smtp_username"
                               class="mc-form-input"
                               value="{{ old('smtp_username', $server->smtp_username) }}"
                               autocomplete="new-password">
                    </div>
                    <div class="mc-form-field">
                        <label class="mc-label" for="smtp_password">Contraseña SMTP</label>
                        <input type="password" name="smtp_password" id="smtp_password"
                               class="mc-form-input"
                               autocomplete="new-password"
                               placeholder="Dejar vacío para mantener">
                        <p class="mc-form-hint">Solo rellena si deseas cambiarla.</p>
                    </div>
                </div>

                <hr style="border:none;border-top:1px solid var(--color-border);margin:0;">

                <p style="font-weight:var(--font-semibold);font-size:var(--text-sm);margin:0;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;">Credenciales API / AWS</p>

                <div class="mc-form-field">
                    <label class="mc-label" for="api_key">API Key</label>
                    <input type="password" name="api_key" id="api_key"
                           class="mc-form-input"
                           autocomplete="new-password"
                           placeholder="Dejar vacío para mantener">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:var(--space-3);">
                    <div class="mc-form-field">
                        <label class="mc-label" for="aws_access_key_id">AWS Access Key ID</label>
                        <input type="text" name="aws_access_key_id" id="aws_access_key_id"
                               class="mc-form-input"
                               value="{{ old('aws_access_key_id', $server->aws_access_key_id) }}">
                    </div>
                    <div class="mc-form-field">
                        <label class="mc-label" for="aws_secret_access_key">AWS Secret Key</label>
                        <input type="password" name="aws_secret_access_key" id="aws_secret_access_key"
                               class="mc-form-input"
                               autocomplete="new-password"
                               placeholder="Dejar vacío para mantener">
                    </div>
                    <div class="mc-form-field">
                        <label class="mc-label" for="aws_region">Región AWS</label>
                        <input type="text" name="aws_region" id="aws_region"
                               class="mc-form-input"
                               value="{{ old('aws_region', $server->aws_region) }}"
                               placeholder="us-east-1">
                    </div>
                </div>

                <div class="mc-form-field">
                    <label class="mc-label" for="domain">Dominio (Mailgun)</label>
                    <input type="text" name="domain" id="domain"
                           class="mc-form-input"
                           value="{{ old('domain', $server->domain) }}"
                           placeholder="mg.example.com">
                </div>

                <hr style="border:none;border-top:1px solid var(--color-border);margin:0;">

                <p style="font-weight:var(--font-semibold);font-size:var(--text-sm);margin:0;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;">Cuota / Rate limit</p>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:var(--space-3);">
                    <div class="mc-form-field">
                        <label class="mc-label" for="quota_value">Máx. mensajes</label>
                        <input type="number" name="quota_value" id="quota_value"
                               class="mc-form-input"
                               value="{{ old('quota_value', $server->quota_value) }}"
                               min="1">
                    </div>
                    <div class="mc-form-field">
                        <label class="mc-label" for="quota_base">Cada (base)</label>
                        <input type="number" name="quota_base" id="quota_base"
                               class="mc-form-input"
                               value="{{ old('quota_base', $server->quota_base) }}"
                               min="1">
                    </div>
                    <div class="mc-form-field">
                        <label class="mc-label" for="quota_unit">Unidad</label>
                        <select name="quota_unit" id="quota_unit" class="mc-form-input mc-form-select">
                            @foreach (['second' => 'Segundo', 'minute' => 'Minuto', 'hour' => 'Hora', 'day' => 'Día'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('quota_unit', $server->quota_unit) === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

            </div>

            <div style="padding:var(--space-4) var(--space-5);border-top:1px solid var(--color-border);display:flex;gap:var(--space-3);">
                <button type="submit" class="mc-btn mc-btn-primary mc-btn-sm">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 16])
                    Guardar cambios
                </button>
                <a href="{{ route('manager.sending-servers.show', $server->uid) }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                    Cancelar
                </a>
            </div>

        </form>
    </div>

    {{-- Sidebar info --}}
    <div class="mc-card" style="position:sticky;top:var(--space-5);">
        <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-border);">
            <p style="font-weight:var(--font-semibold);margin:0;">Información del servidor</p>
        </div>
        <div style="padding:var(--space-4) var(--space-5);font-size:var(--text-sm);display:grid;gap:var(--space-3);">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="color:var(--color-text-muted);">Tipo</span>
                <span style="font-weight:var(--font-semibold);">{{ $server->getTypeName() }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="color:var(--color-text-muted);">Estado actual</span>
                @if($server->status === 'active')
                    <span class="mc-badge mc-badge-success">{{ trans('campaign::sending-servers.status.active') }}</span>
                @else
                    <span class="mc-badge mc-badge-neutral">{{ trans('campaign::sending-servers.status.inactive') }}</span>
                @endif
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="color:var(--color-text-muted);">UID</span>
                <code style="font-size:var(--text-xs);">{{ $server->uid }}</code>
            </div>
            @if($server->default_from_email)
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="color:var(--color-text-muted);">From</span>
                <code style="font-size:var(--text-xs);">{{ $server->default_from_email }}</code>
            </div>
            @endif
        </div>
        <div style="padding:var(--space-3) var(--space-5);border-top:1px solid var(--color-border);">
            <form method="post" action="{{ route('manager.sending-servers.test', $server->uid) }}">
                @csrf
                <button type="submit" class="mc-btn mc-btn-secondary mc-btn-sm" style="width:100%;">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 14])
                    {{ trans('campaign::sending-servers.action.test') }}
                </button>
            </form>
        </div>
    </div>

</div>

@endsection
