@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formPixel" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <div class="card-body border-top">

                        <div class="row mt-50">

                            <div class="col-12 ">
                                <div class="mb-4 mt-3">
                                    <div class="mb-4 row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <h5 class="mb-3">Habilitar facebook</h5>
                                            <p class="card-subtitle mb-3 mt-0">(Si "habilita" esta configuración, los clientes solo podrán ver el nombre que proporcione en el campo de entrada a continuación. No podrán ver el nombre ni la función de los empleados).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="fb_pixel_enable" id="fb_pixel_enable"   @if(setting('fb_pixel_enable')=='true' ) checked @endif/>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4 row align-items-center">
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" id="fb_pixel"  name="fb_pixel" value="{{ setting('fb_pixel') }}">
                                        </div>
                                        <label for="userreopentime" class="form-label fw-semibold col-sm-9 col-form-label">ID de rastreo</label>
                                    </div>

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

            $(".form-check-input").click(function(){
                var check = $(this).prop('checked');
                if(check == true) {
                    $(this).prop('checked', true);
                } else {
                    $(this).prop('checked', false);
                }
            });

            $("#formPixel").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    fb_pixel: {
                        required: true,
                        minlength: 1,
                        maxlength: 100,
                    },
                },
                messages: {
                    fb_pixel: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formPixel');
                    var formData = new FormData($form[0]);
                    var fb_pixel = $("#fb_pixel").val();
                    var fb_pixel_enable = $("#fb_pixel_enable").is(':checked');

                    formData.append('fb_pixel', fb_pixel);
                    formData.append('fb_pixel_enable', fb_pixel_enable);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('manager.settings.pixel.update') }}",
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
                                    window.location.href = "{{ route('manager.dashboard') }}";
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



