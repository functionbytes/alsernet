@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Listas'])

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
                                <div class="input-group">
                                    <select class="form-select select2" name="role" data-minimum-results-for-search="Infinity">
                                        <option value="">Seleccionar estado</option>
                                        <option value="admin" @isset($role) @if ($role=='manager') selected @endif @endisset>  Administrador</option>
                                        <option value="customer" @isset($role) @if ($role=='inventarie') selected @endif @endisset>  Inventario</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href=" {{ route('manager.maillists.create') }}" class="btn btn-primary">
                                    <i class="fa-duotone fa-plus"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="card card-body">
            <div class="table-responsive">
                <table class="table search-table align-middle text-nowrap">
                    <thead class="header-item">
                    <tr>
                        <th>Camapana</th>
                        <th>Suscriptores</th>
                        <th>Tasa de clics</th>
                        <th>Tasa de apertura</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach ($maillists as $key => $maillist)
                        <tr class="search-items">
                            <td>
                                <div class="">
                                    <h6 class=" fw-semibold mb-0">{{ Str::words( Str::lower($maillist->title ), 12, '...')  }}</h6>
                                    <span class="fw-normal">Creado el: {{ date('Y-m-d', strtotime($maillist->created_at)) }}</span>
                                </div>
                            </td>
                            <td>
                                <p class="mb-0 fw-normal">{{ number_with_delimiter($maillist->subscribersCount()) }}</p>
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fw-normal">{{ number_to_percentage($maillist->clickRate()) }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fw-normal">{{ number_to_percentage($maillist->openUniqRate()) }}</span>
                                </div>
                            </td>

                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('manager.campaigns.maillists.overview', $maillist->uid) }}">
                                                Estadisticas
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('manager.campaigns.maillists.subscribers.index', $maillist->uid) }}">
                                                Listado
                                            </a>
                                        </li>
                                        <li>
                                             <a class="dropdown-item" href="{{ route('manager.campaigns.maillists.segments.index', $maillist->uid) }}">
                                                    Segmentos
                                             </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('manager.maillists.verification', $maillist->uid) }}">
                                                Verificar
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('manager.maillists.edit', $maillist->uid) }}">
                                                Editar
                                            </a>
                                        </li>

                                            <li>
                                                <a class="dropdown-item" href="{{ route('manager.campaigns.maillists.subscribers.import', $maillist->uid) }}">
                                                   Importar
                                                </a>
                                            </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('manager.campaigns.maillists.subscribers.import.wizard', $maillist->uid) }}">
                                                Importar wizard
                                            </a>
                                        </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('manager.campaigns.maillists.subscribers.export', $maillist->uid) }}">
                                                    Exportar
                                                </a>
                                            </li>

                                        <li>
                                            <a class="copy-list-button dropdown-item" href="{{ route('manager.campaigns.maillists.copy', ['copy_list_uid' => $maillist->uid]) }}" >
                                                Copiar
                                            </a>
                                        </li>

                                        <li>
                                            <a class="dropdown-item list-action-single" link-method="POST"  link-confirm-url="{{ route('manager.campaigns.maillists.delete.confirm', ['uids' => $maillist->uid]) }}" href="{{ route('manager.campaigns.maillists.delete', ['uids' => $maillist->uid]) }}">
                                                Eliminar
                                            </a>
                                        </li>

                                    </ul>
                                </div>

                            </td>
                        </tr>
                    @endforeach

                    </tbody>
                </table>
            </div>

        </div>
    </div>
@endsection



@push('scripts')

    <script type="text/javascript">

            var ListsList = {
                clonePopup: new Popup(),
                copyPopup: null,

                getCopyPopup: function() {
                    if (this.copyPopup === null) {
                        this.copyPopup = new Popup();
                    }

                    return this.copyPopup;
                },

                getClonePopup: function() {
                    if (this.clonePopup === null) {
                        this.clonePopup = new Popup();
                    }

                    return this.clonePopup;
                },
            }

            $('.copy-list-button').on('click', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');

                ListsList.getCopyPopup().load({
                url: url
            });

        });
    </script>

@endpush


