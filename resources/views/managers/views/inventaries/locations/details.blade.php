@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Historico inventario'])

    <div class="widget-content searchable-container list">

        <div class="card card-body">
            <div class="row">
                <div class="col-md-12 col-xl-12">
                    <form class="position-relative form-search" action="{{ Request::fullUrl() }}" method="GET">
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
                                    <select class="form-select select2" name="available" data-minimum-results-for-search="Infinity">
                                        <option value="">Seleccionar estado</option>
                                        <option value="1" @isset($available) @if ($available==1) selected @endif @endisset>  Publico</option>
                                        <option value="0" @isset($available) @if ($available==0) selected  @endif @endisset>  Oculto</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href=" {{ route('manager.products.create') }}" class="btn btn-primary">
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
                            <th>Referencia</th>
                            <th>Cantidad</th>
                            <th>Kardez</th>
                            <th>Original</th>
                            <th>Validacion</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>
                    <tbody>

                    @foreach ($items as $key => $item)
                        <tr class="search-items">
                            <td>
                                <span class="usr-email-addr" >{{ $item->product->reference  }}</span>
                            </td>
                            <td>
                                <span class="usr-email-addr" >{{ $item->count  }}</span>
                            </td>
                            <td>
                                <span class="usr-email-addr" >{{ $item->count  }}</span>
                            </td>
                            <td>
                                <span class="usr-email-addr" >{{ $item->original->title  }}</span>
                            </td>
                            <td>
                                <span class="usr-email-addr" >{{ $item->validate->title   }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light-primary rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">
                                    {{ $item->condition->title  }}
                                </span>
                            </td>
                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('manager.historys.edit', $item->slack) }}">
                                                Editar
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3 confirm-delete" data-href="{{ route('manager.historys.destroy', $item->slack) }}">Eliminar</a>
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


