@extends('layouts.auth')

@section('title', 'Ingresar')

@section('content')
    <div class="container">
        <div class="row justify-content-center align-items-center vh-100">
            <div class="col-xxl-5 col-xl-6 col-lg-7 col-md-8 col-sm-10">
                <div class="card p-2 p-sm-2">
                    <div class="card-body text-center">
                        <div class="card-title mb-4">
                            <h3 class="mb-2">Ingrese a su cuenta</h3>
                            <p class="mb-0">¡Bienvenido de nuevo! Por favor ingrese sus datos. <a href="{{ route('register') }}">¿No tienes una cuenta?</a></p>
                        </div>
                        <form id="formLogin" enctype="multipart/form-data" role="form" onSubmit="return false">
                            @csrf
                            <div class="mb-3 input-group-auth ">
                                <input id="email" type="text" name="email" placeholder="Correo electrónico" class="form-control form-control-lg">
                                <label id="email-error" class="error d-none" for="email"></label>
                            </div>
                            <div class="mb-3 input-group-auth ">
                                <div class="position-relative">
                                    <input class="form-control form-control-lg" id="password" type="password" name="password" placeholder="Ingresar contraseña">
                                    <span class="d-flex position-absolute top-50 end-0 translate-middle-y p-0 pe-2 me-2 translate-eye">
                                    <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 576 512" class="cursor-pointer" height="18" width="18" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M572.52 241.4C518.29 135.59 410.93 64 288 64S57.68 135.64 3.48 241.41a32.35 32.35 0 0 0 0 29.19C57.71 376.41 165.07 448 288 448s230.32-71.64 284.52-177.41a32.35 32.35 0 0 0 0-29.19zM288 400a144 144 0 1 1 144-144 143.93 143.93 0 0 1-144 144zm0-240a95.31 95.31 0 0 0-25.31 3.79 47.85 47.85 0 0 1-66.9 66.9A95.78 95.78 0 1 0 288 160z"></path>
                                    </svg>
                                    <label id="password-error" class="error d-none" for="password"></label>
                                </span>
                                </div>
                            </div>


                            <div class="col-12 mb-2">
                                <div class="errors d-none">
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Ingresar</button>
                            </div>
                        </form>
                    </div>
                </div>
                <p class="mb-0 mt-3 text-center">&copy; 2024 <a href="{{ route('index') }}" target="_blank">Sitio</a>. Todos los derechos reservados</p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {


            $(".position-relative span").on("click", function () {
                const input = $(this).siblings("input"); // Obtener el campo de entrada asociado
                const currentType = input.attr("type"); // Obtener el tipo actual del input

                // Alternar entre 'password' y 'text'
                const newType = currentType === "password" ? "text" : "password";
                input.attr("type", newType);

                // Cambiar el icono del ojo (opcional)
                const svg = $(this).find("svg");
                if (newType === "text") {
                    svg.attr("fill", "gray"); // Cambiar el color del ícono al mostrar contraseña
                } else {
                    svg.attr("fill", "currentColor"); // Restaurar el color al ocultar contraseña
                }
            });


            jQuery.validator.addMethod('emailExt', function(value, element, param) {
                return value.match(/^(([^<>()[\]\.,;:\s@"]+(\.[^<>()[\]\.,;:\s@"]+)*)|(".+"))@(([^<>()[\]\.,;:\s@"]+\.)+[^<>()[\]\.,;:\s@"]{2,})$/i);
            }, 'Por favor, ingrese un email válido.');

            $("#formLogin").validate({
                ignore: ".ignore",
                rules: {
                    email: {
                        required: true,
                        email: true,
                        emailExt: true,
                    },
                    password: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                    },
                },
                messages: {
                    email: {
                        required: 'El campo de correo electrónico es necesario.',
                        email: 'Por favor, introduce una dirección de correo electrónico válida.',
                    },
                    password: {
                        required: 'El campo de contraseña es necesario.',
                        minlength: 'Debe contener al menos 3 caracteres.',
                        maxlength: 'Debe contener como máximo 100 caracteres.',
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formLogin');
                    var formData = new FormData($form[0]);
                    var email = $("#email").val();
                    var password = $("#password").val();
                    var remember = $("#remember").prop("checked");

                    formData.append('email', email);
                    formData.append('password', password);
                    formData.append('remember', remember);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('auth.login') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(response) {
                            if(response.success == true){
                                window.location.href = response.redirect;
                            }else{
                                $submitButton.prop('disabled', false);
                                error = response.message;
                                $('.errors').text(error);
                                $('.errors').removeClass('d-none');
                            }
                        }
                    });
                }
            });
        });


    </script>
@endpush
