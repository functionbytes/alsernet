@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formMaintenance" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}

                    <div class="card-body border-top">

                        <div class="row mt-50">

                            <div class="col-12 ">

                                    <div class="row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <h5 class="mb-3">Habilitar modo mantenimiento</h5>
                                            <p class="card-subtitle mb-3 mt-0">(Si "habilita" esta configuración, los clientes solo podran ver la vista de mantenimiento hasta q no sea de nuevo desabiltiado).</p>
                                        </div>
                                        <div class="col-sm-1 justify-content-end d-flex align-items">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode"   @if(setting('maintenance_mode')=='true' ) checked @endif/>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="border-top mt-4 pt-4 row align-items-center maintenance_mode @if(setting('maintenance_mode')=='false' ) d-none @endif" >
                                        <div class="mb-3">
                                        <label class="form-label">Llave secreta</label>
                                        <input type="text" id="maintenance_mode_value"  name="maintenance_mode_value" value="{{ setting('maintenance_mode_value')!=null ? setting('maintenance_mode_value') :$secret}}" class="form-control" readonly=""  placeholder="Deje un mensaje en espera">
                                        <div class="alert alert-light-warning note mt-4 mb-0">
                                            <p class="mb-0">
                                                <b class="pb-1 d-flex"> ¿Cómo utilizar la clave secreta? </b>
                                            <ol>
                                                <li>
                                                    La clave secreta se utiliza básicamente para acceder a su URL web. cuando esta en <b>en modo de mantenimiento.</b>
                                                </li>
                                                <li>Ahora copia tu generado <b>Llave secreta</b> desde el campo de entrada y péguelo en su URL para acceder a su URL web en modo de mantenimiento
                                                    <b>Ej: {{ getUrl() }}/{{ setting('maintenance_mode_value')!=null ? setting('maintenance_mode_value') :$secret}}</b>
                                                </li>
                                                <li>Y también puede permitir que otras redes o IP accedan a su sitio web al <b>intercambio</b> Tu clave secreta con ellos.</li>
                                            </ol>
                                            </p>
                                        </div>
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

            $("#maintenance_mode").click(function(){
                var check = $(this).prop('checked');
                if(check == true) {
                    $(".maintenance_mode").removeClass("d-none");
                } else {
                    $(".maintenance_mode").addClass("d-none");
                }
            });

            $("#formMaintenance").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    maintenance_mode_value: {
                        required: true,
                        minlength: 1,
                        maxlength: 200,
                    },
                },
                messages: {
                    maintenance_mode_value: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 4 caracter",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formMaintenance');
                    var formData = new FormData($form[0]);
                    var maintenance_mode_value = $("#maintenance_mode_value").val();
                    var maintenance_mode = $("#maintenance_mode").is(':checked');

                    formData.append('maintenance_mode', maintenance_mode);
                    formData.append('maintenance_mode_value', maintenance_mode_value);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);


                    $.ajax({
                        url: "{{ route('manager.settings.maintenance.update') }}",
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


