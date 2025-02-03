@extends('layouts.popup.small')

@section('content')

	<div class="row">
        <div class="col-md-12">
            <h4 class="mt-0 mb-0 mt-2">Cambiar nombre</h4>
            <p class="mb-1">Ingrese el nombre de su nueva plantilla a continuación</p>

            <form id="changeNameForm" action="{{ route('manager.templates.changemame', ['uid' => $template->uid]) }}" method="POST">
                {{ csrf_field() }}
                @include('helpers.form_control', [
                    'type' => 'text',
                    'label' => '',
                    'name' => 'title',
                    'value' => request()->has('title') ? request()->title : $template->title,
                ])

                <div class="mt-4">
                    <button class="btn btn-primary bg-grey-600 mt-2 w-100">Guardar</button>
                </div>

            </form>
        </div>
    </div>

    <script>
        $(function() {

            $('#changeNameForm').submit(function(e) {

                e.preventDefault();
                var url = $(this).attr('action');
                var data = $(this).serialize();


                $.ajax({
                    url: url,
                    method: 'POST',
                    data: data,
                    globalError: false
                }).done(function (response) {

                    TemplatesList.getChangeNamePopup().hide();

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


                }).fail(function (response) {

                    toastr.success(response.responseText, "Operación exitosa", {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right",
                    });

                });
            });

        });
    </script>
@endsection
