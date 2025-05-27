@extends('layouts.shops')

@php
    use Carbon\Carbon;
@endphp

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formSubcriptions" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input type="hidden" id="id" name="id" value="{{ $subscriber->id }}">
                    <input type="hidden" id="uid" name="uid" value="{{ $subscriber->uid }}">
                    <input type="hidden" id="edit" name="edit" value="true">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">

                            <h5 class="mb-0">Editar suscripcion
                            </h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Nombres</label>
                                    <input type="text" class="form-control" id="firstname"   value="{{ $subscriber->firstname }}" placeholder="Ingresar nombres"  autocomplete="new-password">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Apellidos</label>
                                    <input type="text" class="form-control" id="lastname"   value="{{ $subscriber->lastname }}" placeholder="Ingresar apellidos" autocomplete="new-password">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Correo electronico</label>
                                    <input type="text" class="form-control"   value="{{ $subscriber->email }}" placeholder="Ingresar el correo electronico" autocomplete="new-password" disabled>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="categories" class="control-label col-form-label">Categorias</label>
                                    <select class="form-control select2" id="categories" multiple="multiple">
                                        @foreach($categories as $id => $name)
                                            <option value="{{ $id }}" {{ in_array($id, $subscriber->categories->pluck('id')->toArray()) ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <label id="categories-error" class="error d-none" for="categories"></label>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="prioritie" class="control-label col-form-label">Idioma</label>
                                    <select class="form-control select2" id="lang" >
                                        @foreach($langs as $id => $name)
                                            <option value="{{ $id }}" {{  $subscriber->lang_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Recibir notificacion comercial</label>
                                    <select class="form-control select2" id="commercial" >
                                        <option value="1" {{ $subscriber->commercial == 1 ? 'selected' : '' }}>Si</option>
                                        <option value="0" {{ $subscriber->commercial == 0 ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Recibir parties</label>
                                    <select class="form-control select2" id="parties" >
                                        <option value="1" {{ $subscriber->parties == 1 ? 'selected' : '' }}>Si</option>
                                        <option value="0" {{ $subscriber->parties == 0 ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                            </div>

                            @if($subscriber->check_at)
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="control-label col-form-label">Fecha verificación</label>
                                        <input type="text" class="form-control" id="check_at"
                                               value="{{ Carbon::parse($subscriber->check_at)->format('d/m/Y H:i') }}"
                                               placeholder="Fecha no disponible" autocomplete="off" readonly>
                                    </div>
                                </div>
                            @endif

                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Observacion</label>
                                    <input type="text" class="form-control" id="observation" name="observation"  value="" placeholder="Ingresar observacion por el cual haces la edicion" autocomplete="new-password">
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
                    commercial: {
                        required: true,
                    },
                    parties: {
                        required: true,
                    },
                    lang: {
                        required: true,
                    },
                    "categories[]": {
                        required: true,
                    },
                    observation: {
                        required: true,
                        minlength: 0,
                        maxlength: 10000,
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
                    commercial: {
                        required: "El parametro es necesario.",
                    },
                    parties: {
                        required: "El parametro es necesario.",
                    },
                    suscribe: {
                        required: "El parametro es necesario.",
                    },
                    lang: {
                        required: "El parametro es necesario.",
                    },
                    "categories[]": {
                        required: "El parametro es necesario.",
                    },
                    observation: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 0 caracter",
                        maxlength: "Debe contener al menos 10000 caracter",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formSubcriptions');
                    var formData = new FormData($form[0]);
                    var uid = $("#uid").val();
                    var firstname = $("#firstname").val();
                    var lastname = $("#lastname").val();
                    var commercial = $("#commercial").val();
                    var parties = $("#parties").val();
                    var categories = $("#categories").val();
                    var lang = $("#lang").val();
                    var observation = $("#observation").val();

                    formData.append('uid', uid);
                    formData.append('firstname', firstname);
                    formData.append('lastname', lastname);
                    formData.append('parties', parties);
                    formData.append('commercial', commercial);
                    formData.append('lang', lang);
                    formData.append('categories', categories);
                    formData.append('observation', observation);

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


                                $submitButton.prop('disabled', false);

                                setTimeout(function() {
                                    window.location.href = "{{ route('manager.subscribers.edit', $subscriber->uid) }}";
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



            $('#lang').on('change', function() {
                let langId = $(this).val();
                let categorySelect = $('#categories');

                if (langId) {
                    $.ajax({
                        url: '{{ route('manager.langs.categories') }}', // Ruta del controlador
                        type: 'GET',
                        data: { term: langId },
                        dataType: 'json',
                        success: function(response) {
                            let selectedValues = categorySelect.val() || []; // Obtener valores seleccionados actuales
                            let newCategoryIds = response.map(category => category.id); // IDs de las nuevas categorías disponibles

                            // Limpiar select2 y agregar nueva opción por defecto
                            categorySelect.empty();
                            categorySelect.append(new Option('Selecciona una categoría', '', false, false));

                            let newSelectedValues = [];

                            // Agregar nuevas opciones
                            $.each(response, function(index, category) {
                                let isSelected = selectedValues.includes(category.id.toString()); // Verificar si estaba seleccionado
                                categorySelect.append(new Option(category.text, category.id, false, isSelected));

                                if (isSelected) {
                                    newSelectedValues.push(category.id.toString()); // Mantener seleccionados los válidos
                                }
                            });

                            // Asignar los valores seleccionados filtrados
                            categorySelect.val(newSelectedValues).trigger('change');
                        }
                    });
                }else {
                    // Si no hay lang seleccionado, vaciar el select
                    categorySelect.empty().trigger('change');
                }
            });



        });

    </script>

@endpush



