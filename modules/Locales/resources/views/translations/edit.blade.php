@extends('layouts.theme')

@section('title', "Traducciones — {$group} / {$locale}")

@section('content')

    @include('core::components.card', ['title' => 'Traducciones'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Traducciones</h5>
                        <p class="small mb-0 text-muted">
                            Traducir de <strong>{{ $sourceLocaleName }}</strong>
                            a <strong>{{ $targetLocaleName }}</strong>
                            &mdash;
                            @foreach ($locales as $loc)
                                @if ($loc->code !== $locale)
                                    <a href="{{ route('locales.translations.edit', [$loc->code, $group]) }}"
                                       class="text-decoration-none me-1">
                                        {{ $loc->flag }} {{ $loc->name }}
                                    </a>
                                @endif
                            @endforeach
                        </p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button"
                                    class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="#"
                                   data-bs-toggle="modal" data-bs-target="#new-key-modal">
                                    Nueva clave
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item"
                                   href="{{ route('locales.translations.export', [$locale, $group]) }}">
                                    Exportar JSON
                                </a>
                                <a class="dropdown-item" href="#"
                                   data-bs-toggle="modal" data-bs-target="#import-modal">
                                    Importar JSON
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('locales.translations.index') }}">
                                    Ver todos los idiomas
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Total claves</h6>
                                        <h4 class="mb-1 fw-bold">{{ $totalKeys }}</h4>
                                        <small class="text-muted">En este grupo</small>
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
                                        <h6 class="card-title mb-2">Traducidas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $translatedCount }}</h4>
                                        <small class="text-muted">Con traducción propia</small>
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
                                        <h6 class="card-title mb-2">Sin traducir</h6>
                                        <h4 class="mb-1 fw-bold">{{ $untranslatedCount }}</h4>
                                        <small class="text-muted">Usan texto fuente</small>
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
                                        <h6 class="card-title mb-2">Completado</h6>
                                        <h4 class="mb-1 fw-bold">{{ $percent }}%</h4>
                                        <div class="progress mt-2" style="height:6px">
                                            <div class="progress-bar bg-success" style="width:{{ $percent }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ request()->url() }}" id="filter-form">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search"
                                       class="form-control border-start-0 ps-0"
                                       placeholder="Buscar clave o texto..."
                                       value="{{ $search }}">
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            @if ($search !== '')
                                <a href="{{ request()->url() }}?tab={{ $tab }}" class="btn btn-outline-secondary" title="Limpiar búsqueda">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Tabs --}}
            <ul class="nav nav-tabs border-0 user-profile-tab" role="tablist">
                @foreach (['all' => 'Todas', 'translated' => 'Traducidas', 'untranslated' => 'Sin traducir'] as $tabKey => $tabLabel)
                    <li class="nav-item" role="presentation">
                        <a href="{{ request()->url() }}?tab={{ $tabKey }}{{ $search ? '&search='.urlencode($search) : '' }}"
                           class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $tab === $tabKey ? 'active' : '' }}"
                           role="tab">
                            <span class="d-none d-md-block">{{ $tabLabel }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Table --}}
            <div class="card-body">
                @if ($translations->isEmpty())
                    <div class="text-center py-5">
                        <h6 class="mb-1">No se encontraron traducciones</h6>
                        <p class="text-muted mb-3">Prueba con otros filtros o crea una nueva clave.</p>
                        <button type="button" class="btn btn-sm btn-primary"
                                data-bs-toggle="modal" data-bs-target="#new-key-modal">
                            Nueva clave
                        </button>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="translations-table">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%">
                                        <input type="checkbox" id="select-all" class="form-check-input">
                                    </th>
                                    <th style="width:25%">CLAVE</th>
                                    <th style="width:33%">{{ strtoupper($sourceLocaleName) }}</th>
                                    <th style="width:33%">{{ strtoupper($targetLocaleName) }}</th>
                                    <th width="5%" class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($translations as $key => $value)
                                    @php
                                        $sourceText   = $sourceTranslations[$key] ?? $key;
                                        $isTranslated = $value !== '' && $value !== $sourceText;
                                    @endphp
                                    <tr class="translation-row"
                                        data-key="{{ $key }}"
                                        data-source="{{ $sourceText }}"
                                        data-target="{{ $value }}">
                                        <td>
                                            <input type="checkbox" class="form-check-input bulk-checkbox"
                                                   value="{{ $key }}">
                                        </td>
                                        <td>
                                            <code class="small text-muted">{{ $key }}</code>
                                        </td>
                                        <td class="text-muted small">{{ $sourceText }}</td>
                                        <td class="translation-cell">
                                            <span class="translation-text {{ $isTranslated ? 'text-dark' : 'text-muted fst-italic' }}"
                                                  style="cursor:pointer; border-bottom:1px dashed #aaa;"
                                                  title="Clic para editar">{{ $value ?: '—' }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if ($isTranslated)
                                                <span class="badge bg-success-subtle text-success">OK</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if ($translations->hasPages())
                <div class="card-footer">{{ $translations->withQueryString()->links() }}</div>
            @endif

        </div>
    </div>

    {{-- Edit translation modal --}}
    <div class="modal fade" id="edit-translation-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar traducción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Clave</label>
                        <input type="text" id="edit-modal-key-input" class="form-control font-monospace">
                        <div class="form-text">Modificar la clave renombrará la entrada</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">
                            {{ strtoupper($sourceLocaleName) }}
                        </label>
                        <p id="edit-modal-source" class="mb-0 text-muted small border rounded p-2 bg-light"></p>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">
                            {{ strtoupper($targetLocaleName) }}
                        </label>
                        <textarea id="edit-modal-value" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer flex-column gap-2">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="edit-modal-save-btn">
                        Guardar traducción
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4"
                data-bs-toggle="modal" data-bs-target="#bulk-modal">
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
                    <p class="text-muted mb-3">
                        Se aplicará la acción sobre
                        <strong><span data-bulk-count>0</span> clave(s)</strong>.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="delete">Eliminar claves</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer flex-column gap-2">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">
                        Aplicar
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Nueva clave modal --}}
    <div class="modal fade" id="new-key-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva clave de traducción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Clave <span class="text-danger">*</span></label>
                        <input type="text" id="new-key-input" class="form-control"
                               placeholder="Ej: contact_us_today">
                        <div class="form-text">Identificador único de la traducción</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Traducción</label>
                        <textarea id="new-value-input" class="form-control" rows="3"
                                  placeholder="Texto traducido..."></textarea>
                    </div>
                </div>
                <div class="modal-footer flex-column gap-2">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="new-key-btn">
                        <i class="fas fa-plus me-1"></i> Crear clave
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Import modal --}}
    <div class="modal fade" id="import-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Importar traducciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Sube un archivo JSON con las traducciones. Se fusionarán con las existentes.
                    </p>
                    <input type="file" id="import-file" class="form-control" accept=".json">
                </div>
                <div class="modal-footer flex-column gap-2">
                    <button type="button" class="btn btn-primary w-100 mb-1" id="import-btn">
                        <i class="fas fa-upload me-1"></i> Importar
                    </button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const updateKeyUrl  = '{{ route('locales.translations.update-key', [$locale, $group]) }}';
    const storeKeyUrl   = '{{ route('locales.translations.store-key', [$locale, $group]) }}';
    const renameKeyUrl  = '{{ route('locales.translations.rename-key', [$locale, $group]) }}';
    const bulkActionUrl = '{{ route('locales.translations.bulk-action', [$locale, $group]) }}';
    const importUrl     = '{{ route('locales.translations.import', [$locale, $group]) }}';
    const csrfToken     = $('meta[name="csrf-token"]').attr('content');

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // ── Edit translation modal ───────────────────────────────────────
    let $editingRow = null;

    $(document).on('click', '.translation-text', function () {
        $editingRow = $(this).closest('.translation-row');
        $('#edit-modal-key-label').text($editingRow.data('key'));
        $('#edit-modal-key-input').val($editingRow.data('key'));
        $('#edit-modal-source').text($editingRow.data('source'));
        $('#edit-modal-value').val($editingRow.data('target'));
        $('#edit-translation-modal').modal('show');
    });

    $('#edit-translation-modal').on('shown.bs.modal', function () {
        $('#edit-modal-value').focus();
    });

    $('#edit-modal-save-btn').on('click', function () {
        if (!$editingRow) { return; }

        const $btn      = $(this);
        const oldKey    = $editingRow.data('key');
        const newKey    = $('#edit-modal-key-input').val().trim();
        const newVal    = $('#edit-modal-value').val();
        const keyChanged = newKey && newKey !== oldKey;

        if (!newKey) { toastr.warning('La clave no puede estar vacía.'); return; }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

        // If key renamed: delete old + create new; else just update value
        const payload = keyChanged
            ? { old_key: oldKey, key: newKey, value: newVal }
            : { key: oldKey, value: newVal };

        const url = keyChanged ? renameKeyUrl : updateKeyUrl;

        $.ajax({
            url: url,
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: JSON.stringify(payload),
            success: function (res) {
                if (!res.success) { toastr.error(res.message ?? 'Error.'); return; }

                const sourceText   = $editingRow.data('source');
                const isTranslated = newVal !== '' && newVal !== sourceText;

                $editingRow
                    .data('key', newKey)
                    .data('target', newVal)
                    .data('translated', isTranslated ? '1' : '0');

                $editingRow.find('td:nth-child(2) code').text(newKey);
                $editingRow.find('.bulk-checkbox').val(newKey);

                const $span = $editingRow.find('.translation-text');
                $span.text(newVal || '—')
                     .toggleClass('text-dark', isTranslated)
                     .toggleClass('text-muted fst-italic', !isTranslated);

                $editingRow.find('td:last-child').html(
                    isTranslated
                        ? '<span class="badge bg-success-subtle text-success">OK</span>'
                        : '<span class="badge bg-warning-subtle text-warning">—</span>'
                );

                $('#edit-translation-modal').modal('hide');
                toastr.success(res.message);
                $editingRow = null;
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al guardar.');
            },
            complete: function () {
                $btn.prop('disabled', false).html('Guardar traducción');
            }
        });
    });

    // ── Bulk actions ─────────────────────────────────────────────────
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const keys   = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!keys.length) { toastr.warning('Selecciona al menos una clave.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar las ' + keys.length + ' clave(s) seleccionadas? Esta acción no se puede deshacer.')) { return; }

        $(this).prop('disabled', true).text('Procesando...');

        $.ajax({
            url: bulkActionUrl,
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: JSON.stringify({ action: action, keys: keys }),
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message);
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            }
        });
    });

    // ── Nueva clave ──────────────────────────────────────────────────
    $('#new-key-modal').on('show.bs.modal', function () {
        $('#new-key-input').val('');
        $('#new-value-input').val('');
    });

    $('#new-key-btn').on('click', function () {
        const key   = $('#new-key-input').val().trim();
        const value = $('#new-value-input').val();

        if (!key) { toastr.warning('La clave es obligatoria.'); return; }

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Creando...');

        $.ajax({
            url: storeKeyUrl,
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: JSON.stringify({ key: key, value: value }),
            success: function (res) {
                if (!res.success) { toastr.error(res.message ?? 'Error.'); return; }

                $('#new-key-modal').modal('hide');
                toastr.success(res.message);

                const $tbody = $('#translations-table tbody');
                $tbody.append(`
                    <tr class="translation-row"
                        data-key="${res.key}"
                        data-source="${res.key}"
                        data-target="${res.value}"
                        data-translated="0">
                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="${res.key}"></td>
                        <td><code class="small text-muted">${res.key}</code></td>
                        <td class="text-muted small">${res.key}</td>
                        <td class="translation-cell">
                            <span class="translation-text text-muted fst-italic"
                                  style="cursor:pointer;border-bottom:1px dashed #aaa;"
                                  title="Clic para editar">${res.value || '—'}</span>
                        </td>
                        <td class="text-center"><span class="badge bg-warning-subtle text-warning">—</span></td>
                    </tr>`);

                filterRows();
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al crear la clave.');
            },
            complete: function () {
                $('#new-key-btn').prop('disabled', false).html('<i class="fas fa-plus me-1"></i> Crear clave');
            }
        });
    });

    // ── Import ───────────────────────────────────────────────────────
    $('#import-btn').on('click', function () {
        const file = $('#import-file')[0].files[0];
        if (!file) { toastr.warning('Selecciona un archivo JSON.'); return; }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', csrfToken);

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Importando...');

        $.ajax({
            url: importUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                $('#import-modal').modal('hide');
                toastr.success(res.message);
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al importar.');
            },
            complete: function () {
                $('#import-btn').prop('disabled', false).html('<i class="fas fa-upload me-1"></i> Importar');
            }
        });
    });
});
</script>
@endpush
