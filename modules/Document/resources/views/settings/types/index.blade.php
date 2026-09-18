@extends('layouts.theme')

@section('title', 'Tipos de documentos')

@section('page_header')
    @include('core::components.card', ['title' => 'Tipos de Documentos'])
@endsection

@section('content')
    @php
        $dtFiltering = request()->hasAny(['search', 'status']);
        $dtActiveFilterCount = collect(['status'])->filter(fn ($k) => request($k) !== null && request($k) !== '')->count();
    @endphp

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- System Settings Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center gap-3">
                    <div class="me-3">
                        <h5 class="mb-1 fw-bold">Tipos de documentos</h5>
                        <p class="small mb-0 text-muted">Gestiona los tipos de documentos con soporte multi-idioma y requisitos personalizados</p>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <a href="{{ route('settings.documents.types.create') }}" class="btn btn-primary">
                            Nuevo tipo
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
                                <small class="text-muted">Tipos registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">Disponibles para usar</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Inactivos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['inactive']) }}</h4>
                                <small class="text-muted">Ocultos en los formularios</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.documents.types.index') }}" id="dt-filter-form">
                    <input type="hidden" name="status" id="dt-filter-status" value="{{ request('status') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por tipo, etiqueta o descripcion..."
                               value="{{ request('search') }}">

                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#dt-filter-modal" title="Filtros avanzados">
                            <i class="fas fa-filter"></i>
                            @if($dtActiveFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary ts-filter-badge">
                                    {{ $dtActiveFilterCount }}
                                </span>
                            @endif
                        </button>

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($dtFiltering)
                                <a href="{{ route('settings.documents.types.index') }}" class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <!-- Document Types List -->
            <div class="card-body">
                @if($documentTypes->count() > 0)
                    <div class="alert alert-info mb-3">
                        <i class="fa fa-circle-info me-2"></i>
                        Cada tipo de documento puede tener traducciones en múltiples idiomas y requisitos personalizados
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th >Etiqueta</th>
                                    <th >Tipo</th>
                                    <th  class="text-center">Requisitos</th>
                                    <th  class="text-center">Traducciones</th>
                                    <th  class="text-center">Estado</th>
                                    <th  class="text-center">Orden</th>
                                    <th  class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($documentTypes as $type)
                                    @php
                                        $translation = $type->translate(session('lang_id', 1));
                                        $totalLangs = $langs->count();
                                        $completedLangs = $type->getTranslationsList()->count();
                                        $translationPercentage = $totalLangs > 0 ? round(($completedLangs / $totalLangs) * 100) : 0;
                                    @endphp
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $type->id }}"></td>
                                        <td>
                                            <div>
                                                <strong>{{ $type->label }}</strong>
                                                @if($type->description)
                                                    <br>
                                                    <small class="text-muted">{{ Str::limit($type->description, 60) }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded">{{ $type->slug }}</code>
                                        </td>
                                        <td class="text-center">
                                            @if($type->requirements->count() > 0)
                                                <span class="badge bg-info-subtle text-info">
                                                    {{ $type->requirements->count() }}
                                                </span>
                                            @else
                                                <span class="badge bg-light text-muted">
                                                    <i class="fa fa-minus"></i>
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($translationPercentage === 100)
                                                <span class="badge bg-success-subtle text-success">
                                                    {{ $completedLangs }}/{{ $totalLangs }}
                                                </span>
                                            @elseif($translationPercentage > 0)
                                                <span class="badge bg-warning-subtle text-warning">
                                                    {{ $completedLangs }}/{{ $totalLangs }}
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">
                                                    0/{{ $totalLangs }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($type->is_active)
                                                <span class="badge bg-success-subtle text-success">
                                                    Activo
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                    Inactivo
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark">{{ $type->sort_order }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('settings.documents.types.edit', $type->slug) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('settings.documents.types.toggle-active', $type->slug) }}"
                                                              method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                @if($type->is_active)
                                                                    Desactivar
                                                                @else
                                                                    Activar
                                                                @endif
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a
                                                                class="dropdown-item delete-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#delete-modal"
                                                                data-url="{{ route('settings.documents.types.destroy', $type->slug) }}"
                                                                data-title="Eliminar tipo: {{ $type->getLabel() }}">
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
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fa fa-inbox fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay tipos de documentos para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados para "{{ request('search') }}"
                                @else
                                    Crea tu primer tipo de documento para comenzar
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.documents.types.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-plus"></i> Crear Primer Tipo
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($documentTypes->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $documentTypes->firstItem() }}</strong> a <strong>{{ $documentTypes->lastItem() }}</strong>
                            de <strong>{{ $documentTypes->total() }}</strong> tipos
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $documentTypes->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @include('core::components.delete')

    {{-- Filtros avanzados --}}
    <div class="modal fade" id="dt-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="dt-modal-status" class="form-control select2-filter-modal">
                            <option value="">Activos e inactivos</option>
                            <option value="1" @selected(request('status') === '1')>Solo activos</option>
                            <option value="0" @selected(request('status') === '0')>Solo inactivos</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="dt-filter-apply-btn" class="btn btn-primary w-100 mb-1">
                        Aplicar filtros
                    </button>
                    <button type="button" id="dt-filter-clear-btn" class="btn btn-secondary w-100">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Barra flotante de seleccion --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none ts-bulk-toolbar">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar accion
        </button>
    </div>

    {{-- Modal de accion masiva --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Accion masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicara la accion sobre <strong><span data-bulk-count>0</span> tipo(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Accion</label>
                        <select id="bulk-action-select" class="form-select select2">
                            <option value="">Seleccionar accion...</option>
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

@push('styles')
<style>
    .ts-bulk-toolbar { z-index: 1050; }
    .ts-filter-badge { font-size: .6rem; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script>
$(document).ready(function() {
    // 'select.select2': el contenedor que genera select2 hereda esa clase y un
    // selector por clase acabaria reinicializandose sobre si mismo.
    $('select.select2').select2({
        allowClear: false,
        minimumResultsForSearch: Infinity
    });

    // --- Filtros avanzados: el modal solo rellena los hidden del formulario ---
    $('.select2-filter-modal').select2({ dropdownParent: $('#dt-filter-modal'), width: '100%' });

    $('#dt-filter-apply-btn').on('click', function () {
        $('#dt-filter-status').val($('#dt-modal-status').val());
        $('#dt-filter-modal').modal('hide');
        $('#dt-filter-form').submit();
    });

    $('#dt-filter-clear-btn').on('click', function () {
        window.location = '{{ route('settings.documents.types.index') }}';
    });

    // --- Seleccion masiva ---
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });
    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids = bulk.getIds();

        if (!action) {
            toastr.warning('Selecciona una accion.');
            return;
        }
        if (!ids.length) {
            toastr.warning('No hay tipos seleccionados.');
            return;
        }

        const $btn = $(this).prop('disabled', true).text('Aplicando...');

        $.ajax({
            url: '{{ route('settings.documents.types.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (res) {
            toastr.success(res.message || 'Accion aplicada.');
            setTimeout(() => location.reload(), 1000);
        }).fail(function (xhr) {
            toastr.error(xhr.responseJSON?.message || 'Error al aplicar la accion.');
            $btn.prop('disabled', false).text('Aplicar');
        });
    });

    // Delete modal functionality
    $('.delete-btn').on('click', function() {
        const deleteUrl = $(this).data('url');
        const deleteTitle = $(this).data('title');

        $('#delete-modal .modal-title').text(deleteTitle);
        $('#delete-form').attr('action', deleteUrl);
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
