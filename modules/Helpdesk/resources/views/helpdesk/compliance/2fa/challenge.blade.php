@extends('layouts.auth')

@section('title', 'Verificación en dos pasos')

@section('content')

<div id="login" class="bg--scroll login-section division">
    <div class="container">
        <div class="row justify-content-center">

            <div class="col-lg-11">
                <div class="register-page-wrapper r-16 bg--fixed">
                    <div class="row">

                        <div class="col-md-6">
                            <div class="register-page-txt color--white">
                                <h2 class="s-42 w-700">Verificación</h2>
                                <h2 class="s-42 w-700">en dos pasos</h2>
                                <p class="p-md mt-25">
                                    Ingresa el código de 6 dígitos de tu aplicación autenticadora
                                    o uno de tus códigos de recuperación.
                                </p>
                                <div class="register-page-copyright">
                                    <p class="p-sm">&copy; {{ date('Y') }} {{ config('app.name') }}. <strong>Reservados todos los derechos</strong></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="register-page-form">

                                {{-- TOTP panel --}}
                                <div id="panel-otp">
                                    <p class="p-sm mb-4 color--white">
                                        <i class="fas fa-mobile-screen me-2"></i>
                                        Abre tu aplicación autenticadora e ingresa el código de 6 dígitos.
                                    </p>

                                    <form id="form-otp" onsubmit="return false">
                                        @csrf

                                        <div class="col-md-12 mb-4">
                                            <p class="p-sm input-header">Código de verificación</p>
                                            <input class="form-control text-center"
                                                   id="otp-code"
                                                   type="text"
                                                   name="code"
                                                   maxlength="6"
                                                   inputmode="numeric"
                                                   autocomplete="one-time-code"
                                                   placeholder="000000"
                                                   autofocus>
                                        </div>

                                        <div class="col-12 mb-2">
                                            <div class="errors d-none"></div>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <button type="submit" class="btn btn--theme hover--theme submit w-100">
                                                Verificar
                                            </button>
                                        </div>

                                        <div class="col-md-12 text-center">
                                            <button type="button" class="btn btn-link color--white p-0 small" id="btn-show-recovery">
                                                ¿No tienes acceso al código? Usa un código de recuperación
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                {{-- Recovery code panel --}}
                                <div id="panel-recovery" class="d-none">
                                    <p class="p-sm mb-4 color--white">
                                        <i class="fas fa-key me-2"></i>
                                        Ingresa uno de tus códigos de recuperación de emergencia.
                                    </p>

                                    <form id="form-recovery" onsubmit="return false">
                                        @csrf

                                        <div class="col-md-12 mb-4">
                                            <p class="p-sm input-header">Código de recuperación</p>
                                            <input class="form-control"
                                                   id="recovery-code"
                                                   type="text"
                                                   name="code"
                                                   placeholder="XXXX-XXXX"
                                                   autocomplete="off">
                                        </div>

                                        <div class="col-12 mb-2">
                                            <div class="errors-recovery d-none"></div>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <button type="submit" class="btn btn--theme hover--theme submit w-100">
                                                Usar código de recuperación
                                            </button>
                                        </div>

                                        <div class="col-md-12 text-center">
                                            <button type="button" class="btn btn-link color--white p-0 small" id="btn-show-otp">
                                                Volver al código de autenticación
                                            </button>
                                        </div>
                                    </form>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
window.HdTwoFaChallengeConfig = {
    verifyUrl: '{{ route("manager.helpdesk.2fa.verify") }}',
    fallbackRedirectUrl: '{{ route("manager.helpdesk") }}',
};
</script>
<script src="{{ asset('vendor/helpdesk/misc/twofa-challenge.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/misc/twofa-challenge.js')) }}" defer></script>
@endpush
