@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Estilos de estanterías'])

    <div class="widget-content searchable-container list">

        <div class="card card-body">
            <div class="row">
                <div class="col-md-12 col-xl-12">
                    <form class="position-relative form-search" action="{{ request()->fullUrl() }}" method="GET">
                        <div class="row justify-content-between g-2 ">
                            <div class="col-auto flex-grow-1">
                                <div class="tt-search-box">
                                    <div class="input-group">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-2"> <i
                                                    data-feather="search"></i></span>
                                        <input class="form-control rounded-start w-100" type="text" id="search"
                                               name="search" placeholder="Buscar por código o nombre"
                                               @isset($search) value="{{ $search }}" @endisset>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip"
                                        data-bs-placement="top" data-bs-original-title="Buscar">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('manager.warehouse.styles.create') }}" class="btn btn-primary">
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
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Caras</th>
                        <th>Niveles</th>
                        <th>Secciones</th>
                        <th>Estanterías</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($styles as $style)
                        <tr>
                            <td>
                                <span class="text-muted font-weight-bold">{{ $style->code }}
                            </td>
                            <td>
                                <span class="text-muted font-weight-bold">{{ $style->name }}
                            </td>
                            <td>
                                {{ count($style->faces) }} caras
                            </td>
                            <td>
                                {{ $style->default_levels }}
                            </td>
                            <td>
                                {{ $style->default_sections }}
                            </td>
                            <td>
                                {{ $style->locations()->count() }}
                            </td>
                            <td>
                                <span class="badge {{ $style->available ? 'bg-light-secondary text-primary' : 'bg-light-secondary text-primary' }} rounded-3 py-2">
                                    {{ $style->available ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>

                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" id="dropdownMenuButton{{ $loop->index }}" data-bs-toggle="dropdown"
                                       aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $loop->index }}">

                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3"
                                               href="{{ route('manager.warehouse.styles.view', $style->uid) }}">
                                                Ver
                                            </a>
                                        </li>

                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3"
                                               href="{{ route('manager.warehouse.styles.edit', $style->uid) }}">
                                                Editar
                                            </a>
                                        </li>

                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3 confirm-delete"
                                               data-href="{{ route('manager.warehouse.styles.destroy', $style->uid) }}">
                                                Eliminar
                                            </a>
                                        </li>

                                    </ul>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No hay estilos registrados
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($styles->hasPages())
            <div class="d-flex justify-content-center">
                {{ $styles->links() }}
            </div>
        @endif

    </div>

@endsection
