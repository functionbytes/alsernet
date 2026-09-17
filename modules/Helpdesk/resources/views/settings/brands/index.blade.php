@extends('layouts.theme')

@section('title', 'Marcas')

@push('styles')
<style>
.hd-color-swatch { width: 24px; height: 24px; display: inline-block; }
.hd-bulk-toolbar { z-index: 1050; }
.hd-filter-badge { font-size: .6rem; }
</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Marcas'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Marcas</h5>
                    <p class="small mb-0 text-muted">Gestiona las marcas del helpdesk, cada una con su propia identidad visual y configuracion de correo</p>
                </div>
                @can('helpdesk.brands.manage')
                    <a href="{{ route('settings.helpdesk.brands.create') }}" class="btn btn-primary">
                        Nueva marca
                    </a>
                @endcan
            </div>
        </div>

        {{-- Stats --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title text-primary mb-2">Total</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                            <small class="text-muted">Marcas configuradas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title  mb-2">Activas</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                            <small class="text-muted">Marcas en uso</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title  mb-2">Con bandejas</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['with_inboxes'] }}</h4>
                            <small class="text-muted">Tienen inboxes asignados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search --}}
        <div class="card-body border-bottom">
            @php
                $activeFilterCount = collect(['status'])->filter(fn ($k) => request($k))->count();
                $hasAnyFilter = $activeFilterCount > 0 || request('search');
            @endphp
            <form id="brands-filter-form" method="GET" action="{{ route('settings.helpdesk.brands.index') }}">
                <input type="hidden" name="status" id="filter-status" value="{{ request('status') }}">

                <div class="d-flex align-items-center gap-2">
                    <input type="search" name="search" class="form-control flex-grow-1"
                        placeholder="Buscar por nombre, slug, dominio o correo..."
                        value="{{ request('search') }}">

                    <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                        data-bs-toggle="modal" data-bs-target="#brands-filter-modal" title="Filtros avanzados">
                        <i class="fas fa-filter"></i>
                        @if($activeFilterCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary hd-filter-badge">{{ $activeFilterCount }}</span>
                        @endif
                    </button>

                    <div class="d-flex gap-1 flex-shrink-0">
                        <button type="submit" class="btn btn-primary" title="Buscar">
                            <i class="fas fa-magnifying-glass"></i>
                        </button>
                        @if($hasAnyFilter)
                            <a href="{{ route('settings.helpdesk.brands.index') }}"
                                class="btn btn-secondary" title="Limpiar filtros">
                                <i class="fas fa-xmark"></i>
                            </a>
                        @endif
                    </div>
                </div>

                @if($activeFilterCount > 0)
                    <div class="d-flex gap-2 flex-wrap mt-4">
                        <div>
                            <h6 class="mb-1">Filtrados:</h6>
                        </div>
                        @if(request('status'))
                            <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                Estado: {{ request('status') === 'active' ? 'Activas' : 'Inactivas' }}
                            </span>
                        @endif
                    </div>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body">
            @if($brands->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Dominio</th>
                                <th scope="col" class="text-center">Color</th>
                                <th scope="col">Correo</th>
                                <th scope="col" class="text-center">Estado</th>
                                <th scope="col" class="text-center">Inboxes</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($brands as $brand)
                                <tr>
                                    <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $brand->id }}"></td>
                                    <td>
                                        <strong>{{ $brand->name }}</strong>
                                        <div><small class="text-muted">{{ $brand->slug }}</small></div>
                                    </td>
                                    <td>
                                        @if($brand->domain)
                                            <span class="small">{{ $brand->domain }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($brand->primary_color)
                                            <span class="hd-color-swatch rounded border"
                                                data-color="{{ $brand->primary_color }}"
                                                title="{{ $brand->primary_color }}">
                                            </span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($brand->email_from_address)
                                            <span class="small">{{ $brand->email_from_address }}</span>
                                            @if($brand->email_from_name)
                                                <div><small class="text-muted">{{ $brand->email_from_name }}</small></div>
                                            @endif
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($brand->is_active)
                                            <span class="badge bg-success-subtle text-success">Activa</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold">{{ $brand->inboxes_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @can('helpdesk.brands.manage')
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.brands.edit', $brand) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('settings.helpdesk.brands.toggle', $brand) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                {{ $brand->is_active ? 'Desactivar' : 'Activar' }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item btn-delete"
                                                            data-id="{{ $brand->id }}"
                                                            data-url="{{ route('settings.helpdesk.brands.destroy', $brand) }}"
                                                            data-name="{{ $brand->name }}">
                                                            Eliminar
                                                        </button>
                                                    </li>
                                                @endcan
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
                            <i class="fas fa-building fs-7"></i>
                        </div>
                        <h6 class="mb-1">No hay marcas configuradas</h6>
                        <p class="text-muted mb-3">
                            @if(request('search') || request('status'))
                                No se encontraron resultados para los filtros aplicados
                            @else
                                Crea tu primera marca para personalizar la identidad del helpdesk
                            @endif
                        </p>
                        @if(! request('search') && ! request('status'))
                            @can('helpdesk.brands.manage')
                                <a href="{{ route('settings.helpdesk.brands.create') }}" class="btn btn-sm btn-primary">
                                    Crear primera marca
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>
            @endif
        </div>

        @if($brands->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Mostrando <strong>{{ $brands->firstItem() }}</strong> a <strong>{{ $brands->lastItem() }}</strong>
                        de <strong>{{ $brands->total() }}</strong> marcas
                    </div>
                    {{ $brands->appends(request()->input())->links() }}
                </div>
            </div>
        @endif

    </div>

    @include('core::components.delete')

    {{-- Filter modal --}}
    <div class="modal fade" id="brands-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="modal-status" class="form-control select2-filter-modal">
                            <option value="">Cualquier estado</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activas</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivas</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="brands-filter-apply-btn" class="btn btn-primary w-100 mb-1">
                        Aplicar filtros
                    </button>
                    <button type="button" id="brands-filter-clear-btn" class="btn btn-secondary w-100">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk toolbar --}}
    <div id="bulk-toolbar" class="hd-bulk-toolbar position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none">
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> marca(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="activate">Activar</option>
                            <option value="deactivate">Desactivar</option>
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

@endsection

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script>
window.HdPageFlash = { success: @json(session('success')), error: @json(session('error')) };
window.HdBrandsIndexConfig = {
    bulkUrl: '{{ route('settings.helpdesk.brands.bulk-action') }}',
};
</script>
<script src="{{ asset('vendor/helpdesk/settings/settings-common.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/settings-common.js')) }}" defer></script>
<script src="{{ asset('vendor/helpdesk/settings/brands-index.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/brands-index.js')) }}" defer></script>
@endpush
