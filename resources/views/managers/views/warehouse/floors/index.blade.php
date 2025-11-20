@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Pisos'])

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
                                <a href="{{ route('manager.warehouse.floors.create' , $warehouse->uid) }}" class="btn btn-primary">
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
                        <th>Estanterías</th>
                        <th>Posiciones</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>

                    @forelse ($floors as $floor)
                        <tr class="search-items">
                            <td>
                                <span class="usr-email-addr"><strong>{{ $floor->code }}</strong></span>
                            </td>
                            <td>
                                <span class="usr-email-addr">{{ $floor->name }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light-secondary rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">{{ $floor->getAvailableLocationCount() }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light-secondary rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">{{ $floor->getTotalSlotsCount() }}</span>
                            </td>

                            <td>
                                <span class="badge {{ $floor->available ? 'bg-light-secondary text-primary' : 'bg-light-secondary text-primary' }} rounded-3 py-2">
                                    {{ $floor->available ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown"
                                       aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">

                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3"
                                               href="{{ route('manager.warehouse.locations', [$warehouse->uid, $floor->uid,]) }}">
                                                Ubicaciones
                                            </a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3"
                                               href="{{ route('manager.warehouse.floors.view', [$warehouse->uid, $floor->uid]) }}">
                                                Ver
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3"
                                               href="{{ route('manager.warehouse.floors.edit' ,[$warehouse->uid, $floor->uid]) }}">
                                                Editar
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3 confirm-delete" data-href="{{ route('manager.warehouse.floors.destroy', [$warehouse->uid, $floor->uid]) }}">
                                                Eliminar
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center py-4 text-muted">
                                No hay pisos registrados
                            </td>
                        </tr>
                    @endforelse

                    </tbody>
                </table>
            </div>

            @if ($floors->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div>
                        Mostrando {{ $floors->firstItem() }} a {{ $floors->lastItem() }} de {{ $floors->total() }} pisos
                    </div>
                    <div>
                        {{ $floors->links() }}
                    </div>
                </div>
            @endif
        </div>

    </div>

@endsection

@push('scripts')
@endpush
