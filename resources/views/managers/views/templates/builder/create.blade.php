@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Nueva plantilla'])

    <div class="widget-content searchable-container list">
        <div class="card">

            <div class="card-body border-bottom">
                <form action="{{ route('manager.templates.builder.create') }}" method="POST" class="template-form form-validate-jquery">
                    {{ csrf_field() }}

                    <input type="hidden" value="" name="template" />

                    <h4 class="card-title">Seleccione una de las plantillas base a continuación</h4>
                    <p class="card-subtitle mb-3">Asigna un nombre a la plantilla </p>

                    <div class="mb-4">
                        <input type="text" class="form-control" id="name"  name="title" value="{{$template->title}}" placeholder="Ingresar nombre">
                    </div>


                    <div>
                        <div class="text-start">
                            <button class="btn btn-primary w-100 "><i class="icon-check"></i> Iniciar diseño</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body">
                <div class="row">
                    @foreach ($categories as $category)
                                    @php
                                        $templates = $category->templates()->get();
                                    @endphp
                                    @if ($templates->count() > 0)
                                        <div class="subsection pb-4">
                                            <div class="card-body border-bottom pb-2 mb-2">
                                                <h5 class="card-title p-4 mb-0">{{ $category->title }}</h5>
                                            </div>
                                            <div class="row">
                                                @foreach ($templates as $key => $template)
                                                    <div  class="col-sm-3 col-xxl-3" >
                                                        <div href="javascript:;" class="card  select-template-layout" data-template="{{ $template->uid }}">
                                                            <div class="position-relative">
                                                                    <img src="{{ $template->getThumbUrl() }}?v={{ rand(0,10) }}" class="card-img-top" alt="modernize-img">
                                                            </div>
                                                            <div class="card-body pt-3 p-4">
                                                                <div class="pb-2">
                                                                    <h6 class="fs-4">{{ $template->title }}</h6>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                    @endforeach
                </div>
            </div>

        </div>
    </div>

@endsection


@push('scripts')

    <script type="text/javascript">

        $(document).ready(function() {

            $(document).on('click', '.select-template-layout', function() {
                var template = $(this).attr('data-template');
                $('.select-template-layout').removeClass('selected');
                $(this).addClass('selected');
                $('[name=template]').val('');
                if (typeof(template) !== 'undefined') {
                    $('[name=template]').val(template);
                }
            });

            $('.select-template-layout').eq(0).click();

            $(document).on('click', '.start-design', function() {
                var form = $('.template-form');

                if ($('.select-template-layout.selected').length == 0) {
                    new Dialog('alert', {
                        title: "Error",
                        message: "Continuar desde una base de plantilla existente",
                    });
                    return;
                }
                if (form.valid()) {
                    form.submit();
                }
            });

        });

    </script>

@endpush

