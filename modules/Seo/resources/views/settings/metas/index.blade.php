@extends('layouts.theme')

@section('title', 'Meta SEO')

@section('content')
    @include('core::components.card', ['title' => 'Meta SEO'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Panel de control SEO</h5>
                        <p class="small mb-0 text-muted">Auditoría centralizada de configuraciones SEO de todos los modelos del sistema</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('settings.seo.metas.export') }}">Exportar CSV</a>
                                <a class="dropdown-item" href="{{ route('settings.seo.metas.import') }}">Importar CSV</a>
                                <a class="dropdown-item" href="{{ route('settings.seo.metas.export-json') }}">Exportar JSON</a>
                                <a class="dropdown-item" href="{{ route('settings.seo.metas.import-json') }}">Importar JSON</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('settings.seo.audit.index') }}">Auditoría SEO</a>
                                <button class="dropdown-item" type="button" id="btn-bulk-canonical">Generar canónicas faltantes</button>
                                <button class="dropdown-item" type="button" data-action="reload">Actualizar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Total registros</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                        <small class="text-muted">Configuraciones meta SEO</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Indexables</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['indexable']) }}</h4>
                                        <small class="text-muted">Visibles en buscadores</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">No indexables</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['noindex']) }}</h4>
                                        <small class="text-muted">Bloqueadas con noindex</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Sin optimizar</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['missing_description'] + $stats['missing_og_image']) }}</h4>
                                        <small class="text-muted">{{ $stats['missing_description'] }} sin desc. / {{ $stats['missing_og_image'] }} sin imagen OG</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Score promedio</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['avg_score'] > 0 ? $stats['avg_score'] : '-' }}</h4>
                                        <small class="text-muted">Puntuación SEO media</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.seo.metas.index') }}" id="filter-form">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search"
                                       name="search"
                                       class="form-control border-start-0 ps-0"
                                       placeholder="Buscar en título o descripción..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 180px;">
                            <select name="seoable_type" class="form-select select2 h-100">
                                <option value="">Todos los tipos</option>
                                @foreach($seoableTypes as $type)
                                    <option value="{{ $type }}" @selected(request('seoable_type') === $type)>
                                        {{ class_basename($type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 160px;">
                            <select name="sort_by" class="form-select select2 h-100">
                                <option value="updated_at" @selected(request('sort_by', 'updated_at') === 'updated_at')>Actualizado</option>
                                <option value="created_at" @selected(request('sort_by') === 'created_at')>Creado</option>
                                <option value="title" @selected(request('sort_by') === 'title')>Título</option>
                                <option value="seo_score" @selected(request('sort_by') === 'seo_score')>Score SEO</option>
                                <option value="seo_grade" @selected(request('sort_by') === 'seo_grade')>Grado SEO</option>
                            </select>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 150px;">
                            <select name="sort_direction" class="form-select select2 h-100">
                                <option value="asc" @selected(request('sort_direction', 'desc') === 'asc')>Ascendente</option>
                                <option value="desc" @selected(request('sort_direction', 'desc') === 'desc')>Descendente</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(request()->hasAny(['search', 'seoable_type', 'sort_by', 'sort_direction']))
                                <a href="{{ route('settings.seo.metas.index', ['tab' => $tab]) }}"
                                   class="btn btn-outline-secondary"
                                   title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs border-0 user-profile-tab" id="seo-settings-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a href="{{ route('settings.seo.metas.index', array_merge(request()->except('tab', 'page'), ['tab' => 'all'])) }}"
                       class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $tab === 'all' ? 'active' : '' }}"
                       role="tab">
                        <span class="d-none d-md-block">Todas</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('settings.seo.metas.index', array_merge(request()->except('tab', 'page'), ['tab' => 'indexable'])) }}"
                       class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $tab === 'indexable' ? 'active' : '' }}"
                       role="tab">
                        <span class="d-none d-md-block">Indexables</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('settings.seo.metas.index', array_merge(request()->except('tab', 'page'), ['tab' => 'noindex'])) }}"
                       class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $tab === 'noindex' ? 'active' : '' }}"
                       role="tab">
                        <span class="d-none d-md-block">No indexables</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('settings.seo.metas.index', array_merge(request()->except('tab', 'page'), ['tab' => 'unoptimized'])) }}"
                       class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $tab === 'unoptimized' ? 'active' : '' }}"
                       role="tab">
                        <span class="d-none d-md-block">Sin optimizar</span>
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="card-body">

                @php
                    $tabTitles = [
                        'all'         => ['Todas las configuraciones SEO', 'Vista completa de todas las configuraciones meta SEO del sistema'],
                        'indexable'   => ['Configuraciones indexables', 'Registros con robots index,follow que aparecerán en motores de búsqueda'],
                        'noindex'     => ['Configuraciones no indexables', 'Registros con noindex que no aparecerán en resultados de búsqueda'],
                        'unoptimized' => ['Configuraciones sin optimizar', 'Registros que requieren atención: sin descripción o imagen OG'],
                    ];
                    [$tabTitle, $tabSubtitle] = $tabTitles[$tab] ?? $tabTitles['all'];

                    $tabAlerts = [
                        'all'         => 'Los meta tags SEO se aplican automáticamente en las páginas públicas. Asegúrate de que cada registro tenga título, descripción e imagen OG optimizados.',
                        'indexable'   => 'Estas configuraciones permiten que el contenido aparezca en resultados de búsqueda. Asegúrate de que tengan título, descripción e imagen OG optimizados.',
                        'noindex'     => 'Estos contenidos están bloqueados para motores de búsqueda. Verifica que sea intencional.',
                        'unoptimized' => 'Estos registros carecen de descripción meta o imagen Open Graph, lo cual afecta negativamente su rendimiento en redes sociales y resultados de búsqueda.',
                    ];
                    $tabAlert = $tabAlerts[$tab] ?? $tabAlerts['all'];
                @endphp

                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">{{ $tabTitle }}</h6>
                        <p class="text-muted mb-0">{{ $tabSubtitle }}</p>
                    </div>
                </div>

                <div class="alert alert-info border-0 bg-info-subtle mb-3">
                    <div class="d-flex align-items-start gap-2">
                        <div>
                            <small class="fw-semibold">Importante:</small>
                            <small class="d-block">{{ $tabAlert }}</small>
                        </div>
                    </div>
                </div>

                @if($metas->count() > 0)
                    <form id="bulk-robots-form" method="POST" action="{{ route('settings.seo.metas.bulk-robots') }}" class="d-none">
                        @csrf
                        <input type="hidden" name="ids" id="bulk-robots-ids">
                        <input type="hidden" name="robots" id="bulk-robots-value">
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30">
                                        <input type="checkbox" class="form-check-input" id="select-all-metas">
                                    </th>
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Idioma</th>
                                    <th>Modelo</th>
                                    <th class="text-center">Robots</th>
                                    @if($tab === 'all' || $tab === 'indexable')
                                        <th class="text-center">Score</th>
                                    @endif
                                    @if($tab === 'unoptimized')
                                        <th class="text-center">Sin descripción</th>
                                        <th class="text-center">Sin imagen OG</th>
                                    @endif
                                    <th>Actualizado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($metas as $meta)
                                    @php
                                        $robots = $meta->robots ?? 'index,follow';
                                        $hasNoindex = str_contains($robots, 'noindex');
                                        $hasNofollow = str_contains($robots, 'nofollow');
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $meta->id }}">
                                        </td>
                                        <td class="editable-cell" data-meta-id="{{ $meta->id }}" data-field="title" data-original-value="{{ $meta->title ?? '' }}" style="cursor:pointer;">
                                            <span class="cell-text text-muted">
                                                    {{ Str::limit($meta->title ?? 'Sin título', 50) }}
                                            </span>
                                            <span class="cell-text d-block">
                                                <small class="text-muted editable-cell" data-meta-id="{{ $meta->id }}" data-field="description" data-original-value="{{ $meta->description ?? '' }}" style="cursor:pointer;">
                                                    {{ Str::limit($meta->description ?? 'Sin descripción', 60) }}
                                                </small>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ $meta->short_type }}</span>
                                        </td>
                                        <td>
                                            @if($meta->locale)
                                                <span class="badge bg-primary">{{ strtoupper($meta->locale) }}</span>
                                            @else
                                                <span class="badge bg-secondary">Global</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small>{{ Str::limit($meta->seoable?->title ?? $meta->seoable?->name ?? '#'.$meta->seoable_id, 30) }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($hasNoindex)
                                                <span class="badge bg-danger-subtle text-danger">{{ $robots }}</span>
                                            @elseif($hasNofollow)
                                                <span class="badge bg-warning-subtle text-warning">{{ $robots }}</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success">{{ $robots }}</span>
                                            @endif
                                        </td>
                                        @if($tab === 'all' || $tab === 'indexable')
                                            <td class="text-center">
                                                @if($meta->seo_score !== null)
                                                    @php
                                                        $score = $meta->seo_score;
                                                        [$scoreBg, $scoreGrade] = match(true) {
                                                            $score >= 90 => ['bg-success', 'A'],
                                                            $score >= 75 => ['bg-lime', 'B'],
                                                            $score >= 60 => ['bg-warning', 'C'],
                                                            $score >= 40 => ['bg-orange', 'D'],
                                                            default      => ['bg-danger', 'F'],
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $scoreBg }} text-white" title="{{ $score }}">{{ $scoreGrade }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        @endif
                                        @if($tab === 'unoptimized')
                                            <td class="text-center">
                                                @if(empty($meta->description))
                                                    <i class="fas fa-times-circle text-danger"></i>
                                                @else
                                                    <i class="fas fa-check-circle text-success"></i>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if(empty($meta->og_image))
                                                    <i class="fas fa-times-circle text-danger"></i>
                                                @else
                                                    <i class="fas fa-check-circle text-success"></i>
                                                @endif
                                            </td>
                                        @endif
                                        <td>
                                            <small class="text-muted">{{ $meta->updated_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.seo.metas.show', $meta) }}">
                                                            Ver detalle
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.seo.metas.edit', $meta) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.seo.metas.destroy', $meta) }}"
                                                           data-title="Eliminar meta SEO: {{ Str::limit($meta->title ?? 'Sin título', 30) }}">
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
                        <div class="mb-4">
                            @if($tab === 'unoptimized')
                                <i class="fas fa-check-circle fa-4x text-success opacity-50"></i>
                            @elseif($tab === 'noindex')
                                <i class="fas fa-ban fa-4x text-muted opacity-50"></i>
                            @elseif($tab === 'indexable')
                                <i class="fas fa-check-circle fa-4x text-muted opacity-50"></i>
                            @else
                                <i class="fas fa-tags fa-4x text-muted opacity-50"></i>
                            @endif
                        </div>
                        <h5 class="text-muted mb-2">
                            @if($tab === 'unoptimized')
                                Todas las configuraciones están optimizadas
                            @else
                                No hay registros para mostrar
                            @endif
                        </h5>
                        @if($tab === 'all')
                            <p class="text-muted mb-0">Los meta tags se crean automáticamente cuando se edita el SEO de una página.</p>
                        @endif
                    </div>
                @endif

            </div>

            @if($metas->hasPages())
                <div class="card-footer">{{ $metas->links() }}</div>
            @endif

        </div>
    </div>

    @include('core::components.delete')

    {{-- Floating bulk toolbar --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <div class="card shadow-lg border-0">
            <div class="card-body py-2 px-4 d-flex align-items-center gap-3">
                <span class="text-muted small"><span data-bulk-count>0</span> seleccionados</span>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                        Cambiar robots
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item bulk-robots-btn" href="#" data-robots="index,follow">index, follow</a></li>
                        <li><a class="dropdown-item bulk-robots-btn" href="#" data-robots="noindex,follow">noindex, follow</a></li>
                        <li><a class="dropdown-item bulk-robots-btn" href="#" data-robots="index,nofollow">index, nofollow</a></li>
                        <li><a class="dropdown-item bulk-robots-btn" href="#" data-robots="noindex,nofollow">noindex, nofollow</a></li>
                    </ul>
                </div>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#bulk-modal">
                    Eliminar
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="bulk-cancel">
                    Cancelar
                </button>
            </div>
        </div>
    </div>

    {{-- Modal eliminacion masiva --}}
    <div id="bulk-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="bulk-delete-form" method="POST" action="{{ route('settings.seo.metas.bulk-delete') }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="ids" id="bulk-delete-ids">
                    <div class="modal-body text-center px-4 pb-2">
                        <div class="display-4 text-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h4 class="my-0">¿Eliminar registros seleccionados?</h4>
                        <p class="text-muted mt-2">Se eliminarán <strong id="bulk-count">0</strong> registros meta SEO. Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer flex-column gap-1 border-0 pt-0">
                        <button type="submit" class="btn btn-danger w-100 mb-1">Confirmar eliminación</button>
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $(document).on('click', '[data-action="reload"]', function() {
        window.location.reload();
    });

    $('.delete-btn').on('click', function() {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    const $toolbar  = $('#bulk-toolbar');
    const $bulkCount = $('#bulk-count');
    const $bulkIds   = $('#bulk-delete-ids');

    function getSelectedIds() {
        return $('.bulk-checkbox:checked').map(function() { return $(this).val(); }).get();
    }

    function updateBulkState() {
        const ids = getSelectedIds();
        const count = ids.length;
        $toolbar.toggleClass('d-none', count === 0);
        $('[data-bulk-count]').text(count);
        $bulkCount.text(count);
        $bulkIds.val(JSON.stringify(ids));
    }

    $('#select-all-metas').on('change', function() {
        $('.bulk-checkbox').prop('checked', $(this).is(':checked'));
        updateBulkState();
    });

    $(document).on('change', '.bulk-checkbox', updateBulkState);

    $('#bulk-cancel').on('click', function() {
        $('.bulk-checkbox, #select-all-metas').prop('checked', false);
        updateBulkState();
    });

    $('#btn-bulk-canonical').on('click', function() {
        if (!confirm('¿Generar URLs canónicas automáticamente para todas las páginas que no tienen una?')) return;
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Generando...');
        $.post('{{ route("settings.seo.metas.bulk-generate-canonicals") }}', {
            _token: $('meta[name="csrf-token"]').attr('content')
        }, function(data) {
            toastr.success(data.message);
            setTimeout(function() { location.reload(); }, 1500);
        }).fail(function() {
            toastr.error('Error al generar URLs canónicas.');
        }).always(function() {
            $btn.prop('disabled', false).html('<i class="fas fa-link me-1"></i>Generar canónicas faltantes');
        });
    });

    $(document).on('click', '.bulk-robots-btn', function(e) {
        e.preventDefault();
        const ids = getSelectedIds();
        if (!ids.length) return;
        $('#bulk-robots-ids').val(JSON.stringify(ids));
        $('#bulk-robots-value').val($(this).data('robots'));
        $('#bulk-robots-form').submit();
    });

    // Inline editing
    $(document).on('click', '.editable-cell', function (e) {
        e.stopPropagation();
        if ($(this).find('input, textarea').length) return;

        const cell = $(this);
        const metaId = cell.data('meta-id');
        const field = cell.data('field');
        const currentValue = cell.data('original-value') || cell.find('.cell-text').first().text().trim();

        const input = $('<input type="text" class="form-control form-control-sm">')
            .val(currentValue)
            .on('blur keydown', function (e) {
                if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== 'Escape') return;
                if (e.key === 'Escape') {
                    cell.find('.cell-text').show();
                    input.remove();
                    return;
                }
                const newValue = input.val();
                $.ajax({
                    url: '{{ url("panel/setting/seo/metas") }}/' + metaId + '/inline-update',
                    method: 'PATCH',
                    data: { _token: $('meta[name="csrf-token"]').attr('content'), field: field, value: newValue },
                    success: function () {
                        cell.data('original-value', newValue);
                        if (cell.find('.cell-text a').length) {
                            cell.find('.cell-text a').text(newValue || 'Sin título');
                        } else {
                            var display = newValue || (field === 'description' ? 'Sin descripción' : '-');
                            cell.find('.cell-text').first().contents().filter(function () {
                                return this.nodeType === 3;
                            }).first().replaceWith(document.createTextNode(display + ' '));
                        }
                        cell.find('.cell-text').show();
                        input.remove();
                        toastr.success('Actualizado');
                    },
                    error: function () {
                        toastr.error('Error al guardar');
                        cell.find('.cell-text').show();
                        input.remove();
                    }
                });
            });

        cell.find('.cell-text').hide();
        cell.append(input);
        input.focus().select();
    });
});
</script>
@endpush
