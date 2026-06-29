@extends('campaign::refactor.layout')

@section('title', 'Nuevo dominio de envío')

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">Nuevo dominio de envío</h1>
            <p class="mc-page-subtitle">Asocia un dominio a un servidor y configura la firma DKIM</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.sending-servers.domains.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'arrow-left', 'size' => 16])
                Volver
            </a>
        </div>
    </div>
@endsection

@section('content')

<div style="display:grid;grid-template-columns:1fr 340px;gap:var(--space-5);align-items:start;">

    {{-- Form --}}
    <div class="mc-card">
        <form action="{{ route('manager.sending-servers.domains.store') }}" method="POST">
            @csrf

            <div style="padding:var(--space-5);border-bottom:1px solid var(--color-border);">
                <p style="font-weight:var(--font-semibold);font-size:var(--text-base);margin:0 0 var(--space-1);">Datos del dominio</p>
                <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin:0;">Rellena el dominio y selecciona la configuración DKIM deseada</p>
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

                {{-- Dominio --}}
                <div class="mc-form-field">
                    <label class="mc-label" for="name">Dominio <span class="mc-required">*</span></label>
                    <input type="text" name="name" id="name"
                           class="mc-form-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                           value="{{ old('name') }}"
                           placeholder="example.com"
                           required>
                    @error('name')
                        <p class="mc-form-error">{{ $message }}</p>
                    @enderror
                    <p class="mc-form-hint">Solo letras, números, puntos y guiones.</p>
                </div>

                {{-- Servidor de envío --}}
                <div class="mc-form-field">
                    <label class="mc-label" for="sending_server_id">Servidor de envío</label>
                    <select name="sending_server_id" id="sending_server_id"
                            class="mc-form-input mc-form-select {{ $errors->has('sending_server_id') ? 'is-invalid' : '' }}">
                        <option value="">Sin asociar</option>
                        @foreach ($servers as $s)
                            <option value="{{ $s->id }}" @selected(old('sending_server_id') == $s->id)>
                                {{ $s->name }} ({{ $s->getTypeName() }})
                            </option>
                        @endforeach
                    </select>
                    @error('sending_server_id')
                        <p class="mc-form-error">{{ $message }}</p>
                    @enderror
                </div>

                <hr style="border:none;border-top:1px solid var(--color-border);margin:0;">

                <p style="font-weight:var(--font-semibold);font-size:var(--text-sm);margin:0;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.05em;">Firma DKIM</p>

                {{-- Signing enabled --}}
                <div class="mc-form-field">
                    <label style="display:flex;align-items:center;gap:var(--space-3);cursor:pointer;">
                        <input type="checkbox" name="signing_enabled" value="1"
                               id="signing_enabled"
                               class="mc-form-checkbox"
                               @checked(old('signing_enabled'))>
                        <span class="mc-label" style="margin:0;">Activar firma DKIM</span>
                    </label>
                    <p class="mc-form-hint">Se generará un par de claves RSA automáticamente al guardar.</p>
                </div>

                {{-- Selector DKIM --}}
                <div class="mc-form-field">
                    <label class="mc-label" for="dkim_selector">Selector DKIM</label>
                    <input type="text" name="dkim_selector" id="dkim_selector"
                           class="mc-form-input {{ $errors->has('dkim_selector') ? 'is-invalid' : '' }}"
                           value="{{ old('dkim_selector', 'mail') }}"
                           placeholder="mail">
                    @error('dkim_selector')
                        <p class="mc-form-error">{{ $message }}</p>
                    @enderror
                    <p class="mc-form-hint">Prefijo del registro TXT en DNS. Ej: <code>mail</code> generará <code>mail._domainkey.tudominio.com</code></p>
                </div>

            </div>

            <div style="padding:var(--space-4) var(--space-5);border-top:1px solid var(--color-border);display:flex;gap:var(--space-3);">
                <button type="submit" class="mc-btn mc-btn-primary mc-btn-sm">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 16])
                    Guardar dominio
                </button>
                <a href="{{ route('manager.sending-servers.domains.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                    Cancelar
                </a>
            </div>

        </form>
    </div>

    {{-- Sidebar help --}}
    <div class="mc-card" style="position:sticky;top:var(--space-5);">
        <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-border);">
            <p style="font-weight:var(--font-semibold);margin:0;">Sobre los dominios de envío</p>
        </div>
        <div style="padding:var(--space-4) var(--space-5);font-size:var(--text-sm);color:var(--color-text-muted);display:grid;gap:var(--space-4);">
            <p style="margin:0;">Los dominios de envío permiten autenticar el correo saliente mediante DKIM, SPF y DMARC para mejorar la entregabilidad.</p>
            <div>
                <p style="font-weight:var(--font-semibold);color:var(--color-text);margin:0 0 var(--space-1);">Firma DKIM</p>
                <p style="margin:0;">Al activarla, se genera un par de claves RSA. Debes publicar el registro TXT en tu DNS para completar la verificación.</p>
            </div>
            <div>
                <p style="font-weight:var(--font-semibold);color:var(--color-text);margin:0 0 var(--space-1);">Selector</p>
                <p style="margin:0;">El selector es el prefijo del registro DNS. El valor por defecto <code>mail</code> genera <code>mail._domainkey.tudominio.com</code>.</p>
            </div>
            <div>
                <p style="font-weight:var(--font-semibold);color:var(--color-text);margin:0 0 var(--space-1);">Verificación</p>
                <p style="margin:0;">Tras crear el dominio, publica el TXT en tu DNS y pulsa <em>Verificar DKIM</em> desde el detalle.</p>
            </div>
        </div>
    </div>

</div>

@endsection
