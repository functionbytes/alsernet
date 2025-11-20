@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Posiciones de Inventario (Inventory Slots)'])

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
                                               name="search" placeholder="Buscar por código de barras o stand"
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
                                <a href="{{ route('manager.warehouse.slots.create') }}" class="btn btn-primary">
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
                        <th>Código de Barras</th>
                        <th>Stand</th>
                        <th>Cara</th>
                        <th>Nivel</th>
                        <th>Sección</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Peso</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($slots as $slot)
                        <tr>
                            <td>
                                <span class="text-muted font-weight-bold">{{ $slot->barcode ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <a href="{{ route('manager.warehouse.locations.view', $slot->stand->uid) }}">
                                    {{ $slot->stand->code }}
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-light-info">{{ ucfirst($slot->face) }}</span>
                            </td>
                            <td>
                                <span class="badge badge-light-primary">{{ $slot->level }}</span>
                            </td>
                            <td>
                                <span class="badge badge-light-warning">{{ $slot->section }}</span>
                            </td>
                            <td>
                                @if($slot->product)
                                    <a href="#">{{ $slot->product->title }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span>{{ $slot->quantity }} / {{ $slot->max_quantity ?? '∞' }}</span>
                            </td>
                            <td>
                                <span>{{ $slot->weight_current }} / {{ $slot->weight_max ?? '∞' }} kg</span>
                            </td>
                            <td>
                                @if($slot->is_occupied)
                                    <span class="badge badge-light-success">Ocupada</span>
                                @else
                                    <span class="badge badge-light-secondary">Disponible</span>
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
                                        <a class="dropdown-item" href="{{ route('manager.warehouse.slots.view', $slot->uid) }}">
                                            <i class="fa-duotone fa-eye"></i> Ver
                                        </a>
                                        <a class="dropdown-item" href="{{ route('manager.warehouse.slots.edit', $slot->uid) }}">
                                            <i class="fa-duotone fa-pencil"></i> Editar
                                        </a>
                                        <a class="dropdown-item" href="{{ route('manager.warehouse.slots.destroy', $slot->uid) }}"
                                           onclick="return confirm('¿Eliminar esta posición?')">
                                            <i class="fa-duotone fa-trash text-danger"></i> Eliminar
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                No hay posiciones de inventario registradas
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($slots->hasPages())
            <div class="d-flex justify-content-center">
                {{ $slots->links() }}
            </div>
        @endif

    </div>

@endsection
