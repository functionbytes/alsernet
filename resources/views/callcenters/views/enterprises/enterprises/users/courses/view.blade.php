@extends('layouts.callcenters')

@section('content')

    @include('distributors.includes.card', ['title' => "Usuarios - " . $course->title ])

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
                            </div> <div class="col-auto">
                                <div class="input-group">
                                    <select class="form-select select2" name="culminated" data-minimum-results-for-search="Infinity">
                                        <option value="">Seleccionar estado</option>
                                        <option value="1" @isset($culminated) @if ($culminated==1) selected @endif @endisset>  Culminado </option>
                                        <option value="0" @isset($culminated) @if ($culminated==0) selected  @endif @endisset>  Pendiente</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
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
                        <th>Identificación</th>
                        <th>Cliente</th>
                        <th>Año</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach ($users as $key => $user)
                        <tr class="search-items">
                            <td>
                                <span class="usr-email-addr" data-email="{{ $user->identification }}">{{ ucfirst($user->identification) }}</span>
                            </td>
                            <td>
                                <span class="usr-email-addr" data-email="{{ $user->firstname . ' ' . $user->lastname }}">{{ Str::words( Str::upper(Str::lower($user->firstname . ' ' . $user->lastname)), 12, '...')  }}</span>
                            </td>
                            <td>
                                <span class="usr-email-addr" data-email="{{ date(' Y', strtotime($user->culminated_at)) }}">{{ date(' Y', strtotime($user->culminated_at)) }}</span>
                            </td>
                            <td>
                                    <span class="badge {{ $user->culminated == 1 ? 'bg-light-primary' : 'bg-light-secondary' }} rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">
                                        {{ $user->culminated == 1 ? 'Culminado' : 'Pendiente' }}
                                    </span>
                            </td>
                            <td class="text-center">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <li class="{{ $user->culminated == 1 ? '' : 'd-none'}}">
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('callcenter.certificate.user', $user->uid) }}">
                                                <i class="fa-duotone fa-money-check-pen"></i>
                                                Certificado
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item  d-flex align-items-center gap-3" href="{{ route('callcenter.courses.details', $user->uid) }}">
                                                <i class="fa-light fa-file-certificate"></i>
                                                Detalle
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('callcenter.courses.progress', $user->uid) }}">
                                                <i class="fa-duotone fa-money-check-pen"></i>
                                                Reporte
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
                <span>Mostrar {{ $users->firstItem() }}-{{ $users->lastItem() }} de {{ $users->total() }} resultados</span>
                <nav>
                    {{ $users->appends(request()->input())->links() }}
                </nav>
            </div>
        </div>
    </div>

@endsection


