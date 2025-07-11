@extends('layouts.callcenters')

@section('content')

    @include('managers.includes.card', ['title' => 'Devoluciones'])

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
                                    <select class="form-select select2" name="reviewed" data-minimum-results-for-search="Infinity">
                                        <option value="">Seleccionar estado</option>
                                        <option value="1" @isset($reviewed) @if ($reviewed==1) selected @endif @endisset>  Gestionado</option>
                                        <option value="0" @isset($reviewed) @if ($reviewed==0) selected  @endif @endisset>  Pendiente</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>

                            @can('returns.create')
                                <div class="col-auto">
                                    <a href=" {{ route('callcenter.returns.create' ) }}" class="btn btn-primary">
                                        <i class="fa-duotone fa-plus"></i>
                                    </a>
                                </div>
                            @endcan

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
                        <th>Titulo</th>
                        <th>Estado</th>
                        <th>Fecha solicitud</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach ($returns as $key => $return)
                        <tr class="search-items">
                            <td>
                                <span>{{ Str::words( $return->reference)  }}</span>
                            </td>
                            <td>
                              <span class="badge {{ $return->status->id == 1 ? 'bg-light-primary' : 'bg-light-secondary' }} rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">
                                 {{ $return->status->title }}
                              </span>
                            </td>
                            <td>
                                <span>
                                   <span>{{ $return->request_at ? formatCarbonDate($return->updated_at) : 'Pendiente' }}</span>
                                </span>
                            </td>
                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        @can('returns.update')
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('callcenter.returns.edit', $return->uid) }}">
                                                    Editar
                                                </a>
                                            </li>
                                        @endcan
                                        @can('returns.delete')
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-3 confirm-delete" data-href="{{ route('callcenter.returns.destroy', $return->uid) }}">
                                                    Eliminar
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    </tbody>
                </table>
            </div>
            <div class="result-body ">
                <span>Mostrar {{ $returns->firstItem() }}-{{ $returns->lastItem() }} de {{ $returns->total() }} resultados</span>
                <nav>
                    {{ $returns->appends(request()->input())->links() }}
                </nav>
            </div>
        </div>
    </div>
@endsection


