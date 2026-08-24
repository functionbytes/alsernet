@extends('layouts.theme')

@section('title', $pageTitle)

@section('page_header')
    @include('core::components.card', ['title' => $pageTitle])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">{{ $pageTitle }}</h5>
                        <p class="small mb-0 text-muted">PDFs generados, con acceso a volver a descargarlos</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('giftmessage.index') }}" class="btn btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-3">
                        <a href="{{ route('giftmessage.history.index', array_merge(request()->except(['type', 'page']), [])) }}"
                           class="text-decoration-none d-block h-100">
                            <div class="card bg-light-secondary stat-card h-100 {{ !request('type') ? 'stat-card-active' : '' }}">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Total</h6>
                                    <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                    <small class="text-muted">Generaciones registradas</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-3">
                        <a href="{{ route('giftmessage.history.index', array_merge(request()->except(['type', 'page']), ['type' => 'card'])) }}"
                           class="text-decoration-none d-block h-100">
                            <div class="card bg-light-secondary stat-card h-100 {{ request('type') === 'card' ? 'border-success border-2' : '' }}">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Tarjetas</h6>
                                    <h4 class="mb-1 fw-bold">{{ $stats['cards'] }}</h4>
                                    <small class="text-muted">Tipo tarjeta</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-3">
                        <a href="{{ route('giftmessage.history.index', array_merge(request()->except(['type', 'page']), ['type' => 'envelope'])) }}"
                           class="text-decoration-none d-block h-100">
                            <div class="card bg-light-secondary stat-card h-100 {{ request('type') === 'envelope' ? 'border-success border-2' : '' }}">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Sobres</h6>
                                    <h4 class="mb-1 fw-bold">{{ $stats['envelopes'] }}</h4>
                                    <small class="text-muted">Tipo sobre</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Hoy</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['today'] }}</h4>
                                <small class="text-muted">Generadas hoy</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $activeFilterCount = collect(['type', 'date_from', 'date_to'])->filter(fn ($key) => request($key))->count();
                $hasAnyFilter = $activeFilterCount > 0 || request('search');
            @endphp

            <!-- Search and Filters Section -->
            <div class="card-body border-bottom">
                <form id="history-filter-form" method="GET" action="{{ route('giftmessage.history.index') }}">
                    <input type="hidden" name="type" id="filter-type" value="{{ request('type') }}">
                    <input type="hidden" name="date_from" id="filter-date-from" value="{{ request('date_from') }}">
                    <input type="hidden" name="date_to" id="filter-date-to" value="{{ request('date_to') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por pedido o generado por..."
                               value="{{ request('search') }}">

                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#filterModal"
                                title="Filtros avanzados">
                            <i class="fas fa-sliders"></i>
                            @if($activeFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"
                                      style="font-size: 0.6rem;">{{ $activeFilterCount }}</span>
                            @endif
                        </button>

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($hasAnyFilter)
                                <a href="{{ route('giftmessage.history.index') }}" class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($activeFilterCount > 0)
                        <div class="d-flex gap-2 flex-wrap mt-2">
                            @if(request('type'))
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                    Tipo: {{ request('type') === 'card' ? 'Tarjeta' : 'Sobre' }}
                                </span>
                            @endif
                            @if(request('date_from') || request('date_to'))
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                    Fechas: {{ request('date_from') ?: '…' }} → {{ request('date_to') ?: '…' }}
                                </span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            <div class="card-body">
                @if($generations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Tipo</th>
                                    <th>Generado por</th>
                                    <th>Pedidos</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($generations as $generation)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $generation->id }}">
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                {{ $generation->type === 'card' ? 'Tarjeta' : 'Sobre' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small">
                                                {{ $generation->generatedBy ? trim($generation->generatedBy->firstname.' '.$generation->generatedBy->lastname) : 'Sistema' }}
                                            </div>
                                        </td>
                                        <td>
                                            @if(!empty($generation->order_numbers))
                                                <div class="d-flex flex-wrap gap-1" style="max-width:240px;">
                                                    @foreach(array_slice($generation->order_numbers, 0, 4) as $orderNumber)
                                                        <span class="badge bg-secondary-subtle text-secondary" title="Numero de pedido">{{ $orderNumber }}</span>
                                                    @endforeach
                                                    @if(count($generation->order_numbers) > 4)
                                                        <span class="badge bg-light text-dark"
                                                              title="{{ implode(', ', array_slice($generation->order_numbers, 4)) }}">
                                                            +{{ count($generation->order_numbers) - 4 }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="small text-muted">{{ $generation->rows_count }} pedido(s)</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small">{{ $generation->created_at->format('d/m/Y H:i') }}</div>
                                            <small class="text-muted">{{ $generation->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('giftmessage.history.view', $generation) }}" target="_blank" rel="noopener">Visualizar</a></li>
                                                    <li><a class="dropdown-item" href="{{ route('giftmessage.history.download', $generation) }}">Descargar</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-url="{{ route('giftmessage.history.destroy', $generation) }}"
                                                           data-title="Eliminar esta generacion">
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
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-file-pdf fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if($hasAnyFilter)
                                No se encontraron resultados
                            @else
                                Aun no hay generaciones
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if($hasAnyFilter)
                                Prueba a cambiar o limpiar los filtros
                            @else
                                Los PDFs generados apareceran aqui
                            @endif
                        </p>
                        @if($hasAnyFilter)
                            <a href="{{ route('giftmessage.history.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            <div class="card-footer bg-white border-top py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        @if($generations->total() > 0)
                            <span class="text-muted small">
                                Mostrando {{ $generations->firstItem() }}–{{ $generations->lastItem() }} de {{ $generations->total() }}
                            </span>
                        @endif
                        <form method="GET" action="{{ route('giftmessage.history.index') }}" class="d-inline-flex align-items-center gap-1 mb-0">
                            @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $v)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <label class="text-muted small mb-0">Por página:</label>
                            <select name="per_page" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                @foreach([10, 20, 50, 100, 200] as $opt)
                                    <option value="{{ $opt }}" {{ request('per_page', 10) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    @if($generations->hasPages())
                        <nav>{{ $generations->appends(request()->query())->links() }}</nav>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('giftmessage.history.index') }}" id="filterModalForm">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">

                    <div class="modal-header">
                        <h5 class="modal-title">Filtros avanzados</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <select name="type" class="form-select">
                                <option value="">Todos</option>
                                <option value="envelope" {{ request('type') === 'envelope' ? 'selected' : '' }}>Sobre</option>
                                <option value="card" {{ request('type') === 'card' ? 'selected' : '' }}>Tarjeta</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rango de fechas</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                                <span class="text-muted">—</span>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Aplicar filtros</button>
                        <a href="{{ route('giftmessage.history.index') }}" class="btn btn-secondary w-100">Limpiar filtros</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Eliminar
        </button>
    </div>

    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar generaciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-0">Se eliminaran <strong><span data-bulk-count>0</span> generacion(es)</strong> y sus PDFs asociados. Esta accion no se puede deshacer.</p>
                </div>
                <div class="modal-footer flex-column">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-2">Eliminar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-apply-btn').prop('disabled', false).text('Eliminar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const ids = bulk.getIds();

        if (!ids.length) { toastr.warning('Selecciona al menos una generacion.'); return; }
        if (!confirm('¿Eliminar las ' + ids.length + ' generacion(es)?')) return;

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('giftmessage.history.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action: 'delete', ids: ids }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                $('#bulk-modal').modal('hide');
                toastr.success('Generaciones eliminadas correctamente.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Eliminar');
            },
        });
    });

    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
        $('#delete-modal').modal('show');
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
});
</script>
@endpush
