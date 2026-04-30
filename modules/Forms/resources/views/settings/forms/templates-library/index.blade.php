@extends('layouts.theme')

@section('title', 'Plantillas de formularios')

@section('page_header')
    @include('core::components.card', ['title' => 'Plantillas de formularios'])
@endsection

@section('content')

    @php
        $categories = collect($templates)->pluck('category')->filter()->unique()->sort()->values();
        $totalFields = collect($templates)->sum(fn($t) => count($t['fields'] ?? []));
    @endphp

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Plantillas de formularios</h5>
                        <p class="small mb-0 text-muted">Crea un formulario a partir de una plantilla prediseñada</p>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ count($templates) }}</h4>
                                <small class="text-muted">Plantillas disponibles</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Categorías</h6>
                                <h4 class="mb-1 fw-bold">{{ $categories->count() ?: '—' }}</h4>
                                <small class="text-muted">Grupos temáticos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Campos totales</h6>
                                <h4 class="mb-1 fw-bold">{{ $totalFields }}</h4>
                                <small class="text-muted">Entre todas las plantillas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                    <div class="flex-fill">
                        <div class="input-group h-100">
                            <span class="input-group-text bg-white border-end-1">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="search" id="searchTemplate" class="form-control border-start-0 ps-0"
                                   placeholder="Buscar por nombre o descripción...">
                        </div>
                    </div>
                    @if($categories->count())
                        <div class="flex-shrink-0" >
                            <select id="filterCategory" class="form-select select2 h-100">
                                <option value="">Todas las categorías</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="d-flex gap-2 flex-shrink-0">
                        <button type="button" class="btn btn-outline-secondary" id="btnReset" title="Limpiar filtros" style="display:none!important;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if(empty($templates))
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-layer-group fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay plantillas disponibles</h6>
                            <p class="text-muted mb-0">No se han configurado plantillas en el sistema.</p>
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    @if($categories->count())
                                        <th>Categoría</th>
                                    @endif
                                    <th class="text-center">Campos</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="templatesBody">
                                @foreach($templates as $key => $template)
                                    <tr class="template-row"
                                        data-name="{{ strtolower($template['name']) }}"
                                        data-desc="{{ strtolower($template['description'] ?? '') }}"
                                        data-cat="{{ strtolower($template['category'] ?? '') }}">
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $key }}"></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <strong>{{ $template['name'] }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ Str::limit($template['description'] ?? '—', 70) }}</small>
                                        </td>
                                        @if($categories->count())
                                            <td>
                                                @if(!empty($template['category']))
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $template['category'] }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        @endif
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary">{{ count($template['fields'] ?? []) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ route('settings.forms.templates-library.preview', $key) }}">
                                                            Vista previa
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item btn-use-template" href="javascript:void(0)"
                                                           data-key="{{ $key }}"
                                                           data-name="{{ $template['name'] }}"
                                                           data-fields="{{ count($template['fields'] ?? []) }}">
                                                            Usar plantilla
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

                    <div id="noResults" class="text-center py-5 d-none">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-search fs-7"></i>
                            </div>
                            <h6 class="mb-1">No se encontraron plantillas</h6>
                            <p class="text-muted mb-0">No hay resultados para los criterios de búsqueda</p>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" >
        <button type="button" id="bulk-use-btn" class="btn btn-primary shadow-lg px-4">
            Crear <span data-bulk-count>0</span> formulario(s) desde plantillas
        </button>
    </div>

    {{-- Modal usar plantilla --}}
    <div class="modal fade" id="useTemplateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Usar plantilla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">Se creará un nuevo formulario basado en:</p>
                    <p class="fw-bold" id="use-template-name"></p>
                    <p class="text-muted mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        El formulario se creará con <span id="use-template-fields"></span> campo(s). Podrás editarlo libremente después.
                    </p>
                </div>
                <form id="use-template-form" method="POST" action="">
                    @csrf
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">
                            <i class="fas fa-plus me-1"></i> Crear formulario
                        </button>
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // ── Bulk actions ───────────────────────────────────────────────────────
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-use-btn').on('click', async function () {
        const keys = bulk.getIds();
        if (!keys.length) return;
        if (!confirm('¿Crear ' + keys.length + ' formulario(s) a partir de las plantillas seleccionadas?')) return;

        $(this).prop('disabled', true).text('Creando...');

        let created = 0;
        for (const key of keys) {
            try {
                await $.ajax({
                    url: '{{ route('settings.forms.templates-library.use', ':key') }}'.replace(':key', key),
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                });
                created++;
            } catch (e) { /* continuar con los demás */ }
        }

        if (created > 0) {
            toastr.success(created + ' formulario(s) creados. Redirigiendo...');
            setTimeout(() => { window.location.href = '{{ route('settings.forms.index') }}'; }, 800);
        } else {
            toastr.error('No se pudo crear ningún formulario.');
            $(this).prop('disabled', false).text('Crear formularios desde plantillas');
            bulk.reset();
        }
    });

    function applyFilters() {
        const search = $('#searchTemplate').val().toLowerCase();
        const cat    = ($('#filterCategory').val() || '').toLowerCase();
        let visible  = 0;

        $('.template-row').each(function () {
            const matchSearch = !search || $(this).data('name').includes(search) || $(this).data('desc').includes(search);
            const matchCat    = !cat    || $(this).data('cat') === cat;
            const show        = matchSearch && matchCat;
            $(this).toggleClass('d-none', !show);
            if (show) visible++;
        });

        $('#noResults').toggleClass('d-none', visible > 0);
        $('#btnReset').toggle(!!(search || cat));
    }

    $('#searchTemplate').on('input', applyFilters);
    $('#filterCategory').on('change', applyFilters);

    $('#btnReset').on('click', function () {
        $('#searchTemplate').val('');
        $('#filterCategory').val('').trigger('change');
    });

    // Usar plantilla
    $(document).on('click', '.btn-use-template', function () {
        const key    = $(this).data('key');
        const name   = $(this).data('name');
        const fields = $(this).data('fields');

        $('#use-template-name').text(name);
        $('#use-template-fields').text(fields);
        $('#use-template-form').attr('action',
            '{{ route('settings.forms.templates-library.use', ':key') }}'.replace(':key', key)
        );
        $('#useTemplateModal').modal('show');
    });

    $('.select2').select2({ minimumResultsForSearch: Infinity });
});
</script>
@endpush
