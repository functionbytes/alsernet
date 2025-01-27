@extends('layouts.callcenters')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formAction" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input name="order" id="enterprise"  type="hidden" value="{{ $enterprise->id }}">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0"> Reporte usuarios</h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Empresa</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control"  value="{{ Str::upper(Str::lower($enterprise->title))  }}" placeholder="Ingresar celular" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Cursos</label>
                                    <div class="input-group">
                                        {!! Form::select('course', $courses, null , ['class' => 'select2 form-control' ,'name' => 'course', 'id' => 'course' ]) !!}
                                    </div>
                                    <label id="course-error" class="error d-none" for="course"></label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Fecha</label>
                                    <div class="input-group">
                                        <input type="text" id="range" name="range" class="form-control daterange" />
                                        <span class="input-group-text">
                                          <i class="ti ti-calendar fs-5"></i>
                                        </span>
                                    </div>
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

    <script src="{{ url('managers/libs/daterangepicker/daterangepicker.js') }}" type="text/javascript"></script>

    <script type="text/javascript">

        $(document).ready(function() {

            $('.daterange').daterangepicker();

            $("#formAction").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    range: {
                        required: true,
                    },
                    course: {
                        required: true,
                    },
                },
                messages: {
                    range: {
                        required: "Es necesario una opción.",
                    },
                    course: {
                        required: "Es necesario una opción.",
                    }
                },
                submitHandler: function(form) {

                    toastr.success("Se esta genrando el reporte.", "Operación exitosa", {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });

                    var query = {
                        range: $("#range").val(),
                        enterprise: $("#enterprise").val(),
                        course: $("#course").val(),
                    }

                    var url = "{{ route('callcenter.enterprises.users.incoming') }}?" + $.param(query);

                    window.location = url;

                }

            });


        });
    </script>



@endpush



