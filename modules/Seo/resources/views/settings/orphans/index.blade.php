@extends('layouts.theme')

@section('title', 'Contenido sin SEO')

@section('content')
    @include('core::components.card', ['title' => 'Contenido sin SEO'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        @php
            $totalOrphans = collect($orphans)->sum(fn ($group) => count($group));
            $groupNames = array_keys($orphans);
            $activeGroup = request('group', '');
            $filteredItems = $activeGroup && isset($orphans[$activeGroup])
                ? [$activeGroup => $orphans[$activeGroup]]
                : $orphans;
        @endphp

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Contenido sin SEO</h5>
                        <p class="small mb-0 text-muted">Contenido publicado que no tiene metadatos SEO configurados</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('setting.seo.metas.index') }}" class="btn btn-outline-secondary">
                            Ver metas SEO
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
                                <h6 class="card-title mb-2">Total sin SEO</h6>
                                <h4 class="mb-1 fw-bold {{ $totalOrphans > 0 ? 'text-warning' : 'text-success' }}">{{ $totalOrphans }}</h4>
                                <small class="text-muted">Elementos sin metadatos</small>
                            </div>
                        </div>
                    </div>
                    @foreach($orphans as $groupName => $items)
                        <div class="col-6 col-md-4">
                            <div class="card bg-light-secondary h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">{{ $groupName }}</h6>
                                    <h4 class="mb-1 fw-bold">{{ count($items) }}</h4>
                                    <small class="text-muted">Sin configuración SEO</small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('setting.seo.orphans.index') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control border-start-0 ps-0"
                                    placeholder="Buscar por título o slug..."
                                    value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 220px;">
                            <select name="group" class="form-select select2 h-100">
                                <option value="">Todas las categorías</option>
                                @foreach($groupNames as $groupName)
                                    <option value="{{ $groupName }}" {{ $activeGroup === $groupName ? 'selected' : '' }}>
                                        {{ $groupName }} ({{ count($orphans[$groupName]) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(request('search') || $activeGroup)
                                <a href="{{ route('setting.seo.orphans.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($totalOrphans === 0)
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <h6 class="mb-1">Todo el contenido tiene SEO</h6>
                            <p class="text-muted mb-3">No hay contenido sin metadatos SEO configurados</p>
                            <a href="{{ route('setting.seo.metas.index') }}" class="btn btn-sm btn-outline-secondary">
                                Ver metas SEO
                            </a>
                        </div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Título</th>
                                    <th>Slug</th>
                                    <th class="text-center">Categoría</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($filteredItems as $groupName => $items)
                                    @php $modelClass = $items[0]['modelClass'] ?? ''; @endphp
                                    @foreach($items as $item)
                                        <tr id="row-{{ $item['id'] }}-{{ $groupName }}">
                                            <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $item['id'] }}" data-model-class="{{ $modelClass }}"></td>
                                            <td><strong>{{ $item['title'] }}</strong></td>
                                            <td><code class="small">{{ $item['slug'] }}</code></td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark">{{ $groupName }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-auto-close="true" data-bs-boundary="viewport">
                                                        <i class="fas fa-ellipsis-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ $item['create_url'] }}">
                                                                Crear SEO manualmente
                                                            </a>
                                                        </li>
                                                        @if($modelClass)
                                                            <li>
                                                                <a class="dropdown-item generate-seo-btn" href="javascript:void(0)"
                                                                   data-model-class="{{ $modelClass }}"
                                                                   data-model-id="{{ $item['id'] }}"
                                                                   data-group="{{ $groupName }}">
                                                                    Auto-generar SEO
                                                                </a>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
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
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> elemento(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="generate">Auto-generar SEO</option>
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
<script>
$(function () {
    var generateUrl = '{{ route('setting.seo.orphans.generate') }}';
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Individual generate via dropdown
    $(document).on('click', '.generate-seo-btn', function (e) {
        e.preventDefault();
        var $link = $(this);
        var modelClass = $link.data('model-class');
        var modelId = $link.data('model-id');
        var groupName = $link.data('group');

        $link.text('Generando...');

        $.ajax({
            url: generateUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { model_class: modelClass, model_id: modelId },
            success: function (response) {
                if (response.status) {
                    toastr.success(response.message);
                    $('#row-' + modelId + '-' + groupName).fadeOut(300, function () { $(this).remove(); });
                } else {
                    toastr.error(response.message);
                    $link.text('Auto-generar SEO');
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al generar SEO');
                $link.text('Auto-generar SEO');
            }
        });
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
        var action = $('#bulk-action-select').val();
        if (!action) { toastr.warning('Selecciona una acción.'); return; }

        var $checked = $('.bulk-checkbox:checked');
        if (!$checked.length) { toastr.warning('Selecciona al menos un elemento.'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Procesando...');

        var pending = $checked.length;
        var success = 0;

        $checked.each(function () {
            var modelClass = $(this).data('model-class');
            var modelId = $(this).val();
            var $row = $(this).closest('tr');

            $.ajax({
                url: generateUrl,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                data: { model_class: modelClass, model_id: modelId },
                success: function (response) {
                    if (response.status) {
                        success++;
                        $row.fadeOut(300, function () { $(this).remove(); });
                    }
                },
                complete: function () {
                    pending--;
                    if (pending === 0) {
                        $('#bulk-modal').modal('hide');
                        toastr.success(success + ' elemento(s) generados.');
                        setTimeout(function () { location.reload(); }, 1000);
                    }
                }
            });
        });
    });
});
</script>
@endpush
