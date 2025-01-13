@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formUsers" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input type="hidden" id="id" name="id" value="{{ $user->id }}">
                    <input type="hidden" id="slack" name="slack" value="{{ $user->slack }}">
                    <input type="hidden" id="edit" name="edit" value="true">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">

                            <h5 class="mb-0">Editar
                                @if ($user->role == 'manager')
                                    administrador
                                @elseif($user->role == 'customer')
                                    cliente
                                @elseif($user->role == 'enterprises')
                                    empresa
                                @endif
                            </h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>

                        <div class="row">

                            <div class="col-6">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Nombres</label>
                                        <input type="text" class="form-control" id="firstname"  name="firstname" value="{{ $user->firstname }}" placeholder="Ingresar nombres">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Apellidos</label>
                                        <input type="text" class="form-control" id="lastname"  name="lastname" value="{{ $user->lastname }}" placeholder="Ingresar apellido">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Correo electronico</label>
                                        <input type="text" class="form-control" id="email"  name="email" value="{{ $user->email }}" placeholder="Ingresar correo electronico">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Contraseña</label>
                                        <input type="password" class="form-control" id="password"  name="password" value="" placeholder="Ingresar contraseña">
                                </div>
                            </div>
                            <div class="col-6 divEnterprise {{ $user->getRole()->id == 2  ? '' : 'd-none' }}">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Tienda</label>
                                    <div class="input-group">
                                        {!! Form::select('shops', $shops, $user->shop_id, ['class' => 'select2 form-control','id' => 'shops']) !!}
                                    </div>
                                    <label id="shops-error" class="error d-none" for="shops"></label>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <div class="input-group">
                                        {!! Form::select('available', $availables, $user->available , ['class' => 'select2 form-control','id' => 'available']) !!}
                                    </div>
                                    <label id="available-error" class="error d-none" for="available"></label>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Perfil</label>
                                    <div class="input-group">
                                            {!! Form::select('role', $roles, $user->getRole()->id , ['class' => 'select2 form-control','id' => 'roles']) !!}
                                    </div>
                                    <label id="role-error" class="error d-none" for="role"></label>
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

            $('#roles').change(function(e) {

                e.preventDefault();
                var role = $(this).val();

                if (role == 2) {
                    $('.divEnterprise').removeClass("d-none");
                }  else {
                    $('.divEnterprise').addClass("d-none");
                }

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

            $("#formUsers").validate({
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
                    available: {
                        required: true,
                    },
                    role: {
                        required: true,
                    },
                    shops: {
                        required: false,
                    },
                    email: {
                        required: true,
                        email: true,
                        emailExt: true,
                    },
                    password: {
                        required: false,
                        minlength: 3,
                        maxlength: 100,
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
                    email: {
                        required: 'Tu email ingresar correo electrónico es necesario.',
                        email: 'Por favor, introduce una dirección de correo electrónico válida.',
                    },
                    password: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 6 caracter",
                        maxlength: "Debe contener al menos 10 caracter",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formUsers');
                    var formData = new FormData($form[0]);
                    var slack = $("#slack").val();
                    var firstname = $("#firstname").val();
                    var lastname = $("#lastname").val();
                    var email = $("#email").val();
                    var password = $("#password").val();
                    var available = $("#available").val();
                    var role = $("#roles").val();
                    var shop = $("#shops").val();

                    formData.append('slack', slack);
                    formData.append('firstname', firstname);
                    formData.append('lastname', lastname);
                    formData.append('email', email);
                    formData.append('password', password);
                    formData.append('available', available);
                    formData.append('role', role);
                    formData.append('shop', shop);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('manager.users.update') }}",
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
                                    window.location.href = "{{ route('manager.users') }}";
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



