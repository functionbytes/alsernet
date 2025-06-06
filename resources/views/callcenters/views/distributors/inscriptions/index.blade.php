@extends('layouts.callcenters')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <!-- ---------------------
                                    start Donation
                                ---------------- -->
            <div class="card w-100">

                <form id="formInscriptions" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input id="distributor" name="distributor" type="hidden" value="{{ $distributor->uid }}">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0"> Inscripciones</h5>

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
                                    <label  class="control-label col-form-label">Empresas</label>
                                    <div class="input-group">
                                        {!! Form::select('enterprise', $enterprises, null , ['class' => 'select2 form-control' ,'name' => 'enterprise', 'id' => 'enterprise']) !!}
                                    </div>
                                    <label id="enterprise-error" class="error d-none" for="enterprise"></label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Cursos</label>
                                    <div class="input-group">
                                        {!! Form::select('course',[], null , ['class' => 'select2 form-control' ,'name' => 'course', 'id' => 'course']) !!}
                                    </div>
                                    <label id="course-error" class="error d-none" for="course"></label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Usuarios</label>
                                    <div class="input-group">
                                        {!! Form::select('user',[], null , ['class' => 'select2 form-control' ,'name' => 'user', 'id' => 'user']) !!}
                                    </div>
                                    <label id="user-error" class="error d-none" for="user"></label>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <div class="action-form border-top mt-4">
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


    <div id="error-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="display-4 text-danger"><i data-feather="x-octagon"></i></div>
                    <h4 class="my-0">Este usuario ya esta inscrito a este curso</h4>
                    <p><span class="course"> <span> con fecha <span class="start"> <span></span>
                    <div class="row justify-content-center mt-20  ">
                        <div class="col-sm-12 col-md-5 w-100">
                            <a class="btn btn-primary w-100 registration" data-course="" data-user="" data-enterprise="" >Inscribir nuevamente</a>
                            <button type="button" class="btn btn-primary w-100 mt-1 registration-close" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection



@push('scripts')

    <script type="text/javascript">


        $(document).ready(function() {

            $("#enterprise").select2({
                minimumResultsForSearch: 0
            });

            $("#course").select2({
                minimumResultsForSearch: 0
            });


            $(".registration").on("click", function() {

                var enterprise = $(this).data("enterprise");
                var course = $(this).data("course");
                var user = $(this).data("user");

                var $submitButton = $('button[type="submit"]');

                $.ajax({
                    url: '{{ route('callcenter.distributors.inscriptions.enroll') }}', // Ruta para el método en el controlador
                    type: 'POST',
                    data: {
                        enterprise: enterprise,
                        course: course,
                        user: user,
                        _token: $('meta[name="csrf-token"]').attr('content') // Token CSRF
                    },
                    success: function(response) {

                        if (response.success) {

                            $("#error-modal").modal("hide");

                            toastr.success(response.message, "Operación exitosa", {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });

                            $('.registration').attr('data-enterprise','' ).attr('data-course', '').attr('data-user','');

                            $('#user').val(0).trigger('change');
                            $('#course').val(0).trigger('change');
                            $submitButton.prop('disabled', false);


                        } else {
                            toastr.error("Error al inscribir al usuario.");
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error en la solicitud AJAX:', error);
                    }
                });
            });

            $('#enterprise').on('change', function() {
                var enterpriseId = $(this).val();

                $.ajax({
                    url: '{{ route('callcenter.distributors.inscriptions.get.users') }}',
                    type: 'POST',
                    data: {
                        enterprise: enterpriseId
                    },
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        $('#user').empty().select2({
                            data: data.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.text
                                };
                            }),
                            placeholder: "Seleccionar usuarios"
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Error en la solicitud AJAX:', error);
                    }
                });

                $.ajax({
                    url: '{{ route('callcenter.distributors.inscriptions.get.courses') }}',
                    type: 'POST',
                    data: {
                        enterprise: enterpriseId
                    },
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        $('#course').empty().select2({
                            data: data.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.text
                                };
                            }),
                            placeholder: "Seleccionar un curso"
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Error en la solicitud AJAX:', error);
                    }
                });

            });


            $('#user').on('change', function(e) {
                var $submitButton = $('button[type="submit"]');
                if ($submitButton.prop('disabled')) {
                   $submitButton.prop('disabled', false);
                }
            });

            $(".registration-close").on("click", function() {

                $('#user').val(0).trigger('change');
                $('#course').val(0).trigger('change');
            });


            $("#formInscriptions").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    'course': {
                        required: true,
                    },
                    'enterprise': {
                        required: true,
                    },
                    'user': {
                        required: true,
                    },
                },
                messages: {
                    'course': {
                        required: "Es necesario una opción.",
                    },
                    'enterprise': {
                        required: "Es necesario una opción.",
                    },
                    'user': {
                        required: "Es necesario una opción.",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formInscriptions');
                    var formData = new FormData($form[0]);
                    var distributor = $("#distributor").val();
                    var enterprise = $("#enterprise").val();
                    var course = $("#course").val();
                    var users = $("#user").val();

                    formData.append('enterprise', enterprise);
                    formData.append('enterprise', enterprise);
                    formData.append('users', users);
                    formData.append('distributor', distributor);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('callcenter.distributors.inscriptions.store') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(response) {

                            if(response.success == true){

                                toastr.success(response.message, "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                $('#user').val(0).trigger('change');
                                $('#course').val(0).trigger('change');
                                $('#enterprise').val(0).trigger('change');

                                $submitButton.prop('disabled', false);

                            }else{

                                data = response['data'];
                                enterprise = data['enterprise_enroll'];
                                course = data['course_enroll'];
                                slack = data['customer_enroll'];

                                $('.registration').attr('data-enterprise', enterprise).attr('data-course', course).attr('data-user', slack);

                                identification = data['identification'];
                                courses = data['course'];
                                enroll_start = data['enroll_start'];
                                enroll_expire = data['enroll_expire'];

                                $(".identification").text(identification);
                                $(".course").text(courses);
                                $(".start").text(enroll_start);
                                $(".expire").text(enroll_expire);

                                $("#error-modal").modal("show");

                            }

                        }
                    });

                }

            });




        });

    </script>



@endpush
