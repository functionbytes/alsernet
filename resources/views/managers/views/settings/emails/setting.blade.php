@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formEmails" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}


                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Configuración imap</h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este formulario te permite configurar los detalles de conexión IMAP para tu aplicación Laravel. La configuración IMAP es crucial si deseas interactuar con servidores de correo electrónico para realizar acciones como leer correos electrónicos entrantes, enviar mensajes, etc.
                        </p>

                        <div class="row">

                            <div class="col-12">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">IMAP Host</label>
                                        <input type="text" class="form-control" id="imap_host"  name="imap_host" value="{{ setting('imap_host') }}" placeholder="Ingresar nombres">

                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">IMAP Port </label>
                                        <input type="text" class="form-control" id="imap_port"  name="imap_port" value="{{ setting('imap_port') }}" placeholder="Ingresar nombres">

                                </div>
                            </div> <div class="col-12">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">IMAP Encryption</label>
                                        <input type="text" class="form-control" id="imap_encryption"  name="imap_encryption" value="{{ setting('imap_encryption') }}" placeholder="Ingresar nombres">

                                </div>
                            </div> 
                            <div class="col-12">
                                    <div class="mb-3">
                                        <label  class="control-label col-form-label">IMAP Protocol</label>
                                        <input type="text" class="form-control" id="imap_protocol"  name="imap_protocol" value="{{ setting('imap_protocol') }}" placeholder="Ingresar nombres">
                                    </div>
                            </div>
                            <div class="col-12">
                                    <div class="mb-3">
                                        <label  class="control-label col-form-label">IMAP Username</label>
                                        <input type="text" class="form-control" id="imap_username"  name="imap_username" value="{{ setting('imap_username') }}" placeholder="Ingresar nombres">
                                    </div>
                            </div>
                            <div class="col-12">
                                    <div class="mb-3">
                                        <label  class="control-label col-form-label">IMAP Password </label>
                                        <input type="text" class="form-control" id="imap_password"  name="imap_password" value="{{ setting('imap_password') }}" placeholder="Ingresar nombres">
                                    </div>
                            </div>

                        </div>

                    </div>

                        <div class="card-body border-top">
                                <div class="row align-items-center">
                                    <div class=" col-sm-11 ">
                                        <label  class="control-label col-form-label ">Enviar correo electrónico Activar/Desactivar</label>
                                        <p class="card-subtitle mb-3 mt-0">Se activara el uso de correos referente a tickets</p>
                                    </div>
                                    <div class="col-sm-1 justify-content-end d-flex align-items">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="imap_status" id="imap_status"   @if(setting('imap_status')=='true' ) checked @endif/>
                                        </div>

                                    </div>
                            </div>
                        </div>

                    <div class="card-body border-top">
                        <div class="mb-4 row align-items-center">

                        <div class="col-12">
                            <div class="action-form">
                                <div class="text-center">
                                    <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                        Guardar
                                    </button>
                                </div>
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


            $("#formEmails").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    imap_host: {
                        required: true,
                        minlength: 1,
                        maxlength: 100,
                    },
                    imap_port: {
                        required: true,
                        minlength: 1,
                        maxlength: 100,
                    },
                    imap_protocol: {
                        required: true,
                        minlength: 1,
                        maxlength: 100,
                    },
                    imap_username: {
                        required: true,
                        minlength: 1,
                        maxlength: 100,
                    },
                    imap_password: {
                        required: true,
                        minlength: 1,
                        maxlength: 100,
                    },
                    imap_encryption: {
                        required: true,
                        minlength: 1,
                        maxlength: 100,
                    },
                    
                },
                messages: {
                    imap_host: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    imap_port: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    imap_protocol: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    imap_username: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    imap_password: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    imap_encryption: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formEmails');
                    var formData = new FormData($form[0]);
                    var imap_port = $("#imap_port").val();
                    var imap_protocol = $("#imap_protocol").val();
                    var imap_host = $("#imap_host").val();
                    var imap_status = $("#imap_status").is(':checked');
                    var imap_username = $("#imap_username").val();
                    var imap_password = $("#imap_password").val();
                    var imap_encryption = $("#imap_encryption").val();

                    formData.append('imap_port', imap_port);
                    formData.append('imap_host', imap_host);
                    formData.append('imap_protocol', imap_protocol);
                    formData.append('imap_status', imap_status);
                    formData.append('imap_username', imap_username);
                    formData.append('imap_password', imap_password);
                    formData.append('imap_encryption', imap_encryption);

                    $.ajax({
                        url: "{{ route('manager.settings.emails.update') }}",
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
                                        window.location = "{{ route('manager.dashboard') }}";
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



