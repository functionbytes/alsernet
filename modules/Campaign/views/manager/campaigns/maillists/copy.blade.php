@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formMaillists" enctype="multipart/form-data" role="form" onSubmit="return false">


                    <input type="hidden" id="uid" name="uid" value="{{$list->uid}}">

                    {{ csrf_field() }}


                    <div class="card-body">

                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Editar lista</h5>
                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Titulo</label>
                                    <input type="text" class="form-control" id="title" value="{{ $list->title }}" placeholder="Ingresar apellidos" autocomplete="new-password">
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Asunto</label>
                                    <input type="text" class="form-control" id="default_subject"   value="{{ $list->default_subject }}" placeholder="Ingresar el asunto" autocomplete="new-password">
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Correo electronico</label>
                                    <input type="text" class="form-control" id="from_email"  value="{{ $list->from_email }}" placeholder="Ingresar correo electronico"  autocomplete="new-password">
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Titulo correo</label>
                                    <input type="text" class="form-control" id="from_name"  value="{{ $list->from_name }}" placeholder="Ingresar titulo correo electronico" autocomplete="new-password">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Ajustes lista</h5>
                        </div>
                        <p class="card-subtitle mb-3 mt-0">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura.
                        </p>

                        <div class="col-12 ">
                            <div class="mb-3">
                                <div class="form-check form-switch d-flex m-0 p-0">
                                    <div class="col-10">
                                        <label class="form-check-label" for="send_welcome_email">
                                            <h5 class="checkbox-description mt-1">Enviar un correo electrónico final de bienvenida</h5>
                                            <span class="checkbox-description mt-1">
                                                Cuando las personas se suscriban a tu lista, envíales un correo electrónico de bienvenida. El correo electrónico de bienvenida final se puede editar en la sección Lista -> Gestión de formularios/páginas
                                            </span>
                                        </label>
                                    </div>
                                    <div class="col-2 d-flex justify-content-center align-items-center">
                                        <input class="form-check-input" type="checkbox" id="send_welcome_email"  {{ $list->send_welcome_email ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 mt-3">
                            <div class="mb-3">
                                <div class="form-check form-switch d-flex m-0 p-0">
                                    <div class="col-10">
                                        <label class="form-check-label" for="subscribe_confirmation">
                                            <h5 class="checkbox-description mt-1">Enviar correo electrónico de confirmación de suscripción (doble opt-in)</h5>
                                            <span class="checkbox-description mt-1">
                                                Cuando las personas se suscriban a tu lista, envíales un correo electrónico de confirmación de suscripción.
                                            </span>
                                        </label>
                                    </div>
                                    <div class="col-2 d-flex justify-content-center align-items-center">
                                        <input class="form-check-input" type="checkbox" id="subscribe_confirmation"  {{ $list->subscribe_confirmation ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="col-12 mt-3">
                            <div class="mb-3">
                                <div class="form-check form-switch d-flex m-0 p-0">
                                    <div class="col-10">
                                        <label class="form-check-label" for="unsubscribe_notification">
                                            <h5 class="checkbox-description mt-1">Enviar notificación de cancelación de suscripción a los suscriptores</h5>
                                            <span class="checkbox-description mt-1">
                                                Envíe a sus suscriptores un último correo electrónico de “Adiós” para informarles que se han dado de baja.
                                            </span>
                                        </label>
                                    </div>
                                    <div class="col-2 d-flex justify-content-center align-items-center">
                                        <input class="form-check-input" type="checkbox" id="unsubscribe_notification"  {{ $list->unsubscribe_notification ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>
                        </div>




                        <div class="col-12">
                            <div class="errors d-none">
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="border-top pt-1 mt-4">
                                <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                    Guardar
                                </button>
                            </div>
                        </div>
                    </div>
            </div>
            </form>
        </div>

    </div>

    </div>

@endsection



@push('scripts')

    <script type="text/javascript">
        Dropzone.autoDiscover = false;

        $(document).ready(function() {

            jQuery.validator.addMethod(
                'emailExt',
                function (value, element, param) {
                    return value.match(
                        /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i,
                    )
                },
                'Porfavor ingrese email valido',
            );


            $("#formMaillists").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    title: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                    },
                    default_subject: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                    },
                    from_name: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                    },
                    from_email: {
                        required: true,
                        email: true,
                        emailExt: true,
                    }

                },
                messages: {
                    title: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    default_subject: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    from_name: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    from_email: {
                        required: 'Tu email ingresar correo electrónico es necesario.',
                        email: 'Por favor, introduce una dirección de correo electrónico válida.',
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formMaillists');
                    var formData = new FormData($form[0]);

                    var uid = $("#uid").val();
                    var title = $("#title").val();
                    var default_subject = $("#default_subject").val();
                    var from_email = $("#from_email").val();
                    var from_name = $("#from_name").val();
                    var send_welcome_email = $("#send_welcome_email").is(':checked') == true ? 1 : 0;
                    var subscribe_confirmation = $("#subscribe_confirmation").is(':checked') == true ? 1 : 0;
                    var unsubscribe_notification = $("#unsubscribe_notification").is(':checked') == true ? 1 : 0;

                    formData.append('uid', uid);
                    formData.append('title', title);
                    formData.append('default_subject', default_subject);
                    formData.append('from_email', from_email);
                    formData.append('from_name', from_name)
                    formData.append('send_welcome_email', send_welcome_email);
                    formData.append('subscribe_confirmation', subscribe_confirmation);
                    formData.append('unsubscribe_notification', unsubscribe_notification);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('manager.maillists.update' , $list->uid) }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(response) {

                            if(response.success == true){

                                message = response.message;

                                toastr.success(message, "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                setTimeout(function() {
                                    window.location.href = "{{ route('manager.maillists') }}";
                                }, 2000);

                            }else{

                                $submitButton.prop('disabled', false);
                                error = response.message;

                                toastr.warning(error, "Operación fallida", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

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



