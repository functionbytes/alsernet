@extends('layouts.callcenters')

@section('content')

    @include('managers.includes.card', ['title' => 'Usuarios'])

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
                                        <option value="admin" @isset($role) @if ($role=='admin') selected @endif @endisset>  Administrador</option>
                                        <option value="customer" @isset($role) @if ($role=='customer') selected @endif @endisset>  Cliente</option>
                                        <option value="enterprise" @isset($role) @if ($role=='enterprises') selected  @endif @endisset>  Empresa</option>
                                        <option value="enterprise" @isset($role) @if ($role=='distributors') selected  @endif @endisset>  Distribuidor</option>
                                        <option value="enterprise" @isset($role) @if ($role=='supports') selected  @endif @endisset>  Soporte</option>
                                        <option value="enterprise" @isset($role) @if ($role=='accountings') selected  @endif @endisset>  Contabilidad</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href=" {{ route('callcenter.users.create') }}" class="btn btn-primary">
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
                        <th>Identificación</th>
                        <th>Cliente</th>
                        <th>Correo electronico</th>
                        <th>Perfil</th>
                        <th>Fecha</th>
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
                                <span class="usr-email-addr" data-email="{{ $user->email }}">{{ $user->email }}</span>
                            </td>
                            <td>
                               <span class="badge {{ $user->role == 'manager' ? 'bg-light-primary' : 'bg-light-secondary' }} rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">

                                @if ($user->role == 'manager')
                                Administrador
                             @elseif($user->role == 'customer')
                                 Cliente
                             @elseif($user->role == 'enterprise')
                                 Empresa
                              @elseif($user->role == 'distributor')
                               Distribuidor
                              @elseif($user->role == 'support')
                                  Soporte
                              @elseif($user->role == 'accounting')
                                  Contabilidad
                             @endif
                               </span>
                            </td>

                            <td>
                                <span class="usr-ph-no" data-phone="{{ date('Y-m-d', strtotime($user->updated_at)) }}">{{ date('Y-m-d', strtotime($user->updated_at)) }}</span>
                            </td>
                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('callcenter.users.navegation', $user->uid) }}">
                                                Dashboard
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('callcenter.users.view', $user->uid) }}">
                                                Visualizar
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('callcenter.users.edit', $user->uid) }}">
                                                Editar
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3 confirm-delete" data-href="{{ route('callcenter.users.destroy', $user->uid) }}">
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
            <div class="result-body ">
                <span>Mostrar {{ $users->firstItem() }}-{{ $users->lastItem() }} de {{ $users->total() }} resultados</span>
                <nav>
                    {{ $users->appends(request()->input())->links() }}
                </nav>
            </div>
        </div>
    </div>
@endsection


