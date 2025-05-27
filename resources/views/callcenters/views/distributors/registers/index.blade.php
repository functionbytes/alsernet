@extends('layouts.callcenters')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formRegisters" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input id="distributor" name="distributor" type="hidden" value="{{ $distributor->uid }}">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0"> Registro</h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera
                            eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que
                            corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier
                            información que consideres necesario actualizar para mantener tus datos al día.
                        </p>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Empresa</label>
                                    <div class="input-group">
                                        {!! Form::select('enterprise', $enterprises, null , ['class' => 'select2 form-control' ,'name' => 'enterprise', 'id' => 'enterprise']) !!}
                                    </div>
                                    <label id="enterprise-error" class="error d-none" for="enterprise"></label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Nombres</label>
                                    <input type="text" class="form-control" id="firstname"  name="firstname" value="" autocomplete="new-password"placeholder="Ingresar nombres">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Apellidos</label>
                                    <input type="text" class="form-control" id="lastname"  name="lastname" value="" autocomplete="new-password"placeholder="Ingresar apellido">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Identificación</label>
                                    <input type="text" class="form-control" id="identification"  name="identification" value="" autocomplete="new-password" placeholder="Ingresar identificación">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Dirección</label>
                                    <input type="text" class="form-control" id="address"  name="address" value="" autocomplete="new-password" placeholder="Ingresar dirección">
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Correo electronico</label>
                                    <input type="text" class="form-control" id="email"  name="email" value="" autocomplete="new-password" placeholder="Ingresar correo electronico">
                                </div>
                            </div>


                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Celular</label>
                                    <input type="text" class="form-control" id="cellphone"  name="cellphone" value="" autocomplete="new-password"placeholder="Ingresar celular">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="password"  name="password" value="" autocomplete="new-password"placeholder="Ingresar contraseña">
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

    <div id="users-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="display-4 text-danger"><i data-feather="x-octagon"></i></div>
                    <h4 class="my-0">Confirmacion de registro</h4>
                    <p>Deseas crear un nuevo usuario</p>
                    <div class="row justify-content-center mt-20  ">
                        <div class="col-sm-12 col-md-5 enterprise-div d-none">
                            <a href="" id="enterprise-link" class="btn btn-danger w-100">Empresa</a>
                        </div>
                        <div class="col-sm-12 col-md-5">
                            <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal">Seguir creando</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div id="message-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="display-4 text-danger"><i data-feather="x-octagon"></i></div>
                    <h4 class="my-0 modal-content-message">¿Deseas seguir creando usuarios o retornar a la empresa?</h4>
                    <p class="modal-content-description"></p>
                    <div class="row justify-content-center mt-20  ">
                        <div class="col-sm-12 col-md-5 reassign-div d-none w-100 mt-1">
                            <a href="" id="reassign-links" class="btn btn-danger w-100">Reasignar</a>
                        </div>
                        <div class="col-sm-12 col-md-5 enterprise-div d-none w-100 mt-1 ">
                            <a href="" id="enterprise-links" class="btn btn-danger w-100">Empresa</a>
                        </div>
                        <div class="col-sm-12 col-md-5 w-100 mt-1">
                            <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @endsection




    @push('scripts')

    <script type="text/javascript">
    Dropzone.autoDiscover = false;

    $(document).ready(function() {


        $("#enterprise").select2({
                minimumResultsForSearch: 0
        });

        $('#identification').on('blur', function () {
            let identification = $(this).val();

            $.ajax({
                url: '{{ route("support.distributors.registers.users.check") }}',
                method: 'POST',
                data: {
                    identification: identification,
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.success) {

                        if (response.url_reassign && response.url_enterprise) {
                            $('.reassign-div').removeClass('d-none');
                            $('.enterprise-div').removeClass('d-none');
                            $('#reassign-links').attr('href', response.url_reassign);
                            $('#enterprise-links').attr('href', response.url_enterprise);
                        }

                        $('#message-modal .modal-content-message').html(response.message);
                        $('#message-modal').modal('show');

                    }
                },
                error: function () {
                    alert('Error al validar la identificación.');
                }
            });
        });


        jQuery.validator.addMethod(
            'emailExt',
            function (value, element, param) {
                return value.match(
                    /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i,
                )
            },
            'Porfavor ingrese email valido',
        );

        $("#formRegisters").validate({
            submit: false,
            ignore: ".ignore",
            rules: {
                firstname: {
                    required: true,
                    minlength: 3,
                    maxlength: 100,
                },
                lastname: {
                    required: true,
                    minlength: 3,
                    maxlength: 100,
                },
                identification: {
                    required: true,
                    number: true,
                    minlength: 3,
                    maxlength: 100,
                },
                cellphone: {
                    required: false,
                    number: true,
                    minlength: 6,
                    maxlength: 10,
                },
                email: {
                    required: true,
                    email: true,
                    emailExt: true,
                },
                address: {
                    required: false,
                    minlength: 3,
                    maxlength: 100,
                },
                password: {
                    required: false,
                    minlength: 3,
                    maxlength: 100,
                },
                enterprise: {
                    required: true,
                },

            },
            messages: {
                firstname: {
                    required: "El parametro es necesario.",
                    minlength: "Debe contener al menos 3 caracter",
                    maxlength: "Debe contener al menos 100 caracter",
                },
                lastname: {
                    required: "El parametro es necesario.",
                    minlength: "Debe contener al menos 3 caracter",
                    maxlength: "Debe contener al menos 100 caracter",
                },
                identification: {
                    required: "El parametro es necesario.",
                    number: 'Solo se puede ingresar números.',
                    minlength: "Debe contener al menos 3 caracter",
                    maxlength: "Debe contener al menos 100 caracter",
                },
                cellphone: {
                    required: "El parametro es necesario.",
                    number: 'Solo se puede ingresar números.',
                    minlength: "Debe contener al menos 6 caracter",
                    maxlength: "Debe contener al menos 10 caracter",
                },
                email: {
                    required: 'Tu email ingresar correo electrónico es necesario.',
                    email: 'Por favor, introduce una dirección de correo electrónico válida.',
                },
                address: {
                    required: "El parametro es necesario.",
                    minlength: "Debe contener al menos 3 caracter",
                    maxlength: "Debe contener al menos 100 caracter",
                },
                password: {
                    required: "El parametro es necesario.",
                    minlength: "Debe contener al menos 6 caracter",
                    maxlength: "Debe contener al menos 10 caracter",
                },
                enterprise: {
                    required: "El parametro es necesario.",
                },
            },
            submitHandler: function(form) {

                var $form = $('#formRegisters');
                var formData = new FormData($form[0]);
                var slack = $("#slack").val();
                var firstname = $("#firstname").val();
                var lastname = $("#lastname").val();
                var identification = $("#identification").val();
                var cellphone = $("#cellphone").val();
                var email = $("#email").val();
                var address = $("#address").val();
                var password = $("#password").val();
                var available = $("#available").val();
                var enterprise = $("#enterprise").val();

                formData.append('slack', slack);
                formData.append('firstname', firstname);
                formData.append('lastname', lastname);
                formData.append('identification', identification);
                formData.append('cellphone', cellphone);
                formData.append('email', email);
                formData.append('address', address);
                formData.append('password', password);
                formData.append('available', available);
                formData.append('enterprise', enterprise);

                $.ajax({
                    url: "{{ route('callcenter.distributors.registers.store') }}",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    type: "POST",
                    contentType: false,
                    processData: false,
                    data: formData,
                    success: function(response) {

                        if(response.success == true){

                            enterprise = response.success;

                            var urlEnterprises =  "{{ route('callcenter.enterprises.users', ':slack') }}".replace(':slack', enterprise);
                            var url =  "{{ route('callcenter.enterprises.users', ':slack') }}".replace(':slack', enterprise);

                            $("#firstname").val('');
                            $("#lastname").val('');
                            $("#identification").val('');
                            $("#cellphone").val('');
                            $("#email").val('');
                            $("#address").val('');
                            $("#password").val('');
                            $("#enterprise").val(0).trigger('change');

                            $("#users-modal").modal("show");
                            $("#users-link").attr("href", urlEnterprises);

                        }else{
                            error = response.message;
                            $('.errors').removeClass('d-none');
                            $('.errors').removeClass('d-none');
                        }
                    }

                });

            }

        });



    });

    </script>

    @endpush


