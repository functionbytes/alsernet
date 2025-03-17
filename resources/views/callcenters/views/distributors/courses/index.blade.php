@extends('layouts.callcenters')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formCourses" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input  id="slack" name="slack" type="hidden" value="{{ $distributor->uid }}">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0"> Asignacion cursos</h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>

                        <div class="row">

                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Cursos</label>
                                    <div class="input-group">
                                        {!! Form::select('courses', $courses, $course , ['class' => 'select2 form-control' , 'multiple' => 'multiple' ,'name' => 'courses', 'id' => 'courses' ]) !!}
                                    </div>
                                    <label id="courses-error" class="error d-none" for="courses"></label>
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

            $("#formCourses").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    'courses[]': {
                        required: true,
                    },
                },
                messages: {
                    'courses[]': {
                        required: "Es necesario una opción.",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formCourses');
                    var formData = new FormData($form[0]);
                    var slack = $("#slack").val();
                    var courses = $("#courses").val();

                    formData.append('slack', slack);
                    formData.append('courses', courses);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('callcenter.distributors.courses.update') }}",
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
                                    let slack = @json($distributor->uid);
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



