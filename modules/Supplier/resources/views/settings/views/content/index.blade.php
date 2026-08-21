@extends('layouts.theme')

@section('title', 'Revisión de Contenido')

@push('css')
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Revisión de Contenido'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Contenido generado</h5>
                        <p class="small mb-0 text-muted">Revisa y aprueba el contenido generado por IA</p>
                    </div>
                    <div class="d-flex gap-2"></div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                {{-- Fila 1: Total · Por validar · Validado · Publicado --}}
                <div class="row g-3 mb-3">
                    <div class="col-3">
                        <a href="{{ route('settings.suppliers.content.index', array_merge(request()->except(['status', 'page']), [])) }}"
                           class="text-decoration-none d-block h-100">
                            <div class="card bg-light-secondary stat-card h-100 {{ !request('status') ? 'stat-card-active' : '' }}">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Total</h6>
                                    <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                    <small class="text-muted">Contenidos generados</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-3">
                        <a href="{{ route('settings.suppliers.content.index', array_merge(request()->except(['status', 'page']), ['status' => 'pending_validation'])) }}"
                           class="text-decoration-none d-block h-100">
                            <div class="card bg-light-secondary stat-card h-100 {{ request('status') === 'pending_validation' ? 'border-success border-2' : '' }}">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Por validar</h6>
                                    <h4 class="mb-1 fw-bold">{{ $stats['pending'] }}</h4>
                                    <small class="text-muted">Requiere revisión</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-3">
                        <a href="{{ route('settings.suppliers.content.index', array_merge(request()->except(['status', 'page']), ['status' => 'validated'])) }}"
                           class="text-decoration-none d-block h-100">
                            <div class="card bg-light-secondary stat-card h-100 {{ request('status') === 'validated' ? 'border-success border-2' : '' }}">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Validado</h6>
                                    <h4 class="mb-1 fw-bold">{{ $stats['validated_total'] }}</h4>
                                    <small class="text-muted">Aprobado (publicado + no publicado)</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-3">
                        <a href="{{ route('settings.suppliers.content.index', array_merge(request()->except(['status', 'page']), ['status' => 'published'])) }}"
                           class="text-decoration-none d-block h-100">
                            <div class="card bg-light-secondary stat-card h-100 {{ request('status') === 'published' ? 'border-success border-2' : '' }}">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Publicado</h6>
                                    <h4 class="mb-1 fw-bold">{{ $stats['published'] }}</h4>
                                    <small class="text-muted">Visible en tienda</small>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                {{-- Fila 2: No publicado · Rechazado · Pendiente · Sin contenido --}}
                <div class="row g-3">
                    <div class="col-3">
                        <a href="{{ route('settings.suppliers.content.index', array_merge(request()->except(['status', 'page']), ['status' => 'published_hidden'])) }}"
                           class="text-decoration-none d-block h-100">
                            <div class="card bg-light-secondary stat-card h-100 {{ request('status') === 'published_hidden' ? 'border-secondary border-2' : '' }}">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">No publicado</h6>
                                    <h4 class="mb-1 fw-bold">{{ $stats['published_hidden'] }}</h4>
                                    <small class="text-muted">Enviado al ERP, oculto</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-3">
                        <a href="{{ route('settings.suppliers.content.index', array_merge(request()->except(['status', 'page']), ['status' => 'rejected'])) }}"
                           class="text-decoration-none d-block h-100">
                            <div class="card bg-light-secondary stat-card h-100 {{ request('status') === 'rejected' ? 'border-danger border-2' : '' }}">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Rechazado</h6>
                                    <h4 class="mb-1 fw-bold">{{ $stats['rejected'] }}</h4>
                                    <small class="text-muted">Contenido descartado</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-3">
                        <a href="{{ route('settings.suppliers.content.index', array_merge(request()->except(['status', 'page']), ['status' => 'pending_generation'])) }}"
                           class="text-decoration-none d-block">
                            <div class="card stat-card {{ $limboCounts > 0 ? 'stat-card-attention' : 'bg-light-secondary' }}">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Sin generar</h6>
                                    <h4 class="mb-1 fw-bold">{{ $limboCounts }}</h4>
                                    <small class="text-muted">Sin prompt activo</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-3">
                        <a href="{{ route('settings.suppliers.content.index', array_merge(request()->except(['status', 'page']), ['status' => 'on_hold'])) }}"
                           class="text-decoration-none d-block h-100">
                            <div class="card stat-card h-100 {{ request('status') === 'on_hold' ? 'border-secondary border-2' : 'bg-light-secondary' }}">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Sin contenido</h6>
                                    <h4 class="mb-1 fw-bold">{{ $stats['on_hold'] }}</h4>
                                    <small class="text-muted">Retenidos manualmente — no subir aún</small>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="card-body p-0 d-flex align-items-center justify-content-between flex-wrap">
                <ul class="nav nav-pills user-profile-tab" id="content-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $tab === 'available' ? 'active' : '' }}"
                           href="{{ route('settings.suppliers.content.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'available'])) }}">
                            <span class="d-none d-md-block">Disponibles</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $tab === 'mine' ? 'active' : '' }}"
                           href="{{ route('settings.suppliers.content.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'mine'])) }}">
                            <span class="d-none d-md-block">Mis asignados</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                           href="{{ route('settings.suppliers.content.coverage') }}">
                            <span class="d-none d-md-block">Productos sin ficha</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Search and Filters Section -->
            <div class="card-body border-bottom">
                @php
                    $activeFilterCount = collect(['status', 'supplier_id', 'sport_id', 'erp_categoria_id', 'category_id', 'subfamily_id', 'prompt_id', 'content_type', 'date_from', 'date_to', 'older_than_days'])->filter(fn($k) => request($k))->count();
                    $hasAnyFilter = $activeFilterCount > 0 || request('search');
                @endphp
                <form id="content-filter-form" method="GET" action="{{ route('settings.suppliers.content.index') }}">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    {{-- Campos ocultos para los filtros del modal --}}
                    <input type="hidden" name="status"          id="filter-status"          value="{{ request('status') }}">
                    <input type="hidden" name="supplier_id"     id="filter-supplier"        value="{{ request('supplier_id') }}">
                    <input type="hidden" name="sport_id"        id="filter-sport"           value="{{ request('sport_id') }}">
                    <input type="hidden" name="erp_categoria_id" id="filter-erp-cat"        value="{{ request('erp_categoria_id') }}">
                    <input type="hidden" name="category_id"     id="filter-category"        value="{{ request('category_id') }}">
                    <input type="hidden" name="subfamily_id"    id="filter-subfamily"       value="{{ request('subfamily_id') }}">
                    <input type="hidden" name="prompt_id"       id="filter-prompt"          value="{{ request('prompt_id') }}">
                    <input type="hidden" name="content_type"    id="filter-content-type"    value="{{ request('content_type') }}">
                    <input type="hidden" name="date_from"       id="filter-date-from"       value="{{ request('date_from') }}">
                    <input type="hidden" name="date_to"         id="filter-date-to"         value="{{ request('date_to') }}">
                    <input type="hidden" name="older_than_days" id="filter-older-than-days"  value="{{ request('older_than_days') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por nombre, referencia..."
                               value="{{ request('search') }}">

                        {{-- Botón filtros avanzados --}}
                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#filter-modal"
                                title="Filtros avanzados">
                            <i class="fas fa-sliders"></i>
                            @if($activeFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"
                                      style="font-size: 0.6rem;">{{ $activeFilterCount }}</span>
                            @endif
                        </button>

                        {{-- Acceso rápido: reintentar fallidos --}}
                        <a href="{{ route('settings.suppliers.content.index', array_merge(request()->query(), ['status' => 'failed'])) }}"
                           class="btn btn-secondary flex-shrink-0{{ request('status') === 'failed' ? ' active' : '' }}"
                           title="Ver y reintentar contenidos fallidos">
                            <i class="fas fa-rotate-right"></i>
                        </a>

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" title="Información sobre los estados"
                                    data-bs-toggle="modal" data-bs-target="#modal-estados-info">
                                <i class="fas fa-circle-question"></i>
                            </button>
                            @if($hasAnyFilter)
                                <a href="{{ route('settings.suppliers.content.index', ['tab' => $tab]) }}"
                                   class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Chips de filtros activos --}}
                    @if($activeFilterCount > 0)
                        <div class="d-flex gap-2 flex-wrap mt-2">
                            @if(request('status'))
                                @php $statusChip = $contentStatuses->get(request('status')); @endphp
                                <span class="badge badge-status {{ $statusChip?->badge_class ?? 'badge-content-pending' }} py-1 px-2">
                                    Estado: {{ $statusChip?->label ?? request('status') }}
                                </span>
                            @endif
                            @if(request('supplier_id'))
                                @php $sup = $suppliers->firstWhere('id', request('supplier_id')); @endphp
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                    Proveedor: {{ $sup?->label ?? request('supplier_id') }}
                                </span>
                            @endif
                            @if(request('content_type'))
                                @php $typeLabels = ['name'=>'Nombre','description'=>'Descripción','seo'=>'SEO']; @endphp
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                    Tipo: {{ $typeLabels[request('content_type')] ?? request('content_type') }}
                                </span>
                            @endif
                            @if(request('sport_id'))
                                @php $sp = $sports->firstWhere('id', (int) request('sport_id')); @endphp
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                    Deporte: {{ $sp?->name ?? request('sport_id') }}
                                </span>
                            @endif
                            @if(request('erp_categoria_id'))
                                @php $ec = $erpcategorias->firstWhere('erp_categoria_id', (int) request('erp_categoria_id')); @endphp
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                    Categoría: {{ $ec?->erp_categoria_name ?? request('erp_categoria_id') }}
                                </span>
                            @endif
                            @if(request('category_id'))
                                @php $cat = $categories->firstWhere('id', (int) request('category_id')); @endphp
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                    Familia: {{ $cat?->name ?? request('category_id') }}
                                </span>
                            @endif
                            @if(request('subfamily_id'))
                                @php $sf = $subfamilies->firstWhere('id', (int) request('subfamily_id')); @endphp
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                    Subfamilia: {{ $sf?->name ?? request('subfamily_id') }}
                                </span>
                            @endif
                            @if(request('prompt_id'))
                                @php $pr = $prompts->firstWhere('id', (int) request('prompt_id')); @endphp
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                    Prompt: {{ $pr?->label ?? request('prompt_id') }}
                                </span>
                            @endif
                            @if(request('date_from') || request('date_to'))
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                    Fechas: {{ request('date_from') ?: '…' }} → {{ request('date_to') ?: '…' }}
                                </span>
                            @endif
                            @if(request('older_than_days'))
                                @php $olderLabels = ['30'=>'30 días','60'=>'60 días','90'=>'90 días']; @endphp
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                    Generado hace más de: {{ $olderLabels[request('older_than_days')] ?? request('older_than_days').' días' }}
                                </span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            <!-- Content List -->
            <div class="card-body">
                @if($contents->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                <th>Producto</th>
                                <th>Proveedor</th>
                                <th>Categoría</th>
                                <th>Prompt</th>
                                <th width="10%" class="text-center">Estado</th>
                                <th width="15%">Fecha</th>
                                @if(request('status') === 'on_hold')
                                    <th width="20%">Notas</th>
                                @endif
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($contents as $content)
                                <tr data-uid="{{ $content->uid }}">
                                    <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $content->uid }}"></td>
                                    <td>
                                        <div>
                                            <a href="{{ route('settings.suppliers.content.show', $content->uid) }}">
                                                <strong>{{ $content->supplierProduct?->name ?? $content->generated_name ?? $content->model_id ?? 'Sin nombre' }}</strong>
                                            </a>
                                            @if($content->erp_reference)
                                                <br><small class="text-muted">Ref: {{ $content->erp_reference }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $content->supplier?->label ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $cat      = $content->supplierProduct?->category;
                                            $subfamily = $content->supplierProduct?->subfamily;
                                        @endphp
                                        @if($cat)
                                            <div class="d-flex flex-column gap-1">
                                                {{-- Deporte --}}
                                                @if($cat->sport)
                                                    <span class="small fw-semibold">{{ $cat->sport->name }}</span>
                                                @endif
                                                {{-- Categoría --}}
                                                @if($cat->erp_categoria_name)
                                                    <span class="small text-muted">{{ $cat->erp_categoria_name }}</span>
                                                @endif
                                                {{-- Familia --}}
                                                <span class="small text-muted">{{ $cat->name }}</span>
                                                {{-- Subfamilia --}}
                                                @if($subfamily)
                                                    <span class="small text-muted" style="opacity:.75">{{ $subfamily->name }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <small class="text-muted">N/A</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($content->prompt)
                                            <small class="text-muted" title="{{ $content->prompt->label }}">
                                                {{ Str::limit($content->prompt->label, 30) }}
                                            </small>
                                        @else
                                            <small class="text-muted">—</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-status {{ $content->contentStatus?->badge_class ?? 'badge-content-pending' }}">
                                            {{ $content->contentStatus?->label ?? $content->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $content->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</span>
                                        @if($content->created_at && $content->created_at->lt(now()->subDays(90)))
                                            <br><span class="badge bg-secondary" style="font-size:.65rem;" title="Generado hace más de 90 días">Desactualizado</span>
                                        @endif
                                    </td>
                                    @if(request('status') === 'on_hold')
                                        <td>
                                            <textarea class="form-control form-control-sm content-notes-input" rows="2"
                                                      data-uid="{{ $content->uid }}"
                                                      placeholder="Ej: No subir hasta Septiembre">{{ $content->notes }}</textarea>
                                        </td>
                                    @endif
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <button type="button" class="dropdown-item preview-content-btn" data-uid="{{ $content->uid }}">
                                                        Vista previa rápida
                                                    </button>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.suppliers.content.show', $content->uid) }}">
                                                        Ver detalle completo
                                                    </a>
                                                </li>
                                                @if($tab === 'mine')
                                                    <li>
                                                        <button type="button" class="dropdown-item unassign-btn"
                                                                data-uid="{{ $content->uid }}">
                                                            Desasignarme
                                                        </button>
                                                    </li>
                                                @endif
                                                @if($content->status === 'pending_validation')
                                                    <li>
                                                        <form method="POST" action="{{ route('settings.suppliers.content.action', $content->uid) }}" class="approve-form"
                                                              data-nombre="{{ $content->generated_name ?? $content->supplierProduct?->name ?? '' }}"
                                                              data-marca="{{ $content->supplierProduct?->metadata['erp_marca'] ?? ($content->source_attributes['marca'] ?? ($content->source_attributes['brand'] ?? '')) }}">
                                                            @csrf
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" class="dropdown-item">
                                                                Aprobar
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item text-danger reject-btn"
                                                                data-uid="{{ $content->uid }}">
                                                            Rechazar
                                                        </button>
                                                    </li>
                                                @endif
                                                @if(in_array($content->status, ['validated', 'published_hidden']))
                                                    <li>
                                                        <button type="button" class="dropdown-item publish-btn"
                                                                data-uid="{{ $content->uid }}"
                                                                data-nombre="{{ $content->generated_name ?? $content->erp_reference ?? $content->uid }}"
                                                                data-url="{{ route('settings.suppliers.content.action', $content->uid) }}">
                                                            Publicar
                                                        </button>
                                                    </li>
                                                @endif
                                                @if($content->supplier_product_id && in_array($content->status, ['pending_validation', 'pending_generation']))
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.suppliers.products.chat', ['uid' => $content->supplierProduct?->uid ?? $content->supplier_product_id]) }}">
                                                        Chat ai
                                                    </a>
                                                </li>
                                                @endif
                                                @if($content->status === 'on_hold')
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item hold-restore-btn" data-uid="{{ $content->uid }}">
                                                            Quitar de Sin contenido
                                                        </button>
                                                    </li>
                                                @elseif(! in_array($content->status, ['published', 'published_hidden']))
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item hold-content-btn" data-uid="{{ $content->uid }}">
                                                            Marcar como sin contenido
                                                        </button>
                                                    </li>
                                                @endif
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
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-magic fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if($tab === 'mine')
                                    No tienes contenido asignado
                                @else
                                    No hay contenido disponible
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('status') || request('supplier_id'))
                                    No se encontraron resultados con los filtros aplicados
                                @elseif($tab === 'mine')
                                    Ve a "Disponibles" y asígnate contenido para revisarlo
                                @else
                                    Aún no se ha generado contenido con IA
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            <div class="card-footer bg-white border-top py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        @if($contents->total() > 0)
                        <span class="text-muted small">
                            Mostrando {{ $contents->firstItem() }}–{{ $contents->lastItem() }} de {{ $contents->total() }}
                        </span>
                        @endif
                        <form method="GET" action="{{ route('settings.suppliers.content.index') }}" class="d-inline-flex align-items-center gap-1 mb-0">
                            <input type="hidden" name="tab" value="{{ $tab }}">
                            @foreach(request()->except(['per_page', 'page', 'tab']) as $key => $value)
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
                                    <option value="{{ $opt }}" {{ request('per_page', 10) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                                <option value="200" {{ request('per_page') == '200' ? 'selected' : '' }}>200</option>
                            </select>
                        </form>
                    </div>
                    @if($contents->hasPages())
                    <nav>{{ $contents->appends(request()->query())->links('pagination::bootstrap-5') }}</nav>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal info estados --}}
    <div class="modal fade" id="modal-estados-info" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" >
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom px-4 py-3">
                    <h6 class="modal-title fw-bold mb-0">
                        Guía de estados del contenido
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <p class="text-muted small mb-3">El contenido generado por IA pasa por un flujo de revisión con los siguientes estados:</p>

                    <div class="d-flex flex-column gap-3">

                        <div class="d-flex gap-3 align-items-start">
                            <span class="badge badge-content-pending badge-content-guide mt-1">Pendiente gen.</span>
                            <div>
                                <div class="fw-semibold small">Pendiente de generación</div>
                                <div class="text-muted small">El producto está en cola. La IA aún no ha generado ningún texto.</div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 align-items-start">
                            <span class="badge badge-content-generating badge-content-guide mt-1">Generando</span>
                            <div>
                                <div class="fw-semibold small">Generando</div>
                                <div class="text-muted small">La IA está procesando el contenido en este momento. No se puede editar.</div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 align-items-start">
                            <span class="badge badge-content-review badge-content-guide mt-1">Por validar</span>
                            <div>
                                <div class="fw-semibold small">Pendiente de validación</div>
                                <div class="text-muted small">La IA finalizó la generación. Está listo para que un revisor lo apruebe o rechace.</div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 align-items-start">
                            <span class="badge badge-content-inreview badge-content-guide mt-1">En revisión</span>
                            <div>
                                <div class="fw-semibold small">En revisión</div>
                                <div class="text-muted small">Un revisor lo ha tomado y está evaluando el contenido actualmente.</div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 align-items-start">
                            <span class="badge badge-content-validated badge-content-guide mt-1">Validado</span>
                            <div>
                                <div class="fw-semibold small">Validado</div>
                                <div class="text-muted small">El contenido ha sido aprobado por un revisor y sincronizado con el ERP. Puede publicarse en tienda.</div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 align-items-start">
                            <span class="badge badge-content-published badge-content-guide mt-1">Publicado</span>
                            <div>
                                <div class="fw-semibold small">Publicado</div>
                                <div class="text-muted small">El contenido está validado y visible en la tienda online.</div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 align-items-start">
                            <span class="badge badge-content-danger badge-content-guide mt-1">Rechazado</span>
                            <div>
                                <div class="fw-semibold small">Rechazado</div>
                                <div class="text-muted small">El contenido no superó la revisión. Debe regenerarse o corregirse antes de volver al flujo.</div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 align-items-start border-top pt-3">
                            <span class="badge badge-content-danger badge-content-guide-wrap mt-1">Error</span>
                            <div>
                                <div class="fw-semibold small">Errores de generación</div>
                                <div class="text-muted small">
                                    <strong>Sin datos:</strong> El ERP no devolvió suficiente información del producto.<br>
                                    <strong>Sin fuente:</strong> No se pudo acceder a las fuentes web necesarias.<br>
                                    <strong>Error generación:</strong> La IA falló al procesar. Usa <em>Regenerar</em> para reintentar.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 align-items-start border-top pt-3">
                            <span class="badge badge-content-hold badge-content-guide mt-1">Sin contenido</span>
                            <div>
                                <div class="fw-semibold small">Sin contenido (retenido manualmente)</div>
                                <div class="text-muted small">El equipo lo marcó a propósito para no subirlo todavía, tenga o no contenido generado por la IA. Queda fuera del listado general hasta que alguien lo quite de aquí. Se pueden dejar notas internas (ej. "No subir hasta Septiembre").</div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter modal --}}
    <div class="modal fade" id="filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:420px;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="modal-title fw-bold mb-0">Filtros avanzados</h6>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="modal-status" class="form-control select2-modal">
                            <option value="">Todos los estados</option>
                            @foreach($contentStatuses as $key => $statusObj)
                                <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                    {{ $statusObj->label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Proveedor</label>
                        <select id="modal-supplier" class="form-control select2-modal">
                            <option value="">Todos los proveedores</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deporte</label>
                        <select id="modal-sport" class="form-control select2-modal">
                            <option value="">Todos los deportes</option>
                            @foreach($sports as $sport)
                                <option value="{{ $sport->id }}" {{ request('sport_id') == $sport->id ? 'selected' : '' }}>
                                    {{ $sport->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Categoría</label>
                        <select id="modal-erp-cat" class="form-control select2-modal">
                            <option value="">Todas las categorías</option>
                            @foreach($erpcategorias as $ec)
                                <option value="{{ $ec->erp_categoria_id }}"
                                        data-sport-id="{{ $ec->sport_id }}"
                                        {{ request('erp_categoria_id') == $ec->erp_categoria_id ? 'selected' : '' }}>
                                    {{ $ec->erp_categoria_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Familia</label>
                        <select id="modal-category" class="form-control select2-modal">
                            <option value="">Todas las familias</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}"
                                        data-sport-id="{{ $cat->sport_id }}"
                                        data-erp-cat-id="{{ $cat->erp_categoria_id }}"
                                        {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subfamilia</label>
                        <select id="modal-subfamily" class="form-control select2-modal">
                            <option value="">Todas las subfamilias</option>
                            @foreach($subfamilies as $sf)
                                <option value="{{ $sf->id }}"
                                        data-family-id="{{ $sf->category_id }}"
                                        {{ request('subfamily_id') == $sf->id ? 'selected' : '' }}>
                                    {{ $sf->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Prompt utilizado</label>
                        <select id="modal-prompt" class="form-control select2-modal">
                            <option value="">Todos los prompts</option>
                            @foreach($prompts as $pr)
                                <option value="{{ $pr->id }}" {{ request('prompt_id') == $pr->id ? 'selected' : '' }}>
                                    {{ $pr->label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de contenido</label>
                        <select id="modal-content-type" class="form-control select2-modal">
                            <option value="">Todos los tipos</option>
                            <option value="name"        {{ request('content_type') == 'name'        ? 'selected' : '' }}>Nombre</option>
                            <option value="description" {{ request('content_type') == 'description' ? 'selected' : '' }}>Descripción</option>
                            <option value="seo"         {{ request('content_type') == 'seo'         ? 'selected' : '' }}>SEO</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Desde</label>
                            <input type="date" id="modal-date-from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Hasta</label>
                            <input type="date" id="modal-date-to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Antigüedad de generación</label>
                        <select id="modal-older-than" class="form-control select2-modal">
                            <option value="">Todo (sin límite)</option>
                            <option value="30"  {{ request('older_than_days') == '30'  ? 'selected' : '' }}>Generado hace más de 30 días</option>
                            <option value="60"  {{ request('older_than_days') == '60'  ? 'selected' : '' }}>Generado hace más de 60 días</option>
                            <option value="90"  {{ request('older_than_days') == '90'  ? 'selected' : '' }}>Generado hace más de 90 días</option>
                        </select>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="modal-no-sources"
                               {{ request('no_sources') ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold" for="modal-no-sources">
                            Solo sin fuentes web consultadas
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 d-flex flex-column gap-2">
                    <button type="button" id="filter-apply-btn" class="btn btn-primary w-100">Aplicar</button>
                    <button type="button" id="filter-clear-btn" class="btn btn-outline-secondary w-100">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Preview modal (quick view) --}}
    <div class="modal fade" id="preview-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom px-4 py-3">
                    <div class="min-w-0 flex-grow-1">
                        <h6 class="modal-title fw-bold mb-0 text-truncate" id="preview-title">Cargando...</h6>
                        <small class="text-muted" id="preview-meta"></small>
                    </div>
                    <button type="button" class="btn-close ms-3" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3" id="preview-body">
                    <div class="text-center py-5 text-muted">
                        <div class="spinner-border text-primary mb-3" style="width:2rem;height:2rem;"></div>
                        <p class="small mb-0">Cargando vista previa…</p>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 gap-2">
                    <a href="#" id="preview-detail-link" class="btn btn-outline-secondary flex-grow-1" target="_blank">
                        <i class="fas fa-up-right-from-square me-1"></i> Ver detalle
                    </a>
                    <a href="#" id="preview-chat-link" class="btn btn-primary flex-grow-1" target="_blank">
                        <i class="fas fa-comments me-1"></i> Chat IA
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal confirmación aprobar --}}
    <div class="modal fade" id="approve-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-flex align-items-center justify-content-center rounded-circle"
                              style="width:32px;height:32px;flex-shrink:0;background:var(--pb-primary-soft);border:1px solid var(--pb-primary-border);">
                            <i class="fas fa-check" style="font-size:.8rem;color:var(--pb-primary-dark);"></i>
                        </span>
                        <h6 class="modal-title fw-bold mb-0">Aprobar contenido</h6>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <p class="text-muted small mb-3" id="approve-product-name"></p>

                    {{-- Preview de descripción larga --}}
                    <div id="approve-desc-wrapper" class="mb-3 d-none">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100 d-flex align-items-center justify-content-between"
                                id="approve-desc-toggle" aria-expanded="false" aria-controls="approve-desc-collapse">
                            <span><i class="fas fa-align-left me-1"></i> Ver descripción</span>
                            <i class="fas fa-chevron-down" id="approve-desc-chevron" style="font-size:.75rem;transition:transform .2s;"></i>
                        </button>
                        <div id="approve-desc-collapse" class="collapse mt-2">
                            <div id="approve-desc-content"
                                 class="border rounded p-3 bg-white overflow-auto"
                                 style="max-height:220px;font-size:.85rem;line-height:1.5;">
                                <span class="text-muted">Cargando…</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <div>
                            <label class="form-label fw-semibold mb-1" for="approve-nombre">Nombre del producto</label>
                            <input type="text" class="form-control" id="approve-nombre" placeholder="Nombre del producto...">
                            <div class="form-text">Se enviará al ERP como <code>nombre</code>.</div>
                        </div>
                        <div>
                            <label class="form-label fw-semibold mb-1" for="approve-marca">Marca (ID ERP)</label>
                            <input type="text" class="form-control" id="approve-marca" placeholder="Vacío si no aplica">
                            <div class="form-text">Deja vacío para omitir.</div>
                        </div>
                        <div>
                            <label class="form-label fw-semibold mb-1" for="approve-publicar">Publicar en tienda</label>
                            <select class="form-select" id="approve-publicar">
                                <option value="1" selected>Sí — publicar</option>
                                <option value="0">No — solo aprobar</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 d-flex flex-column gap-2">
                    <button id="approve-confirm-btn" type="button" class="btn btn-primary w-100">
                        Validar y sincronizar
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal confirmación rechazar --}}
    <div class="modal fade" id="reject-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-flex align-items-center justify-content-center rounded-circle bg-danger-subtle"
                              style="width:32px;height:32px;flex-shrink:0;">
                            <i class="fas fa-xmark text-danger" style="font-size:.8rem;"></i>
                        </span>
                        <h6 class="modal-title fw-bold mb-0">Rechazar contenido</h6>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <p class="text-muted small mb-3" id="reject-product-name"></p>
                    <label class="form-label fw-semibold small mb-1">Motivo del rechazo <span class="text-danger">*</span></label>
                    <textarea id="reject-reason-input" class="form-control" rows="3"
                              placeholder="Describe por qué se rechaza este contenido…"></textarea>
                    <div class="invalid-feedback" id="reject-reason-error">Debes escribir un motivo.</div>
                </div>
                <div class="modal-footer border-top px-4 py-3 gap-2">
                    <button type="button" class="btn btn-secondary flex-grow-1" data-bs-dismiss="modal">Cancelar</button>
                    <button id="reject-confirm-btn" type="button" class="btn btn-danger flex-grow-1">
                        <i class="fas fa-xmark me-1"></i> Rechazar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Marcar como sin contenido (individual) --}}
    <div class="modal fade" id="hold-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-flex align-items-center justify-content-center rounded-circle bg-secondary-subtle"
                              style="width:32px;height:32px;flex-shrink:0;">
                            <i class="fas fa-ban text-secondary" style="font-size:.8rem;"></i>
                        </span>
                        <h6 class="modal-title fw-bold mb-0">Marcar como sin contenido</h6>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <p class="text-muted small mb-3">
                        Este contenido saldrá del listado general hasta que lo quites de "Sin contenido".
                    </p>
                    <label class="form-label fw-semibold small mb-1">Nota interna <span class="text-muted fw-normal">(opcional)</span></label>
                    <textarea id="hold-notes-input" class="form-control" rows="3"
                              placeholder="Ej: No subir hasta Septiembre"></textarea>
                </div>
                <div class="modal-footer border-top px-4 py-3 d-flex flex-column gap-2">
                    <button id="hold-confirm-btn" type="button" class="btn btn-primary w-100">Marcar sin contenido</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal confirmación publicar --}}
    <div class="modal fade" id="publish-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold mb-0">Publicar en tienda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <p class="text-muted small mb-3">Selecciona el estado de publicación que se enviará al ERP.</p>
                    <label class="form-label fw-semibold mb-1" for="publish-publicar">Estado en tienda</label>
                    <select class="form-select" id="publish-publicar">
                        <option value="1" selected>1 — Publicar</option>
                        <option value="0">0 — No publicar</option>
                    </select>
                </div>
                <div class="modal-footer border-top px-4 py-3 d-flex flex-column gap-2">
                    <button id="publish-confirm-btn" type="button" class="btn btn-validate-green fw-semibold text-white w-100">
                        Publicar y sincronizar
                    </button>
                    <button type="button" class="btn btn-action-black fw-semibold text-white w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-flex align-items-center justify-content-center rounded-circle"
                              style="width:32px;height:32px;flex-shrink:0;background:var(--pb-primary-soft);border:1px solid var(--pb-primary-border);">
                            <i class="fas fa-bolt" style="font-size:.8rem;color:var(--pb-primary-dark);"></i>
                        </span>
                        <h6 class="modal-title fw-bold mb-0">Acción masiva</h6>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <p class="text-muted small mb-3">
                        Acción sobre <strong><span data-bulk-count>0</span> contenido(s)</strong> seleccionado(s).
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small mb-1">Acción a aplicar</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            @if($tab === 'available')
                                <option value="assign">Asignarme para revisión</option>
                                @if($canAssignOthers)
                                    <option value="assign-to-user">Asignar a usuario...</option>
                                @endif
                            @endif
                            @if($tab === 'mine')
                                <option value="unassign">Desasignarme</option>
                            @endif
                            <option value="approve">Aprobar</option>
                            <option value="publish">Publicar (solo validados)</option>
                            <option value="reject">Rechazar</option>
                            <option value="regenerate">Regenerar IA</option>
                            @if(request('status') === 'on_hold')
                                <option value="restore">Quitar de Sin contenido</option>
                            @else
                                <option value="hold">Marcar como sin contenido</option>
                            @endif
                        </select>
                    </div>
                    @if($canAssignOthers)
                    <div class="mb-3 d-none" id="assign-user-wrap">
                        <label class="form-label fw-semibold small mb-1">Asignar a</label>
                        <select id="bulk-assign-user" class="form-select select2-bulk">
                            <option value="">Seleccionar usuario...</option>
                        </select>
                    </div>
                    @endif
                    <div class="mb-0 d-none" id="bulk-publicar-wrap">
                        <label class="form-label fw-semibold small mb-1">Publicar en tienda</label>
                        <select id="bulk-publicar" class="form-select select2-bulk-publicar">
                            <option value="1">Sí — publicar</option>
                            <option value="0" selected>No — solo aprobar</option>
                        </select>
                    </div>
                    <div class="mb-0 d-none" id="bulk-hold-notes-wrap">
                        <label class="form-label fw-semibold small mb-1">Nota interna (opcional)</label>
                        <textarea id="bulk-hold-notes" class="form-control" rows="2" placeholder="Ej: No subir hasta Septiembre"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 d-flex flex-column gap-2">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de progreso bulk regenerar --}}
    <div class="modal fade" id="bulk-progress-modal" tabindex="-1" aria-hidden="true"
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
            <div class="modal-content border-0 shadow">

                <div class="modal-header border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-flex align-items-center justify-content-center rounded-circle"
                              style="width:32px;height:32px;flex-shrink:0;background:var(--pb-primary-soft);border:1px solid var(--pb-primary-border);">
                            <i class="fas fa-sparkles" style="font-size:.8rem;color:var(--pb-primary-dark);"></i>
                        </span>
                        <h6 class="modal-title fw-bold mb-0" id="bpm-title">Procesando acción</h6>
                    </div>
                </div>

                <div class="modal-body px-4 py-4">

                    {{-- Resumen encolados / fallidos --}}
                    <div id="bpm-summary" class="mb-4"></div>

                    {{-- Barra de progreso --}}
                    <div id="bpm-progress-wrap">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold text-muted">Generando contenido IA…</span>
                            <span class="small text-muted">
                                <span id="bpm-done" class="fw-bold text-dark">0</span>
                                <span class="text-muted"> / </span>
                                <span id="bpm-total">0</span>
                            </span>
                        </div>
                        <div class="progress rounded-pill" style="height:6px;">
                            <div id="bpm-bar"
                                 class="progress-bar progress-bar-striped progress-bar-animated"
                                 style="width:0%; background:var(--pb-primary);"
                                 role="progressbar"></div>
                        </div>
                        <p class="text-muted text-center mt-3 mb-0" style="font-size:.8rem;">
                            La tabla se actualizará automáticamente al finalizar.
                        </p>
                    </div>

                    {{-- Resultado final --}}
                    <div id="bpm-result" class="d-none text-center"></div>

                </div>

                <div class="modal-footer border-top px-4 py-3 d-none" id="bpm-footer">
                    <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal"
                            onclick="location.reload()">
                        Cerrar y actualizar tabla
                    </button>
                </div>

            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    const currentTab = '{{ $tab }}';

    // Inicializar tooltips de Bootstrap (iconos ERP sincronizado)
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // Filter modal selects — dropdownParent evita que queden detrás del modal
    $('.select2-modal').select2({ width: '100%', dropdownParent: $('#filter-modal') });

    // Approve modal select
    $('#approve-publicar').select2({ width: '100%', dropdownParent: $('#approve-modal'), minimumResultsForSearch: Infinity });

    // ── Filtros en cascada: Deporte → Categoría → Familia → Subfamilia ──────────
    // Guardar todas las opciones originales al inicio
    const allCatOptions      = $('#modal-erp-cat option').clone();
    const allFamilyOptions   = $('#modal-category option').clone();
    const allSubfamOptions   = $('#modal-subfamily option').clone();

    function rebuildSelect2(selector, newOptions) {
        const $sel = $(selector);
        const old  = $sel.val();
        $sel.empty().append(newOptions).val(
            newOptions.filter('[value="' + old + '"]').length ? old : ''
        ).trigger('change');
    }

    // Deporte cambia → filtrar Categorías
    $('#modal-sport').on('change', function () {
        const sportId = $(this).val();
        const opts = allCatOptions.filter(function () {
            return !$(this).val() || !sportId || $(this).data('sport-id') == sportId;
        }).clone();
        rebuildSelect2('#modal-erp-cat', opts);
        $('#modal-erp-cat').trigger('change'); // dispara el siguiente nivel
    });

    // Categoría cambia → filtrar Familias
    $('#modal-erp-cat').on('change', function () {
        const catId  = $(this).val();
        const sportId = $('#modal-sport').val();
        const opts = allFamilyOptions.filter(function () {
            const $o = $(this);
            if (!$o.val()) return true;
            if (sportId && $o.data('sport-id') != sportId) return false;
            if (catId   && $o.data('erp-cat-id') != catId) return false;
            return true;
        }).clone();
        rebuildSelect2('#modal-category', opts);
        $('#modal-category').trigger('change');
    });

    // Familia cambia → filtrar Subfamilias
    $('#modal-category').on('change', function () {
        const familyId = $(this).val();
        const opts = allSubfamOptions.filter(function () {
            return !$(this).val() || !familyId || $(this).data('family-id') == familyId;
        }).clone();
        rebuildSelect2('#modal-subfamily', opts);
    });

    // Limpiar en cascada al limpiar filtros
    $('#filter-clear-btn').on('click.cascade', function () {
        rebuildSelect2('#modal-erp-cat',   allCatOptions.clone());
        rebuildSelect2('#modal-category',  allFamilyOptions.clone());
        rebuildSelect2('#modal-subfamily', allSubfamOptions.clone());
    });

    // Pre-cargar estado si ya hay filtros activos en la URL
    (function preloadCascade() {
        const sportId = '{{ request('sport_id') }}';
        const catId   = '{{ request('erp_categoria_id') }}';
        if (sportId) $('#modal-sport').val(sportId).trigger('change.select2');
        if (catId)   $('#modal-erp-cat').val(catId).trigger('change.select2');
    })();

    $('#filter-apply-btn').on('click', function () {
        $('#filter-status').val($('#modal-status').val());
        $('#filter-supplier').val($('#modal-supplier').val());
        $('#filter-sport').val($('#modal-sport').val());
        $('#filter-erp-cat').val($('#modal-erp-cat').val());
        $('#filter-category').val($('#modal-category').val());
        $('#filter-subfamily').val($('#modal-subfamily').val());
        $('#filter-prompt').val($('#modal-prompt').val());
        $('#filter-content-type').val($('#modal-content-type').val());
        $('#filter-date-from').val($('#modal-date-from').val());
        $('#filter-date-to').val($('#modal-date-to').val());
        $('#filter-older-than-days').val($('#modal-older-than').val());
        $('input[name="no_sources"]').remove();
        if ($('#modal-no-sources').is(':checked')) {
            $('#content-filter-form').append('<input type="hidden" name="no_sources" value="1">');
        }
        $('#filter-modal').modal('hide');
        $('#content-filter-form').submit();
    });

    $('#filter-clear-btn').on('click', function () {
        $('#modal-status, #modal-supplier, #modal-sport, #modal-erp-cat, #modal-category, #modal-subfamily, #modal-prompt, #modal-content-type, #modal-older-than').val('').trigger('change');
        $('#modal-date-from, #modal-date-to').val('');
        $('#modal-no-sources').prop('checked', false);
    });

    // Approve — abre modal Bootstrap de confirmación
    let _approveUrl = null;
    const approveModal = new bootstrap.Modal(document.getElementById('approve-modal'));
    const _previewUrlTpl = '{{ route("settings.suppliers.content.preview", ":uid") }}';

    function _loadApproveDescription(uid) {
        const wrapper  = $('#approve-desc-wrapper');
        const content  = $('#approve-desc-content');
        const collapse = $('#approve-desc-collapse');

        // Reset colapso y chevron
        collapse.collapse('hide');
        $('#approve-desc-chevron').css('transform', '');
        $('#approve-desc-toggle').attr('aria-expanded', 'false');
        content.html('<span class="text-muted">Cargando…</span>');
        wrapper.addClass('d-none');

        $.getJSON(_previewUrlTpl.replace(':uid', uid), function(data) {
            if (data.long_description) {
                content.html(data.long_description);
                wrapper.removeClass('d-none');
            }
        }).fail(function() {
            // Si falla la carga, simplemente no mostramos el preview
        });
    }

    // Toggle chevron al expandir/colapsar
    $('#approve-desc-collapse').on('show.bs.collapse', function() {
        $('#approve-desc-chevron').css('transform', 'rotate(180deg)');
        $('#approve-desc-toggle').attr('aria-expanded', 'true');
    }).on('hide.bs.collapse', function() {
        $('#approve-desc-chevron').css('transform', '');
        $('#approve-desc-toggle').attr('aria-expanded', 'false');
    });

    $('#approve-desc-toggle').on('click', function() {
        $('#approve-desc-collapse').collapse('toggle');
    });

    $(document).on('submit', '.approve-form', function(e) {
        e.preventDefault();
        _approveUrl = $(this).attr('action');
        const productName = $(this).closest('tr').find('td:nth-child(2) strong').text().trim();
        $('#approve-product-name').text(productName ? 'Producto: ' + productName : '');
        $('#approve-nombre').val($(this).data('nombre') || '');
        $('#approve-marca').val($(this).data('marca') || '');

        // Extraer UID desde la URL de acción (.../content/{uid}/action)
        const uidMatch = _approveUrl.match(/\/content\/([^/]+)\/action/);
        if (uidMatch) {
            _loadApproveDescription(uidMatch[1]);
        }

        approveModal.show();
    });

    $('#approve-confirm-btn').on('click', function() {
        if (!_approveUrl) return;
        const token   = $('meta[name="csrf-token"]').attr('content');
        const publicar = parseInt($('#approve-publicar').val(), 10);
        const nombre   = $('#approve-nombre').val().trim();
        const marca    = $('#approve-marca').val().trim();
        const btn = $(this).prop('disabled', true).text('Procesando...');

        const approveData = { action: 'approve', publicar: publicar, _token: token, marca: marca };
        if (nombre) approveData.nombre = nombre;

        $.ajax({
            url: _approveUrl,
            method: 'POST',
            data: approveData,
            headers: { 'X-CSRF-TOKEN': token },
            success: function(res) {
                approveModal.hide();
                const erp = res.erp_result;
                if (erp && erp.success) {
                    toastr.success('Validado y sincronizado con ERP correctamente');
                } else if (erp) {
                    toastr.warning('Contenido validado pero falló la sincronización con ERP: ' + (erp.message || 'Error desconocido'));
                } else {
                    toastr.success(res.message ?? 'Contenido aprobado');
                }
                setTimeout(() => location.reload(), 700);
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al aprobar');
                btn.prop('disabled', false).text('Validar y sincronizar');
            }
        });
    });

    document.getElementById('approve-modal').addEventListener('hidden.bs.modal', function () {
        _approveUrl = null;
        $('#approve-nombre').val('');
        $('#approve-marca').val('');
        $('#approve-publicar').val('1').trigger('change');
        $('#approve-confirm-btn').prop('disabled', false).text('Validar y sincronizar');
        // Limpiar preview descripción
        $('#approve-desc-wrapper').addClass('d-none');
        $('#approve-desc-collapse').collapse('hide');
        $('#approve-desc-content').html('<span class="text-muted">Cargando…</span>');
        $('#approve-desc-chevron').css('transform', '');
    });

    // Reject modal
    let _rejectUrl = null;
    const rejectModal = new bootstrap.Modal(document.getElementById('reject-modal'));

    $(document).on('click', '.reject-btn', function() {
        _rejectUrl = '{{ route("settings.suppliers.content.action", ":uid") }}'.replace(':uid', $(this).data('uid'));
        const productName = $(this).closest('tr').find('td:nth-child(2) strong').text().trim();
        $('#reject-product-name').text(productName ? 'Producto: ' + productName : '');
        $('#reject-reason-input').val('').removeClass('is-invalid');
        rejectModal.show();
    });

    $('#reject-confirm-btn').on('click', function() {
        const reason = $('#reject-reason-input').val().trim();
        if (!reason) {
            $('#reject-reason-input').addClass('is-invalid');
            return;
        }
        const token = $('meta[name="csrf-token"]').attr('content');
        const btn = $(this).prop('disabled', true).text('Rechazando...');

        $.ajax({
            url: _rejectUrl,
            method: 'POST',
            data: JSON.stringify({ action: 'reject', reason: reason, _token: token }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': token },
            success: function(res) {
                rejectModal.hide();
                toastr.success(res.message ?? 'Contenido rechazado');
                setTimeout(() => location.reload(), 700);
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al rechazar');
                btn.prop('disabled', false).text('Rechazar');
            }
        });
    });

    document.getElementById('reject-modal').addEventListener('hidden.bs.modal', function() {
        _rejectUrl = null;
        $('#reject-reason-input').val('').removeClass('is-invalid');
        $('#reject-confirm-btn').prop('disabled', false).text('Rechazar');
    });

    // Publish form confirmation
    let _publishUrl = null;
    const publishModal = new bootstrap.Modal(document.getElementById('publish-modal'));

    $('#publish-publicar').select2({ width: '100%', dropdownParent: $('#publish-modal'), minimumResultsForSearch: Infinity });

    $(document).on('click', '.publish-btn', function () {
        _publishUrl = $(this).data('url');
        $('#publish-publicar').val('1').trigger('change');
        $('#publish-confirm-btn').prop('disabled', false).text('Publicar y sincronizar');
        publishModal.show();
    });

    $('#publish-confirm-btn').on('click', function () {
        if (!_publishUrl) return;
        const publicar = parseInt($('#publish-publicar').val(), 10);
        const $btn = $(this).prop('disabled', true).text('Sincronizando…');

        $.ajax({
            url: _publishUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { action: 'publish_erp', publicar: publicar },
            success: function (res) {
                publishModal.hide();
                if (res.success) {
                    toastr.success(res.message || 'Publicado y sincronizado correctamente', 'Contenido IA');
                } else {
                    toastr.warning(res.message || 'Error al sincronizar', 'Advertencia');
                }
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al publicar', 'Error');
                $btn.prop('disabled', false).text('Publicar y sincronizar');
            }
        });
    });

    document.getElementById('publish-modal').addEventListener('hidden.bs.modal', function () {
        _publishUrl = null;
    });

    // Unassign single item
    $('.unassign-btn').on('click', function() {
        const uid = $(this).data('uid');
        const unassignUrl = '{{ route("settings.suppliers.content.unassign", ":uid") }}'.replace(':uid', uid);

        Swal.fire({
            title: '¿Desasignarte?',
            text: 'Este contenido volverá a estar disponible para otros revisores.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, desasignar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: unassignUrl,
                    method: 'POST',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        toastr.success(res.message);
                        setTimeout(() => location.reload(), 600);
                    },
                    error: function (xhr) {
                        toastr.error(xhr.responseJSON?.message ?? 'Error al desasignar.');
                    },
                });
            }
        });
    });

    // Notas inline en el listado "Sin contenido" — guarda al perder el foco
    $(document).on('blur', '.content-notes-input', function () {
        const $el = $(this);
        const uid = $el.data('uid');
        const value = $el.val();

        if (value === $el.data('saved-value')) return;

        const editUrl = '{{ route("settings.suppliers.content.edit", ":uid") }}'.replace(':uid', uid);

        $.ajax({
            url: editUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { field: 'notes', value: value },
            success: function (res) {
                if (res.success) {
                    $el.data('saved-value', value);
                    toastr.success('Nota guardada', 'Sin contenido');
                } else {
                    toastr.error(res.message || 'Error al guardar la nota', 'Error');
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al guardar la nota', 'Error');
            },
        });
    });

    // Marcar sin contenido (individual) — misma acción que el bulk, con un solo id
    let _holdUid = null;
    const holdModal = new bootstrap.Modal(document.getElementById('hold-modal'));

    $(document).on('click', '.hold-content-btn', function () {
        _holdUid = $(this).data('uid');
        $('#hold-notes-input').val('');
        holdModal.show();
    });

    $('#hold-confirm-btn').on('click', function () {
        if (!_holdUid) return;
        const notes = $('#hold-notes-input').val();
        const $btn  = $(this).prop('disabled', true).text('Marcando…');

        $.ajax({
            url: '{{ route("settings.suppliers.content.bulk-action") }}',
            method: 'POST',
            data: JSON.stringify({ action: 'hold', ids: [_holdUid], notes: notes || null, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                holdModal.hide();
                toastr.success('Marcado como sin contenido');
                setTimeout(() => location.reload(), 600);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al marcar sin contenido.');
                $btn.prop('disabled', false).text('Marcar sin contenido');
            },
        });
    });

    document.getElementById('hold-modal').addEventListener('hidden.bs.modal', function () {
        _holdUid = null;
        $('#hold-notes-input').val('');
        $('#hold-confirm-btn').prop('disabled', false).text('Marcar sin contenido');
    });

    // Quitar de "Sin contenido" (individual) — vuelve a Por validar
    $(document).on('click', '.hold-restore-btn', function () {
        const uid = $(this).data('uid');

        $.ajax({
            url: '{{ route("settings.suppliers.content.bulk-action") }}',
            method: 'POST',
            data: JSON.stringify({ action: 'restore', ids: [uid], _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                toastr.success('Contenido restaurado a Por validar');
                setTimeout(() => location.reload(), 600);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al restaurar.');
            },
        });
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Preview modal (quick view)
    const previewModal = new bootstrap.Modal(document.getElementById('preview-modal'));
    const previewUrl = '{{ route("settings.suppliers.content.preview", ":uid") }}';

    $('.preview-content-btn').on('click', function () {
        const uid = $(this).data('uid');
        $('#preview-title').text('Cargando...');
        $('#preview-meta').text('');
        $('#preview-body').html('<div class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Cargando preview...</div>');
        previewModal.show();

        $.getJSON(previewUrl.replace(':uid', uid), function (data) {
            $('#preview-title').text(data.title);
            $('#preview-meta').html([
                '<span class="badge ' + data.status_class + '">' + data.status_label + '</span>',
                '<span class="ms-2 text-muted">' + (data.product_code || '') + '</span>',
                '<span class="ms-2 text-muted">' + (data.supplier || '') + '</span>',
                '<span class="ms-2 text-muted">' + (data.created_at || '') + '</span>'
            ].join(''));

            let body = '<div class="row g-3">';
            if (data.generated_name) {
                body += '<div class="col-12"><label class="form-label fw-semibold small text-muted">Nombre generado</label><div class="border rounded p-2 bg-light">' + $('<div>').text(data.generated_name).html() + '</div></div>';
            }
            if (data.short_description) {
                body += '<div class="col-12"><label class="form-label fw-semibold small text-muted">Descripción corta</label><div class="border rounded p-2 bg-light">' + $('<div>').text(data.short_description).html() + '</div></div>';
            }
            if (data.long_description) {
                body += '<div class="col-12"><label class="form-label fw-semibold small text-muted">Descripción larga (HTML)</label><div class="border rounded p-3 bg-white">' + data.long_description + '</div></div>';
            }
            if (data.seo_title || data.seo_description) {
                body += '<div class="col-12"><label class="form-label fw-semibold small text-muted">SEO</label><div class="border rounded p-2 bg-light">';
                if (data.seo_title) body += '<div><strong>Title:</strong> ' + $('<div>').text(data.seo_title).html() + '</div>';
                if (data.seo_description) body += '<div><strong>Meta:</strong> ' + $('<div>').text(data.seo_description).html() + '</div>';
                body += '</div></div>';
            }
            body += '</div>';
            if (!data.generated_name && !data.short_description && !data.long_description && !data.seo_title) {
                body = '<div class="text-center py-4 text-muted">Este contenido no tiene campos poblados.</div>';
            }
            $('#preview-body').html(body);
            $('#preview-detail-link').attr('href', data.detail_url);
            if (data.chat_url) {
                $('#preview-chat-link').show().attr('href', data.chat_url);
            } else {
                $('#preview-chat-link').hide();
            }
        }).fail(function () {
            $('#preview-body').html('<div class="alert alert-danger m-3">Error al cargar el preview.</div>');
        });
    });

    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });
    $('#bulk-publicar').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    // Sincroniza el contador del modal al abrirse (bulk.js solo actualiza el toolbar span)
    $('#bulk-modal').on('show.bs.modal', function () {
        const count = $('.bulk-checkbox:checked').length;
        $(this).find('[data-bulk-count]').text(count);
    });

    // Mostrar/ocultar selector de usuario al cambiar acción
    let _assignableUsersLoaded = false;
    $('#bulk-action-select').on('change', function () {
        const val = $(this).val();
        if (val === 'assign-to-user') {
            $('#assign-user-wrap').removeClass('d-none');
            if (!_assignableUsersLoaded) {
                _assignableUsersLoaded = true;
                $.getJSON('{{ route("settings.suppliers.content.assignable-users") }}', function (users) {
                    const $select = $('#bulk-assign-user');
                    // Destruir select2 antes de reconstruir opciones
                    if ($.fn.select2 && $select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }
                    $select.empty().append('<option value="">Seleccionar usuario...</option>');
                    if (!users.length) {
                        $select.append('<option value="" disabled>Sin usuarios disponibles</option>');
                    } else {
                        users.forEach(function (u) {
                            $select.append('<option value="' + u.id + '">' + $('<div>').text(u.name).html() + ' — ' + $('<div>').text(u.email).html() + '</option>');
                        });
                    }
                    // Inicializar Select2
                    if ($.fn.select2) {
                        $select.select2({ width: '100%', dropdownParent: $('#bulk-modal'), placeholder: 'Seleccionar usuario...' });
                    }
                }).fail(function () {
                    toastr.error('No se pudo cargar la lista de usuarios.');
                    _assignableUsersLoaded = false;
                });
            }
        } else {
            $('#assign-user-wrap').addClass('d-none');
        }

        if (val === 'approve') {
            $('#bulk-publicar-wrap').removeClass('d-none');
        } else {
            $('#bulk-publicar-wrap').addClass('d-none');
        }

        if (val === 'hold') {
            $('#bulk-hold-notes-wrap').removeClass('d-none');
        } else {
            $('#bulk-hold-notes-wrap').addClass('d-none');
        }
    });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-publicar').val('0');
        $('#bulk-hold-notes').val('');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un contenido.'); return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        // Asignar a sí mismo: usa el endpoint dedicado
        if (action === 'assign') {
            $.ajax({
                url: '{{ route("settings.suppliers.content.bulk-assign") }}',
                method: 'POST',
                data: JSON.stringify({ action: 'assign', ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    $('#bulk-modal').modal('hide');
                    toastr.success(res.message);
                    setTimeout(() => location.reload(), 800);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message ?? 'Error al asignar.');
                    $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
                },
            });
            return;
        }

        // Asignar a otro usuario (admin)
        if (action === 'assign-to-user') {
            const userId = $('#bulk-assign-user').val();
            if (!userId) { toastr.warning('Selecciona un usuario.'); $('#bulk-apply-btn').prop('disabled', false).text('Aplicar'); return; }
            $.ajax({
                url: '{{ route("settings.suppliers.content.bulk-assign") }}',
                method: 'POST',
                data: JSON.stringify({ action: 'assign', ids: ids, user_id: parseInt(userId), _token: $('meta[name="csrf-token"]').attr('content') }),
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    $('#bulk-modal').modal('hide');
                    toastr.success(res.message);
                    setTimeout(() => location.reload(), 800);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message ?? 'Error al asignar.');
                    $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
                },
            });
            return;
        }

        const payload = { action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') };
        if (action === 'approve') {
            payload.publicar = parseInt($('#bulk-publicar').val(), 10);
        }
        if (action === 'hold') {
            payload.notes = $('#bulk-hold-notes').val();
        }

        $.ajax({
            url: '{{ route("settings.suppliers.content.bulk-action") }}',
            method: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                bulk.reset();

                const results  = res.results || [];
                const queued   = results.filter(r => r.success);
                const failed   = results.filter(r => !r.success);

                // Mostrar panel de resumen
                showBulkSummary(action, queued, failed);

                // Si hay jobs encolados (regeneración), hacer polling
                if (action === 'regenerate' && queued.length) {
                    const queuedUids = queued.map(r => r.uid);
                    pollBulkStatus(queuedUids, failed);
                } else if (queued.length) {
                    setTimeout(() => location.reload(), 1500);
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    // ── Modal de progreso bulk ───────────────────────────────────────────────
    const $bpm      = $('#bulk-progress-modal');
    const bpmModal  = new bootstrap.Modal($bpm[0], { backdrop: 'static', keyboard: false });

    function showBulkSummary(action, queued, failed) {
        const actionLabels = { regenerate:'Regenerar IA', approve:'Aprobar', reject:'Rechazar', publish:'Publicar' };
        $('#bpm-title').text('Resumen — ' + (actionLabels[action] || action));

        // Construir HTML del resumen
        let html = '';
        if (queued.length) {
            const msg = action === 'regenerate' ? 'encolado(s) para generación IA' : 'procesado(s) correctamente';
            html += `<div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-3"
                          style="background:var(--pb-primary-soft);border:1px solid var(--pb-primary-border);">
                <div class="d-flex align-items-center justify-content-center rounded-circle"
                     style="width:36px;height:36px;flex-shrink:0;background:var(--pb-primary);">
                    <i class="fas fa-${action === 'regenerate' ? 'sparkles' : 'check'} text-white" style="font-size:.8rem;"></i>
                </div>
                <div>
                    <div class="fw-bold" style="color:var(--pb-primary-dark);">${queued.length} ${msg}</div>
                    ${action === 'regenerate' ? '<div class="text-muted small">El proceso comenzará en breve.</div>' : ''}
                </div>
            </div>`;
        }
        if (failed.length) {
            const msgs = [...new Set(failed.map(r => r.message).filter(Boolean))];
            html += `<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-danger-subtle">
                <div class="d-flex align-items-center justify-content-center rounded-circle bg-danger"
                     style="width:36px;height:36px;flex-shrink:0;">
                    <i class="fas fa-triangle-exclamation text-white" style="font-size:.8rem;"></i>
                </div>
                <div class="min-w-0">
                    <div class="fw-bold text-danger">${failed.length} no procesado(s)</div>
                    <div class="mt-1">
                        ${msgs.slice(0,3).map(m => `<div class="text-muted small">• ${m}</div>`).join('')}
                    </div>
                </div>
            </div>`;
        }
        $('#bpm-summary').html(html);

        // Configurar barra de progreso
        if (action === 'regenerate' && queued.length) {
            // El total es solo los encolados (los fallidos ya se muestran arriba)
            $('#bpm-total').text(queued.length);
            $('#bpm-done').text(0);
            $('#bpm-bar').css('width', '0%');
            $('#bpm-progress-wrap').removeClass('d-none');
            $('#bpm-result').addClass('d-none');
            $('#bpm-footer').addClass('d-none');
        } else {
            // Para acciones inmediatas no hay polling
            $('#bpm-progress-wrap').addClass('d-none');
            $('#bpm-result').removeClass('d-none').html(
                queued.length
                    ? `<div class="text-success text-center"><i class="fas fa-check-circle fa-2x mb-2"></i><br>Acción aplicada correctamente.</div>`
                    : `<div class="text-muted text-center small">Sin cambios.</div>`
            );
            $('#bpm-footer').removeClass('d-none');
        }

        bpmModal.show();
    }

    // ── Polling hasta que todos los jobs terminen ────────────────────────────
    function pollBulkStatus(uids, failedItems = []) {
        let pollTimer = null;
        const total   = uids.length;

        function check() {
            $.ajax({
                url: '{{ route("settings.suppliers.content.bulk-status") }}',
                method: 'POST',
                data: JSON.stringify({ ids: uids, _token: $('meta[name="csrf-token"]').attr('content') }),
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(res) {
                    const items    = res.items || [];
                    const doneCount = items.filter(i => i.done).length;
                    const pct      = total > 0 ? Math.round((doneCount / total) * 100) : 0;

                    // Actualizar barra
                    $('#bpm-done').text(doneCount);
                    $('#bpm-bar').css('width', pct + '%');

                    // Actualizar badges en tabla en tiempo real
                    items.forEach(item => {
                        $('tr[data-uid="' + item.uid + '"]').find('.badge-status')
                            .text(item.status_label)
                            .removeClass().addClass('badge badge-status ' + item.badge_class);
                    });

                    if (res.all_done) {
                        clearInterval(pollTimer);

                        const ok  = items.filter(i => i.success);
                        const err = items.filter(i => i.error);

                        // Barra completa — color del tema
                        $('#bpm-bar').removeClass('progress-bar-striped progress-bar-animated')
                                     .css({'width':'100%','background':'var(--pb-primary)'});
                        $('#bpm-progress-wrap .spinner-border').parent().remove();

                        // Resultado final dentro del modal
                        let resultHtml = `<div class="py-1">`;
                        if (ok.length) resultHtml += `
                            <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-2"
                                 style="background:var(--pb-primary-soft);border:1px solid var(--pb-primary-border);">
                                <i class="fas fa-circle-check fa-xl" style="color:var(--pb-primary);"></i>
                                <div>
                                    <div class="fw-bold" style="color:var(--pb-primary-dark);">${ok.length} generado(s) correctamente</div>
                                    <div class="text-muted small">Listos para revisar y aprobar.</div>
                                </div>
                            </div>`;
                        if (err.length) {
                            resultHtml += `<div class="p-3 rounded-3 bg-danger-subtle mb-2">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="fas fa-circle-xmark text-danger"></i>
                                    <span class="fw-bold text-danger">${err.length} con error de generación</span>
                                </div>`;
                            err.forEach(item => {
                                const errMsg = item.error_msg || 'Error desconocido';
                                resultHtml += `<div class="d-flex gap-2 small mb-1">
                                    <span class="text-muted flex-shrink-0">•</span>
                                    <span class="text-danger">${errMsg}</span>
                                </div>`;
                            });
                            resultHtml += `</div>`;
                        }
                        // Mostrar los que no tenían prompt (failed antes del encolado)
                        if (failedItems.length) {
                            const msgs = [...new Set(failedItems.map(r => r.message).filter(Boolean))];
                            resultHtml += `
                            <div class="p-3 rounded-3 bg-light border mt-2">
                                <div class="fw-semibold small mb-1">
                                    <i class="fas fa-circle-info text-muted me-1"></i>
                                    ${failedItems.length} sin prompt asignado:
                                </div>
                                ${msgs.slice(0,3).map(m=>`<div class="text-muted small">• ${m}</div>`).join('')}
                                <div class="text-muted small mt-1">
                                    Ve a <a href="/panel/setting/suppliers/prompts" target="_blank">Prompts</a> y asigna una subfamilia.
                                </div>
                            </div>`;
                        }
                        resultHtml += `</div>`;
                        $('#bpm-result').removeClass('d-none').html(resultHtml);

                        // Mostrar botón de cierre
                        $('#bpm-footer').removeClass('d-none');

                        // Auto-cerrar solo si no hay errores; si hay, esperar que el usuario cierre
                        if (!err.length && !failedItems.length) {
                            setTimeout(() => {
                                bpmModal.hide();
                                location.reload();
                            }, 2000);
                        }
                    }
                }
            });
        }

        setTimeout(check, 3000);
        pollTimer = setInterval(check, 4000);
    }

});
</script>
@endpush
