@extends('layouts.managers')

@section('content')
    @include('managers.includes.card', ['title' => "Estanterías piso - {$floor->name}"])

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
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('manager.warehouse.locations.print-all', [$warehouse->uid, $floor->uid]) }}" class="btn btn-info" title="Imprimir todos los códigos de barras del piso">
                                    <i class="fa-duotone fa-barcode"></i>
                                </a>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('manager.warehouse.locations.transfer.bulk', [$warehouse->uid, $floor->uid]) }}" class="btn btn-primary" title="Trasladar múltiples ubicaciones a otro piso">
                                    <i class="fa-duotone fa-arrow-right"></i>
                                </a>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('manager.warehouse.locations.create', [$warehouse->uid, $floor->uid]) }}" class="btn btn-info">
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
                        <th>Posición x</th>
                        <th>Posición y</th>
                        <th>Secciones</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>

                    @forelse ($locations as $location)
                        <tr class="search-items">
                            <td>
                                <span class="usr-email-addr"><strong>{{ $location->code }}</strong></span>
                            </td>
                            <td>
                                <span class="usr-email-addr">{{ $location->floor?->code }}</span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $location->style?->name }}</span>
                            </td>
                            <td>
                                <small class="text-muted">{{ $location->position_x }}</small>
                            </td>
                            <td>
                                <small class="text-muted">{{ $location->position_y }}</small>
                            </td>
                            <td>
                                <span class="badge bg-light-primary text-primary">{{ $location->total_levels }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $location->available ? 'bg-light-success text-success' : 'bg-light-primary text-primary' }} rounded-3 py-2">
                                    {{ $location->available ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('manager.warehouse.locations.view', [$warehouse->uid, $floor->uid, $location->uid]) }}">
                                                Ver
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('manager.warehouse.locations.edit', [$warehouse->uid, $floor->uid, $location->uid] ) }}">
                                                Editar
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('manager.warehouse.locations.print', [$warehouse->uid, $floor->uid, $location->uid]) }}" target="_blank">
                                                Imprimir códigos
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('manager.warehouse.locations.transfer', [$warehouse->uid, $floor->uid, $location->uid]) }}">
                                                Trasladar a otro piso
                                            </a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-3 confirm-delete" data-href="{{ route('manager.warehouse.locations.destroy', [$warehouse->uid, $floor->uid, $location->uid]) }}">
                                                Eliminar
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
