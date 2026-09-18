@extends('layouts.theme')

@section('title', 'Configuración de autenticación de dos factores')

@section('page_header')
    @include('core::components.card', ['title' => 'Seguridad de cuenta'])
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations.css') }}?v={{ @filemtime(public_path('vendor/helpdesk/conversations.css')) }}"/>
@endpush

@section('content')
<div class="widget-content">

    @include('core::components.alerts')

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-6">

            @if(auth()->user()->two_factor_confirmed_at)
                {{-- 2FA already active --}}
                <div class="card">
                    <div class="card-body text-center py-4">
                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3 bv-wh-64">
                            <i class="fas fa-shield-alt text-success fs-3"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Autenticación de dos factores activa</h5>
                        <p class="text-muted mb-0">
                            Activada el
                            {{ auth()->user()->two_factor_confirmed_at->format('d/m/Y \a \l\a\s H:i') }}
                        </p>
                    </div>
                    <div class="card-footer bg-white border-top p-3">
                        <button type="button" class="btn btn-brand w-100 mb-2" data-bs-toggle="modal" data-bs-target="#disableModal">
                            Desactivar autenticación de dos factores
                        </button>
                        <a href="{{ route('manager.helpdesk') }}" class="btn btn-light w-100">Volver al inicio</a>
                    </div>
                </div>

                {{-- Disable 2FA modal --}}
                <div class="modal fade" id="disableModal" tabindex="-1" aria-labelledby="disableModalLabel" aria-modal="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold" id="disableModalLabel">Desactivar 2FA</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted mb-3">
                                    Ingresa tu contraseña actual para confirmar la desactivación.
                                    Tu cuenta quedará menos protegida.
                                </p>
                                <div class="mb-3">
                                    <label for="disable-password" class="form-label">Contraseña</label>
                                    <input type="password"
                                           id="disable-password"
                                           class="form-control"
                                           placeholder="Tu contraseña actual"
                                           autocomplete="current-password">
                                    <div class="invalid-feedback" id="disable-password-error"></div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0 flex-column gap-2">
                                <button type="button" id="btn-disable" class="btn btn-brand w-100">
                                    Desactivar 2FA
                                </button>
                                <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            @else
                {{-- Setup wizard --}}
                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Configurar autenticación de dos factores</h5>
                        <small class="text-muted">Protege tu cuenta con un segundo paso de verificación</small>
                    </div>
                    <div class="card-body">

                        {{-- Stepper --}}
                        <div class="d-flex align-items-center justify-content-center mb-4 gap-0">
                            <div class="d-flex flex-column align-items-center">
                                <div class="step-circle active" id="circle-1">1</div>
                                <small class="text-muted mt-1 text-center bv-fs-75">Configurar app</small>
                            </div>
                            <div class="step-line flex-grow-1 mx-2" id="line-1-2"></div>
                            <div class="d-flex flex-column align-items-center">
                                <div class="step-circle" id="circle-2">2</div>
                                <small class="text-muted mt-1 text-center bv-fs-75">Escanear QR</small>
                            </div>
                            <div class="step-line flex-grow-1 mx-2" id="line-2-3"></div>
                            <div class="d-flex flex-column align-items-center">
                                <div class="step-circle" id="circle-3">3</div>
                                <small class="text-muted mt-1 text-center bv-fs-75">Códigos de respaldo</small>
                            </div>
                        </div>

                        {{-- Step 1: Configure app --}}
                        <div id="step-1">
                            <h6 class="fw-bold mb-3">Paso 1: Instala una aplicación autenticadora</h6>
                            <p class="text-muted mb-3">
                                Necesitas una aplicación autenticadora en tu teléfono para generar
                                los códigos de verificación de 6 dígitos.
                            </p>
                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <div class="border rounded p-3 text-center h-100">
                                        <i class="fas fa-mobile-alt text-muted fs-3 mb-2 d-block"></i>
                                        <div class="fw-semibold small">Google Authenticator</div>
                                        <small class="text-muted">iOS y Android</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-3 text-center h-100">
                                        <i class="fas fa-lock text-muted fs-3 mb-2 d-block"></i>
                                        <div class="fw-semibold small">Authy</div>
                                        <small class="text-muted">iOS, Android y escritorio</small>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="btn-generate-qr" class="btn btn-primary w-100 mb-2">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="generate-spinner"></span>
                                Generar QR
                            </button>
                        </div>

                        {{-- Step 2: Scan QR --}}
                        <div id="step-2" class="d-none">
                            <h6 class="fw-bold mb-3">Paso 2: Escanea el código QR</h6>
                            <p class="text-muted mb-3">
                                Abre tu aplicación autenticadora y escanea el siguiente código QR.
                                Luego ingresa el código de 6 dígitos que aparece en la app.
                            </p>

                            <div class="text-center mb-3">
                                <div id="qr-image-wrapper" class="d-inline-block border rounded p-3 bg-white">
                                    <img id="qr-image" src="" alt="QR Code 2FA" width="200" height="200" loading="lazy">
                                </div>
                            </div>

                            <div class="bg-light rounded p-3 mb-3">
                                <label class="form-label fw-semibold small mb-1">Clave manual (si no puedes escanear)</label>
                                <div class="d-flex align-items-center gap-2">
                                    <code id="secret-text" class="flex-grow-1 text-break small"></code>
                                    <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" id="btn-copy-secret" title="Copiar clave">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="totp-code" class="form-label fw-semibold">Código de verificación</label>
                                <input type="text"
                                       id="totp-code"
                                       class="form-control form-control-lg text-center"
                                       placeholder="000000"
                                       maxlength="6"
                                       inputmode="numeric"
                                       autocomplete="one-time-code"
                                       pattern="[0-9]{6}">
                                <div class="invalid-feedback" id="totp-error"></div>
                            </div>

                            <button type="button" id="btn-verify-code" class="btn btn-primary w-100 mb-2">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="verify-spinner"></span>
                                Verificar código
                            </button>
                            <button type="button" class="btn btn-light w-100" id="btn-back-step1">
                                Volver
                            </button>
                        </div>

                        {{-- Step 3: Recovery codes --}}
                        <div id="step-3" class="d-none">
                            <h6 class="fw-bold mb-3">Paso 3: Guarda tus códigos de recuperación</h6>

                            <div class="alert alert-warning d-flex gap-2" role="alert">
                                <i class="fas fa-exclamation-triangle flex-shrink-0 mt-1"></i>
                                <div>
                                    <strong>Importante:</strong> Guarda estos códigos en un lugar seguro.
                                    Podrás usarlos para acceder a tu cuenta si pierdes tu dispositivo.
                                    No podrás verlos de nuevo.
                                </div>
                            </div>

                            <div class="bg-light border rounded p-3 mb-3">
                                <div id="recovery-codes-list" class="row g-2">
                                    {{-- Filled by JS --}}
                                </div>
                            </div>

                            <button type="button" id="btn-copy-codes" class="btn btn-outline-secondary w-100 mb-3">
                                Copiar todos los códigos
                            </button>

                            <a href="{{ route('manager.helpdesk') }}" class="btn btn-primary w-100">
                                He guardado mis códigos — Finalizar
                            </a>
                        </div>

                    </div>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection

@push('css')
<style>
.step-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: .9rem;
    transition: background .3s, color .3s;
    flex-shrink: 0;
}
.step-circle.active {
    background: #90bb13;
    color: #fff;
}
.step-circle.done {
    background: #13C672;
    color: #fff;
}
.step-line {
    height: 2px;
    background: #e9ecef;
    min-width: 40px;
    transition: background .3s;
}
.step-line.done {
    background: #13C672;
}
</style>
@endpush

@push('scripts')
<script>
window.HdTwoFaSetupConfig = {
    enableUrl: '{{ route("manager.helpdesk.2fa.enable") }}',
    confirmUrl: '{{ route("manager.helpdesk.2fa.confirm") }}',
    disableUrl: '{{ route("manager.helpdesk.2fa.disable") }}',
};
</script>
<script src="{{ asset('vendor/helpdesk/misc/twofa-setup.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/misc/twofa-setup.js')) }}" defer></script>
@endpush
