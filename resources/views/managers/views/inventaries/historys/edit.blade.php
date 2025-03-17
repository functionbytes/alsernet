@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formHistory" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input type="hidden" id="id" name="id" value="{{ $item->id }}">
                    <input type="hidden" id="slack" name="slack" value="{{ $item->uid }}">
                    <input type="hidden" id="edit" name="edit" value="true">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">

                            <h5 class="mb-0">Editar registro de inventario
                            </h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>

                        <div class="row">

                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Usuario</label>
                                    <input type="text" class="form-control" id="original"  name="original" value="{{ $item->user->firstname }} {{ $item->user->lastname }}" autocomplete="new-password" disabled>
                                </div>
                            </div>


                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Producto</label>
                                    <input type="text" class="form-control" id="product"  name="product" value="{{ $item->product->reference }}" autocomplete="new-password" disabled>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Ubicacion original</label>
                                    <input type="text" class="form-control" id="locate"  name="locate" value="{{ $item->validate->title }}" autocomplete="new-password" disabled>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Ubicacion original</label>
                                    <input type="text" class="form-control" id="original"  name="original" value="{{ $item->original?->title }}" autocomplete="new-password" disabled>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Ubicacion validada</label>
                                    <input type="text" class="form-control" id="original"  name="original" value="{{ $item->validate->title }}" autocomplete="new-password" disabled>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Cantidad</label>
                                    <input type="text" class="form-control" id="count"  name="count" value="{{ $item->count }}" autocomplete="new-password" >
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <div class="input-group">
                                        {!! Form::select('condition', $conditions, $item->condition_id , ['class' => 'select2 form-control','id' => 'condition' , 'disabled']) !!}
                                    </div>
                                    <label id="condition-error" class="error d-none" for="condition"></label>
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

            $("#formHistory").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    count: {
                        required: true,
                        number: true,
                    },
                },
                messages: {
                    count: {
                        required: "El parametro es necesario.",
                        number: "Debe ser valor numerico",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formHistory');
                    var formData = new FormData($form[0]);
                    var slack = $("#slack").val();
                    var count = $("#count").val();

                    formData.append('slack', slack);
                    formData.append('count', count);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('manager.historys.update') }}",
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
                                    window.location.href = "{{ route('manager.inventaries.historys', $inventarie->uid) }}";
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



