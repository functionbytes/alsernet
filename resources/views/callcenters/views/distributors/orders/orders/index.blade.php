@extends('layouts.callcenters')

@section('content')

    @include('distributors.includes.card', ['title' => 'Ordenes'])

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
                                <a href="{{ route('supportorders.reports') }}" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Reporte">
                                    <i class="fa-solid fa-file-chart-column"></i>
                                </a>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('supportorders.resumen') }}" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Resumen">
                                    <i class="fa-solid fa-memo-circle-info"></i>
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
                        <th>Orden</th>
                        <th>Numero</th>
                        <th>Identification</th>
                        <th>Cliente</th>
                        <th>Empresa</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach ($orders as $key =>$order)
                        <tr class="search-items">

                            <td>
                                <span class="usr-email-addr" >{{$order->uid }}</span>
                            </td>
                            <td>
                                <span class="usr-email-addr" >{{$order->reference }}</span>
                            </td>
                            <td>
                                <span class="usr-email-addr" >{{ Str::upper($order->user->identification) }}</span>
                            </td>
                            <td>
                                <span class="usr-email-addr" >{{ Str::upper($order->user->firstname . ' ' . $order->user->lastname) }}</span>
                            </td>
                            <td>
                                <span class="usr-email-addr" >{{ Str::upper($order->activity->enterprise->title) }}</span>
                            </td>
                            <td>
                                <span class="usr-ph-no" >{{ date('Y-m-d', strtotime($order->updated_at)) }}</span>
                            </td>
                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('supportorders.view',$order->uid) }}">Visualizar</a>
                                            </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    </tbody>
                </table>
            </div>
            <div class="result-body ">
                <span>Mostrar {{ $orders->firstItem() }}-{{ $orders->lastItem() }} de {{ $orders->total() }} resultados</span>
                <nav>
                    {{ $orders->appends(request()->input())->links() }}
                </nav>
            </div>
        </div>
    </div>
@endsection




