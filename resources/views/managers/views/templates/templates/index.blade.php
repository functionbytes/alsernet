@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Plantillas campañas'])

    <div class="widget-content searchable-container list">

        <div class="card card-body">
            <div class="row">
                <div class="col-md-12 col-xl-12">
                    <form class="position-relative form-search" action="{{ request()->fullUrl() }}" method="GET">
                        <div class="row justify-content-between g-2 ">
                            <div class="col-auto flex-grow-1">
                                <div class="tt-search-box">
                                    <div class="input-group">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-2"> <i data-feather="search"></i></span>
                                        <input class="form-control rounded-start w-100" type="text" id="search" name="search" placeholder="Buscar" @isset($searchKey) value="{{ $searchKey }}" @endisset>
                                    </div>
                                </div>
                            </div>

                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href=" {{ route('manager.templates.builder.create') }}" class="btn btn-primary">
                                    <i class="fa-duotone fa-plus"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>



                <div class="card card-body">
                    <div class="row">

                        @foreach ($templates as $key => $template)
                            <div class="col-sm-3 col-xxl-3">
                                <div class="card ">
                                    <div class="position-relative">
                                        <a>
                                            <img src="{{ $template->getThumbUrl() }} " class="card-img-top" alt="modernize-img">
                                        </a>

                                        <div class="template-options bg-primary text-bg-primary rounded-circle p-2 text-white d-inline-flex position-absolute bottom-0 end-0 mb-n3 me-3 ql-color-white">
                                            <button type="button" class="p-1 border-0 bg-transparent" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="display: flex; align-items: center;">
                                                <i class="fa-duotone fa-solid fa-gear"></i>
                                            </button>
                                            <ul class="dropdown-menu  rubberBand" data-bs-popper="static" style="z-index: 1050;">
                                                <li>
                                                    <a class="dropdown-item change-template-name" href="{{ route('manager.templates.changemame', ['uid' => $template->uid]) }}">
                                                        Cambiar nombre
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item upload-thumb-button" href="{{ route('manager.templates.update.thumb', $template->uid) }}">
                                                        Cambiar imagen
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item template-categories" href="{{ route('manager.templates.categories', ['uid' => $template->uid]) }}">
                                                        Categorias
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('manager.templates.export', $template->uid) }}" role="button" class="dropdown-item" link-method="POST">
                                                        Exportar
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('manager.templates.copy', $template->uid) }}" role="button" class="dropdown-item copy-template-button" link-method="GET">
                                                        Copiar
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item list-action-single" link-confirm="Estás a punto de eliminar :plantilla(s) de números." href="{{ route('manager.templates.delete', ["uids" => $template->uid]) }}">
                                                        Eliminar
                                                    </a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#preview" onclick="popupwindow('{{ route('manager.templates.preview', $template->uid) }}', `{{ $template->title }}`, 800)">
                                                        Previsualizar
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>

                                    </div>
                                    <div class="card-body pt-3 p-4">

                                        <div class="pb-2 pt-3">
                                            <h6 class="fs-4">{{ $template->title }}</h6>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <a href="{{ route('manager.templates.categories', ['uid' => $template->uid,]) }}" class="template-categories text-secondary">
                                                    <div class="templates-category">
                                                        @foreach($template->categories as $cat)
                                                            <span class="category-item">{{ $cat->title }}</span>
                                                        @endforeach
                                                    </div>
                                                </a>
                                            </div>
                                        </div>

                                        <div class="mb-0 border-top mt-2 pt-3">
                                            <div>
                                                <a class="btn btn-primary btn-icon template-compose d-block mb-2  w-100" href="#preview" onclick="popupwindow('{{ route('manager.templates.preview', $template->uid) }}', `{{ $template->title }}`, 800)">
                                                    Previsulizar
                                                </a>
                                            </div>
                                            <div>
                                                <a href="{{ route('manager.templates.builder.edit', $template->uid) }}" class="btn btn-primary mb-2 w-100">
                                                    Avanzado
                                                </a>
                                            </div>
                                            <div>
                                                <a href="{{ route('manager.templates.edit', $template->uid) }}" class="btn btn-info mb-2 w-100">
                                                    Clasico
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                    <div class="result-body ">
                        <span>Mostrar {{ $templates->firstItem() }}-{{ $templates->lastItem() }} de {{ $templates->total() }} resultados</span>
                        <nav>
                            {{ $templates->appends(request()->input())->links() }}
                        </nav>
                    </div>
                </div>


            </div>
        </div>
    </div>
@endsection


@push('scripts-head')

    <script type="text/javascript">

        var TemplatesList = {

            copyPopup: null,
            changeNamePopup: null,

            getCopyPopup: function() {
                if (this.copyPopup === null) {
                    this.copyPopup = new Popup();
                }

                return this.copyPopup;
            },

            getChangeNamePopup: function() {

                if (this.changeNamePopup === null) {
                    this.changeNamePopup = new Popup();
                }

                return this.changeNamePopup;
            }
        }


    </script>

@endpush




@push('scripts')

    <script type="text/javascript">

        var thumbPopup = new Popup();
        var categoriesPopup = new Popup();

        $(document).ready(function () {


        $('.upload-thumb-button').click(function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            thumbPopup.load(url);
        });

        $('.template-categories').click(function(e) {
            e.preventDefault();

            var url = $(this).attr('href');

            categoriesPopup.load(url);
        });


        $('.change-template-name').on('click', function(e) {

            e.preventDefault();
            var url = $(this).attr('href');

            TemplatesList.getChangeNamePopup().load({
                url: url
            });
        });

        $('.copy-template-button').on('click', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            TemplatesList.getCopyPopup().load({
                url: url
            });
        });

        $('.template-compose').click(function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            openBuilder(url);
        });

        $('.template-compose-classic').click(function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            openBuilderClassic(url);
        });


        function popupwindow(url, title, w, h) {
            var left = (screen.width / 2) - (w / 2);
            var top = 0;
            var height = screen.height;

            if (typeof (h) !== 'undefined') {
                height = h;
                top = (screen.height / 2) - (height / 2);
            }

            return window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' + w + ', height=' + height + ', top=' + top + ', left=' + left);
        }



        });

    </script>

@endpush

