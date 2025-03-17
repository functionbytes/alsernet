@extends('layouts.callcenters')

@section('content')

    @include('distributors.includes.card', ['title' => 'Facturación'])

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
                                    <select class="form-select select2" name="condition" data-minimum-results-for-search="Infinity">
                                        <option value="">Seleccionar estado</option>
                                        @foreach($conditions as $item)
                                            <option value="{{ $item->id }}" @if (isset($condition) && $condition ==  $item->id ) selected @endif>
                                                {{ $item->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="input-group">
                                    <select class="form-select select2" name="methods" data-minimum-results-for-search="Infinity">
                                        <option value="">Seleccionar metodo</option>
                                        @foreach($methods as $item)
                                            <option value="{{ $item->id }}" @if (isset($method) && $method ==  $item->id ) selected @endif>
                                                {{ $item->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('callcenter.distributors.invoices.report') }}" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Reporte">
                                    <i class="fa-solid fa-file-chart-column"></i>
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
                        <th>Factura</th>
                        <th>Empresa</th>
                        <th>Estado</th>
                        <th>Metodo de pago</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach ($invoices as $key =>$invoice)
                        <tr class="search-items">

                            <td>
                                <span class="usr-email-addr" data-email="{{$invoice->reference }}">{{$invoice->reference }}</span>
                            </td>
                            <td>
                                <span class="usr-email-addr" data-email="{{ $invoice->distributor->title  }}">{{$invoice->distributor->title}}</span>
                            </td>
                            <td>
                                <span class="badge bg-light-{{$invoice->condition->slug }} rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">
                                    {{ $invoice->condition->title }}
                                </span>
                            </td>
                            <td>
                                <span class="badge  bg-light-{{ $invoice->method->slug }} rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">
                                     {{ $invoice->method->title }}
                                </span>
                            </td>

                            </td>
                            <td>
                                <span class="usr-ph-no" data-phone="{{ date('Y-m-d', strtotime($invoice->updated_at)) }}">{{ date('Y-m-d', strtotime($invoice->updated_at)) }}</span>
                            </td>
                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('callcenter.distributors.invoices.view',$invoice->uid) }}">General</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('callcenter.distributors.invoices.detail',$invoice->uid) }}">Detallado</a>
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
                <span>Mostrar {{ $invoices->firstItem() }}-{{ $invoices->lastItem() }} de {{ $invoices->total() }} resultados</span>
                <nav>
                    {{ $invoices->appends(request()->input())->links() }}
                </nav>
            </div>
        </div>
    </div>
@endsection




