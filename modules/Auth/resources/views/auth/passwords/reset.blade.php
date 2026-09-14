
@extends('layouts.auth')

@section('title', 'Ingresar')

@section('content')
    <section class="account pt-150 padding-bottom">
        <div class="px-3">
            <div class="account__wrapper aos-init aos-animate" data-aos="fade-up" data-aos-duration="800">
                <div class="row g-4">
                    <div class="col-lg-12">
                        <div class="account__content">
                            <!-- account tittle -->
                            <div class="account__header pb-30">
                                <h3  class="pb-5">¿Olvidaste tu contraseña?</h3>
                                <p>Ingrese la dirección de correo electrónico asociada con su cuenta y le enviaremos un enlace por correo electrónico para restablecer su contraseña.</p>
                            </div>
                            <!-- account form -->

                            <form action="{{ route('auth.password.update') }}" method="POST" class="account__form" id="formPassword">

                            <input type="hidden" name="token" value="{{ $token }}">
                            <input type="hidden" name="email" value="{{ $email }}">
                            @csrf
                            <div class="row g-4">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <input class="form-control"  type="text" disabled  value="{{ $email }}" placeholder="Correo electrónico o cedula" >
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="input-group">
                                        <input class="form-control" type="password" autocomplete="new-password" name="password" id="password" placeholder="Contraseña (mínimo 8 caracteres)">
                                    </div>
                                    <div class="mt-2" id="password-strength-wrapper" class="d-none">
                                        <div class="progress" style="height:5px">
                                            <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width:0%"></div>
                                        </div>
                                        <small id="password-strength-text" class="text-muted mt-1 d-block"></small>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="input-group">
                                        <input class="form-control" type="password" autocomplete="new-password" name="password_confirmation" id="password_confirmation" placeholder="Repetir contraseña" >
                                    </div>
                                </div>
                            </div>

                            @if ($errors->has('password'))
                                <div class="notification errors closeable">
                                    <p>{{ $errors->first('password') }}</p>
                                    <a class="close"></a>
                                </div>
                            @endif



                            <button type="submit" class="theme-btn w-100 mt-30">Cambiar contraseña</button>

                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection


@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {

            // Password strength meter
            function passwordStrength(pw) {
                let score = 0;
                if (pw.length >= 8)  { score++; }
                if (pw.length >= 12) { score++; }
                if (/[A-Z]/.test(pw))  { score++; }
                if (/[0-9]/.test(pw))  { score++; }
                if (/[^A-Za-z0-9]/.test(pw)) { score++; }
                return score;
            }
            const strengthLabels = ['', 'Muy débil', 'Débil', 'Regular', 'Fuerte', 'Muy fuerte'];
            const strengthClasses = ['', 'bg-danger', 'bg-warning', 'bg-info', 'bg-primary', 'bg-success'];

            $('#password').on('input', function () {
                const val = $(this).val();
                const $wrapper = $('#password-strength-wrapper');
                if (!val) { $wrapper.hide(); return; }
                $wrapper.show();
                const score = passwordStrength(val);
                $('#password-strength-bar')
                    .css('width', (score / 5 * 100) + '%')
                    .removeClass('bg-danger bg-warning bg-info bg-primary bg-success')
                    .addClass(strengthClasses[score] || 'bg-danger');
                $('#password-strength-text').text(strengthLabels[score] || '');
            });

            $("#formPassword").validate({
                submit: true,
                rules: {
                    password: {
                        required: true,
                        minlength: 8
                    },
                    password_confirmation: {
                        required: true,
                        minlength: 8,
                        equalTo: "#password"
                    },
                },
                messages: {
                    password: {
                        required: "La contraseña es obligatoria.",
                        minlength: "Debe contener al menos 8 caracteres.",
                    },
                    password_confirmation: {
                        required: "La confirmación es obligatoria.",
                        minlength: "Debe contener al menos 8 caracteres.",
                        equalTo: "Las contraseñas no coinciden."
                    },
                }
            });

        });
    </script>

@endpush



