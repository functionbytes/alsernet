@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formLists" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input type="hidden" id="id" name="id" value="{{ $list->id }}">
                    <input type="hidden" id="uid" name="uid" value="{{ $list->uid }}">
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

                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Titulo</label>
                                    <input type="text" class="form-control" id="title"  name="title" value="{{ $list->title }}" placeholder="Ingresar nombre">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Codigo</label>
                                    <input type="text" class="form-control" id="code"  name="code" value="{{ $list->code }}" placeholder="Ingresar codigo">
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="prioritie" class="control-label col-form-label">Pais</label>
                                    <select class="form-control select2" id="lang" name="lang">
                                        @foreach($langs as $id => $name)
                                            <option value="{{ $id }}" {{  $list->lang_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <label id="lang-error" class="error d-none" for="lang"></label>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <select class="form-control select2" id="available" name="available">
                                        <option value="1" {{ $list->available == 1 ? 'selected' : '' }}>Público</option>
                                        <option value="0" {{ $list->available == 0 ? 'selected' : '' }}>Oculto</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Predeterminada</label>
                                    <select class="form-control select2" id="default" name="default">
                                        <option value="1" {{ $list->default == 1 ? 'selected' : '' }}>Si</option>
                                        <option value="0" {{ $list->default == 0 ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Categorias</label>
                                    <select class="form-control select2" id="categories" name="categories[]" multiple="multiple">
                                        @foreach($categories as $id => $name)
                                            <option value="{{ $id }}" {{ $list->categorie->pluck('categorie_id')->contains($id) ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <label id="categories-error" class="error d-none" for="categories"></label>
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


            $("#formLists").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    title: {
                        required: true,
                        minlength: 1,
                        maxlength: 100,
                    },
                    code: {
                        required: true,
                        minlength: 1,
                        maxlength: 100,
                    },
                    available: {
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
                    title: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    code: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    available: {
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

                    var $form = $('#formLists');
                    var formData = new FormData($form[0]);
                    var uid = $("#uid").val();
                    var title = $("#title").val();
                    var code = $("#code").val();
                    var lang = $("#lang").val();
                    var available = $("#available").val();
                    var categories = $("#categories").val();

                    formData.append('uid', uid);
                    formData.append('title', title);
                    formData.append('code', code);
                    formData.append('lang', lang);
                    formData.append('available', available);
                    formData.append('categories', categories);


                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('manager.subscribers.lists.update') }}",
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
                                    window.location.href = "{{ route('manager.subscribers.lists') }}";
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



