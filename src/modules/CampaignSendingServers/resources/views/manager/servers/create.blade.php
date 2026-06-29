@extends('campaign::refactor.layout')

@section('title', 'Nuevo servidor de envío')

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">Nuevo servidor de envío</h1>
            <p class="mc-page-subtitle">Configura un nuevo proveedor SMTP o API para el envío de emails</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.sending-servers.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                Volver
            </a>
        </div>
    </div>
@endsection

@section('content')

<div style="display:grid;grid-template-columns:1fr 340px;gap:var(--space-5);align-items:start;">

    {{-- Main form --}}
    <div class="mc-card">
        <form method="post" action="{{ route('manager.sending-servers.store') }}" id="create-form">
            @csrf

            <div style="padding:var(--space-5);border-bottom:1px solid var(--color-border);">
                <p style="font-weight:var(--font-semibold);font-size:var(--text-base);margin:0 0 var(--space-1);">Información del servidor</p>
                <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin:0;">Completa los datos según el proveedor seleccionado</p>
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

                {{-- Tipo --}}
                <div class="mc-form-field">
                    <label class="mc-label" for="type">Tipo de proveedor <span class="mc-required">*</span></label>
                    <select name="type" id="type" class="mc-form-input mc-form-select" required>
                        @foreach ($providers as $key => $meta)
                            <option value="{{ $key }}" @selected($key === $type)>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Nombre --}}
                <div class="mc-form-field">
                    <label class="mc-label" for="name">Nombre interno <span class="mc-required">*</span></label>
                    <input type="text" name="name" id="name"
                           class="mc-form-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                           value="{{ old('name') }}"
                           placeholder="Ej: SES producción"
                           required>
                    @error('name')
                        <p class="mc-form-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email por defecto --}}
                <div class="mc-form-field">
                    <label class="mc-label" for="default_from_email">Email de envío por defecto</label>
                    <input type="email" name="default_from_email" id="default_from_email"
                           class="mc-form-input"
                           value="{{ old('default_from_email') }}"
                           placeholder="noreply@example.com">
                    <p class="mc-form-hint">Será el From por defecto si la campaña no especifica uno.</p>
                </div>

                <hr style="border:none;border-top:1px solid var(--color-border);margin:0;">

                <p style="font-weight:var(--font-semibold);font-size:var(--text-sm);margin:0;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;">SMTP / Conexión</p>

                {{-- Host + Puerto + Protocolo --}}
                <div style="display:grid;grid-template-columns:1fr 120px 130px;gap:var(--space-3);">
                    <div class="mc-form-field">
                        <label class="mc-label" for="host">Host SMTP</label>
                        <input type="text" name="host" id="host"
                               class="mc-form-input"
                               value="{{ old('host') }}"
                               placeholder="smtp.example.com">
                    </div>
                    <div class="mc-form-field">
                        <label class="mc-label" for="smtp_port">Puerto</label>
                        <input type="number" name="smtp_port" id="smtp_port"
                               class="mc-form-input"
                               value="{{ old('smtp_port', 587) }}">
                    </div>
                    <div class="mc-form-field">
                        <label class="mc-label" for="smtp_protocol">Protocolo</label>
                        <select name="smtp_protocol" id="smtp_protocol" class="mc-form-input mc-form-select">
                            <option value="tls" @selected(old('smtp_protocol','tls') === 'tls')>TLS</option>
                            <option value="ssl" @selected(old('smtp_protocol') === 'ssl')>SSL</option>
                            <option value="none" @selected(old('smtp_protocol') === 'none')>Ninguno</option>
                        </select>
                    </div>
                </div>

                {{-- Usuario / Contraseña SMTP --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);">
                    <div class="mc-form-field">
                        <label class="mc-label" for="smtp_username">Usuario SMTP</label>
                        <input type="text" name="smtp_username" id="smtp_username"
                               class="mc-form-input"
                               value="{{ old('smtp_username') }}"
                               autocomplete="new-password">
                    </div>
                    <div class="mc-form-field">
                        <label class="mc-label" for="smtp_password">Contraseña SMTP</label>
                        <input type="password" name="smtp_password" id="smtp_password"
                               class="mc-form-input"
                               autocomplete="new-password">
                    </div>
                </div>

                <hr style="border:none;border-top:1px solid var(--color-border);margin:0;">

                <p style="font-weight:var(--font-semibold);font-size:var(--text-sm);margin:0;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;">API / Credenciales</p>

                {{-- API Key --}}
                <div class="mc-form-field">
                    <label class="mc-label" for="api_key">API Key</label>
                    <input type="password" name="api_key" id="api_key"
                           class="mc-form-input"
                           autocomplete="new-password">
                    <p class="mc-form-hint">Para proveedores API (SendGrid, Mailgun, SparkPost, ElasticEmail, Brevo).</p>
                </div>

                {{-- AWS --}}
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:var(--space-3);">
                    <div class="mc-form-field">
                        <label class="mc-label" for="aws_access_key_id">AWS Access Key ID</label>
                        <input type="text" name="aws_access_key_id" id="aws_access_key_id"
                               class="mc-form-input"
                               value="{{ old('aws_access_key_id') }}">
                    </div>
                    <div class="mc-form-field">
                        <label class="mc-label" for="aws_secret_access_key">AWS Secret Key</label>
                        <input type="password" name="aws_secret_access_key" id="aws_secret_access_key"
                               class="mc-form-input"
                               autocomplete="new-password">
                    </div>
                    <div class="mc-form-field">
                        <label class="mc-label" for="aws_region">Región AWS</label>
                        <input type="text" name="aws_region" id="aws_region"
                               class="mc-form-input"
                               value="{{ old('aws_region') }}"
                               placeholder="us-east-1">
                    </div>
                </div>

                {{-- Domain (Mailgun) --}}
                <div class="mc-form-field">
                    <label class="mc-label" for="domain">Dominio (Mailgun)</label>
                    <input type="text" name="domain" id="domain"
                           class="mc-form-input"
                           value="{{ old('domain') }}"
                           placeholder="mg.example.com">
                </div>

                {{-- Sendmail path --}}
                <div class="mc-form-field">
                    <label class="mc-label" for="sendmail_path">Path Sendmail</label>
                    <input type="text" name="sendmail_path" id="sendmail_path"
                           class="mc-form-input"
                           value="{{ old('sendmail_path', '/usr/sbin/sendmail') }}">
                    <p class="mc-form-hint">Solo para el tipo <em>Sendmail (local)</em>.</p>
                </div>

                <hr style="border:none;border-top:1px solid var(--color-border);margin:0;">

                <p style="font-weight:var(--font-semibold);font-size:var(--text-sm);margin:0;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;">Cuota / Rate limit</p>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:var(--space-3);">
                    <div class="mc-form-field">
                        <label class="mc-label" for="quota_value">Máx. mensajes</label>
                        <input type="number" name="quota_value" id="quota_value"
                               class="mc-form-input"
                               value="{{ old('quota_value', 60) }}"
                               min="1">
                    </div>
                    <div class="mc-form-field">
                        <label class="mc-label" for="quota_base">Cada (base)</label>
                        <input type="number" name="quota_base" id="quota_base"
                               class="mc-form-input"
                               value="{{ old('quota_base', 1) }}"
                               min="1">
                    </div>
                    <div class="mc-form-field">
                        <label class="mc-label" for="quota_unit">Unidad</label>
                        <select name="quota_unit" id="quota_unit" class="mc-form-input mc-form-select">
                            <option value="second" @selected(old('quota_unit') === 'second')>segundo</option>
                            <option value="minute" @selected(old('quota_unit','minute') === 'minute')>minuto</option>
                            <option value="hour" @selected(old('quota_unit') === 'hour')>hora</option>
                            <option value="day" @selected(old('quota_unit') === 'day')>día</option>
                        </select>
                    </div>
                </div>

            </div>

            <div style="padding:var(--space-4) var(--space-5);border-top:1px solid var(--color-border);display:flex;gap:var(--space-3);">
                <button type="submit" class="mc-btn mc-btn-primary mc-btn-sm">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 16])
                    Guardar servidor
                </button>
                <a href="{{ route('manager.sending-servers.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                    Cancelar
                </a>
            </div>

        </form>
    </div>

    {{-- Sidebar help --}}
    <div class="mc-card" style="position:sticky;top:var(--space-5);">
        <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-border);">
            <p style="font-weight:var(--font-semibold);margin:0;">Guía rápida</p>
        </div>
        <div style="padding:var(--space-4) var(--space-5);font-size:var(--text-sm);color:var(--color-text-muted);display:grid;gap:var(--space-4);">
            <div>
                <p style="font-weight:var(--font-semibold);color:var(--color-text);margin:0 0 var(--space-1);">SMTP genérico</p>
                <p style="margin:0;">Rellena Host, Puerto, Protocolo y credenciales. Compatible con cualquier servidor SMTP estándar.</p>
            </div>
            <div>
                <p style="font-weight:var(--font-semibold);color:var(--color-text);margin:0 0 var(--space-1);">Amazon SES (API)</p>
                <p style="margin:0;">Requiere Access Key ID, Secret Key y Región. Asegúrate de que la identidad del dominio esté verificada en AWS.</p>
            </div>
            <div>
                <p style="font-weight:var(--font-semibold);color:var(--color-text);margin:0 0 var(--space-1);">Mailgun / SendGrid / Brevo</p>
                <p style="margin:0;">Solo necesitas la API Key. Para Mailgun también el dominio asociado.</p>
            </div>
            <div>
                <p style="font-weight:var(--font-semibold);color:var(--color-text);margin:0 0 var(--space-1);">Cuota</p>
                <p style="margin:0;">Limita la velocidad de envío. Ej: 60 mensajes por minuto.</p>
            </div>
        </div>
    </div>

</div>

@endsection
