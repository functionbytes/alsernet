@extends('layouts.shops')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formSubcriptions" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}


                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">

                            <h5 class="mb-0">Crear suscripcion
                            </h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Nombres</label>
                                    <input type="text" class="form-control" id="firstname"  name="firstname" value="" placeholder="Ingresar nombres"  autocomplete="new-password">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Apellidos</label>
                                    <input type="text" class="form-control" id="lastname"  name="lastname" value="" placeholder="Ingresar apellidos" autocomplete="new-password">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Correo electronico</label>
                                    <input type="text" class="form-control" id="email"  name="email" value="" placeholder="Ingresar el correo electronico" autocomplete="new-password">
                                </div>
                            </div>


                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="prioritie" class="control-label col-form-label">Idioma</label>
                                    <select class="form-control select2" id="lang" name="lang">
                                        @foreach($langs as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>


                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Recibir erp</label>
                                    <select class="form-control select2" id="erp" name="erp">
                                        <option value="1" >Si</option>
                                        <option value="0" >No</option>
                                    </select>
                                </div>
                            </div>



                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Recibir lopd</label>
                                    <select class="form-control select2" id="lopd" name="lopd">
                                        <option value="1" >Si</option>
                                        <option value="0" >No</option>
                                    </select>
                                </div>
                            </div>




                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Recibir none</label>
                                    <select class="form-control select2" id="none" name="none">
                                        <option value="1" >Si</option>
                                        <option value="0" >No</option>
                                    </select>
                                </div>
                            </div>



                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Recibir sports</label>
                                    <select class="form-control select2" id="sports" name="sports">
                                        <option value="1" >Si</option>
                                        <option value="0" >No</option>
                                    </select>
                                </div>
                            </div>



                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Recibir parties</label>
                                    <select class="form-control select2" id="parties" name="parties">
                                        <option value="1">Si</option>
                                        <option value="0" >No</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Recibir suscribe</label>
                                    <select class="form-control select2" id="suscribe" name="suscribe">
                                        <option value="1" >Si</option>
                                        <option value="0" >No</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="users" class="control-label col-form-label">Categorias</label>
                                    <select class="form-control select2" id="categories" name="categories[]" multiple="multiple">
                                        @foreach($categories as $id => $name)
                                            <option value="{{ $id }}" >{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <label id="categories-error" class="error d-none" for="users"></label>
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


            $("#formSubcriptions").validate({
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
                    email: {
                        required: true,
                        email: true,
                        emailExt: true,
                    },
                    erp: {
                        required: true,
                    },
                    lopd: {
                        required: true,
                    },
                    none: {
                        required: true,
                    },
                    sports: {
                        required: true,
                    },
                    parties: {
                        required: true,
                    },
                    suscribe: {
                        required: true,
                    },
                    lang: {
                        required: true,
                    },
                    "categories[]": {
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
                    email: {
                        required: 'Tu email ingresar correo electrónico es necesario.',
                        email: 'Por favor, introduce una dirección de correo electrónico válida.',
                    },
                    erp: {
                        required: "El parametro es necesario.",
                    },
                    lopd: {
                        required: "El parametro es necesario.",
                    },
                    none: {
                        required: "El parametro es necesario.",
                    },
                    sports: {
                        required: "El parametro es necesario.",
                    },
                    parties: {
                        required: "El parametro es necesario.",
                    },
                    suscribe: {
                        required: "El parametro es necesario.",
                    },
                    check: {
                        required: "El parametro es necesario.",
                    },
                    lang: {
                        required: "El parametro es necesario.",
                    },
                    "categories[]": {
                        required: "El parametro es necesario.",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formSubcriptions');
                    var formData = new FormData($form[0]);
                    var uid = $("#uid").val();
                    var firstname = $("#firstname").val();
                    var lastname = $("#lastname").val();
                    var email = $("#email").val();
                    var erp = $("#erp").val();
                    var lopd = $("#lopd").val();
                    var none = $("#none").val();
                    var sports = $("#sports").val();
                    var parties = $("#parties").val();
                    var suscribe = $("#suscribe").val();
                    var categories = $("#categories").val();
                    var check = $("#check").val();
                    var lang = $("#lang").val();
                    var checkat = $("#check_at").val();

                    formData.append('uid', uid);
                    formData.append('firstname', firstname);
                    formData.append('lastname', lastname);
                    formData.append('email', email);
                    formData.append('erp', erp);
                    formData.append('lopd', lopd);
                    formData.append('none', none);
                    formData.append('sports', sports);
                    formData.append('parties', parties);
                    formData.append('suscribe', suscribe);
                    formData.append('lang', lang);
                    formData.append('check', check);
                    formData.append('checkat', checkat);
                    formData.append('categories', categories);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('shop.subscribers.update') }}",
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
                                    window.location.href = "{{ route('shop.subscribers') }}";
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



