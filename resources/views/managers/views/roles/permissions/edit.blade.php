@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formPermissions" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input type="hidden" id="id" name="id" value="{{$permission->id}}">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">

                            <h5 class="mb-0">Editar permiso
                            </h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>

                        <div class="row">

                            <div class="col-6 mb-3">
                                    <label  class="form-label">Nombres</label>
                                    <input type="text" class="form-control" id="name"  name="name" value="{{$permission->name}}" placeholder="Ingresar nombres" autocomplete="new-password">
                            </div>

                            <div class="col-6 mb-3">
                                    <label class="form-label">Guard</label>
                                    <select class="form-select select2" name="guard_name">
                                        <option value="web" {{ $permission->guard_name == 'web' ? 'selected' : '' }}>Web</option>
                                        <option value="api" {{ $permission->guard_name == 'api' ? 'selected' : '' }}>API</option>
                                    </select>
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

        $(document).ready(function() {


            $("#formPermissions").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    name: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                    },
                    guard_name: {
                        required: true,
                    }
                },
                messages: {
                    name: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    guard_name: {
                        required: "Debe seleccionar un guard."
                    }
                },
                submitHandler: function(form) {

                    var $form = $('#formPermissions');
                    var formData = new FormData($form[0]);
                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('manager.permissions.update') }}",
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
                                    positionClass: "toast-bottom-right",
                                    timeOut: 1000,
                                    onHidden: function () {
                                        window.location.href = "{{ route('manager.permissions') }}";
                                    }
                                });


                            }else{

                                $submitButton.prop('disabled', false);
                                error = response.message;

                                toastr.warning(error, "Operación fallida", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right",
                                    timeOut: 1000,
                                    onHidden: function () {
                                        $('.errors').text(error);
                                        $('.errors').removeClass('d-none');
                                    }
                                });


                            }

                        }
                    });

                }

            });



        });

    </script>

@endpush



