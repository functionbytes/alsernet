@extends('layouts.popup.small')

@section('title')

@endsection

@section('content')

    <form id="copyTemplateForm" action="" method="POST" class="form-validate-jquery">
        {{ csrf_field() }}


        <input type="hidden" name="_method" value="">
        <input type="hidden" name="uids" value="">


        <h5 class="mt-0 mb-0 mt-2">Copiar plantilla -  {{ $template->title }}</h5>
        <p>¿Cómo te gustaría nombrar tu plantilla?</p>

        @foreach (request()->all() as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach

        @include('helpers.form_control', [ 'type' => 'text', 'name' => 'title', 'value' => 'Copia de ' .  $template->title, 'label' => '', 'help_class' => 'template', 'rules' => ['title' => 'required'] ])

        <hr>
        <div class="">
            <div class="mt-2">
                <button class="btn btn-primary bg-grey-600 me-1 mt-4  w-100"  type="submit" id="doCopyButton" >
                    Copiar
                </button>
                <a class="btn btn-secondary bg-grey-600 me-1 mt-1 w-100" onclick="TemplatesList.getCopyPopup().hide()">
                    Cerrar
                </a>
            </div>
        </div>
    </form>



    <script>

        var TemplatesCopy = {
            action: '{{ route('manager.templates.copy', $template->uid) }}',

            copy: function(url, data) {

                TemplatesList.getCopyPopup().mask();
                addButtonMask($('#doCopyButton'));

                $.ajax({
                    url: this.action,
                    type: 'POST',
                    data: data,
                    globalError: false
                }).done(function(response) {

                    TemplatesList.getCopyPopup().hide();

                    toastr.success(response.message, "Operación exitosa", {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right",
                        onHidden: function() {
                            window.location.reload();
                        }
                    });


                }).fail(function(jqXHR, textStatus, errorThrown) {

                    TemplatesList.getCopyPopup().hide();

                    toastr.success(jqXHR.responseText, "Operación exitosa", {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right",
                        timeOut: 200,
                        extendedTimeOut: 200,
                        onHidden: function() {
                            window.location.reload();
                        }
                    });

                }).always(function() {
                    TemplatesList.getCopyPopup().unmask();
                    removeButtonMask($('#doCopyButton'));
                });
            }
        };


        $(document).ready(function() {
            $('#copyTemplateForm').on('submit', function(e) {
                e.preventDefault();
                var url = $(this).attr('action');
                var data = $(this).serialize();
                TemplatesCopy.copy(url, data);
            });
        });

    </script>
@endsection


