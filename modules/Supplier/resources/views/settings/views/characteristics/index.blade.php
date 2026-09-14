@extends('layouts.theme')

@section('title', 'Características')

@section('page_header')
    @include('core::components.card', ['title' => 'Características'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Cabecera --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1 fw-bold">Características</h5>
                        <p class="small mb-0 text-muted">
                            Caché local de características, valores y sus vínculos con modelos y variantes del ERP.
                            Última sincronización:
                            <strong>{{ $lastSync ? \Carbon\Carbon::parse($lastSync)->diffForHumans() : 'Nunca' }}</strong>
                        </p>
                    </div>
                    <button type="button" id="btn-sync-characteristics" class="btn btn-primary btn-sm">
                        Sincronizar desde ERP
                    </button>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Características</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['caracteristicas'] }}</h4>
                                <small class="text-muted">Características registradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Valores</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['valores'] }}</h4>
                                <small class="text-muted">Valores registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Vínculos por modelo</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['modelo'] }}</h4>
                                <small class="text-muted">Modelo ↔ Característica</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Vínculos por variante</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['variante'] }}</h4>
                                <small class="text-muted">Variante ↔ Valor</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <ul class="nav nav-pills user-profile-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $type === 'caracteristicas' ? 'active' : '' }}"
                       href="{{ route('settings.suppliers.characteristics.index') }}">
                        <span class="d-none d-md-block">Características</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $type === 'valores' ? 'active' : '' }}"
                       href="{{ route('settings.suppliers.characteristics.index') }}?type=valores">
                        <span class="d-none d-md-block">Valores</span>
                    </a>
                </li>
            </ul>

            {{-- Filtros --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.suppliers.characteristics.index') }}" id="filter-form">
                    <input type="hidden" name="type" value="{{ $type }}">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="flex-grow-1">
                            <input type="search" name="search" class="form-control"
                                   placeholder="Buscar por nombre..."
                                   value="{{ $search }}">
                        </div>

                        <button type="submit" class="btn btn-primary" title="Buscar">
                            <i class="fas fa-magnifying-glass"></i>
                        </button>

                        @if($search)
                            <a href="{{ route('settings.suppliers.characteristics.index') }}?type={{ $type }}"
                               class="btn btn-secondary" title="Limpiar filtros">
                                <i class="fas fa-xmark"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Listado --}}
            <div class="card-body">
                @if($items->count())
                    <div class="table-responsive">
                        <table class="table search-table align-middle text-nowrap">
                            <thead class="header-item">
                            <tr>
                                <th>Id Erp</th>
                                @if($type === 'caracteristicas')
                                    <th>Nombre</th>
                                @elseif($type === 'valores')
                                    <th>Nombre</th>
                                    <th>Característica</th>
                                @elseif($type === 'modelo')
                                    <th>Id modelo</th>
                                    <th>Característica</th>
                                    <th class="text-center">Orden</th>
                                @elseif($type === 'variante')
                                    <th>Id artículo</th>
                                    <th>Id modelo</th>
                                    <th>Característica</th>
                                    <th>Valor</th>
                                @endif
                                <th class="text-center">Estado</th>
                                <th class="text-center">Última sync</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td><span class="text-muted small">{{ $item->erp_id ?? '—' }}</span></td>
                                    @if($type === 'caracteristicas')
                                        <td><strong>{{ $item->nombre }}</strong></td>
                                    @elseif($type === 'valores')
                                        <td><strong>{{ $item->nombre }}</strong></td>
                                        <td>{{ $item->characteristic?->nombre ?? '—' }}</td>
                                    @elseif($type === 'modelo')
                                        <td>{{ $item->erp_model_id }}</td>
                                        <td>{{ $item->characteristic?->nombre ?? '—' }}</td>
                                        <td class="text-center">{{ $item->orden }}</td>
                                    @elseif($type === 'variante')
                                        <td>{{ $item->erp_article_id }}</td>
                                        <td>{{ $item->erp_model_id }}</td>
                                        <td>{{ $item->characteristic?->nombre ?? '—' }}</td>
                                        <td>{{ $item->value?->nombre ?? '—' }}</td>
                                    @endif
                                    <td class="text-center">
                                        @if($item->estado)
                                            <span class="badge bg-primary-subtle text-primary">Activa</span>
                                        @else
                                            <span class="badge bg-light text-dark">Inactiva</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="text-muted small">
                                            {{ $item->last_sync_at?->format('d/m/Y H:i') ?? '—' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-folder-open fa-2x mb-3 d-block"></i>
                        <h6 class="mb-0">No hay datos sincronizados todavía</h6>
                    </div>
                @endif
            </div>

            @if($items->count())
                <div class="card-footer bg-white border-top py-2">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted small">
                                Mostrando {{ $items->firstItem() }}–{{ $items->lastItem() }} de {{ $items->total() }}
                            </span>
                            <form method="GET" action="{{ route('settings.suppliers.characteristics.index') }}" class="d-inline-flex align-items-center gap-1 mb-0">
                                @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endforeach
                                <label class="text-muted small mb-0">Por página:</label>
                                <select name="per_page" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                    @foreach([10, 20, 50, 100, 200] as $opt)
                                        <option value="{{ $opt }}" {{ $perPage == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                        @if($items->hasPages())
                            <nav>{{ $items->appends(request()->query())->links('pagination::bootstrap-5') }}</nav>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(function () {
        const csrf = $('meta[name="csrf-token"]').attr('content');

        $('#btn-sync-characteristics').on('click', function () {
            Swal.fire({
                title: '¿Sincronizar características?',
                text: 'El proceso se ejecutará en segundo plano.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, ejecutar',
                cancelButtonText: 'Cancelar',
            }).then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: '{{ route("settings.suppliers.characteristics.sync") }}',
                    method: 'POST',
                    dataType: 'json',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                })
                .done(function (data) {
                    if (!data.success) {
                        toastr.error(data.message || 'Error al iniciar la sincronización');
                        return;
                    }
                    toastr.info('Sincronización iniciada. Batch ID: ' + data.batch_id);
                    pollSyncProgress(data.batch_id);
                })
                .fail(function (xhr) {
                    toastr.error('Error: ' + (xhr.responseJSON?.message || xhr.statusText));
                });
            });
        });

        function pollSyncProgress(batchId) {
            const progressUrl = '{{ route("settings.suppliers.sync.progress", ["batchId" => "__BATCH_ID__"]) }}'
                .replace('__BATCH_ID__', batchId);

            const interval = setInterval(function () {
                $.getJSON(progressUrl, function (data) {
                    if (!data.success) return;

                    const status = data.batch.status;
                    if (status !== 'completed' && status !== 'failed' && status !== 'cancelled') return;

                    clearInterval(interval);
                    status === 'completed'
                        ? toastr.success('Sincronización completada.')
                        : toastr.error('La sincronización terminó con estado: ' + status);
                    setTimeout(() => location.reload(), 1200);
                });
            }, 3000);
        }
    });
    </script>
@endpush
