@extends('layouts.theme')

@section('title', 'Meta SEO')

@section('content')
    @include('core::components.card', ['title' => 'Meta SEO'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light text-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Panel de control SEO</h5>
                        <p class="small mb-0 text-black">Auditoria centralizada de configuraciones SEO de todos los modelos del sistema</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" onclick="window.location.reload()">
                            <i class="fa fa-arrows-rotate me-1"></i> Actualizar
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-lg-4 col-md-6">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-2 text-muted fw-semibold">Total registros</p>
                                        <h2 class="text-success mb-0" style="font-weight: 700;">{{ $stats['total'] }}</h2>
                                    </div>
                                    <div class="text-end">
                                        <i class="fa fa-database fa-2x text-muted opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-2 text-muted fw-semibold">Indexables</p>
                                        <h2 class="text-success mb-0" style="font-weight: 700;">{{ $stats['indexable'] }}</h2>
                                    </div>
                                    <div class="text-end">
                                        <i class="fa fa-check-circle fa-2x text-success opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-2 text-muted fw-semibold">No indexables</p>
                                        <h2 class="text-success mb-0" style="font-weight: 700;">{{ $stats['noindex'] }}</h2>
                                    </div>
                                    <div class="text-end">
                                        <i class="fa fa-ban fa-2x text-danger opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-2 text-muted fw-semibold">Sin descripcion</p>
                                        <h2 class="text-success mb-0" style="font-weight: 700;">{{ $stats['missing_description'] }}</h2>
                                    </div>
                                    <div class="text-end">
                                        <i class="fa fa-file-lines fa-2x text-warning opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-2 text-muted fw-semibold">Sin imagen OG</p>
                                        <h2 class="text-success mb-0" style="font-weight: 700;">{{ $stats['missing_og_image'] }}</h2>
                                    </div>
                                    <div class="text-end">
                                        <i class="fa fa-image fa-2x text-info opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-pills user-profile-tab" id="seo-settings-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 active"
                            id="all-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#all"
                            type="button"
                            role="tab"
                            aria-controls="all"
                            aria-selected="true">
                        <i class="fa fa-list me-2"></i>
                        <span class="d-none d-md-block">Todas</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="indexable-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#indexable"
                            type="button"
                            role="tab"
                            aria-controls="indexable"
                            aria-selected="false">
                        <i class="fa fa-check-circle me-2"></i>
                        <span class="d-none d-md-block">Indexables</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="noindex-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#noindex"
                            type="button"
                            role="tab"
                            aria-controls="noindex"
                            aria-selected="false">
                        <i class="fa fa-ban me-2"></i>
                        <span class="d-none d-md-block">No indexables</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            id="unoptimized-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#unoptimized"
                            type="button"
                            role="tab"
                            aria-controls="unoptimized"
                            aria-selected="false">
                        <i class="fa fa-exclamation-triangle me-2"></i>
                        <span class="d-none d-md-block">Sin optimizar</span>
                    </button>
                </li>
            </ul>

            <div class="card-body">
                <div class="tab-content" id="seo-settings-content">
                    {{-- TAB: TODAS --}}
                    <div role="tabpanel" class="tab-pane fade show active" id="all">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-bold">Todas las configuraciones SEO</h6>
                                    <p class="text-muted small mb-0">Vista completa de todas las configuraciones meta SEO del sistema</p>
                                </div>

                                <div class="card mb-3">
                                    <div class="card-body">
                                        <form method="GET" action="{{ route('setting.seo.metas.index') }}" class="row g-3 align-items-end">
                                            <div class="col-lg-4 col-md-6">
                                                <label class="form-label fw-semibold">Buscar</label>
                                                <input type="text" name="search" class="form-control" placeholder="Buscar en titulo o descripcion..." value="{{ request('search') }}">
                                            </div>
                                            <div class="col-lg-3 col-md-6">
                                                <label class="form-label fw-semibold">Tipo de modelo</label>
                                                <select name="seoable_type" class="form-select">
                                                    <option value="">Todos los tipos</option>
                                                    @foreach($seoableTypes as $type)
                                                        <option value="{{ $type }}" {{ request('seoable_type') === $type ? 'selected' : '' }}>
                                                            {{ class_basename($type) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-lg-3 col-md-6">
                                                <label class="form-label fw-semibold">Ordenar por</label>
                                                <select name="sort_by" class="form-select">
                                                    <option value="updated_at" {{ request('sort_by', 'updated_at') === 'updated_at' ? 'selected' : '' }}>Actualizado</option>
                                                    <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Creado</option>
                                                    <option value="title" {{ request('sort_by') === 'title' ? 'selected' : '' }}>Titulo</option>
                                                </select>
                                            </div>
                                            <div class="col-lg-2 col-md-6">
                                                <div class="d-flex gap-2">
                                                    <button type="submit" class="btn btn-primary w-100">
                                                        <i class="fas fa-search me-1"></i> Filtrar
                                                    </button>
                                                    <a href="{{ route('setting.seo.metas.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                @if($metas->count() > 0)
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="select-all-all">
                                            <label class="form-check-label small text-muted" for="select-all-all">Seleccionar todo</label>
                                        </div>
                                        <button type="button" class="btn btn-outline-danger btn-sm d-none" id="bulk-delete-btn-all"
                                                data-bs-toggle="modal" data-bs-target="#bulk-delete-modal">
                                            <i class="fas fa-trash me-1"></i> Eliminar seleccionados (<span id="selected-count-all">0</span>)
                                        </button>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="30"></th>
                                                    <th>Titulo</th>
                                                    <th>Tipo</th>
                                                    <th>Modelo</th>
                                                    <th class="text-center">Robots</th>
                                                    <th class="text-center">OG</th>
                                                    <th>Actualizado</th>
                                                    <th class="text-center">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($metas as $meta)
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" class="form-check-input meta-checkbox-all" value="{{ $meta->id }}">
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('setting.seo.metas.show', $meta) }}" class="text-primary fw-semibold">
                                                                {{ Str::limit($meta->title ?? 'Sin titulo', 50) }}
                                                            </a>
                                                            <br><small class="text-muted">{{ Str::limit($meta->description ?? 'Sin descripcion', 60) }}</small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark">{{ $meta->short_type }}</span>
                                                        </td>
                                                        <td>
                                                            {{ Str::limit($meta->seoable?->title ?? $meta->seoable?->name ?? '#'.$meta->seoable_id, 30) }}
                                                        </td>
                                                        <td class="text-center">
                                                            @php
                                                                $robots = $meta->robots ?? 'index,follow';
                                                                $hasNoindex = str_contains($robots, 'noindex');
                                                                $hasNofollow = str_contains($robots, 'nofollow');
                                                            @endphp
                                                            @if($hasNoindex)
                                                                <span class="badge bg-danger-subtle text-danger">{{ $robots }}</span>
                                                            @elseif($hasNofollow)
                                                                <span class="badge bg-warning-subtle text-warning">{{ $robots }}</span>
                                                            @else
                                                                <span class="badge bg-success-subtle text-success">{{ $robots }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if($meta->og_title && $meta->og_image)
                                                                <i class="fas fa-check-circle text-success"></i>
                                                            @elseif($meta->og_title || $meta->og_image)
                                                                <i class="fas fa-exclamation-circle text-warning"></i>
                                                            @else
                                                                <i class="fas fa-times-circle text-muted"></i>
                                                            @endif
                                                        </td>
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
                                                                        <a class="dropdown-item" href="{{ route('setting.seo.metas.show', $meta) }}">
                                                                            <i class="fas fa-eye me-2"></i> Ver detalle
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item" href="{{ route('setting.seo.metas.edit', $meta) }}">
                                                                            <i class="fas fa-edit me-2"></i> Editar
                                                                        </a>
                                                                    </li>
                                                                    <li><hr class="dropdown-divider"></li>
                                                                    <li>
                                                                        <a class="dropdown-item text-danger delete-btn"
                                                                           data-bs-toggle="modal"
                                                                           data-bs-target="#delete-modal"
                                                                           data-url="{{ route('setting.seo.metas.destroy', $meta) }}"
                                                                           data-title="Eliminar meta SEO: {{ Str::limit($meta->title ?? 'Sin titulo', 30) }}">
                                                                            <i class="fas fa-trash me-2"></i> Eliminar
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
                                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No hay configuraciones meta SEO registradas</p>
                                    </div>
                                @endif

                                @if($metas->hasPages())
                                    <div class="mt-3">{{ $metas->links() }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- TAB: INDEXABLES --}}
                    <div role="tabpanel" class="tab-pane fade" id="indexable">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-bold">Configuraciones indexables</h6>
                                    <p class="text-muted small mb-0">Registros con robots index,follow que apareceran en motores de busqueda</p>
                                </div>

                                <div class="alert alert-info border-0 bg-info-subtle d-flex align-items-start gap-2 mb-3">
                                    <i class="fa fa-circle-info fs-5"></i>
                                    <div>
                                        <strong>Indexables:</strong> Estas configuraciones permiten que el contenido aparezca en resultados de busqueda. Asegurate de que tengan titulo, descripcion y imagen OG optimizados.
                                    </div>
                                </div>

                                @php
                                    $indexableMetas = $metas->filter(function($meta) {
                                        $robots = $meta->robots ?? 'index,follow';
                                        return str_contains($robots, 'index') && !str_contains($robots, 'noindex');
                                    });
                                @endphp

                                @if($indexableMetas->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Titulo</th>
                                                    <th>Tipo</th>
                                                    <th>Modelo</th>
                                                    <th class="text-center">OG</th>
                                                    <th>Actualizado</th>
                                                    <th class="text-center">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($indexableMetas as $meta)
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('setting.seo.metas.show', $meta) }}" class="text-primary fw-semibold">
                                                                {{ Str::limit($meta->title ?? 'Sin titulo', 50) }}
                                                            </a>
                                                            <br><small class="text-muted">{{ Str::limit($meta->description ?? 'Sin descripcion', 60) }}</small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark">{{ $meta->short_type }}</span>
                                                        </td>
                                                        <td>
                                                            {{ Str::limit($meta->seoable?->title ?? $meta->seoable?->name ?? '#'.$meta->seoable_id, 30) }}
                                                        </td>
                                                        <td class="text-center">
                                                            @if($meta->og_title && $meta->og_image)
                                                                <i class="fas fa-check-circle text-success"></i>
                                                            @elseif($meta->og_title || $meta->og_image)
                                                                <i class="fas fa-exclamation-circle text-warning"></i>
                                                            @else
                                                                <i class="fas fa-times-circle text-muted"></i>
                                                            @endif
                                                        </td>
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
                                                                        <a class="dropdown-item" href="{{ route('setting.seo.metas.show', $meta) }}">
                                                                            <i class="fas fa-eye me-2"></i> Ver detalle
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item" href="{{ route('setting.seo.metas.edit', $meta) }}">
                                                                            <i class="fas fa-edit me-2"></i> Editar
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
                                        <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No hay configuraciones indexables</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- TAB: NO INDEXABLES --}}
                    <div role="tabpanel" class="tab-pane fade" id="noindex">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-bold">Configuraciones no indexables</h6>
                                    <p class="text-muted small mb-0">Registros con noindex que no apareceran en resultados de busqueda</p>
                                </div>

                                <div class="alert alert-warning border-0 bg-warning-subtle d-flex align-items-start gap-2 mb-3">
                                    <i class="fa fa-triangle-exclamation fs-5"></i>
                                    <div>
                                        <strong>No indexables:</strong> Estos contenidos estan bloqueados para motores de busqueda. Verifica que sea intencional.
                                    </div>
                                </div>

                                @php
                                    $noindexMetas = $metas->filter(function($meta) {
                                        $robots = $meta->robots ?? 'index,follow';
                                        return str_contains($robots, 'noindex');
                                    });
                                @endphp

                                @if($noindexMetas->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Titulo</th>
                                                    <th>Tipo</th>
                                                    <th>Modelo</th>
                                                    <th class="text-center">Robots</th>
                                                    <th>Actualizado</th>
                                                    <th class="text-center">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($noindexMetas as $meta)
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('setting.seo.metas.show', $meta) }}" class="text-primary fw-semibold">
                                                                {{ Str::limit($meta->title ?? 'Sin titulo', 50) }}
                                                            </a>
                                                            <br><small class="text-muted">{{ Str::limit($meta->description ?? 'Sin descripcion', 60) }}</small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark">{{ $meta->short_type }}</span>
                                                        </td>
                                                        <td>
                                                            {{ Str::limit($meta->seoable?->title ?? $meta->seoable?->name ?? '#'.$meta->seoable_id, 30) }}
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-danger-subtle text-danger">{{ $meta->robots }}</span>
                                                        </td>
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
                                                                        <a class="dropdown-item" href="{{ route('setting.seo.metas.show', $meta) }}">
                                                                            <i class="fas fa-eye me-2"></i> Ver detalle
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item" href="{{ route('setting.seo.metas.edit', $meta) }}">
                                                                            <i class="fas fa-edit me-2"></i> Editar
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
                                        <i class="fas fa-ban fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No hay configuraciones no indexables</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- TAB: SIN OPTIMIZAR --}}
                    <div role="tabpanel" class="tab-pane fade" id="unoptimized">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-bold">Configuraciones sin optimizar</h6>
                                    <p class="text-muted small mb-0">Registros que requieren atencion: sin descripcion o imagen OG</p>
                                </div>

                                <div class="alert alert-danger border-0 bg-danger-subtle d-flex align-items-start gap-2 mb-3">
                                    <i class="fa fa-circle-exclamation fs-5"></i>
                                    <div>
                                        <strong>Requiere atencion:</strong> Estos registros carecen de descripcion meta o imagen Open Graph, lo cual afecta negativamente su rendimiento en redes sociales y resultados de busqueda.
                                    </div>
                                </div>

                                @php
                                    $unoptimizedMetas = $metas->filter(function($meta) {
                                        return empty($meta->description) || empty($meta->og_image);
                                    });
                                @endphp

                                @if($unoptimizedMetas->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Titulo</th>
                                                    <th>Tipo</th>
                                                    <th>Modelo</th>
                                                    <th class="text-center">Falta descripcion</th>
                                                    <th class="text-center">Falta imagen OG</th>
                                                    <th>Actualizado</th>
                                                    <th class="text-center">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($unoptimizedMetas as $meta)
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('setting.seo.metas.show', $meta) }}" class="text-primary fw-semibold">
                                                                {{ Str::limit($meta->title ?? 'Sin titulo', 50) }}
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark">{{ $meta->short_type }}</span>
                                                        </td>
                                                        <td>
                                                            {{ Str::limit($meta->seoable?->title ?? $meta->seoable?->name ?? '#'.$meta->seoable_id, 30) }}
                                                        </td>
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
                                                                        <a class="dropdown-item" href="{{ route('setting.seo.metas.edit', $meta) }}">
                                                                            <i class="fas fa-edit me-2"></i> Optimizar ahora
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item" href="{{ route('setting.seo.metas.show', $meta) }}">
                                                                            <i class="fas fa-eye me-2"></i> Ver detalle
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
                                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                        <p class="text-muted">Todas las configuraciones estan optimizadas</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')

    <div id="bulk-delete-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <form id="bulk-delete-form" method="POST" action="{{ route('setting.seo.metas.bulk-delete') }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="ids" id="bulk-delete-ids">
                    <div class="modal-header">
                        <h5 class="modal-title">Eliminacion masiva</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="display-4 text-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h4 class="my-0">Eliminar registros seleccionados?</h4>
                        <p>Se eliminaran <strong id="bulk-count">0</strong> registros meta SEO. Esta accion no se puede deshacer.</p>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">Confirmar eliminacion</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.delete-btn').on('click', function() {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Bulk delete functionality for "All" tab
    const $selectAllAll = $('#select-all-all');
    const $checkboxesAll = $('.meta-checkbox-all');
    const $bulkBtnAll = $('#bulk-delete-btn-all');
    const $selectedCountAll = $('#selected-count-all');
    const $bulkCount = $('#bulk-count');
    const $bulkIds = $('#bulk-delete-ids');

    function updateBulkState() {
        const selected = $checkboxesAll.filter(':checked');
        const count = selected.length;

        $bulkBtnAll.toggleClass('d-none', count === 0);
        $selectedCountAll.text(count);
        $bulkCount.text(count);
        $bulkIds.val(JSON.stringify(selected.map(function() { return $(this).val(); }).get()));
    }

    $selectAllAll.on('change', function() {
        $checkboxesAll.prop('checked', $(this).is(':checked'));
        updateBulkState();
    });

    $checkboxesAll.on('change', updateBulkState);
});
</script>
@endpush
