@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Estanterías (locations)'])

    <div class="widget-content searchable-container list">

        <div class="card card-body">
            <div class="row">
                <div class="col-md-12 col-xl-12">
                    <form class="position-relative form-search" action="{{ request()->fullUrl() }}" method="GET">
                        <div class="row justify-content-between g-2">
                            <div class="col-auto flex-grow-1">
                                <div class="tt-search-box">
                                    <div class="input-group">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-2"> <i data-feather="search"></i></span>
                                        <input class="form-control rounded-start w-100" type="text" id="search" name="search" placeholder="Buscar por código o barcode" @isset($search) value="{{ $search }}" @endisset>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <select class="form-select select2" name="floor_id" data-minimum-results-for-search="Infinity">
                                    <option value="">Filtrar por piso</option>
                                    @foreach($floors as $floor)
                                        <option value="{{ $floor->id }}" @if(request('floor_id') == $floor->id) selected @endif>
                                            {{ $floor->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <select class="form-select select2" name="stand_style_id" data-minimum-results-for-search="Infinity">
                                    <option value="">Filtrar por estilo</option>
                                    @foreach($styles as $style)
                                        <option value="{{ $style->id }}" @if(request('stand_style_id') == $style->id) selected @endif>
                                            {{ $style->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('manager.warehouse.locations.create') }}" class="btn btn-primary">
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
                        <th>Piso</th>
                        <th>Estilo</th>
                        <th>Posición</th>
                        <th>Posiciones/Ocupación</th>
                        <th>Capacidad</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>

                    @forelse ($locations as $stand)
                        <tr class="search-items">
                            <td>
                                <span class="usr-email-addr"><strong>{{ $stand->code }}</strong></span>
                            </td>
                            <td>
                                <span class="badge bg-light-primary">{{ $stand->floor?->code }}</span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $stand->style?->name }}</span>
                            </td>
                            <td>
                                <small class="text-muted">X: {{ $stand->position_x }}, Y: {{ $stand->position_y }}</small>
                            </td>
                            <td>
                                <span class="badge bg-light-warning">{{ $stand->getOccupiedSlots() }}/{{ $stand->getTotalSlots() }}</span>
                                <div class="progress mt-2" style="height: 15px; width: 100px;">
                                    <div class="progress-bar bg-success" style="width: {{ $stand->getOccupancyPercentage() }}%"></div>
                                </div>
                            </td>
                            <td>
                                @if($stand->capacity)
                                    <div class="progress mt-2" style="height: 15px; width: 100px;">
                                        <div class="progress-bar {{ $stand->isNearCapacity(80) ? 'bg-warning' : 'bg-success' }}"
                                             style="width: {{ min(($stand->getCurrentWeight() / $stand->capacity) * 100, 100) }}%">
                                        </div>
                                    </div>
                                    <small>{{ round($stand->getCurrentWeight(), 1) }}/{{ round($stand->capacity, 1) }} kg</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $stand->available ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }} rounded-3 py-2">
                                    {{ $stand->available ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('manager.warehouse.locations.view', $stand->uid) }}">
                                                <i class="ti ti-eye"></i> Ver
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('manager.warehouse.locations.edit', $stand->uid) }}">
                                                <i class="ti ti-pencil"></i> Editar
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3 confirm-delete" data-href="{{ route('manager.warehouse.locations.destroy', $stand->uid) }}">
                                                <i class="ti ti-trash"></i> Eliminar
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No hay estanterías registradas
                            </td>
                        </tr>
                    @endforelse

                    </tbody>
                </table>
            </div>

            @if ($locations->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div>
                        Mostrando {{ $locations->firstItem() }} a {{ $locations->lastItem() }} de {{ $locations->total() }} estanterías
                    </div>
                    <div>
                        {{ $locations->links() }}
                    </div>
                </div>
            @endif
        </div>

    </div>

@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.confirm-delete').forEach(el => {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('¿Está seguro de que desea eliminar esta estantería? Debe estar vacía.')) {
                    window.location.href = this.dataset.href;
                }
            });
        });
    </script>
@endpush
