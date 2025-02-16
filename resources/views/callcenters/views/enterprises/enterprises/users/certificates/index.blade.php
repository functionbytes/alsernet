@extends('layouts.callcenters')

@section('content')

    @include('distributors.includes.card', ['title' => 'Certificados - ' . $user->firstname .' '. $user->lastname])

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
                                    <select class="form-select select2" name="course" data-minimum-results-for-search="Infinity">
                                        <option value="">Seleccionar curso</option>
                                        @foreach($courses as $item)
                                            <option value="{{ $item->id }}" @if (isset($course) && $course ==  $item->id ) selected @endif>{{ $item->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>
                            @if (count($certificates) > 1)
                                <div class="col-auto">
                                    <a href=" {{ route('distributors.certificate.broad', $user->uid) }}" class="btn btn-primary">
                                        <i class="fa-light fa-file-certificate"></i>
                                    </a>
                                </div>
                             @endif
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
                        <th>Año</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach ($certificates as $key => $certificate)
                        <tr class="search-items">

                            <td>
                                <span class="usr-email-addr" data-email="{{ $certificate->course->title }}">{{ Str::words( Str::upper(Str::lower($certificate->course->title)), 12, '...')  }}</span>
                            </td>
                            <td>
                                <span class="usr-ph-no" data-phone="{{ date('Y', strtotime($certificate->start_at)) }}">{{ date('Y', strtotime($certificate->start_at)) }}</span>
                            </td>
                            <td>
                                <span class="usr-ph-no" data-phone="{{ date('Y-m-d', strtotime($certificate->updated_at)) }}">{{ date('Y-m-d', strtotime($certificate->updated_at)) }}</span>
                            </td>
                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('callcenter.enterprises.users.certificate.course', $certificate->uid) }}">Descargar</a>
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
                <span>Mostrar {{ $certificates->firstItem() }}-{{ $certificates->lastItem() }} de {{ $certificates->total() }} resultados</span>
                <nav>
                    {{ $certificates->appends(request()->input())->links() }}
                </nav>
            </div>
        </div>
    </div>
@endsection

