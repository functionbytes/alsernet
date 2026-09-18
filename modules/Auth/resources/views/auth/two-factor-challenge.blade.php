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
                                <h2 class="s-42 w-700">de seguridad</h2>
                                <p class="p-md mt-25">Ingresa el código de tu aplicación autenticadora para continuar.</p>
                                <div class="register-page-copyright">
                                    <p class="p-sm">&copy; 2025 alvarez. <strong>Reservados todos los derechos</strong></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="register-page-form">

                                {{-- OTP code panel --}}
                                <div id="panel-otp">
                                    <p class="p-sm mb-4 color--white">
                                        <i class="fas fa-mobile-screen me-2"></i>
                                        Abre tu aplicación autenticadora e ingresa el código de 6 dígitos.
                                    </p>

                                    <form id="formOtp" onSubmit="return false">
                                        @csrf

                                        <div class="col-md-12 mb-4">
                                            <p class="p-sm input-header">Código de verificación</p>
                                            <input class="form-control text-center"
                                                   id="otp_code"
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
                                            <button type="button" class="btn btn-link color--white p-0 small" id="btnShowRecovery">
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

                                    <form id="formRecovery" onSubmit="return false">
                                        @csrf

                                        <div class="col-md-12 mb-4">
                                            <p class="p-sm input-header">Código de recuperación</p>
                                            <input class="form-control"
                                                   id="recovery_code"
                                                   type="text"
                                                   name="recovery_code"
                                                   placeholder="XXXXX-XXXXX"
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
                                            <button type="button" class="btn btn-link color--white p-0 small" id="btnShowOtp">
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
$(document).ready(function () {

    // Toggle between OTP and recovery panels
    $('#btnShowRecovery').on('click', function () {
        $('#panel-otp').addClass('d-none');
        $('#panel-recovery').removeClass('d-none');
        $('#recovery_code').focus();
    });

    $('#btnShowOtp').on('click', function () {
        $('#panel-recovery').addClass('d-none');
        $('#panel-otp').removeClass('d-none');
        $('#otp_code').focus();
    });

    // Only allow digits in OTP input
    $('#otp_code').on('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    function submitChallenge(data) {
        const $submitBtns = $('.submit');
        $submitBtns.prop('disabled', true);

        $.ajax({
            url: "{{ route('two-factor.verify') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: data,
            success: function (response) {
                window.location.href = response.redirect;
            },
            error: function (xhr) {
                $submitBtns.prop('disabled', false);
                const message = xhr.responseJSON?.message || 'Código incorrecto. Inténtalo de nuevo.';
                $('.errors, .errors-recovery').text(message).removeClass('d-none');
            }
        });
    }

    $('#formOtp').on('submit', function () {
        const code = $('#otp_code').val().trim();
        if (code.length !== 6) {
            $('.errors').text('El código debe tener 6 dígitos.').removeClass('d-none');
            return;
        }
        $('.errors').addClass('d-none');
        submitChallenge({ code: code });
    });

    $('#formRecovery').on('submit', function () {
        const code = $('#recovery_code').val().trim();
        if (!code) {
            $('.errors-recovery').text('Ingresa un código de recuperación.').removeClass('d-none');
            return;
        }
        $('.errors-recovery').addClass('d-none');
        submitChallenge({ recovery_code: code });
    });
});
</script>
@endpush
