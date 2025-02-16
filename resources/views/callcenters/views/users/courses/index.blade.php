@extends('layouts.callcenters')

@section('content')

    @include('managers.includes.card', ['title' => 'Cursos'])

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
                        <th>Curso</th>
                        <th>Estado</th>
                        <th>Año</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach ($inscriptions as $key => $inscription)
                        <tr class="search-items">

                            <td>
                                <span class="usr-email-addr" data-email="{{ $inscription->course->title }}">{{ Str::words( Str::upper(Str::lower($inscription->course->title)), 12, '...')  }}</span>
                            </td>
                            <td>
                              <span class="badge {{ $inscription->culminated == 1 ? 'bg-light-primary' : 'bg-light-secondary' }} rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">

                                   {{ $inscription->culminated == 1 ? 'Culminado' : 'Pendiente' }}
                              </span>
                            </td>
                            <td>
                                <span class="usr-ph-no" data-phone="{{ date('Y', strtotime($inscription->enroll_start)) }}">{{ date('Y', strtotime($inscription->enroll_start)) }}</span>
                            </td>
                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('callcenter.users.courses.postpone', $inscription->uid) }}">Gestionar</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3 confirm-delete" data-href="{{ route('callcenter.users.courses.destroy', $inscription->uid) }}">Eliminar</a>
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
                <span>Mostrar {{ $inscriptions->firstItem() }}-{{ $inscriptions->lastItem() }} de {{ $inscriptions->total() }} resultados</span>
                <nav>
                    {{ $inscriptions->appends(request()->input())->links() }}
                </nav>
            </div>
        </div>
    </div>
@endsection




