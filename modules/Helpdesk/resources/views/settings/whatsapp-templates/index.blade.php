@extends('layouts.theme')

@section('title', 'Templates de WhatsApp')

@section('page_header')
    @include('core::components.card', ['title' => 'Templates de WhatsApp'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Templates de WhatsApp</h5>
                    <p class="small mb-0 text-muted">Templates aprobados por Meta disponibles para enviar en campanas de WhatsApp</p>
                </div>
                @can('helpdesk.whatsapp-templates.manage')
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.helpdesk.whatsapp-templates.create') }}" class="btn btn-outline-primary">
                            Nueva plantilla
                        </a>
                        <form action="{{ route('settings.helpdesk.whatsapp-templates.sync') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                Sincronizar desde Meta
                            </button>
                        </form>
                    </div>
                @endcan
            </div>
        </div>

        {{-- Stats --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Total</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                            <small class="text-muted">Templates registrados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Aprobados</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['approved'] }}</h4>
                            <small class="text-muted">Listos para enviar</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Pendientes</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['pending'] }}</h4>
                            <small class="text-muted">En revision por Meta</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Rechazados</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['rejected'] }}</h4>
                            <small class="text-muted">No aprobados por Meta</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card-body border-bottom">
            @php
                $activeFilterCount = collect(['status', 'category', 'language'])->filter(fn ($k) => request($k))->count();
                $hasAnyFilter = $activeFilterCount > 0 || request('search');
                $statusLabels = ['approved' => 'Aprobado', 'pending' => 'Pendiente', 'rejected' => 'Rechazado'];
                $categoryLabels = ['utility' => 'Utilidad', 'marketing' => 'Marketing', 'authentication' => 'Autenticacion'];
            @endphp
            <form id="whatsapp-templates-filter-form" method="GET" action="{{ route('settings.helpdesk.whatsapp-templates.index') }}">
                <input type="hidden" name="status" id="filter-status" value="{{ request('status') }}">
                <input type="hidden" name="category" id="filter-category" value="{{ request('category') }}">
                <input type="hidden" name="language" id="filter-language" value="{{ request('language') }}">

                <div class="d-flex align-items-center gap-2">
                    <input type="search" name="search" class="form-control flex-grow-1"
                           placeholder="Buscar por nombre, ID o contenido..."
                           value="{{ request('search') }}">

                    <x-filter-button target="whatsapp-templates-filter-modal" :count="$activeFilterCount" />

                    <div class="d-flex gap-1 flex-shrink-0">
                        <button type="submit" class="btn btn-primary" title="Buscar">
                            <i class="fas fa-magnifying-glass"></i>
                        </button>
                        @if($hasAnyFilter)
                            <a href="{{ route('settings.helpdesk.whatsapp-templates.index') }}"
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
                                Estado: {{ $statusLabels[request('status')] ?? request('status') }}
                            </span>
                        @endif
                        @if(request('category'))
                            <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                Categoria: {{ $categoryLabels[request('category')] ?? request('category') }}
                            </span>
                        @endif
                        @if(request('language'))
                            <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                Idioma: {{ strtoupper(request('language')) }}
                            </span>
                        @endif
                    </div>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body">
            @if($templates->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                @can('helpdesk.whatsapp-templates.manage')
                                    <th scope="col" width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                @endcan
                                <th scope="col">Nombre</th>
                                <th scope="col" class="text-center">Categoria</th>
                                <th scope="col" class="text-center">Idioma</th>
                                <th scope="col" class="text-center">Estado</th>
                                <th scope="col" class="text-center">Parametros</th>
                                <th scope="col">Preview del cuerpo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templates as $template)
                                <tr>
                                    @can('helpdesk.whatsapp-templates.manage')
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $template->id }}"></td>
                                    @endcan
                                    <td>
                                        <strong>{{ $template->display_name }}</strong>
                                        @if($template->external_id)
                                            <div><small class="text-muted">{{ $template->external_id }}</small></div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @switch($template->category)
                                            @case('utility')
                                                <span class="badge bg-primary-subtle text-primary">Utilidad</span>
                                                @break
                                            @case('marketing')
                                                <span class="badge bg-info-subtle text-info">Marketing</span>
                                                @break
                                            @case('authentication')
                                                <span class="badge bg-secondary-subtle text-secondary">Autenticacion</span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-muted">{{ $template->category }}</span>
                                        @endswitch
                                    </td>
                                    <td class="text-center">
                                        <span class="small">{{ strtoupper($template->language) }}</span>
                                    </td>
                                    <td class="text-center">
                                        @switch($template->status)
                                            @case('approved')
                                                <span class="badge bg-success-subtle text-success">Aprobado</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning-subtle text-warning">Pendiente</span>
                                                @break
                                            @case('rejected')
                                                <span class="badge bg-info-subtle text-info">Rechazado</span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-muted">{{ $template->status }}</span>
                                        @endswitch
                                    </td>
                                    <td class="text-center">
                                        @if($template->param_count > 0)
                                            <span class="badge bg-light text-dark">{{ $template->param_count }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($template->body_template)
                                            <small class="text-muted">{{ Str::limit($template->body_template, 80) }}</small>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
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
                            <i class="fab fa-whatsapp fs-7"></i>
                        </div>
                        <h6 class="mb-1">No hay templates sincronizados</h6>
                        <p class="text-muted mb-3">
                            @if(request('search') || request('status') || request('category'))
                                No se encontraron resultados para los filtros aplicados
                            @else
                                Haz clic en "Sincronizar desde Meta" para importar tus templates aprobados
                            @endif
                        </p>
                        @if(! request('search') && ! request('status') && ! request('category'))
                            @can('helpdesk.whatsapp-templates.manage')
                                <form action="{{ route('settings.helpdesk.whatsapp-templates.sync') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        Sincronizar desde Meta
                                    </button>
                                </form>
                            @endcan
                        @endif
                    </div>
                </div>
            @endif
        </div>

        @if($templates->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Mostrando <strong>{{ $templates->firstItem() }}</strong> a <strong>{{ $templates->lastItem() }}</strong>
                        de <strong>{{ $templates->total() }}</strong> templates
                    </div>
                    {{ $templates->appends(request()->input())->links() }}
                </div>
            </div>
        @endif

    </div>

    {{-- Filtros avanzados --}}
    <x-filter-shell id="whatsapp-templates-filter-modal"
                    :count="$activeFilterCount"
                    apply-id="whatsapp-templates-filter-apply-btn"
                    clear-id="whatsapp-templates-filter-clear-btn">
        <div class="fs-field">
            <label class="form-label fw-semibold">Estado</label>
            <select id="modal-status" class="form-control select2-filter-modal">
                <option value="">Cualquier estado</option>
                @foreach($statusLabels as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="fs-field">
            <label class="form-label fw-semibold">Categoria</label>
            <select id="modal-category" class="form-control select2-filter-modal">
                <option value="">Cualquier categoria</option>
                @foreach($categoryLabels as $value => $label)
                    <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="fs-field">
            <label class="form-label fw-semibold">Idioma</label>
            <select id="modal-language" class="form-control select2-filter-modal">
                <option value="">Cualquier idioma</option>
                @foreach($languages as $language)
                    <option value="{{ $language }}" @selected(request('language') === $language)>{{ strtoupper($language) }}</option>
                @endforeach
            </select>
        </div>
    </x-filter-shell>

    {{-- Barra flotante de seleccion --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none wa-bulk-toolbar">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionada(s) &mdash; Aplicar accion
        </button>
    </div>

    {{-- Accion masiva --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Accion masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicara sobre <strong><span data-bulk-count>0</span> plantilla(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Accion</label>
                        <select id="bulk-action-select" class="form-select select2-bulk">
                            <option value="">Seleccionar accion...</option>
                            <option value="delete">Eliminar de la copia local</option>
                        </select>
                    </div>
                    <p class="small text-muted mb-0">
                        Las plantillas viven en Meta: esto solo limpia la copia de este panel. Una que siga
                        existiendo alli volvera a aparecer en la proxima sincronizacion.
                    </p>
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
    .wa-bulk-toolbar { z-index: 1050; }
</style>
@endpush

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

    // El contenedor de filtros puede ser modal o panel lateral segun el .env:
    // se localiza y se cierra a traves de FilterShell.
    $('.select2-filter-modal').select2({
        dropdownParent: window.FilterShell.el('whatsapp-templates-filter-modal'),
        width: '100%',
    });

    $('#whatsapp-templates-filter-apply-btn').on('click', function () {
        $('#filter-status').val($('#modal-status').val());
        $('#filter-category').val($('#modal-category').val());
        $('#filter-language').val($('#modal-language').val());
        window.FilterShell.close('whatsapp-templates-filter-modal');
        $('#whatsapp-templates-filter-form').submit();
    });

    $('#whatsapp-templates-filter-clear-btn').on('click', function () {
        $('#modal-status, #modal-category, #modal-language').val(null).trigger('change');
    });

    // ── Acciones masivas ─────────────────────────────────────────────────
    if (document.querySelector('.bulk-checkbox')) {
        $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

        var bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

        $('#bulk-modal').on('hide.bs.modal', function () {
            $('#bulk-action-select').val('').trigger('change');
            $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            bulk.reset();
        });

        $('#bulk-apply-btn').on('click', function () {
            var action = $('#bulk-action-select').val();
            var ids = bulk.getIds();

            if (! action) { toastr.warning('Selecciona una accion.'); return; }
            if (! ids.length) { toastr.warning('Selecciona al menos una plantilla.'); return; }

            $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

            $.ajax({
                url: '{{ route('settings.helpdesk.whatsapp-templates.bulk-action') }}',
                method: 'POST',
                data: JSON.stringify({ action: action, ids: ids }),
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    $('#bulk-modal').modal('hide');
                    toastr.success(res.message);
                    setTimeout(function () { location.reload(); }, 800);
                },
                error: function (xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al procesar.');
                    $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
                },
            });
        });
    }
});
</script>
@endpush
