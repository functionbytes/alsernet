@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formCategories" enctype="multipart/form-data" role="form" onSubmit="return false">


                    <input type="hidden" id="list"  name="list" value="{{ $list->uid }}">

                    {{ csrf_field() }}


                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Incluir categorias a la lista</h5>
                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>
                        <div class="row">

                            <div class="col-12">
                                <div class="mb-3">
                                    <select class="form-control select2" id="categories" name="categories[]" multiple="multiple">
                                        @foreach($categories as $id => $name)
                                            <option value="{{ $id }}" {{ $list->categories->pluck('categorie_id')->contains($id) ? 'selected' : '' }}>
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

            $("#formCategories").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    "categories[]": {
                        required: true,
                    },
                },
                messages: {
                    "categories[]": {
                        required: "El parametro es necesario.",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formCategories');
                    var formData = new FormData($form[0]);
                    var uid = $("#uid").val();
                    var categories = $("#categories").val();
                    var list = $("#list").val();

                    formData.append('uid', uid);
                    formData.append('categories', categories);
                    formData.append('list', list);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('manager.subscribers.lists.categories.update') }}",
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



