@extends('campaign::refactor.layout')

@section('title', 'Nuevo dominio de tracking')

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">Nuevo dominio de tracking</h1>
            <p class="mc-page-subtitle">Registra un subdominio propio para personalizar los enlaces de seguimiento</p>
        </div>
        <div class="mc-page-actions">
            <a href="{{ route('manager.sending-servers.tracking-domains.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
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
        <form method="post" action="{{ route('manager.sending-servers.tracking-domains.store') }}">
            @csrf

            <div style="padding:var(--space-5);border-bottom:1px solid var(--color-border);">
                <p style="font-weight:var(--font-semibold);font-size:var(--text-base);margin:0 0 var(--space-1);">Datos del dominio de tracking</p>
                <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin:0;">El dominio debe apuntar a este servidor vía CNAME o registro A</p>
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
                    <label class="mc-label" for="name">Dominio (subdominio recomendado) <span class="mc-required">*</span></label>
                    <input type="text" name="name" id="name"
                           class="mc-form-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                           value="{{ old('name') }}"
                           placeholder="track.example.com"
                           required>
                    @error('name')
                        <p class="mc-form-error">{{ $message }}</p>
                    @enderror
                    <p class="mc-form-hint">Solo letras, números, puntos y guiones. Debe ser único.</p>
                </div>

                {{-- Método de verificación --}}
                <div class="mc-form-field">
                    <label class="mc-label" for="verification_method">Método de verificación</label>
                    <select name="verification_method" id="verification_method" class="mc-form-input mc-form-select">
                        <option value="cname" @selected(old('verification_method','cname') === 'cname')>CNAME (recomendado)</option>
                        <option value="host" @selected(old('verification_method') === 'host')>HOST (registro A)</option>
                        <option value="caddy" @selected(old('verification_method') === 'caddy')>Caddy AutoSSL</option>
                    </select>
                    <p class="mc-form-hint">CNAME es la opción más compatible con la mayoría de DNS.</p>
                </div>

            </div>

            <div style="padding:var(--space-4) var(--space-5);border-top:1px solid var(--color-border);display:flex;gap:var(--space-3);">
                <button type="submit" class="mc-btn mc-btn-primary mc-btn-sm">
                    @include('campaign::refactor.components.icons.mc-icon', ['icon' => 'check', 'size' => 16])
                    Guardar dominio
                </button>
                <a href="{{ route('manager.sending-servers.tracking-domains.index') }}" class="mc-btn mc-btn-secondary mc-btn-sm">
                    Cancelar
                </a>
            </div>

        </form>
    </div>

    {{-- Sidebar help --}}
    <div class="mc-card" style="position:sticky;top:var(--space-5);">
        <div style="padding:var(--space-4) var(--space-5);border-bottom:1px solid var(--color-border);">
            <p style="font-weight:var(--font-semibold);margin:0;">¿Cómo funciona?</p>
        </div>
        <div style="padding:var(--space-4) var(--space-5);font-size:var(--text-sm);color:var(--color-text-muted);display:grid;gap:var(--space-4);">
            <div>
                <p style="font-weight:var(--font-semibold);color:var(--color-text);margin:0 0 var(--space-1);">CNAME</p>
                <p style="margin:0;">Crea un registro CNAME en tu DNS apuntando al hostname de tracking de esta instalación.</p>
            </div>
            <div>
                <p style="font-weight:var(--font-semibold);color:var(--color-text);margin:0 0 var(--space-1);">HOST (registro A)</p>
                <p style="margin:0;">Crea un registro A apuntando directamente a la IP del servidor.</p>
            </div>
            <div>
                <p style="font-weight:var(--font-semibold);color:var(--color-text);margin:0 0 var(--space-1);">Caddy AutoSSL</p>
                <p style="margin:0;">Caddy gestiona automáticamente el certificado SSL. Solo disponible si Caddy está configurado en el servidor.</p>
            </div>
            <div>
                <p style="font-weight:var(--font-semibold);color:var(--color-text);margin:0 0 var(--space-1);">Verificación</p>
                <p style="margin:0;">Tras guardar, pulsa <em>Verificar</em> desde el detalle o espera al cron de verificación automática.</p>
            </div>
        </div>
    </div>

</div>

@endsection
