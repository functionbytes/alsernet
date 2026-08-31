@extends('layouts.theme')

@section('title', 'Banners')

@push('styles')
<style>.hd-bulk-toolbar { z-index: 1050; }</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Banners'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Banners configurados</h5>
                        <p class="small mb-0 text-muted">Mensajes informativos mostrados a los clientes en el widget de chat</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('settings.helpdesk.banners.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo banner
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Banners registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">Visibles ahora</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Programados</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['scheduled']) }}</h4>
                                <small class="text-muted">Con fecha de inicio futura</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="card-body border-bottom">
                @php
                    $typeColors = ['info' => 'primary', 'success' => 'success', 'warning' => 'warning', 'danger' => 'danger'];
                    $typeLabels = ['info' => 'Info', 'success' => 'Exito', 'warning' => 'Advertencia', 'danger' => 'Peligro'];
                    $activeFilterCount = collect(['type', 'status'])->filter(fn ($k) => request($k) !== null && request($k) !== '')->count();
                    $hasAnyFilter = $activeFilterCount > 0 || request('search');
                @endphp
                <form id="banners-filter-form" method="GET" action="{{ route('settings.helpdesk.banners.index') }}">
                    <input type="hidden" name="type" id="filter-type" value="{{ request('type') }}">
                    <input type="hidden" name="status" id="filter-status" value="{{ request('status') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por titulo..."
                               value="{{ request('search') }}">

                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#banners-filter-modal" title="Filtros avanzados">
                            <i class="fas fa-filter"></i>
                            @if($activeFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">{{ $activeFilterCount }}</span>
                            @endif
                        </button>

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($hasAnyFilter)
                                <a href="{{ route('settings.helpdesk.banners.index') }}"
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
                            @if(request('type'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Tipo: {{ $typeLabels[request('type')] ?? request('type') }}
                                </span>
                            @endif
                            @if(request('status') !== null && request('status') !== '')
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Estado: {{ request('status') === 'active' ? 'Activos' : 'Inactivos' }}
                                </span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($banners->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th scope="col">Titulo</th>
                                    <th scope="col">Tipo</th>
                                    <th scope="col">Vigencia</th>
                                    <th scope="col" class="text-center">Cerrable</th>
                                    <th scope="col" class="text-center">Estado</th>
                                    <th scope="col" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($banners as $banner)
                                    @php
                                        $badgeColor = $typeColors[$banner->type] ?? 'secondary';
                                        $typeLabel = $typeLabels[$banner->type] ?? $banner->type;
                                    @endphp
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $banner->id }}"></td>
                                        <td>
                                            <div class="fw-semibold">{{ $banner->title }}</div>
                                            @if($banner->cta_text)
                                                <small class="text-muted">CTA: {{ $banner->cta_text }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }}">
                                                {{ $typeLabel }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                @if($banner->starts_at || $banner->ends_at)
                                                    {{ $banner->starts_at ? $banner->starts_at->format('d/m/Y') : '—' }}
                                                    →
                                                    {{ $banner->ends_at ? $banner->ends_at->format('d/m/Y') : '—' }}
                                                @else
                                                    Siempre
                                                @endif
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            @if($banner->dismissible)
                                                <span class="badge bg-success-subtle text-success">Si</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">No</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($banner->is_active)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.banners.edit', $banner->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.helpdesk.banners.destroy', $banner->id) }}"
                                                           data-title="Eliminar banner: {{ $banner->title }}">
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
                        <i class="fas fa-bullhorn fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if(request()->hasAny(['search', 'type', 'status']))
                                No se encontraron resultados
                            @else
                                No hay banners configurados
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if(request()->hasAny(['search', 'type', 'status']))
                                Intenta con otros filtros de busqueda
                            @else
                                Crea tu primer banner para mostrar mensajes a tus clientes
                            @endif
                        </p>
                        @if(request()->hasAny(['search', 'type', 'status']))
                            <a href="{{ route('settings.helpdesk.banners.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @else
                            <a href="{{ route('settings.helpdesk.banners.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Nuevo banner
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($banners->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $banners->firstItem() }} - {{ $banners->lastItem() }} de {{ $banners->total() }}
                        </div>
                        <div>
                            {{ $banners->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Filter modal --}}
    <div class="modal fade" id="banners-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo</label>
                        <select id="modal-type" class="form-control select2-filter-modal">
                            <option value="">Todos los tipos</option>
                            @foreach($typeLabels as $value => $label)
                                <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="modal-status" class="form-control select2-filter-modal">
                            <option value="">Todos</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activos</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="banners-filter-apply-btn" class="btn btn-primary w-100 mb-1">
                        Aplicar filtros
                    </button>
                    <button type="button" id="banners-filter-clear-btn" class="btn btn-secondary w-100">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="hd-bulk-toolbar position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> banner(s)</strong>.</p>
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

    @include('core::components.delete')

@endsection

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    // Filter modal
    $('.select2-filter-modal').select2({ dropdownParent: $('#banners-filter-modal'), width: '100%' });

    $('#banners-filter-apply-btn').on('click', function () {
        $('#filter-type').val($('#modal-type').val());
        $('#filter-status').val($('#modal-status').val());
        $('#banners-filter-modal').modal('hide');
        $('#banners-filter-form').submit();
    });

    $('#banners-filter-clear-btn').on('click', function () {
        $('#modal-type, #modal-status').val(null).trigger('change');
    });

    // Bulk actions
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un banner.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' banner(s) seleccionados?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route("settings.helpdesk.banners.bulk-action") }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
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
});
</script>
@endpush
