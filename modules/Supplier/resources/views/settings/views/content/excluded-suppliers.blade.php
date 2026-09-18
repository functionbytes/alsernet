@extends('layouts.theme')

@section('title', 'Proveedores excluidos de IA')

@section('page_header')
    @include('core::components.card', ['title' => 'Proveedores excluidos de generación IA'])
@endsection

@section('content')
<div class="widget-content searchable-container list">

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Proveedores excluidos</h5>
                    <p class="small mb-0 text-muted">
                        Los modelos de estos proveedores se siguen sincronizando normalmente, pero
                        <strong>no generan contenido IA automáticamente</strong> — quedan como "Sin generar"
                        para poder generarlos a mano cuando se decida.
                    </p>
                </div>
                <div class="dropdown">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="#"
                               data-bs-toggle="modal" data-bs-target="#modal-add-single">
                                + Añadir proveedor
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#"
                               data-bs-toggle="modal" data-bs-target="#modal-import-file">
                                Importar archivo
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" id="btn-export-txt">
                                Descargar como .txt
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="#"
                               data-bs-toggle="modal" data-bs-target="#modal-how-it-works">
                                ¿Cómo funciona?
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="#" id="btn-clear-all">
                                Limpiar toda la lista
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        @php
            $totalExcluded  = $excluded->count();
            $withReason     = $excluded->filter(fn ($r) => filled($r->reason))->count();
            $withoutReason  = $totalExcluded - $withReason;
            $lastAdded      = $excluded->max('created_at');
        @endphp
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Total excluidos</h6>
                            <h2 class="fw-bold mb-1" id="stat-total">{{ $totalExcluded }}</h2>
                            <small class="text-muted">Proveedores en la lista</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Con motivo</h6>
                            <h2 class="fw-bold mb-1" id="stat-with-reason">{{ $withReason }}</h2>
                            <small class="text-muted">Tienen motivo indicado</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Sin motivo</h6>
                            <h2 class="fw-bold mb-1" id="stat-without-reason">{{ $withoutReason }}</h2>
                            <small class="text-muted">Sin motivo indicado</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Excluido más reciente</h6>
                            <h2 class="fw-bold mb-1" id="stat-last-added">
                                @if($lastAdded)
                                    {{ $lastAdded->format('d/m/Y') }}
                                @else
                                    <span class="text-muted fs-5">—</span>
                                @endif
                            </h2>
                            <small class="text-muted">Última exclusión añadida</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="card-body border-bottom">
            @php $activeFilterCount = 0; @endphp
            <div class="d-flex align-items-center gap-2">
                <input type="search" id="search-input" class="form-control flex-grow-1" style="min-width:0;"
                       placeholder="Buscar proveedor, motivo…" autocomplete="off">

                <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                        data-bs-toggle="modal" data-bs-target="#modal-advanced-filters" title="Filtros avanzados">
                    <i class="fas fa-filter"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary d-none"
                          id="filter-count-badge" style="font-size:0.6rem;"></span>
                </button>

                <button type="button" class="btn btn-secondary d-none flex-shrink-0" id="btn-clear-filters" title="Limpiar filtros">
                    <i class="fas fa-xmark"></i>
                </button>

                {{-- Acciones bulk (visibles al seleccionar) --}}
                <div id="bulk-actions" class="d-none d-flex align-items-center gap-2 ms-auto">
                    <span class="small text-muted" id="selected-count-label"></span>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="btn-bulk-delete">
                        Eliminar selección
                    </button>
                </div>
            </div>
        </div>

        {{-- Listado --}}
        <div class="card-body">
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1 fw-bold">Proveedores excluidos</h6>
                    <p class="text-muted small mb-0" id="count-label" data-total="{{ $excluded->count() }}">{{ $excluded->count() }} proveedor(es) excluidos</p>
                </div>
            </div>

            <div id="table-wrap">
                @if($excluded->isEmpty())
                    <div class="text-center py-5 text-muted" id="empty-state">
                        <i class="fas fa-ban fa-2x mb-3 d-block opacity-25"></i>
                        <p class="mb-1 fw-semibold">Sin proveedores excluidos</p>
                        <p class="small mb-0 text-muted">
                            Pulsa <strong>+ Añadir proveedor</strong> para excluir el primero.
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table search-table align-middle text-nowrap" id="excluded-table">
                            <thead class="header-item">
                                <tr>
                                    <th width="3%">
                                        <input type="checkbox" class="form-check-input" id="check-all" title="Seleccionar todos">
                                    </th>
                                    <th>Proveedor</th>
                                    <th>Motivo</th>
                                    <th class="text-center">Añadido</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="excluded-tbody">
                            @foreach($excluded as $row)
                                <tr id="row-{{ $row->id }}" data-id="{{ $row->id }}"
                                    data-label="{{ strtolower($row->supplier?->label ?? '') }}"
                                    data-reason="{{ strtolower($row->reason ?? '') }}"
                                    data-has-reason="{{ filled($row->reason) ? '1' : '0' }}"
                                    data-date="{{ $row->created_at->format('Y-m-d') }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input row-check" value="{{ $row->id }}">
                                    </td>
                                    <td>
                                        <strong>{{ $row->supplier?->label ?? "Proveedor #{$row->supplier_id}" }}</strong>
                                        @if($row->supplier?->code)
                                            <small class="text-muted d-block">{{ $row->supplier->code }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="editable-cell small text-muted" style="cursor:pointer;"
                                              data-field="reason" data-id="{{ $row->id }}"
                                              title="{{ $row->reason ?? '' }}">
                                            {{ $row->reason ?: '—' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-muted small">{{ $row->created_at->format('d/m/Y') }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown dropstart">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item editable-cell" href="#"
                                                       data-field="reason" data-id="{{ $row->id }}"
                                                       title="{{ $row->reason ?? '' }}">
                                                        Editar motivo
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item btn-destroy" href="#"
                                                       data-id="{{ $row->id }}"
                                                       data-label="{{ $row->supplier?->label ?? '' }}">
                                                        Quitar de la exclusión
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

{{-- Modal: Filtros avanzados --}}
<div class="modal fade" id="modal-advanced-filters" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold">Filtros avanzados</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Motivo</label>
                    <select id="filter-has-reason" class="form-select">
                        <option value="">Todos</option>
                        <option value="1">Con motivo</option>
                        <option value="0">Sin motivo</option>
                    </select>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Añadido desde</label>
                        <input type="date" id="filter-date-from" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Añadido hasta</label>
                        <input type="date" id="filter-date-to" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top gap-2">
                <button type="button" class="btn btn-primary w-100 mb-1" id="btn-apply-filters">Aplicar filtros</button>
                <button type="button" class="btn btn-secondary w-100" id="btn-clear-advanced-filters">Limpiar filtros</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Añadir proveedor individual --}}
<div class="modal fade" id="modal-add-single" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold">Añadir proveedor</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">
                        Proveedor <span class="text-danger">*</span>
                    </label>
                    <select id="input-supplier-id" class="form-select select2-supplier">
                        <option value="">Seleccionar proveedor...</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->label }}@if($supplier->code) ({{ $supplier->code }})@endif</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" id="supplier-id-error"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">
                        Motivo <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <input type="text" id="input-reason" class="form-control" maxlength="255"
                           placeholder="Ej: Catálogo en revisión, no subir todavía">
                </div>

                <div class="d-flex align-items-center gap-2 text-muted small my-3">
                    <hr class="flex-grow-1"><span>o</span><hr class="flex-grow-1">
                </div>

                <div class="mb-0">
                    <label class="form-label small fw-semibold">
                        ¿No aparece en la lista? Añadir por ID/código
                    </label>
                    <input type="text" id="input-supplier-codes" class="form-control" maxlength="500"
                           placeholder="Ej: H10043, 1050, ABC123">
                    <div class="form-text">
                        Uno o varios códigos / ID de Gestión separados por coma. Úsalo si el proveedor
                        todavía no se ha sincronizado localmente y por eso no aparece arriba.
                    </div>
                    <div class="invalid-feedback" id="supplier-codes-error"></div>
                </div>
            </div>
            <div class="modal-footer border-top gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4" id="btn-add">Añadir</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Importar archivo --}}
<div class="modal fade" id="modal-import-file" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold">Importar archivo de proveedores</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">
                        Archivo <span class="text-danger">*</span>
                    </label>
                    <input type="file" id="input-file" class="form-control" accept=".txt,.csv">
                    <div class="form-text">
                        Archivo .txt o .csv con un código de proveedor o ID ERP por línea.
                        Se compara contra el catálogo de proveedores ya sincronizado.
                    </div>
                    <div class="invalid-feedback" id="file-error"></div>
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-semibold">
                        Motivo común <span class="text-muted fw-normal">(opcional, se aplica a todos)</span>
                    </label>
                    <input type="text" id="input-import-reason" class="form-control" maxlength="255"
                           placeholder="Ej: Excluido manualmente">
                </div>
            </div>
            <div class="modal-footer border-top gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4" id="btn-import">
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
                <h6 class="modal-title fw-bold">¿Cómo funciona la exclusión?</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="mb-0 ps-3" style="line-height:1.8;">
                    <li>Los modelos de un proveedor excluido <strong>se siguen sincronizando</strong> desde el ERP con normalidad.</li>
                    <li>Solo se salta el paso de <strong>generación automática de contenido IA</strong> al sincronizar.</li>
                    <li>Quedan como "Sin generar" en Contenido generado, disponibles para generarlos a mano cuando se decida.</li>
                    <li>No afecta a la generación manual ni a la regeneración desde la pantalla de Contenido generado.</li>
                    <li>El cambio tiene efecto en la <strong>siguiente ejecución</strong> de sync.</li>
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
                <h6 class="modal-title fw-bold">Quitar exclusión</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 small">
                    ¿Quitar a <strong id="modal-label">—</strong> de la lista?
                    Sus modelos volverán a generar contenido IA automáticamente.
                </p>
            </div>
            <div class="modal-footer d-flex flex-column gap-1 pt-2">
                <button type="button" class="btn btn-danger w-100 fw-semibold" id="btn-confirm-delete">
                    Sí, quitar
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
                    ¿Quitar <strong id="modal-bulk-count">0</strong> proveedor(es) seleccionados de la exclusión?
                </p>
            </div>
            <div class="modal-footer d-flex flex-column gap-1 pt-2">
                <button type="button" class="btn btn-danger w-100 fw-semibold" id="btn-confirm-bulk-delete">
                    Sí, quitar selección
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
                    Se quitarán <strong id="modal-clear-all-count">todos</strong> los proveedores excluidos.
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

{{-- Modal: Edición inline de motivo --}}
<div class="modal fade" id="modal-edit-cell" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold">Editar motivo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <input type="text" id="modal-edit-input" class="form-control" maxlength="255">
                <div class="small text-muted mt-1">Máx. 255 caracteres.</div>
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

let pendingDeleteId = null;
let pendingEditId   = null;

$('#input-supplier-id').select2({ dropdownParent: $('#modal-add-single'), width: '100%' });

// ── Helpers ────────────────────────────────────────────────────────────────
function rowHtml(row) {
    const label    = row.supplier?.label ?? `Proveedor #${row.supplier_id}`;
    const code     = row.supplier?.code ? `<small class="text-muted d-block">${row.supplier.code}</small>` : '';
    const reason   = row.reason || '—';
    const isoDate  = row.created_at.slice(0, 10);
    const date     = new Date(row.created_at).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
    return `<tr id="row-${row.id}" data-id="${row.id}" data-label="${label.toLowerCase()}" data-reason="${(row.reason || '').toLowerCase()}" data-has-reason="${row.reason ? '1' : '0'}" data-date="${isoDate}">
        <td><input type="checkbox" class="form-check-input row-check" value="${row.id}"></td>
        <td><strong>${label}</strong>${code}</td>
        <td>
            <span class="editable-cell small text-muted" style="cursor:pointer;"
                  data-field="reason" data-id="${row.id}" title="${row.reason || ''}">${reason}</span>
        </td>
        <td class="text-center"><span class="text-muted small">${date}</span></td>
        <td class="text-center">
            <div class="dropdown dropstart">
                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-duotone fa-solid fa-ellipsis"></i>
                </a>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item editable-cell" href="#"
                           data-field="reason" data-id="${row.id}" title="${row.reason || ''}">
                            Editar motivo
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item btn-destroy" href="#" data-id="${row.id}" data-label="${label}">
                            Quitar de la exclusión
                        </a>
                    </li>
                </ul>
            </div>
        </td>
    </tr>`;
}

function updateCount(delta) {
    const label = document.getElementById('count-label');
    const n = Math.max(0, parseInt(label.dataset.total || '0') + delta);
    label.dataset.total = n;
    applyFilter();
    refreshStats();
}

function refreshStats() {
    const rows = [...document.querySelectorAll('#excluded-tbody tr')];
    const total = rows.length;
    const withReason = rows.filter(r => r.dataset.hasReason === '1').length;
    const lastDate = rows.map(r => r.dataset.date).filter(Boolean).sort().pop();

    document.getElementById('stat-total')?.replaceChildren(document.createTextNode(total));
    document.getElementById('stat-with-reason')?.replaceChildren(document.createTextNode(withReason));
    document.getElementById('stat-without-reason')?.replaceChildren(document.createTextNode(total - withReason));

    const lastEl = document.getElementById('stat-last-added');
    if (lastEl) {
        if (lastDate) {
            const [y, m, d] = lastDate.split('-');
            lastEl.textContent = `${d}/${m}/${y}`;
        } else {
            lastEl.innerHTML = '<span class="text-muted fs-5">—</span>';
        }
    }
}

function getOrCreateTbody() {
    let tbody = document.getElementById('excluded-tbody');
    if (tbody) return tbody;

    document.getElementById('table-wrap').innerHTML = `
        <div class="table-responsive">
            <table class="table search-table align-middle text-nowrap" id="excluded-table">
                <thead class="header-item">
                    <tr>
                        <th width="3%">
                            <input type="checkbox" class="form-check-input" id="check-all" title="Seleccionar todos">
                        </th>
                        <th>Proveedor</th>
                        <th>Motivo</th>
                        <th class="text-center">Añadido</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="excluded-tbody"></tbody>
            </table>
        </div>`;

    bindCheckAll();
    return document.getElementById('excluded-tbody');
}

function showEmptyState() {
    document.getElementById('table-wrap').innerHTML =
        `<div class="text-center py-5 text-muted" id="empty-state">
            <i class="fas fa-ban fa-2x mb-3 d-block opacity-25"></i>
            <p class="mb-1 fw-semibold">Sin proveedores excluidos</p>
            <p class="small mb-0 text-muted">Pulsa <strong>+ Añadir proveedor</strong> para excluir el primero.</p>
        </div>`;
}

// ── Búsqueda / filtros avanzados ─────────────────────────────────────────────
const searchInput = document.getElementById('search-input');
const noResults    = document.getElementById('no-results');

let filterHasReason = '';
let filterDateFrom  = '';
let filterDateTo    = '';

function hasActiveAdvancedFilters() {
    return filterHasReason !== '' || filterDateFrom !== '' || filterDateTo !== '';
}

function applyFilter() {
    const term = (searchInput?.value || '').toLowerCase().trim();

    const tbody = document.getElementById('excluded-tbody');
    const total = parseInt(document.getElementById('count-label')?.dataset.total || '0');

    if (!tbody) {
        const label = document.getElementById('count-label');
        if (label) label.textContent = `${total} proveedor(es) excluidos`;
        return;
    }

    let visible = 0;
    tbody.querySelectorAll('tr').forEach(row => {
        const match = (!term
                || (row.dataset.label  || '').includes(term)
                || (row.dataset.reason || '').includes(term))
            && (filterHasReason === '' || row.dataset.hasReason === filterHasReason)
            && (filterDateFrom === '' || row.dataset.date >= filterDateFrom)
            && (filterDateTo === '' || row.dataset.date <= filterDateTo);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });

    const anyFilter = !!term || hasActiveAdvancedFilters();
    const label = document.getElementById('count-label');
    if (label) {
        label.textContent = anyFilter
            ? `${visible} de ${total} proveedor(es) encontrados`
            : `${total} proveedor(es) excluidos`;
    }

    noResults?.classList.toggle('d-none', visible > 0 || !anyFilter);
    const termEl = document.getElementById('no-results-term');
    if (termEl) termEl.textContent = term || '(filtros avanzados)';

    // Badge del botón de filtro + botón "limpiar filtros"
    const activeCount = (filterHasReason !== '' ? 1 : 0) + (filterDateFrom !== '' ? 1 : 0) + (filterDateTo !== '' ? 1 : 0);
    const badge = document.getElementById('filter-count-badge');
    if (badge) {
        badge.textContent = activeCount;
        badge.classList.toggle('d-none', activeCount === 0);
    }
    document.getElementById('btn-clear-filters')?.classList.toggle('d-none', !anyFilter);
}

searchInput?.addEventListener('input', applyFilter);

// ── Filtros avanzados (modal) ────────────────────────────────────────────────
document.getElementById('modal-advanced-filters')?.addEventListener('show.bs.modal', () => {
    document.getElementById('filter-has-reason').value = filterHasReason;
    document.getElementById('filter-date-from').value  = filterDateFrom;
    document.getElementById('filter-date-to').value    = filterDateTo;
});

document.getElementById('btn-apply-filters')?.addEventListener('click', () => {
    filterHasReason = document.getElementById('filter-has-reason').value;
    filterDateFrom  = document.getElementById('filter-date-from').value;
    filterDateTo    = document.getElementById('filter-date-to').value;
    applyFilter();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-advanced-filters')).hide();
});

document.getElementById('btn-clear-advanced-filters')?.addEventListener('click', () => {
    filterHasReason = '';
    filterDateFrom  = '';
    filterDateTo    = '';
    applyFilter();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-advanced-filters')).hide();
});

document.getElementById('btn-clear-filters')?.addEventListener('click', () => {
    searchInput.value = '';
    filterHasReason = '';
    filterDateFrom  = '';
    filterDateTo    = '';
    applyFilter();
});

// Limpiar formularios al abrir modales
document.getElementById('modal-add-single')?.addEventListener('show.bs.modal', () => {
    $('#input-supplier-id').val('').trigger('change');
    document.getElementById('input-reason').value = '';
    document.getElementById('input-supplier-codes').value = '';
    document.getElementById('input-supplier-id').classList.remove('is-invalid');
    document.getElementById('input-supplier-codes').classList.remove('is-invalid');
});

document.getElementById('modal-import-file')?.addEventListener('show.bs.modal', () => {
    document.getElementById('input-file').value = '';
    document.getElementById('input-import-reason').value = '';
});

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

// ── Exportar .txt ──────────────────────────────────────────────────────────
document.getElementById('btn-export-txt')?.addEventListener('click', e => {
    e.preventDefault();
    const tbody = document.getElementById('excluded-tbody');
    if (!tbody) { toastr.warning('No hay proveedores en la lista.'); return; }
    const lines = [...tbody.querySelectorAll('tr')].map(r => {
        const label = r.querySelector('strong')?.textContent || '';
        return [label, r.dataset.reason].filter(Boolean).join('\t');
    });
    if (!lines.length) { toastr.warning('No hay proveedores en la lista.'); return; }
    const a = Object.assign(document.createElement('a'), {
        href:     URL.createObjectURL(new Blob([lines.join('\n')], { type: 'text/plain' })),
        download: 'proveedores-excluidos.txt',
    });
    a.click();
    URL.revokeObjectURL(a.href);
});

// ── Limpiar toda la lista ─────────────────────────────────────────────────
document.getElementById('btn-clear-all')?.addEventListener('click', e => {
    e.preventDefault();
    const total = parseInt(document.getElementById('count-label')?.dataset.total || '0');
    if (total === 0) { toastr.warning('La lista ya está vacía.'); return; }
    const el = document.getElementById('modal-clear-all-count');
    if (el) el.textContent = `los ${total}`;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-confirm-clear-all')).show();
});

document.getElementById('btn-confirm-clear-all')?.addEventListener('click', function () {
    const tbody = document.getElementById('excluded-tbody');
    if (!tbody) return;
    const ids  = [...tbody.querySelectorAll('tr')].map(r => parseInt(r.dataset.id)).filter(Boolean);
    const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Limpiando…');

    const deleteNext = remaining => {
        if (!remaining.length) {
            showEmptyState();
            const label = document.getElementById('count-label');
            if (label) { label.dataset.total = '0'; applyFilter(); }
            refreshStats();
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
            const tbody = document.getElementById('excluded-tbody');
            if (tbody && !tbody.querySelectorAll('tr').length) showEmptyState();
            updateBulkBar();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-confirm-bulk-delete')).hide();
            toastr.success(`${deleted} proveedor(es) quitados.`);
            $btn.prop('disabled', false).html('Sí, quitar selección');
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

// ── Añadir individual (por selector) o por ID/código (uno o varios) ─────────
document.getElementById('btn-add')?.addEventListener('click', function () {
    const supplierId = $('#input-supplier-id').val();
    const codesRaw    = document.getElementById('input-supplier-codes').value.trim();
    const reason      = document.getElementById('input-reason').value.trim();

    document.getElementById('input-supplier-id').classList.remove('is-invalid');
    document.getElementById('input-supplier-codes').classList.remove('is-invalid');

    // El campo de códigos manda si tiene contenido; si no, se usa el selector.
    if (codesRaw) {
        addByCodes(codesRaw, reason, $(this));
        return;
    }

    if (!supplierId) {
        document.getElementById('input-supplier-id').classList.add('is-invalid');
        document.getElementById('supplier-id-error').textContent = 'Selecciona un proveedor, o escribe uno o varios códigos/ID abajo.';
        return;
    }

    const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Añadiendo…');

    fetch(routes.store, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ supplier_id: parseInt(supplierId), reason: reason || null }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            toastr.error(data.message || 'Error al añadir');
        } else {
            toastr.success(data.message);
            getOrCreateTbody().insertAdjacentHTML('beforeend', rowHtml(data.excluded));
            updateCount(1);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-add-single')).hide();
        }
    })
    .catch(() => toastr.error('Error de red'))
    .finally(() => $btn.prop('disabled', false).html('Añadir'));
});

function addByCodes(values, reason, $btn) {
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Añadiendo…');

    fetch(routes.storeByCode, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ values, reason: reason || null }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            document.getElementById('input-supplier-codes').classList.add('is-invalid');
            document.getElementById('supplier-codes-error').textContent = data.message || 'Error al añadir';
            return;
        }
        if (data.excluded?.length) {
            const tbody = getOrCreateTbody();
            data.excluded.forEach(row => tbody.insertAdjacentHTML('beforeend', rowHtml(row)));
            updateCount(data.excluded.length);
        }
        if (data.not_found?.length) {
            toastr.warning(data.message);
        } else {
            toastr.success(data.message);
        }
        if (data.excluded?.length) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-add-single')).hide();
        }
    })
    .catch(() => toastr.error('Error de red'))
    .finally(() => $btn.prop('disabled', false).html('Añadir'));
}

// ── Importar archivo ────────────────────────────────────────────────────────
document.getElementById('btn-import')?.addEventListener('click', function () {
    const fileInput = document.getElementById('input-file');
    const reason    = document.getElementById('input-import-reason').value.trim();

    fileInput.classList.remove('is-invalid');
    if (!fileInput.files.length) {
        fileInput.classList.add('is-invalid');
        document.getElementById('file-error').textContent = 'Selecciona un archivo.';
        return;
    }

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    if (reason) formData.append('reason', reason);

    const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Importando…');

    fetch(routes.import, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: formData,
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            toastr.error(data.message || 'Error al importar');
        } else {
            toastr.success(data.message);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-import-file')).hide();
            setTimeout(() => location.reload(), 800);
        }
    })
    .catch(() => toastr.error('Error de red'))
    .finally(() => $btn.prop('disabled', false).html('<i class="fas fa-file-import me-2"></i>Importar'));
});

// ── Eliminar individual ────────────────────────────────────────────────────
document.addEventListener('click', e => {
    const btn = e.target.closest('.btn-destroy');
    if (!btn) return;
    e.preventDefault();
    pendingDeleteId = parseInt(btn.dataset.id);
    document.getElementById('modal-label').textContent = btn.dataset.label;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-confirm-delete')).show();
});

document.getElementById('btn-confirm-delete')?.addEventListener('click', function () {
    if (!pendingDeleteId) return;
    const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Quitando…');

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
            const tbody = document.getElementById('excluded-tbody');
            if (tbody && !tbody.querySelectorAll('tr').length) showEmptyState();
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-confirm-delete')).hide();
    })
    .catch(() => toastr.error('Error de red'))
    .finally(() => { $btn.prop('disabled', false).html('Sí, quitar'); pendingDeleteId = null; });
});

// ── Edición inline del motivo ───────────────────────────────────────────────
document.addEventListener('click', e => {
    const cell = e.target.closest('.editable-cell');
    if (!cell) return;
    e.preventDefault();

    pendingEditId = parseInt(cell.dataset.id);
    const inputEl = document.getElementById('modal-edit-input');
    inputEl.value = cell.title || '';

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-edit-cell')).show();
    setTimeout(() => { inputEl.focus(); inputEl.select(); }, 300);
});

document.getElementById('btn-confirm-edit')?.addEventListener('click', function () {
    if (!pendingEditId) return;
    const inputEl = document.getElementById('modal-edit-input');
    const value   = inputEl?.value.trim() || null;
    const $btn    = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>');

    fetch(routes.update.replace(':id', pendingEditId), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ reason: value }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { toastr.error(data.message || 'Error'); return; }
        const row = document.getElementById('row-' + pendingEditId);
        row?.querySelectorAll('.editable-cell[data-field="reason"]').forEach(cell => {
            cell.title = value || '';
            if (!cell.closest('.dropdown-menu')) cell.textContent = value || '—';
        });
        if (row) {
            row.dataset.reason = (value || '').toLowerCase();
            row.dataset.hasReason = value ? '1' : '0';
        }
        applyFilter();
        refreshStats();
        toastr.success('Guardado.');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-edit-cell')).hide();
    })
    .catch(() => toastr.error('Error de red'))
    .finally(() => {
        $btn.prop('disabled', false).html('Guardar');
        pendingEditId = null;
    });
});

document.getElementById('modal-edit-input')?.addEventListener('keydown', e => {
    if (e.key === 'Enter')  document.getElementById('btn-confirm-edit')?.click();
    if (e.key === 'Escape') bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-edit-cell')).hide();
});
</script>
@endpush
