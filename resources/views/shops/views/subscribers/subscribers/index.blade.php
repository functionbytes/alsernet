@extends('layouts.shops')

@section('content')

    @include('managers.includes.card', ['title' => 'Suscripciones'])

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
                                    <select class="form-select select2" name="lopd" data-minimum-results-for-search="Infinity">
                                        <option value="">Seleccionar estado</option>
                                        <option value="1" @isset($lopd) @if ($lopd==1) selected @endif @endisset>  Activa</option>
                                        <option value="0" @isset($lopd) @if ($lopd==0) selected  @endif @endisset>  Inactiva</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('shop.subscribers.create') }}" class="btn btn-primary">
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
                        <th>Cliente</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach ($subscribers as $key => $subscriber)
                        <tr class="search-items">
                            <td>
                                <span class="usr-email-addr" >{{ Str::words( Str::upper(Str::lower($subscriber->firstname . ' ' . $subscriber->lastname)), 12, '...')  }}</span>
                            </td>
                            <td>
                                <span class="usr-email-addr" >{{ $subscriber->email  }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $subscriber->lopd == 1 ? 'bg-light-primary' : 'bg-light-secondary' }} rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">
                                           {{ $subscriber->lopd == 1 ? 'Activo' : 'Inactiva' }}
                                </span>
                            </td>

                            <td>
                                <span class="usr-ph-no" data-phone="{{ date('Y-m-d', strtotime($subscriber->updated_at)) }}">{{ date('Y-m-d', strtotime($subscriber->updated_at)) }}</span>
                            </td>
                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('shop.subscribers.logs', $subscriber->uid) }}">
                                                Logs
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('shop.subscribers.edit', $subscriber->uid) }}">
                                                Editar
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
            <div class="result-body ">
                <span>Mostrar {{ $subscribers->firstItem() }}-{{ $subscribers->lastItem() }} de {{ $subscribers->total() }} resultados</span>
                <nav>
                    {{ $subscribers->appends(request()->input())->links() }}
                </nav>
            </div>
        </div>
    </div>
@endsection


