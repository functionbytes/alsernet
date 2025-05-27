@extends('layouts.popup.small')


@section('content')

    <form class="categories-form" action="{{ route('manager.templates.categories', ['uid' => $template->uid,]) }}" method="POST">

        <h5 class="mt-0 mb-2">Establecer categoría de plantilla - {{ $template->title }}</h5>
        <p class="mt-0 pb-2">Elija una o más categorías para su plantilla. Aviso: si su plantilla no pertenece a ninguna categoría, no será visible para los usuarios.</p>

        {{ csrf_field() }}

        @foreach(App\Models\Template\TemplateCategory::all() as $category)
            @include('helpers.form_control', [
                'type' => 'checkbox2',
                'name' => 'categories['.$category->uid.']',
                'value' => ($template->hasCategory($category) ? 'true' : 'false'),
                'label' => $category->title,
                'options' => ['false', 'true'],
                'help_class' => 'template',
                'rules' => [],
            ])
        @endforeach

        <hr>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary bg-grey-600 me-1 mt-4 w-100">Guardar</button>
        </div>

    </form>

    <script>

        $('.categories-form').on('submit', function(e) {

            e.preventDefault();
            var url = $(this).attr('action');
            var data = $(this).serialize();

            $.ajax({
                url: url,
                method: 'POST',
                data: data,
                success: function (response) {

                    categoriesPopup.hide();

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
        })

    </script>
@endsection
