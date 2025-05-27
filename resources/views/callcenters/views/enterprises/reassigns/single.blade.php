@extends('layouts.callcenters')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formCourses" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <input type="hidden" id="slack" name="slack" value="{{ $user->uid }}">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0"> Reasignar a otra empresa</h5>

                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
                        </p>

                        <div class="row">

                            <div class="col-12">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Empresa</label>
                                        <input type="text" class="form-control" value="{{ $enterprise->title }}" disabled>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Usuario</label>
                                        <input type="text" class="form-control" id="slack"  name="slack" value="{{ $user->firstname }} {{ $user->lastname }}" disabled>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label  class="control-label col-form-label">Empresa a reasignar</label>
                                    <div class="input-group">
                                        {!! Form::select('enterprise', $enterprises, null , ['class' => 'select2 form-control'  ,'name' => 'enterprise', 'id' => 'enterprise' ]) !!}
                                    </div>
                                    <label id="enterprise-error" class="error d-none" for="enterprise"></label>
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
                    'enterprise': {
                        required: true,
                    },
                },
                messages: {
                    'enterprise': {
                        required: "Es necesario una opción.",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formCourses');
                    var formData = new FormData($form[0]);
                    var slack = $("#slack").val();
                    var enterprise = $("#enterprise").val();

                    formData.append('slack', slack);
                    formData.append('enterprise', enterprise);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('callcenter.enterprises.users.reassign.single') }}",
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
                                slack = response.enterprise;

                                toastr.success(message, "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                setTimeout(function() {
                                    window.location.href = "{{ route('callcenter.enterprises.users', ':slack') }}".replace(':slack', slack);
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



