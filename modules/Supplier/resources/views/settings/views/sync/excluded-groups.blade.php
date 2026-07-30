@extends('layouts.theme')

@section('title', 'Grupos ERP excluidos')

@section('page_header')
    @include('core::components.card', ['title' => 'Grupos ERP excluidos del sync'])
@endsection

@section('content')
<div class="widget-content searchable-container list">

    @if($affectedProductCount > 0)
    <div class="alert alert-warning d-flex align-items-start gap-3 mb-4 border-0 rounded-3 shadow-sm">
        <i class="fas fa-triangle-exclamation mt-1 flex-shrink-0"></i>
        <div>
            <strong>{{ number_format($affectedProductCount) }} producto(s) en BD pertenecen a grupos excluidos.</strong>
            Fueron importados antes de configurar la exclusión y no se volverán a sincronizar,
            pero sus registros existentes no se eliminan automáticamente.
        </div>
    </div>
    @endif

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h5 class="mb-1 fw-bold">IDs excluidos del ERP</h5>
                    <p class="small mb-0 text-muted">
                        Productos cuyo <code>idgrupo_cl</code>, <code>idsubfamilia_cl</code> o <code>idfamilia_cl</code>
                        coincida con uno de estos IDs se omitirán durante la sincronización.
                    </p>
                </div>
                <span class="badge bg-primary-subtle text-primary fw-semibold fs-6 px-3 flex-shrink-0" id="count-badge">
                    {{ $groups->count() }}
                </span>
            </div>

            {{-- Barra de herramientas --}}
            <div class="d-flex align-items-center gap-2 flex-wrap">

                {{-- Búsqueda --}}
                <div class="input-group input-group-sm" style="max-width:300px;">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted" style="font-size:.75rem;"></i>
                    </span>
                    <input type="text" id="search-input" class="form-control border-start-0 ps-0"
                           placeholder="Buscar ID, descripción, motivo…" autocomplete="off">
                    <button class="btn btn-outline-secondary d-none" type="button" id="search-clear" title="Limpiar">
                        <i class="fas fa-times" style="font-size:.7rem;"></i>
                    </button>
                </div>

                {{-- Acciones bulk (visibles al seleccionar) --}}
                <div id="bulk-actions" class="d-none d-flex align-items-center gap-2">
                    <span class="small text-muted" id="selected-count-label"></span>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="btn-bulk-delete">
                        <i class="fas fa-trash-alt me-1" style="font-size:.7rem;"></i>Eliminar selección
                    </button>
                </div>

                {{-- Acciones principales --}}
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="modal" data-bs-target="#modal-import-bulk"
                            title="Importar lista de IDs">
                        <i class="fas fa-file-import me-1"></i>Importar lista
                    </button>
                    <button type="button" class="btn btn-sm btn-primary"
                            data-bs-toggle="modal" data-bs-target="#modal-add-single">
                        <i class="fas fa-plus me-1"></i>Añadir ID
                    </button>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="dropdown" title="Más opciones">
                            <i class="fas fa-ellipsis-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item small" href="#" id="btn-export-txt">
                                    <i class="fas fa-download me-2 text-muted"></i>Descargar como .txt
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item small" href="#" id="btn-copy-ids">
                                    <i class="fas fa-copy me-2 text-muted"></i>Copiar IDs al portapapeles
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item small" href="#"
                                   data-bs-toggle="modal" data-bs-target="#modal-how-it-works">
                                    <i class="fas fa-circle-info me-2 text-muted"></i>¿Cómo funciona?
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item small text-danger" href="#" id="btn-clear-all">
                                    <i class="fas fa-ban me-2"></i>Limpiar toda la lista
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>

        {{-- Tabla --}}
        <div class="card-body p-0">
            <div id="groups-table-wrap">
                @if($groups->isEmpty())
                    <div class="text-center py-5 text-muted" id="empty-state">
                        <i class="fas fa-ban fa-2x mb-3 d-block opacity-25"></i>
                        <p class="mb-1 fw-semibold">Sin grupos excluidos</p>
                        <p class="small mb-0 text-muted">
                            Pulsa <strong>Añadir ID</strong> para registrar el primer grupo.
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle" id="groups-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:36px" class="ps-4">
                                        <input type="checkbox" class="form-check-input" id="check-all" title="Seleccionar todos">
                                    </th>
                                    <th style="width:150px" class="sortable" data-col="erp_id">
                                        ID ERP <i class="fas fa-sort text-muted ms-1" style="font-size:.65rem;"></i>
                                    </th>
                                    <th class="sortable" data-col="label">
                                        Descripción <i class="fas fa-sort text-muted ms-1" style="font-size:.65rem;"></i>
                                    </th>
                                    <th class="sortable" data-col="reason">
                                        Motivo <i class="fas fa-sort text-muted ms-1" style="font-size:.65rem;"></i>
                                    </th>
                                    <th style="width:110px" class="sortable" data-col="date">
                                        Añadido <i class="fas fa-sort text-muted ms-1" style="font-size:.65rem;"></i>
                                    </th>
                                    <th style="width:56px"></th>
                                </tr>
                            </thead>
                            <tbody id="groups-tbody">
                            @foreach($groups as $group)
                                <tr id="row-{{ $group->id }}" data-id="{{ $group->id }}"
                                    data-erp-id="{{ $group->erp_id }}"
                                    data-label="{{ strtolower($group->resolved_label ?? $group->label ?? '') }}"
                                    data-reason="{{ strtolower($group->reason ?? '') }}"
                                    data-date="{{ $group->created_at->timestamp }}">
                                    <td class="ps-4">
                                        <input type="checkbox" class="form-check-input row-check" value="{{ $group->id }}">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <code class="fw-semibold" style="font-size:.875rem;">{{ $group->erp_id }}</code>
                                            <button type="button" class="btn btn-link p-0 btn-copy-id text-muted opacity-0 ms-1"
                                                    data-value="{{ $group->erp_id }}" title="Copiar"
                                                    style="font-size:.65rem; transition:opacity .15s;">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="editable-cell" style="cursor:pointer;"
                                              data-field="label" data-id="{{ $group->id }}"
                                              title="{{ $group->resolved_label ?? $group->label ?? '' }}">
                                            @if($group->resolved_label)
                                                {{ $group->resolved_label }}
                                                @if(! $group->label)
                                                    <span class="text-muted ms-1" style="font-size:.7rem;">(auto)</span>
                                                @endif
                                            @else
                                                <span class="text-muted fst-italic small">Sin descripción</span>
                                            @endif
                                        </span>
                                    </td>
                                    <td>
                                        <span class="editable-cell small text-muted" style="cursor:pointer;"
                                              data-field="reason" data-id="{{ $group->id }}"
                                              title="{{ $group->reason ?? '' }}">
                                            {{ $group->reason ?: '—' }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        {{ $group->created_at->format('d/m/Y') }}
                                    </td>
                                    <td class="pe-3 text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-destroy"
                                                data-id="{{ $group->id }}" data-erp-id="{{ $group->erp_id }}"
                                                title="Eliminar">
                                            <i class="fas fa-trash-alt" style="font-size:.7rem;"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div id="no-results" class="text-center py-4 text-muted d-none">
                <i class="fas fa-search fa-lg mb-2 d-block opacity-25"></i>
                <p class="small mb-0">Sin resultados para "<span id="no-results-term"></span>".</p>
            </div>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     MODALES
══════════════════════════════════════════════════════════════════════ --}}

{{-- Modal: Añadir ID individual --}}
<div class="modal fade" id="modal-add-single" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold">
                    <i class="fas fa-plus-circle me-2 text-primary"></i>Añadir ID individual
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">
                        ID ERP <span class="text-danger">*</span>
                    </label>
                    <input type="number" id="input-erp-id" class="form-control" min="1"
                           placeholder="Ej: 100000411" autofocus>
                    <div class="invalid-feedback" id="erp-id-error"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">
                        Descripción <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <input type="text" id="input-label" class="form-control" maxlength="120"
                           placeholder="Ej: Grupo pesca recreativa">
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-semibold">
                        Motivo <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <input type="text" id="input-reason" class="form-control" maxlength="255"
                           placeholder="Ej: Sin licencia de distribución">
                </div>
            </div>
            <div class="modal-footer border-top gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4" id="btn-add">
                    <i class="fas fa-plus me-2"></i>Añadir
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Importar lista --}}
<div class="modal fade" id="modal-import-bulk" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold">
                    <i class="fas fa-file-import me-2 text-primary"></i>Importar lista de IDs
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">
                        IDs <span class="text-muted fw-normal">— separados por coma, espacio o salto de línea</span>
                    </label>
                    <textarea id="input-bulk-ids" class="form-control font-monospace" rows="7"
                              placeholder="100000411&#10;100000444&#10;100000471"
                              style="resize:vertical;"></textarea>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <span class="text-muted" style="font-size:.72rem;" id="bulk-ids-count"></span>
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted"
                                id="btn-clear-textarea" style="font-size:.72rem; display:none;">
                            Limpiar
                        </button>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-semibold">
                        Motivo común <span class="text-muted fw-normal">(opcional, se aplica a todos)</span>
                    </label>
                    <input type="text" id="input-bulk-reason" class="form-control" maxlength="255"
                           placeholder="Ej: Excluido manualmente">
                </div>
            </div>
            <div class="modal-footer border-top gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4" id="btn-bulk">
                    <i class="fas fa-file-import me-2"></i>Importar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: ¿Cómo funciona? --}}
<div class="modal fade" id="modal-how-it-works" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold">
                    <i class="fas fa-circle-info me-2 text-info"></i>¿Cómo funciona la exclusión?
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="mb-0 ps-3" style="line-height:1.8;">
                    <li>Los IDs se comprueban contra <code>idgrupo_cl</code>, <code>idsubfamilia_cl</code> e <code>idfamilia_cl</code> de cada modelo ERP.</li>
                    <li>Si algún artículo del modelo pertenece a un grupo excluido, <strong>el modelo completo se omite</strong>.</li>
                    <li>No se crea ni Product, ni AiContent, ni ningún registro asociado.</li>
                    <li>El cambio tiene efecto en la <strong>siguiente ejecución</strong> de sync.</li>
                    <li>Los registros ya existentes en BD <strong>no se eliminan</strong> automáticamente.</li>
                </ul>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Confirmar eliminación individual --}}
<div class="modal fade" id="modal-confirm-delete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold">Eliminar exclusión</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 small">
                    ¿Eliminar el ID <strong id="modal-erp-id-label">—</strong> de la lista?
                    Los productos de este grupo volverán a sincronizarse.
                </p>
            </div>
            <div class="modal-footer d-flex flex-column gap-1 pt-2">
                <button type="button" class="btn btn-danger w-100 fw-semibold" id="btn-confirm-delete">
                    Sí, eliminar
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Confirmar eliminación múltiple --}}
<div class="modal fade" id="modal-confirm-bulk-delete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold">Eliminar selección</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 small">
                    ¿Eliminar <strong id="modal-bulk-count">0</strong> ID(s) seleccionados?
                    Los productos de esos grupos volverán a sincronizarse.
                </p>
            </div>
            <div class="modal-footer d-flex flex-column gap-1 pt-2">
                <button type="button" class="btn btn-danger w-100 fw-semibold" id="btn-confirm-bulk-delete">
                    Sí, eliminar selección
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Confirmar limpiar todo --}}
<div class="modal fade" id="modal-confirm-clear-all" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-bottom border-danger-subtle">
                <h6 class="modal-title fw-bold text-danger">Limpiar toda la lista</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 small">
                    Se eliminarán <strong id="modal-clear-all-count">todos</strong> los IDs.
                    Todos los grupos excluidos volverán a sincronizarse.
                </p>
            </div>
            <div class="modal-footer d-flex flex-column gap-1 pt-2">
                <button type="button" class="btn btn-danger w-100 fw-semibold" id="btn-confirm-clear-all">
                    Sí, limpiar todo
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Edición inline de celda --}}
<div class="modal fade" id="modal-edit-cell" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold" id="modal-edit-title">Editar</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <input type="text" id="modal-edit-input" class="form-control" maxlength="255">
                <div class="small text-muted mt-1" id="modal-edit-hint"></div>
            </div>
            <div class="modal-footer pt-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm fw-semibold" id="btn-confirm-edit">
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.sortable { cursor: pointer; user-select: none; }
.sortable:hover { background-color: rgba(0,0,0,.04); }
.sortable .fa-sort-up,
.sortable .fa-sort-down { color: var(--bs-primary) !important; opacity: 1; }
tr:hover .btn-copy-id { opacity: 1 !important; }
.editable-cell:hover {
    text-decoration: underline;
    text-decoration-style: dashed;
    text-underline-offset: 3px;
}
#search-input:focus { box-shadow: none; }
</style>

@endsection

@push('scripts')
<script>
const routes = @json($routes);
const csrf   = document.querySelector('meta[name="csrf-token"]').content;

let pendingDeleteId  = null;
let pendingEditId    = null;
let pendingEditField = null;
let sortCol          = null;
let sortDir          = 'asc';

// ── Helpers ────────────────────────────────────────────────────────────────
function rowHtml(g) {
    const label     = g.label ? g.label : '<span class="text-muted fst-italic small">Sin descripción</span>';
    const reason    = g.reason || '—';
    const date      = new Date(g.created_at).toLocaleDateString('es-ES', { day:'2-digit', month:'2-digit', year:'numeric' });
    const labelVal  = (g.label  || '').toLowerCase();
    const reasonVal = (g.reason || '').toLowerCase();
    const ts        = Math.floor(Date.parse(g.created_at) / 1000);
    return `<tr id="row-${g.id}" data-id="${g.id}" data-erp-id="${g.erp_id}"
                data-label="${labelVal}" data-reason="${reasonVal}" data-date="${ts}">
        <td class="ps-4"><input type="checkbox" class="form-check-input row-check" value="${g.id}"></td>
        <td>
            <div class="d-flex align-items-center gap-1">
                <code class="fw-semibold" style="font-size:.875rem;">${g.erp_id}</code>
                <button type="button" class="btn btn-link p-0 btn-copy-id text-muted opacity-0 ms-1"
                        data-value="${g.erp_id}" title="Copiar" style="font-size:.65rem; transition:opacity .15s;">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </td>
        <td>
            <span class="editable-cell" style="cursor:pointer;"
                  data-field="label" data-id="${g.id}" title="${g.label || ''}">${label}</span>
        </td>
        <td>
            <span class="editable-cell small text-muted" style="cursor:pointer;"
                  data-field="reason" data-id="${g.id}" title="${g.reason || ''}">${reason}</span>
        </td>
        <td class="small text-muted">${date}</td>
        <td class="pe-3 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger btn-destroy"
                    data-id="${g.id}" data-erp-id="${g.erp_id}" title="Eliminar">
                <i class="fas fa-trash-alt" style="font-size:.7rem;"></i>
            </button>
        </td>
    </tr>`;
}

function updateCount(delta) {
    const badge = document.getElementById('count-badge');
    badge.textContent = Math.max(0, parseInt(badge.textContent || '0') + delta);
}

function getOrCreateTbody() {
    let tbody = document.getElementById('groups-tbody');
    if (tbody) return tbody;

    document.getElementById('groups-table-wrap').innerHTML = `
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" id="groups-table">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px" class="ps-4">
                            <input type="checkbox" class="form-check-input" id="check-all" title="Seleccionar todos">
                        </th>
                        <th style="width:150px" class="sortable" data-col="erp_id">
                            ID ERP <i class="fas fa-sort text-muted ms-1" style="font-size:.65rem;"></i>
                        </th>
                        <th class="sortable" data-col="label">
                            Descripción <i class="fas fa-sort text-muted ms-1" style="font-size:.65rem;"></i>
                        </th>
                        <th class="sortable" data-col="reason">
                            Motivo <i class="fas fa-sort text-muted ms-1" style="font-size:.65rem;"></i>
                        </th>
                        <th style="width:110px" class="sortable" data-col="date">
                            Añadido <i class="fas fa-sort text-muted ms-1" style="font-size:.65rem;"></i>
                        </th>
                        <th style="width:56px"></th>
                    </tr>
                </thead>
                <tbody id="groups-tbody"></tbody>
            </table>
        </div>`;

    bindSortHeaders();
    bindCheckAll();
    return document.getElementById('groups-tbody');
}

function showEmptyState() {
    document.getElementById('groups-table-wrap').innerHTML =
        `<div class="text-center py-5 text-muted" id="empty-state">
            <i class="fas fa-ban fa-2x mb-3 d-block opacity-25"></i>
            <p class="mb-1 fw-semibold">Sin grupos excluidos</p>
            <p class="small mb-0 text-muted">Pulsa <strong>Añadir ID</strong> para registrar el primer grupo.</p>
        </div>`;
}

// ── Búsqueda / filtro ──────────────────────────────────────────────────────
const searchInput = document.getElementById('search-input');
const searchClear = document.getElementById('search-clear');
const noResults   = document.getElementById('no-results');

function applyFilter() {
    const term = (searchInput?.value || '').toLowerCase().trim();
    searchClear?.classList.toggle('d-none', !term);

    const tbody = document.getElementById('groups-tbody');
    if (!tbody) return;

    let visible = 0;
    tbody.querySelectorAll('tr').forEach(row => {
        const match = !term
            || (row.dataset.erpId  || '').includes(term)
            || (row.dataset.label  || '').includes(term)
            || (row.dataset.reason || '').includes(term);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });

    noResults?.classList.toggle('d-none', visible > 0 || !term);
    const termEl = document.getElementById('no-results-term');
    if (termEl) termEl.textContent = term;
}

searchInput?.addEventListener('input', applyFilter);
searchClear?.addEventListener('click', () => { searchInput.value = ''; applyFilter(); searchInput.focus(); });

// ── Contador IDs en textarea de importación ───────────────────────────────
const bulkIdsInput  = document.getElementById('input-bulk-ids');
const bulkCountEl   = document.getElementById('bulk-ids-count');
const clearTextarea = document.getElementById('btn-clear-textarea');

function updateBulkCount() {
    const raw = (bulkIdsInput?.value || '').trim();
    if (!raw) {
        if (bulkCountEl)   bulkCountEl.textContent = '';
        if (clearTextarea) clearTextarea.style.display = 'none';
        return;
    }
    const n = raw.split(/[\s,;]+/).filter(v => v.length > 0).length;
    if (bulkCountEl)   bulkCountEl.textContent = `${n} ID${n !== 1 ? 's' : ''} detectado${n !== 1 ? 's' : ''}`;
    if (clearTextarea) clearTextarea.style.display = '';
}

bulkIdsInput?.addEventListener('input', updateBulkCount);
clearTextarea?.addEventListener('click', () => { bulkIdsInput.value = ''; updateBulkCount(); });

// Limpiar formularios al abrir modales
document.getElementById('modal-add-single')?.addEventListener('show.bs.modal', () => {
    document.getElementById('input-erp-id').value  = '';
    document.getElementById('input-label').value   = '';
    document.getElementById('input-reason').value  = '';
    document.getElementById('input-erp-id').classList.remove('is-invalid');
});

document.getElementById('modal-import-bulk')?.addEventListener('show.bs.modal', () => {
    bulkIdsInput.value = '';
    document.getElementById('input-bulk-reason').value = '';
    updateBulkCount();
});

// Focus al abrir modal de añadir
document.getElementById('modal-add-single')?.addEventListener('shown.bs.modal', () => {
    document.getElementById('input-erp-id').focus();
});

// ── Ordenar columnas ───────────────────────────────────────────────────────
function bindSortHeaders() {
    document.querySelectorAll('.sortable').forEach(th => {
        th.addEventListener('click', () => {
            const col = th.dataset.col;
            sortDir = (sortCol === col && sortDir === 'asc') ? 'desc' : 'asc';
            sortCol = col;

            document.querySelectorAll('.sortable i').forEach(i => {
                i.className = 'fas fa-sort text-muted ms-1';
                i.style.fontSize = '.65rem';
            });
            const icon = th.querySelector('i');
            if (icon) {
                icon.className = `fas fa-sort-${sortDir === 'asc' ? 'up' : 'down'} ms-1`;
                icon.style.fontSize = '.65rem';
            }
            sortTable(col, sortDir);
        });
    });
}

function sortTable(col, dir) {
    const tbody = document.getElementById('groups-tbody');
    if (!tbody) return;
    [...tbody.querySelectorAll('tr')]
        .sort((a, b) => {
            let va, vb;
            if (col === 'erp_id') { va = parseInt(a.dataset.erpId); vb = parseInt(b.dataset.erpId); }
            else if (col === 'date') { va = parseInt(a.dataset.date); vb = parseInt(b.dataset.date); }
            else { va = a.dataset[col] || ''; vb = b.dataset[col] || ''; }
            if (va < vb) return dir === 'asc' ? -1 :  1;
            if (va > vb) return dir === 'asc' ?  1 : -1;
            return 0;
        })
        .forEach(r => tbody.appendChild(r));
}

bindSortHeaders();

// ── Selección múltiple ─────────────────────────────────────────────────────
function bindCheckAll() {
    document.getElementById('check-all')?.addEventListener('change', e => {
        document.querySelectorAll('.row-check').forEach(cb => cb.checked = e.target.checked);
        updateBulkBar();
    });
}
bindCheckAll();

document.addEventListener('change', e => {
    if (e.target.classList.contains('row-check')) updateBulkBar();
});

function updateBulkBar() {
    const n   = document.querySelectorAll('.row-check:checked').length;
    const bar = document.getElementById('bulk-actions');
    const lbl = document.getElementById('selected-count-label');
    if (!bar) return;
    bar.classList.toggle('d-none', n === 0);
    if (lbl) lbl.textContent = `${n} seleccionado${n !== 1 ? 's' : ''}`;
}

// ── Copiar ID individual ───────────────────────────────────────────────────
document.addEventListener('click', e => {
    const btn = e.target.closest('.btn-copy-id');
    if (!btn) return;
    e.stopPropagation();
    navigator.clipboard.writeText(btn.dataset.value)
        .then(() => toastr.info(`ID ${btn.dataset.value} copiado.`, '', { timeOut: 1500 }));
});

// ── Copiar todos los IDs ──────────────────────────────────────────────────
document.getElementById('btn-copy-ids')?.addEventListener('click', e => {
    e.preventDefault();
    const tbody = document.getElementById('groups-tbody');
    const ids = tbody ? [...tbody.querySelectorAll('tr')].map(r => r.dataset.erpId).filter(Boolean).join('\n') : '';
    if (!ids) { toastr.warning('No hay IDs en la lista.'); return; }
    navigator.clipboard.writeText(ids).then(() => toastr.success('IDs copiados al portapapeles.'));
});

// ── Exportar .txt ──────────────────────────────────────────────────────────
document.getElementById('btn-export-txt')?.addEventListener('click', e => {
    e.preventDefault();
    const tbody = document.getElementById('groups-tbody');
    if (!tbody) { toastr.warning('No hay IDs en la lista.'); return; }
    const lines = [...tbody.querySelectorAll('tr')].map(r =>
        [r.dataset.erpId, r.dataset.label, r.dataset.reason].filter(Boolean).join('\t')
    );
    if (!lines.length) { toastr.warning('No hay IDs en la lista.'); return; }
    const a    = Object.assign(document.createElement('a'), {
        href:     URL.createObjectURL(new Blob([lines.join('\n')], { type: 'text/plain' })),
        download: 'grupos-excluidos.txt',
    });
    a.click();
    URL.revokeObjectURL(a.href);
});

// ── Limpiar toda la lista ─────────────────────────────────────────────────
document.getElementById('btn-clear-all')?.addEventListener('click', e => {
    e.preventDefault();
    const total = parseInt(document.getElementById('count-badge')?.textContent || '0');
    if (total === 0) { toastr.warning('La lista ya está vacía.'); return; }
    const el = document.getElementById('modal-clear-all-count');
    if (el) el.textContent = `los ${total}`;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-confirm-clear-all')).show();
});

document.getElementById('btn-confirm-clear-all')?.addEventListener('click', function () {
    const tbody = document.getElementById('groups-tbody');
    if (!tbody) return;
    const ids  = [...tbody.querySelectorAll('tr')].map(r => parseInt(r.dataset.id)).filter(Boolean);
    const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Limpiando…');

    const deleteNext = remaining => {
        if (!remaining.length) {
            showEmptyState();
            document.getElementById('count-badge').textContent = '0';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-confirm-clear-all')).hide();
            toastr.success('Lista limpiada correctamente.');
            $btn.prop('disabled', false).html('Sí, limpiar todo');
            return;
        }
        const id = remaining[0];
        fetch(routes.destroy.replace(':id', id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        }).finally(() => { document.getElementById('row-' + id)?.remove(); deleteNext(remaining.slice(1)); });
    };
    deleteNext(ids);
});

// ── Eliminación múltiple seleccionada ─────────────────────────────────────
document.getElementById('btn-bulk-delete')?.addEventListener('click', () => {
    const n = document.querySelectorAll('.row-check:checked').length;
    if (!n) return;
    document.getElementById('modal-bulk-count').textContent = n;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-confirm-bulk-delete')).show();
});

document.getElementById('btn-confirm-bulk-delete')?.addEventListener('click', function () {
    const checked = [...document.querySelectorAll('.row-check:checked')];
    if (!checked.length) return;
    const ids  = checked.map(cb => parseInt(cb.value));
    const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Eliminando…');

    const deleteNext = (remaining, deleted) => {
        if (!remaining.length) {
            updateCount(-deleted);
            const tbody = document.getElementById('groups-tbody');
            if (tbody && !tbody.querySelectorAll('tr').length) showEmptyState();
            updateBulkBar();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-confirm-bulk-delete')).hide();
            toastr.success(`${deleted} ID(s) eliminados.`);
            $btn.prop('disabled', false).html('Sí, eliminar selección');
            return;
        }
        const id = remaining[0];
        fetch(routes.destroy.replace(':id', id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        }).then(() => { document.getElementById('row-' + id)?.remove(); deleteNext(remaining.slice(1), deleted + 1); })
          .catch(() => deleteNext(remaining.slice(1), deleted));
    };
    deleteNext(ids, 0);
});

// ── Añadir individual ──────────────────────────────────────────────────────
document.getElementById('btn-add')?.addEventListener('click', function () {
    const erpIdInput = document.getElementById('input-erp-id');
    const erpId      = erpIdInput.value.trim();
    const label      = document.getElementById('input-label').value.trim();
    const reason     = document.getElementById('input-reason').value.trim();

    erpIdInput.classList.remove('is-invalid');
    if (!erpId || isNaN(parseInt(erpId))) {
        erpIdInput.classList.add('is-invalid');
        document.getElementById('erp-id-error').textContent = 'Introduce un ID ERP válido.';
        erpIdInput.focus();
        return;
    }

    const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Añadiendo…');

    fetch(routes.store, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ erp_id: parseInt(erpId), label: label || null, reason: reason || null }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            if (data.errors?.erp_id) {
                erpIdInput.classList.add('is-invalid');
                document.getElementById('erp-id-error').textContent = data.errors.erp_id[0];
            } else {
                toastr.error(data.message || 'Error al añadir');
            }
        } else {
            toastr.success(data.message);
            getOrCreateTbody().insertAdjacentHTML('beforeend', rowHtml(data.group));
            updateCount(1);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-add-single')).hide();
        }
    })
    .catch(() => toastr.error('Error de red'))
    .finally(() => $btn.prop('disabled', false).html('<i class="fas fa-plus me-2"></i>Añadir'));
});

// Enter en campos del modal de añadir
['input-erp-id', 'input-label', 'input-reason'].forEach(id => {
    document.getElementById(id)?.addEventListener('keydown', e => {
        if (e.key === 'Enter') document.getElementById('btn-add')?.click();
    });
});

// ── Importar lista ─────────────────────────────────────────────────────────
document.getElementById('btn-bulk')?.addEventListener('click', function () {
    const ids    = bulkIdsInput?.value.trim();
    const reason = document.getElementById('input-bulk-reason').value.trim();

    if (!ids) { toastr.warning('Pega al menos un ID.'); bulkIdsInput?.focus(); return; }

    const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Importando…');

    fetch(routes.bulk, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids, reason: reason || null }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            toastr.error(data.message || 'Error');
        } else {
            toastr.success(data.message);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-import-bulk')).hide();
            setTimeout(() => location.reload(), 600);
        }
    })
    .catch(() => toastr.error('Error de red'))
    .finally(() => $btn.prop('disabled', false).html('<i class="fas fa-file-import me-2"></i>Importar'));
});

// ── Eliminar individual ────────────────────────────────────────────────────
document.addEventListener('click', e => {
    const btn = e.target.closest('.btn-destroy');
    if (!btn) return;
    pendingDeleteId = parseInt(btn.dataset.id);
    document.getElementById('modal-erp-id-label').textContent = btn.dataset.erpId;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-confirm-delete')).show();
});

document.getElementById('btn-confirm-delete')?.addEventListener('click', function () {
    if (!pendingDeleteId) return;
    const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Eliminando…');

    fetch(routes.destroy.replace(':id', pendingDeleteId), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            toastr.error(data.message || 'Error');
        } else {
            toastr.success(data.message);
            document.getElementById('row-' + pendingDeleteId)?.remove();
            updateCount(-1);
            const tbody = document.getElementById('groups-tbody');
            if (tbody && !tbody.querySelectorAll('tr').length) showEmptyState();
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-confirm-delete')).hide();
    })
    .catch(() => toastr.error('Error de red'))
    .finally(() => { $btn.prop('disabled', false).html('Sí, eliminar'); pendingDeleteId = null; });
});

// ── Edición inline ─────────────────────────────────────────────────────────
document.addEventListener('click', e => {
    const cell = e.target.closest('.editable-cell');
    if (!cell) return;

    pendingEditId    = parseInt(cell.dataset.id);
    pendingEditField = cell.dataset.field;
    const isLabel    = pendingEditField === 'label';

    document.getElementById('modal-edit-title').textContent = isLabel ? 'Editar descripción' : 'Editar motivo';
    const inputEl = document.getElementById('modal-edit-input');
    inputEl.value     = cell.title || '';
    inputEl.maxLength = isLabel ? 120 : 255;
    document.getElementById('modal-edit-hint').textContent = isLabel ? 'Máx. 120 caracteres.' : 'Máx. 255 caracteres.';

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-edit-cell')).show();
    setTimeout(() => { inputEl.focus(); inputEl.select(); }, 300);
});

document.getElementById('btn-confirm-edit')?.addEventListener('click', function () {
    if (!pendingEditId || !pendingEditField) return;
    const inputEl = document.getElementById('modal-edit-input');
    const value   = inputEl?.value.trim() || null;
    const $btn    = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>');
    const body    = { [pendingEditField]: value };

    fetch(routes.update.replace(':id', pendingEditId), {
        method: 'PUT',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { toastr.error(data.message || 'Error'); return; }
        const row  = document.getElementById('row-' + pendingEditId);
        const cell = row?.querySelector(`.editable-cell[data-field="${pendingEditField}"]`);
        if (cell) {
            cell.title = value || '';
            if (pendingEditField === 'label') {
                cell.innerHTML = value
                    ? value
                    : '<span class="text-muted fst-italic small">Sin descripción</span>';
                if (row) row.dataset.label = (value || '').toLowerCase();
            } else {
                cell.textContent = value || '—';
                if (row) row.dataset.reason = (value || '').toLowerCase();
            }
        }
        toastr.success('Guardado.');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-edit-cell')).hide();
    })
    .catch(() => toastr.error('Error de red'))
    .finally(() => {
        $btn.prop('disabled', false).html('Guardar');
        pendingEditId = pendingEditField = null;
    });
});

document.getElementById('modal-edit-input')?.addEventListener('keydown', e => {
    if (e.key === 'Enter')  document.getElementById('btn-confirm-edit')?.click();
    if (e.key === 'Escape') bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-edit-cell')).hide();
});
</script>
@endpush
