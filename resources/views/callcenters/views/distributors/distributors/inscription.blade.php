@extends('layouts.callcenters')

@section('content')

<div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">

        <div class="card w-100">

            <form id="formInscriptions" enctype="multipart/form-data" role="form" onSubmit="return false">

                {{ csrf_field() }}


                <input id="enterprise" name="enterprise" type="hidden" value="{{ $enterprise->uid }}">

                <div class="card-body border-top">
                    <div class="d-flex no-block align-items-center">
                        <h5 class="mb-0"> Inscribir usuarios a un curso</h5>

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
                                <label  class="control-label col-form-label">Cursos</label>
                                <div class="input-group">
                                    {!! Form::select('course', $courses, null , ['class' => 'select2 form-control' ,'name'
                                    => 'course', 'id' => 'course']) !!}
                                </div>
                                <label id="courses-error" class="error d-none" for="courses"></label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-3">
                                <label  class="control-label col-form-label">Usuarios</label>
                                <div class="input-group">
                                    {!! Form::select('user[]', $users, null , ['class' => 'select2 form-control' ,'name'
                                    => 'users', 'id' => 'users' , 'multiple' => 'multiple']) !!}
                                </div>
                                <label id="users-error" class="error d-none" for="users"></label>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="action-form border-top mt-4">
                    <div class="text-center">
                        <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                            Guardar
                        </button>
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

            $("#formInscriptions").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    'course': {
                        required: true,
                    },
                    'user[]': {
                        required: true,
                    },
                },
                messages: {
                    'course': {
                        required: "Es necesario una opción.",
                    },
                    'user[]': {
                        required: "Es necesario una opción.",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formInscriptions');
                    var formData = new FormData($form[0]);
                    var enterprise = $("#enterprise").val();
                    var course = $("#course").val();
                    var users = $("#users").val();

                    formData.append('enterprises', enterprise);
                    formData.append('users', users);
                    formData.append('course', course);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "/manager/enterprises/inscriptions/generate",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(d) {

                            if(response.success == true){

                                message = response.message;

                                toastr.success(message, "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                setTimeout(function() {
                                    let slack = @json($enterprise->uid);
                                    window.location.href = "{{ route('callcenter.distributors.navegation', ':slack') }}".replace(':slack', slack);
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
