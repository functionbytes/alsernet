@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Estilos de Estanterías (Stand Styles)'])

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
                                <span class="text-muted font-weight-bold">{{ $style->code }}</span>
                            </td>
                            <td>
                                <span class="text-muted font-weight-bold">{{ $style->name }}</span>
                            </td>
                            <td>
                                <span class="badge badge-light-info">{{ count($style->faces) }} caras</span>
                            </td>
                            <td>
                                <span class="badge badge-light-primary">{{ $style->default_levels }}</span>
                            </td>
                            <td>
                                <span class="badge badge-light-warning">{{ $style->default_sections }}</span>
                            </td>
                            <td>
                                <span class="badge badge-light-success">{{ $style->locations()->count() }}</span>
                            </td>
                            <td>
                                @if($style->available)
                                    <span class="badge badge-light-success">Disponible</span>
                                @else
                                    <span class="badge badge-light-danger">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <a href="javascript:void(0)" class="link" id="dropdownMenuButton{{ $loop->index }}"
                                       data-bs-toggle="dropdown"
                                       aria-expanded="false">
                                        <i data-feather="more-vertical"></i>
                                    </a>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $loop->index }}">
                                        <a class="dropdown-item" href="{{ route('manager.warehouse.styles.view', $style->uid) }}">
                                            <i class="fa-duotone fa-eye"></i> Ver
                                        </a>
                                        <a class="dropdown-item" href="{{ route('manager.warehouse.styles.edit', $style->uid) }}">
                                            <i class="fa-duotone fa-pencil"></i> Editar
                                        </a>
                                        <a class="dropdown-item" href="{{ route('manager.warehouse.styles.destroy', $style->uid) }}"
                                           onclick="return confirm('¿Eliminar este estilo?')">
                                            <i class="fa-duotone fa-trash text-danger"></i> Eliminar
                                        </a>
                                    </div>
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
