@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Histórico de Movimientos'])

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
                                <a href="javascript:void(0)" class="btn btn-primary" onclick="exportMovements()">
                                    <i class="fa-duotone fa-download"></i> Exportar
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
                            <th>Ubicación</th>
                            <th>Producto</th>
                            <th>Tipo Movimiento</th>
                            <th>Cantidad</th>
                            <th>Peso</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>
                    <tbody>

                    @foreach ($movements as $key => $movement)
                        <tr class="search-items">
                            <td>
                                <span class="usr-email-addr">{{ $movement->slot->location->code ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="usr-email-addr">{{ $movement->product->name ?? 'Sin Producto' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light-info text-info rounded-3 fw-semibold">{{ $movement->movement_type }}</span>
                            </td>
                            <td>
                                <span class="usr-email-addr">{{ $movement->quantity }}</span>
                            </td>
                            <td>
                                <span class="usr-email-addr">{{ $movement->weight }} kg</span>
                            </td>
                            <td>
                                <span class="usr-ph-no">{{ date('Y-m-d H:i', strtotime($movement->created_at)) }}</span>
                            </td>

                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('manager.warehouse.history.view', $movement->uid) }}">
                                                Ver Detalles
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



