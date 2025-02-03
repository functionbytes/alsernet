@extends('layouts.popup.small')

@section('content')
    <div class="row">
        <div class="col-md-3">
            <div class=" wizard ">
                <ul class="steps nav nav-tabs mc-nav campaign-template-tabs">
                    <li class=" nav-item">
                        <a class="nav-link thumb-url-tab" href="{{ route('manager.templates.update.thumb', $template->uid) }}">
                            Archivo
                        </a>
                    </li>
                    <li class="active nav-item">
                        <a class="nav-link" href="javascript:;">
                            Url
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-md-9">
            <div class="wizard-body">
                    <h5 class="mt-0 mb-0 mt-2">Seleccionar URL en miniatura</h5>
                    <p>Ingrese la URL de la imagen a continuación que se usará como miniatura de plantilla</p>

                    <form enctype="multipart/form-data" action="{{ route('manager.templates.update.thumburl', $template->uid) }}" method="POST" class="template_upload_form form-validate-jquery">
                        {{ csrf_field() }}

                        @include('helpers.form_control', ['required' => true,'type' => 'text','label' => '','name' => 'url'])

                        <hr>

                        <div class="mt-2">
                            <button class="btn btn-primary bg-grey-600 me-1 mt-4 w-100">Guardar</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>

        $('.thumb-url-tab').click(function(e) {
            e.preventDefault();
            var url = $(this).attr("href");
            thumbPopup.load(url);
        });

        $('.template_upload_form').submit(function(e) {
            e.preventDefault();
            var url = $(this).attr('action');
            var formData = new FormData($(this)[0]);


            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                globalError: false,
                statusCode: {
                    400: function (res) {
                        toastr.success(res.responseText, "Operación exitosa", {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right",
                            onHidden: function() {
                                window.location.reload();
                            }
                        });
                    }
                },
                success: function (response) {

                    thumbPopup.hide();

                    toastr.success(response.message, "Operación exitosa", {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right",
                        timeOut: 200,
                        extendedTimeOut: 200,
                        onHidden: function() {
                            window.location.reload();
                        }
                    });


                }
            });
        });
    </script>

@endsection
