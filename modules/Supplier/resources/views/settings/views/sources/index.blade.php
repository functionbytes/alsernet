@extends('layouts.theme')

@section('title', 'Fuentes de Datos - ' . $supplier->label)

@section('content')

    @include('core::components.card', ['title' => 'Fuentes de Datos - ' . $supplier->label])



    <div class="widget-content searchable-container list">

        @include('core::components.alerts')


        <!-- Sources Card -->
        <div class="card">

            <!-- Header -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Fuentes de datos</h5>
                        <p class="small mb-0 text-muted">Gestiona las fuentes de productos para {{ $supplier->label }}</p>
                    </div>

                    <div class="dropdown">
                        <button class="link text-dark p-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-vertical me-1"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('settings.suppliers.sources.create', $supplier->uid) }}">
                                    Nueva fuente
                                </a>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total de fallos</h6>
                                <h2 class="fw-bold mb-1">{{ $stats['total_failures'] }}</h2>
                                <small class="text-muted">Errores registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Reintentables</h6>
                                <h2 class="fw-bold mb-1">{{ $stats['retryable'] }}</h2>
                                <small class="text-muted">Fallos que se pueden reintentar</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Conflictos totales</h6>
                                <h2 class="fw-bold mb-1">{{ $stats['total_conflicts'] }}</h2>
                                <small class="text-muted">Conflictos detectados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Sin resolver</h6>
                                <h2 class="fw-bold mb-1">{{ $stats['unresolved_conflicts'] }}</h2>
                                <small class="text-muted">Conflictos pendientes</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="card-body border-bottom">
                @php
                    $activeFilterCount = collect(['source_type', 'platform', 'is_active'])->filter(fn($k) => request($k) !== null && request($k) !== '')->count();
                    $hasAnyFilter      = $activeFilterCount > 0 || request('search');
                @endphp
                <form id="sources-filter-form" method="GET" action="{{ route('settings.suppliers.sources.index', $supplier->uid) }}">
                    <input type="hidden" name="source_type" id="filter-source-type" value="{{ request('source_type') }}">
                    <input type="hidden" name="platform"    id="filter-platform"    value="{{ request('platform') }}">
                    <input type="hidden" name="is_active"   id="filter-is-active"   value="{{ request('is_active') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por nombre o descripción..."
                               value="{{ request('search') }}">

                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#sources-filter-modal" title="Filtros avanzados">
                            <i class="fas fa-filter"></i>
                            @if($activeFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"
                                      style="font-size:0.6rem;">{{ $activeFilterCount }}</span>
                            @endif
                        </button>

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($hasAnyFilter)
                                <a href="{{ route('settings.suppliers.sources.index', $supplier->uid) }}"
                                   class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($activeFilterCount > 0)
                        @php
                            $typeLabels = [
                                'website' => 'Sitio Web', 'ftp' => 'FTP', 'sftp' => 'SFTP',
                                'api' => 'API REST', 'upload' => 'Carga Manual', 'file' => 'Archivo',
                            ];
                            $platformLabels = [
                                'shopify' => 'Shopify', 'woocommerce' => 'WooCommerce', 'prestashop' => 'PrestaShop',
                                'magento' => 'Magento', 'joomla' => 'Joomla', 'wordpress' => 'WordPress',
                                'nextjs' => 'Next.js', 'custom' => 'Custom', 'blocked' => 'Bloqueados',
                            ];
                        @endphp
                        <div class="d-flex gap-2 flex-wrap mt-4">
                            <div><h6 class="mb-1">Filtrados:</h6></div>
                            @if(request('source_type'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Tipo: {{ $typeLabels[request('source_type')] ?? request('source_type') }}
                                </span>
                            @endif
                            @if(request('platform'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Plataforma: {{ $platformLabels[request('platform')] ?? request('platform') }}
                                </span>
                            @endif
                            @if(request('is_active') !== null && request('is_active') !== '')
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Estado: {{ request('is_active') === '1' ? 'Activo' : 'Inactivo' }}
                                </span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            <!-- Sources List -->
            <div class="card-body">
                @if($sources->count() > 0)
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 fw-bold">Listado de fuentes</h6>
                            <p class="text-muted small mb-0">{{ $sources->total() }} fuentes encontradas</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Descripción</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Confianza</th>
                                    <th class="text-center">Prioridad</th>
                                    <th>Última conexión</th>
                                    <th>Procesamiento</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sources as $source)
                                    @php
                                        $typeBadges = [
                                            'website' => ['text' => 'Web',     'color' => 'info'],
                                            'ftp'     => ['text' => 'FTP',     'color' => 'warning'],
                                            'sftp'    => ['text' => 'SFTP',    'color' => 'warning'],
                                            'api'     => ['text' => 'API',     'color' => 'primary'],
                                            'upload'  => ['text' => 'Upload',  'color' => 'secondary'],
                                            'file'    => ['text' => 'Archivo', 'color' => 'secondary'],
                                        ];
                                        $badge = $typeBadges[$source->source_type] ?? ['text' => $source->source_type, 'color' => 'light'];
                                        $platformBadges = [
                                            'shopify'     => ['text' => 'Shopify',     'color' => 'success'],
                                            'woocommerce' => ['text' => 'WooCommerce', 'color' => 'primary'],
                                            'prestashop'  => ['text' => 'PrestaShop',  'color' => 'warning'],
                                            'magento'     => ['text' => 'Magento',     'color' => 'danger'],
                                            'joomla'      => ['text' => 'Joomla',      'color' => 'secondary'],
                                            'wordpress'   => ['text' => 'WordPress',   'color' => 'info'],
                                            'nextjs'      => ['text' => 'Next.js',     'color' => 'dark'],
                                            'custom'      => ['text' => 'Custom',      'color' => 'secondary'],
                                            'blocked'     => ['text' => 'Bloqueado',   'color' => 'danger'],
                                        ];
                                        $platBadge   = $source->platform ? ($platformBadges[$source->platform] ?? ['text' => $source->platform, 'color' => 'light']) : null;
                                        $trustColors = ['high' => 'success', 'medium' => 'warning', 'low' => 'danger'];
                                        $trustColor  = $trustColors[$source->trust_level] ?? 'secondary';
                                        $batchStatus = $source->last_batch_status;
                                    @endphp
                                    <tr data-source-uid="{{ $source->uid }}">

                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $source->id }}"></td>

                                        <td>
                                            <strong>{{ $source->label }}</strong>
                                        </td>

                                        <td>
                                            <span class="badge bg-{{ $badge['color'] }}">{{ $badge['text'] }}</span>
                                            @if($platBadge)
                                                <span class="badge bg-{{ $platBadge['color'] }} ms-1">{{ $platBadge['text'] }}</span>
                                            @endif
                                        </td>

                                        <td>
                                            @if($source->description)
                                                <small class="text-muted">{{ Str::limit($source->description, 50) }}</small>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            @if($source->is_active)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-info-subtle text-info">Inactivo</span>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            <span class="badge bg-{{ $trustColor }}-subtle text-{{ $trustColor }}">
                                                {{ ucfirst($source->trust_level) }}
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <span class="badge bg-light-secondary text-info">{{ $source->priority }}</span>
                                        </td>

                                        <td>
                                            @if($source->last_accessed_at)
                                                <small class="text-muted">{{ $source->last_accessed_at->format('d/m/Y H:i') }}</small>
                                            @else
                                                <small class="text-muted">Nunca</small>
                                            @endif
                                        </td>

                                        {{-- Procesamiento column --}}
                                        <td class="processing-cell" style="min-width:180px"
                                            data-uid="{{ $source->uid }}"
                                            data-status="{{ $batchStatus ?? 'never' }}"
                                            data-active="{{ $source->is_active ? '1' : '0' }}">
                                            @include('supplier::settings.views.sources._processing_cell', [
                                                'source' => $source,
                                            ])
                                        </td>

                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.suppliers.sources.edit', [$supplier->uid, $source->uid]) }}">
                                                            <i class="fas fa-pencil me-2 text-muted"></i>Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item test-source" data-uid="{{ $source->uid }}">
                                                            <i class="fas fa-flask me-2 text-muted"></i>Probar conexión
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item extract-source
                                                                {{ (!$source->is_active || in_array($batchStatus, ['processing','pending'])) ? 'disabled' : '' }}"
                                                                data-uid="{{ $source->uid }}"
                                                                data-label="{{ $source->label }}"
                                                                data-active="{{ $source->is_active ? '1' : '0' }}"
                                                                {{ (!$source->is_active || in_array($batchStatus, ['processing','pending'])) ? 'disabled' : '' }}>
                                                            @if(in_array($batchStatus, ['processing','pending']))
                                                                <i class="fas fa-circle-notch fa-spin me-2 text-primary"></i>Procesando...
                                                            @else
                                                                <i class="fas fa-play me-2 text-success"></i>Procesar ahora
                                                            @endif
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item confirm-delete text-danger"
                                                           data-href="{{ route('settings.suppliers.sources.destroy', [$supplier->uid, $source->uid]) }}">
                                                            <i class="fas fa-trash me-2"></i>Eliminar
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
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-plug fa-2x mb-3 d-block"></i>
                        <h6 class="mb-1">No hay fuentes para mostrar</h6>
                        @if(request()->hasAny(['search', 'source_type', 'platform', 'is_active']))
                            <p class="small mb-0">No se encontraron resultados para los filtros aplicados</p>
                        @else
                            <p class="small mb-3">Crea tu primera fuente de datos para comenzar</p>
                            <a href="{{ route('settings.suppliers.sources.create', $supplier->uid) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Crear primera fuente
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            <div class="card-footer bg-white border-top py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        @if($sources->total() > 0)
                        <span class="text-muted small">
                            Mostrando {{ $sources->firstItem() }}–{{ $sources->lastItem() }} de {{ $sources->total() }}
                        </span>
                        @endif
                        <form method="GET" action="{{ route('settings.suppliers.sources.index', $supplier->uid) }}" class="d-inline-flex align-items-center gap-1 mb-0">
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
                                @foreach([10, 20, 50, 100] as $opt)
                                    <option value="{{ $opt }}" {{ request('per_page', 15) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                                <option value="200" {{ request('per_page') == '200' ? 'selected' : '' }}>200</option>
                            </select>
                        </form>
                    </div>
                    @if($sources->hasPages())
                    <nav>{{ $sources->appends(request()->query())->links('pagination::bootstrap-5') }}</nav>
                    @endif
                </div>
            </div>

        </div>
    </div>

{{-- Filter modal --}}
<div class="modal fade" id="sources-filter-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filtros avanzados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tipo de fuente</label>
                    <select id="modal-source-type" class="form-control select2-filter-modal">
                        <option value="">Todos los tipos</option>
                        <option value="website"  {{ request('source_type') == 'website'  ? 'selected' : '' }}>Sitio Web</option>
                        <option value="ftp"      {{ request('source_type') == 'ftp'      ? 'selected' : '' }}>FTP</option>
                        <option value="sftp"     {{ request('source_type') == 'sftp'     ? 'selected' : '' }}>SFTP</option>
                        <option value="api"      {{ request('source_type') == 'api'      ? 'selected' : '' }}>API REST</option>
                        <option value="upload"   {{ request('source_type') == 'upload'   ? 'selected' : '' }}>Carga Manual</option>
                        <option value="file"     {{ request('source_type') == 'file'     ? 'selected' : '' }}>Archivo</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Plataforma</label>
                    <select id="modal-platform" class="form-control select2-filter-modal">
                        <option value="">Todas las plataformas</option>
                        <option value="shopify"     {{ request('platform') == 'shopify'     ? 'selected' : '' }}>Shopify</option>
                        <option value="woocommerce" {{ request('platform') == 'woocommerce' ? 'selected' : '' }}>WooCommerce</option>
                        <option value="prestashop"  {{ request('platform') == 'prestashop'  ? 'selected' : '' }}>PrestaShop</option>
                        <option value="magento"     {{ request('platform') == 'magento'     ? 'selected' : '' }}>Magento</option>
                        <option value="joomla"      {{ request('platform') == 'joomla'      ? 'selected' : '' }}>Joomla</option>
                        <option value="wordpress"   {{ request('platform') == 'wordpress'   ? 'selected' : '' }}>WordPress</option>
                        <option value="nextjs"      {{ request('platform') == 'nextjs'      ? 'selected' : '' }}>Next.js</option>
                        <option value="custom"      {{ request('platform') == 'custom'      ? 'selected' : '' }}>Custom</option>
                        <option value="blocked"     {{ request('platform') == 'blocked'     ? 'selected' : '' }}>Bloqueados</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Estado</label>
                    <select id="modal-is-active" class="form-control select2-filter-modal">
                        <option value="">Todos</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="sources-filter-apply-btn" class="btn btn-primary w-100 mb-1">
                    Aplicar filtros
                </button>
                <button type="button" id="sources-filter-clear-btn" class="btn btn-secondary w-100">
                    Limpiar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Bulk toolbar flotante --}}
<div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
    <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
        <span data-bulk-count>0</span> seleccionada(s) &mdash; Aplicar acción
    </button>
</div>

{{-- Bulk modal --}}
<div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Acción masiva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> fuente(s)</strong>.</p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Acción</label>
                    <select id="bulk-action-select" class="form-select">
                        <option value="">Seleccionar acción...</option>
                        <option value="enable">Activar</option>
                        <option value="disable">Desactivar</option>
                        <option value="delete">Eliminar</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal de confirmación de eliminación --}}
<div class="modal fade" id="delete-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-trash-alt fa-2x text-danger"></i>
                </div>
                <h6 class="fw-semibold mb-1">¿Eliminar esta fuente?</h6>
                <p class="text-muted small mb-4">Esta acción no se puede deshacer. Todos los datos relacionados serán eliminados.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-danger" id="delete-confirm-btn">
                        <i class="fas fa-trash me-1"></i>Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script>
(function () {
    'use strict';

    var supplierUid   = '{{ $supplier->uid }}';
    var csrfToken     = $('meta[name="csrf-token"]').attr('content');
    var pollIntervals = {};

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    function statusBadge(status) {
        var map = {
            pending:    '<span class="badge bg-warning-subtle text-warning"><i class="fas fa-hourglass-half me-1"></i>Pendiente</span>',
            processing: '<span class="badge bg-primary-subtle text-primary"><i class="fas fa-circle-notch fa-spin me-1"></i>Procesando</span>',
            completed:  '<span class="badge bg-success-subtle text-success"><i class="fas fa-check me-1"></i>Completado</span>',
            failed:     '<span class="badge bg-danger-subtle text-danger"><i class="fas fa-xmark me-1"></i>Fallido</span>',
            never:      '<span class="badge bg-info-subtle text-info">Nunca procesado</span>',
        };
        return map[status] || map['never'];
    }

    function relativeDate(isoString) {
        if (!isoString) { return ''; }
        var now  = new Date();
        var then = new Date(isoString);
        var diff = Math.floor((now - then) / 1000);

        if (diff < 60)    { return 'hace ' + diff + 's'; }
        if (diff < 3600)  { return 'hace ' + Math.floor(diff / 60) + 'min'; }
        if (diff < 86400) { return 'hace ' + Math.floor(diff / 3600) + 'h'; }
        return 'hace ' + Math.floor(diff / 86400) + 'd';
    }

    function miniStats(stats) {
        if (!stats || stats.total_items === 0) { return ''; }
        return '<small class="text-muted d-block mt-1">'
            + stats.total_items + ' items'
            + (stats.new_items     > 0 ? ' · <span class="text-success">' + stats.new_items + ' nuevos</span>' : '')
            + (stats.updated_items > 0 ? ' · <span class="text-primary">' + stats.updated_items + ' act.</span>' : '')
            + (stats.failed_items  > 0 ? ' · <span class="text-danger">' + stats.failed_items + ' err.</span>' : '')
            + '</small>';
    }

    // -------------------------------------------------------------------------
    // Processing cell
    // -------------------------------------------------------------------------

    function updateProcessingCell(uid, data) {
        var $row  = $('tr[data-source-uid="' + uid + '"]');
        var $cell = $row.find('.processing-cell');

        $cell.attr('data-status', data.status || 'never');

        var html = statusBadge(data.status);

        if (data.last_processed_at) {
            html += '<small class="text-muted d-block mt-1">' + relativeDate(data.last_processed_at) + '</small>';
        }

        html += miniStats(data.stats);
        $cell.html(html);

        var $btn    = $row.find('.extract-source');
        var active  = $cell.attr('data-active') === '1';
        var running = (data.status === 'processing' || data.status === 'pending');

        $btn.prop('disabled', !active || running)
            .toggleClass('disabled', !active || running);

        if (running) {
            $btn.html('<i class="fas fa-circle-notch fa-spin me-2 text-primary"></i>Procesando...');
        } else {
            $btn.html('<i class="fas fa-play me-2 text-success"></i>Procesar ahora');
        }
    }

    // -------------------------------------------------------------------------
    // Polling
    // -------------------------------------------------------------------------

    function statusUrl(uid) {
        return '{{ route("settings.suppliers.sources.status", [$supplier->uid, ":uid"]) }}'.replace(':uid', uid);
    }

    function pollSource(uid) {
        if (pollIntervals[uid]) { return; }

        pollIntervals[uid] = setInterval(function () {
            $.get(statusUrl(uid))
                .done(function (data) {
                    updateProcessingCell(uid, data);

                    if (data.status === 'completed') {
                        toastr.success('Extracción completada', $('tr[data-source-uid="' + uid + '"] td:nth-child(2) strong').text());
                        stopPoll(uid);
                    } else if (data.status === 'failed') {
                        toastr.error('La extracción falló', $('tr[data-source-uid="' + uid + '"] td:nth-child(2) strong').text());
                        stopPoll(uid);
                    }
                })
                .fail(function () { stopPoll(uid); });
        }, 5000);
    }

    function stopPoll(uid) {
        clearInterval(pollIntervals[uid]);
        delete pollIntervals[uid];
    }

    function stopAllPolls() {
        $.each(pollIntervals, function (uid) { stopPoll(uid); });
    }

    $('.processing-cell').each(function () {
        var status = $(this).attr('data-status');
        if (status === 'processing' || status === 'pending') {
            pollSource($(this).attr('data-uid'));
        }
    });

    // -------------------------------------------------------------------------
    // Extract
    // -------------------------------------------------------------------------

    function extractUrl(uid) {
        return '{{ route("settings.suppliers.sources.extract", [$supplier->uid, ":uid"]) }}'.replace(':uid', uid);
    }

    $(document).on('click', '.extract-source:not(.disabled)', function () {
        var uid   = $(this).data('uid');
        var label = $(this).data('label');

        if (!confirm('¿Iniciar extracción para "' + label + '"?')) { return; }

        $.ajax({
            url:     extractUrl(uid),
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message || 'Extracción iniciada', label);
                    updateProcessingCell(uid, { status: 'pending', last_processed_at: null, stats: {} });
                    pollSource(uid);
                } else {
                    toastr.error(res.message || 'No se pudo iniciar la extracción', label);
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON ? xhr.responseJSON.message : 'Error al iniciar la extracción', label);
            }
        });
    });

    // -------------------------------------------------------------------------
    // Test connection
    // -------------------------------------------------------------------------

    $(document).on('click', '.test-source', function () {
        var uid  = $(this).data('uid');
        var $btn = $(this);
        var orig = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Probando...');

        $.ajax({
            url:     '{{ route("settings.suppliers.sources.test", [$supplier->uid, ":uid"]) }}'.replace(':uid', uid),
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message || 'Conexión exitosa', 'Prueba de conexión');
                } else {
                    toastr.error(res.message || 'Conexión fallida', 'Prueba de conexión');
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON ? xhr.responseJSON.message : 'Error al probar la conexión', 'Error');
            },
            complete: function () {
                $btn.prop('disabled', false).html(orig);
            }
        });
    });

    // -------------------------------------------------------------------------
    // Filter modal
    // -------------------------------------------------------------------------

    $('.select2-filter-modal').select2({ dropdownParent: $('#sources-filter-modal'), width: '100%' });

    $('#sources-filter-apply-btn').on('click', function () {
        $('#filter-source-type').val($('#modal-source-type').val());
        $('#filter-platform').val($('#modal-platform').val());
        $('#filter-is-active').val($('#modal-is-active').val());
        $('#sources-filter-modal').modal('hide');
        $('#sources-filter-form').submit();
    });

    $('#sources-filter-clear-btn').on('click', function () {
        $('#modal-source-type, #modal-platform, #modal-is-active').val(null).trigger('change');
    });

    // -------------------------------------------------------------------------
    // Bulk actions
    // -------------------------------------------------------------------------

    var bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        var action = $('#bulk-action-select').val();
        var ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos una fuente.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar las ' + ids.length + ' fuente(s) seleccionadas?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route("settings.suppliers.sources.bulk-action", $supplier->uid) }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: csrfToken }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message);
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    // -------------------------------------------------------------------------
    // Delete (single, AJAX)
    // -------------------------------------------------------------------------

    var deleteTargetUrl = null;
    var deleteTargetRow = null;

    $(document).on('click', '.confirm-delete', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        deleteTargetUrl = $(this).data('href');
        deleteTargetRow = $(this).closest('tr');
        $('#delete-modal').modal('show');
    });

    $('#delete-confirm-btn').on('click', function () {
        if (!deleteTargetUrl) { return; }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Eliminando...');

        $.ajax({
            url:     deleteTargetUrl,
            method:  'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                $('#delete-modal').modal('hide');
                if (res.success) {
                    deleteTargetRow && deleteTargetRow.fadeOut(300, function () { $(this).remove(); });
                    toastr.success(res.message || 'Fuente eliminada', 'Eliminar');
                } else {
                    toastr.error(res.message || 'No se pudo eliminar', 'Error');
                }
            },
            error: function (xhr) {
                $('#delete-modal').modal('hide');
                toastr.error(xhr.responseJSON ? xhr.responseJSON.message : 'Error al eliminar la fuente', 'Error');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-trash me-1"></i>Eliminar');
                deleteTargetUrl = null;
                deleteTargetRow = null;
            }
        });
    });

    // -------------------------------------------------------------------------
    // Session toasts
    // -------------------------------------------------------------------------

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    $(window).on('beforeunload', stopAllPolls);

}());
</script>
@endpush
