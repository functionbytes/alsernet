@extends('layouts.managers')

@section('content')
    @include('managers.includes.card', ['title' => 'Permisos'])

    <div class="widget-content searchable-container list">
        <div class="card card-body">
            <div class="row">
                <div class="col-md-12 col-xl-12">
                    <form class="position-relative form-search" action="{{ request()->fullUrl() }}" method="GET">
                        <div class="row justify-content-between g-2">
                            <div class="col-auto flex-grow-1">
                                <div class="tt-search-box">
                                    <div class="input-group">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-2">
                                            <i data-feather="search"></i>
                                        </span>
                                        <input class="form-control rounded-start w-100" type="text" name="search" placeholder="Buscar permiso" value="{{ request('search') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('manager.permissions.create') }}" class="btn btn-primary">
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
                        <th>Permiso</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($permissions as $permission)
                        <tr>
                            <td>{{ $permission->name }}</td>
                            <td>{{ $permission->created_at->format('Y-m-d') }}</td>
                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        @can('permissions.edit')
                                            <li>
                                                <a href="{{ route('manager.permissions.edit', $permission->id) }}" class="dropdown-item">
                                                    Editar
                                                </a>
                                            </li>
                                        @endcan

                                        @can('permissions.delete')
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item confirm-delete"
                                                   data-href="{{ route('manager.permissions.destroy', $permission->id) }}">
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
            <div class="result-body">
                <span>Mostrar {{ $permissions->firstItem() }}-{{ $permissions->lastItem() }} de {{ $permissions->total() }} resultados</span>
                <nav>
                    {{ $permissions->appends(request()->input())->links() }}
                </nav>
            </div>
        </div>
    </div>
@endsection
