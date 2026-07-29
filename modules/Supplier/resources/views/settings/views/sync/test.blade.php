@extends('layouts.theme')

@section('title', 'Panel de pruebas — Sincronización')

@section('content')

@include('core::components.card', ['title' => 'Panel de pruebas — Sincronización'])

<div class="widget-content searchable-container list">

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Panel de pruebas</h5>
                    <p class="small mb-0 text-muted">Ejecuta sincronizaciones con parámetros personalizados para validar el comportamiento del sistema</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if($excludedGroupsCount > 0)
                        <a href="{{ route('settings.suppliers.sync.excluded-groups.index') }}"
                           class="badge text-decoration-none"
                           style="background:rgba(220,53,69,.10);color:#842029;border:1px solid rgba(220,53,69,.25);font-size:.72rem;padding:.35em .6em;">
                            <i class="fas fa-ban me-1"></i>{{ $excludedGroupsCount }} grupo(s) excluido(s)
                        </a>
                    @endif
                    <a href="{{ route('settings.suppliers.sync.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">

            <div class="row g-4">

                {{-- Configuración --}}
                <div class="col-12 col-xl-12">

                    {{-- Tipo --}}
                    <div class="mb-3">
                        <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:.68rem;letter-spacing:.08em;">Tipo de sincronización</p>
                        <select id="input-sync-type" class="form-select select2">
                            <option value="model">Modelos — ERP → productos</option>
                            <option value="provider">Proveedores — PROVEEDOR ERP</option>
                            <option value="category">Categorías — Jerarquía ERP</option>
                        </select>
                    </div>

                    {{-- Modo --}}
                    <div class="mb-3" id="field-mode">
                        <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:.68rem;letter-spacing:.08em;">Modo de consulta ERP</p>
                        <select id="input-mode" class="form-select select2">
                            <option value="filter">Filter — recomendado</option>
                            <option value="legacy">Legacy (/api/erp/modelos)</option>
                        </select>
                    </div>

                    {{-- ID modelo ERP específico --}}
                    <div class="mb-3" id="field-erp-model-id">
                        <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:.68rem;letter-spacing:.08em;">ID modelo ERP específico <span class="fw-normal">(opcional)</span></p>
                        <input type="number" id="input-erp-model-id" class="form-control" min="1"
                               placeholder="Ej: 12345 — deja vacío para rango">
                        <small class="text-muted">Si se indica, sincroniza solo ese modelo ignorando el límite.</small>
                    </div>

                    {{-- Límite --}}
                    <div class="mb-3" id="field-limit">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="text-uppercase fw-bold text-muted mb-0" style="font-size:.68rem;letter-spacing:.08em;">Límite <span class="fw-normal">(opcional)</span></p>
                            <span id="erp-total-badge" class="badge bg-light text-muted border small d-none">
                                <span id="erp-total-value">—</span> en ERP
                            </span>
                        </div>
                        <div class="input-group">
                            <input type="number" id="input-limit" class="form-control" min="0"
                                   placeholder="20" value="20">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-use-total" title="Usar total ERP" disabled>
                                Usar total
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-use-zero" title="Todos los productos">
                                Todos
                            </button>
                        </div>
                        <div id="erp-count-loading" class="small text-muted mt-1 d-none">
                            <i class="fas fa-spinner fa-spin me-1"></i>Consultando ERP…
                        </div>
                        <div id="limit-zero-hint" class="small text-success mt-1 d-none">
                            <i class="fas fa-infinity me-1"></i>Sin límite — se procesarán <strong>todos</strong> los productos del filtro
                        </div>
                    </div>

                    {{-- ID proveedor ERP --}}
                    <div class="mb-3" id="field-erp-provider">
                        <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:.68rem;letter-spacing:.08em;">ID proveedor ERP <span class="fw-normal">(opcional)</span></p>
                        <input type="number" id="input-erp-provider" class="form-control" min="1"
                               placeholder="Todos los proveedores">
                        <small class="text-muted">Filtra por idproveedor en el ERP.</small>
                    </div>

                    {{-- Forzar --}}
                    <div class="mb-3" id="field-force">
                        <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:.68rem;letter-spacing:.08em;">Forzar re-sincronización</p>
                        <select id="input-force" class="form-select select2">
                            <option value="0">No — solo procesar cambios</option>
                            <option value="1">Sí — sobreescribir aunque no hayan cambiado</option>
                        </select>
                    </div>

                    {{-- Fecha desde --}}
                    <div class="mb-3">
                        <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:.68rem;letter-spacing:.08em;">Fecha ERP desde <span class="fw-normal">(opcional)</span></p>
                        <div class="d-flex gap-2 mb-1">
                            <input type="text" id="input-date-from" class="form-control"
                                   placeholder="Sin filtro de fecha"
                                   autocomplete="off"
                                   data-default="{{ $settings['filter_date_from'] ?? config('supplier.erp_sync.filter_date_from', '') }}">
                            <select id="input-date-field" class="form-select" style="max-width:200px">
                                <option value="creation" selected>Por creación</option>
                                <option value="modification">Por modificación</option>
                            </select>
                        </div>
                        <small class="text-muted" id="date-field-hint">Filtra por <strong>FCREACION</strong> en Oracle (productos nuevos).</small>
                    </div>

                    {{-- Flags especiales --}}
                    <div class="mb-3" id="field-flags">
                        <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:.68rem;letter-spacing:.08em;">Opciones de ejecución</p>
                        <div class="d-flex flex-column gap-2">

                            <label class="d-flex align-items-center gap-3 p-3 rounded border bg-light cursor-pointer"
                                   style="cursor:pointer" for="input-dry-run">
                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded bg-secondary bg-opacity-10" style="width:34px;height:34px">
                                    <i class="fas fa-eye text-dark"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold small">Dry-run (solo lectura)</div>
                                    <p class="text-muted mb-0" style="font-size:.75rem;">Consulta el ERP y valida datos sin guardar nada en la base de datos</p>
                                </div>
                                <div class="form-check form-switch mb-0 ms-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="input-dry-run"
                                           {{ ($settings['default_dry_run'] ?? '0') === '1' ? 'checked' : '' }}>
                                </div>
                            </label>

                            <label class="d-flex align-items-center gap-3 p-3 rounded border bg-light cursor-pointer"
                                   style="cursor:pointer" for="input-register-only" id="field-register-only">
                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded bg-secondary bg-opacity-10" style="width:34px;height:34px">
                                    <i class="fas fa-database text-dark"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold small">Solo registrar content (sin generar)</div>
                                    <p class="text-muted mb-0" style="font-size:.75rem;">Crea el registro AiContent en <em>pendiente</em> aunque la subfamilia tenga prompt — no dispara la generación IA</p>
                                </div>
                                <div class="form-check form-switch mb-0 ms-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="input-register-only"
                                           {{ ($settings['default_register_only'] ?? '0') === '1' ? 'checked' : '' }}>
                                </div>
                            </label>

                            <label class="d-flex align-items-center gap-3 p-3 rounded border bg-light cursor-pointer"
                                   style="cursor:pointer" for="input-web-filter" id="field-web-filter">
                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded bg-secondary bg-opacity-10" style="width:34px;height:34px">
                                    <i class="fas fa-globe text-dark"></i>
                                </div>
                                <div class="flex-grow-1 ">
                                    <div class="fw-semibold small">Estado web en ERP</div>
                                    <p class="text-muted mb-0" style="font-size:.75rem;">Solo se sincronizan productos <strong>pendientes de publicar</strong> en web (web=2).</p>
                                    {{-- Fijo en pendientes (web=2): es el único estado sincronizable. --}}
                                    <input type="hidden" id="input-web-filter" value="2">
                                </div>
                            </label>

                        </div>
                    </div>

                    {{-- Presets --}}
                    <div class="mb-3" id="preset-section">
                        <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:.68rem;letter-spacing:.08em;">Presets guardados</p>
                        <div class="d-flex gap-2 mb-2">
                            <input type="text" id="input-preset-name" class="form-control"
                                   placeholder="Nombre del preset…" maxlength="40">
                            <button type="button" class="btn btn-sm btn-primary flex-shrink-0" id="btn-save-preset" title="Guardar configuración actual como preset">
                                <i class="fas fa-bookmark me-1"></i>
                            </button>
                        </div>
                        <div id="preset-list" class="d-flex flex-wrap gap-1"></div>
                    </div>


                    {{-- Resumen + Botón --}}
                    <div class="small text-muted mb-2" id="summary-text">
                        Sincronizar <strong id="s-limit">20</strong> <strong id="s-type">modelos</strong><span id="s-mode-fragment"> en modo <strong id="s-mode">filter</strong></span><span id="s-date-fragment" class="d-none"> desde <strong id="s-date">—</strong></span><span id="s-web-fragment" class="d-none"> · <strong class="text-primary" id="s-web-label">—</strong></span><span id="s-dry-fragment" class="d-none"> · <strong class="text-info">DRY-RUN</strong></span><span id="s-register-fragment" class="d-none"> · <strong style="color:#6f42c1;">solo registrar</strong></span>.
                    </div>
                    <button type="button" class="btn btn-primary w-100" id="btn-run">
                        Ejecutar prueba
                    </button>

                </div>

                {{-- Elementos ocultos para mantener las referencias JS --}}
                <div class="d-none">
                    <span id="live-status"></span>
                    <div id="live-bar"></div>
                    <span id="live-batch-name"></span>
                    <span id="live-processed"></span>
                    <span id="live-failed"></span>
                    <span id="live-batch-id"></span>
                    <div id="cancel-wrap"></div>
                    <button id="btn-cancel-sync"></button>
                    <pre id="live-log-panel"></pre>
                    <span id="log-count"></span>
                    <button id="btn-clear-log"></button>
                </div>

            </div>

            <hr class="my-4">

            {{-- Historial reciente --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-semibold">
                    Historial reciente
                    @if($recentBatches->isNotEmpty())
                        <span class="badge bg-light text-muted border ms-1">{{ $recentBatches->count() }}</span>
                    @endif
                </span>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-refresh" title="Actualizar">
                    <i class="fas fa-rotate-right"></i>
                </button>
            </div>

            <div id="results-container">
                @if($recentBatches->isEmpty())
                    <div class="text-center py-5 text-muted" id="empty-state">
                        <i class="fas fa-flask fa-2x mb-3 d-block opacity-25"></i>
                        <p class="small mb-0">Sin pruebas ejecutadas aún.</p>
                    </div>
                @else
                    @include('supplier::settings.views.sync.partials.test-batch-list', ['batches' => $recentBatches])
                @endif
            </div>

        </div>{{-- /card-body --}}

    </div>

    {{-- Modal de ejecución — no se puede cerrar mientras corre --}}
    <div class="modal fade" id="execution-modal" tabindex="-1"
         data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow">

                {{-- Header --}}
                <div class="modal-header border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                              style="width:32px;height:32px;background:var(--pb-primary-soft);border:1px solid var(--pb-primary-border);">
                            <i class="fas fa-rotate small" style="color:var(--pb-primary-dark);" id="modal-spin-icon"></i>
                        </span>
                        <div>
                            <h6 class="modal-title fw-bold mb-0">Ejecutando sincronización</h6>
                            <small class="text-muted" id="modal-batch-name-label">Iniciando…</small>
                        </div>
                    </div>
                </div>

                {{-- Body --}}
                <div class="modal-body p-0">

                    {{-- Estado + progreso --}}
                    <div class="px-4 py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold text-muted">Progreso</span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="small text-muted">
                                    <span class="modal-processed fw-bold text-dark">0</span> ok ·
                                    <span class="modal-failed fw-bold text-danger">0</span> err ·
                                    #<span class="modal-batch-id text-muted">—</span>
                                </span>
                                <span class="badge modal-status">En cola</span>
                            </div>
                        </div>
                        <div class="progress rounded-pill" style="height:6px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated modal-progress-bar"
                                 style="width:5%;background:var(--pb-primary);" role="progressbar"></div>
                        </div>
                    </div>

                    {{-- Log en tiempo real --}}
                    <div style="background:#0d1117;">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2"
                             style="border-bottom:1px solid #30363d;">
                            <span class="small fw-semibold" style="color:#e6edf3;font-family:monospace;letter-spacing:.02em;">
                                <i class="fas fa-terminal me-2" style="color:var(--pb-primary);font-size:.75rem;"></i>Log en tiempo real
                            </span>
                            <span class="badge" style="background:#21262d;color:#8b949e;font-size:.65rem;font-weight:400;" id="modal-log-count">0 líneas</span>
                        </div>
                        <div id="modal-log-panel"
                             style="height:220px;overflow-y:auto;margin:0;padding:.75rem 1rem;
                                    font-family:'SFMono-Regular',Consolas,monospace;">
                            <div class="mlog-line" style="color:#484f58;font-size:.72rem;">// Iniciando…</div>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="modal-footer border-top px-4 py-3 d-flex flex-column gap-2">
                    <button type="button" class="btn btn-primary w-100" id="btn-close-modal" disabled>
                        Ver historial
                    </button>
                    <button type="button" class="btn w-100 d-none" id="btn-modal-cancel"
                            style="background:#0d1117;color:#f0f0f0;border:1px solid #30363d;">
                        Cancelar sincronización
                    </button>
                </div>

            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="{{ themeAsset('libs/moment/moment.js') }}"></script>
<script src="{{ themeAsset('libs/daterangepicker/daterangepicker.js') }}"></script>
<script>
const routes = @json($routes);
const csrf   = document.querySelector('meta[name="csrf-token"]').content;
const typeLabels = { model: 'modelos', provider: 'proveedores', category: 'categorías' };

// ── Datepicker ─────────────────────────────────────────────────────────────
const $inputDateFrom = $('#input-date-from');
const defaultDateFrom = $inputDateFrom.data('default');

$inputDateFrom.daterangepicker({
    singleDatePicker: true, showDropdowns: true, autoUpdateInput: false,
    locale: { format: 'YYYY-MM-DD', cancelLabel: 'Borrar', applyLabel: 'OK',
              daysOfWeek: ['Do','Lu','Ma','Mi','Ju','Vi','Sa'],
              monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
              firstDay: 1 },
    startDate: defaultDateFrom,
});
if (defaultDateFrom) $inputDateFrom.val(defaultDateFrom);
$inputDateFrom.on('apply.daterangepicker', function(ev, picker) { $(this).val(picker.startDate.format('YYYY-MM-DD')); updateSummary(); });
$inputDateFrom.on('cancel.daterangepicker', function() { $(this).val(''); updateSummary(); });

// ── Total ERP ──────────────────────────────────────────────────────────────
let erpTotal = null;

function loadErpTotal(showSpinner = true) {
    if (syncType() !== 'model') return;
    if (showSpinner) {
        document.getElementById('erp-count-loading').classList.remove('d-none');
        document.getElementById('erp-total-badge').classList.add('d-none');
    }
    document.getElementById('btn-use-total').disabled = true;
    $.ajax({
        url: routes.erp_model_count,
        method: 'GET',
        dataType: 'json',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
    })
        .done(function (data) {
            document.getElementById('erp-count-loading').classList.add('d-none');
            if (data.success && data.total !== null) {
                erpTotal = data.total;
                document.getElementById('erp-total-value').textContent = data.total.toLocaleString();
                document.getElementById('erp-total-badge').classList.remove('d-none');
                document.getElementById('btn-use-total').disabled = false;
                document.getElementById('input-limit').max = data.total;
            }
        })
        .fail(function () {
            document.getElementById('erp-count-loading').classList.add('d-none');
        });
}

document.getElementById('btn-use-total').addEventListener('click', function () {
    if (erpTotal !== null) { document.getElementById('input-limit').value = erpTotal; updateSummary(); }
});

document.getElementById('btn-use-zero').addEventListener('click', function () {
    document.getElementById('input-limit').value = '0';
    updateSummary();
});

// ── Visibilidad de campos según tipo ──────────────────────────────────────
function syncType() { return $('#input-sync-type').val(); }

function updateExtraFields() {
    const type    = syncType();
    const isModel = type === 'model';
    document.getElementById('field-erp-provider').style.display  = isModel ? '' : 'none';
    document.getElementById('field-force').style.display         = isModel ? '' : 'none';
    document.getElementById('field-mode').style.display          = isModel ? '' : 'none';
    document.getElementById('field-erp-model-id').style.display  = isModel ? '' : 'none';
    document.getElementById('field-register-only').style.display    = isModel ? '' : 'none';
    updateSummary();
}

$('#input-sync-type').on('change', function () {
    updateExtraFields();
    if (syncType() === 'model') loadErpTotal(true);
});
document.getElementById('input-limit').addEventListener('input', updateSummary);
document.getElementById('input-mode').addEventListener('change', updateSummary);
document.getElementById('input-erp-model-id').addEventListener('input', updateSummary);
document.getElementById('input-dry-run').addEventListener('change', updateSummary);
document.getElementById('input-register-only').addEventListener('change', updateSummary);
document.getElementById('input-web-filter').addEventListener('change', updateSummary);
document.getElementById('input-date-field').addEventListener('change', function() {
    const isModif = this.value === 'modification';
    document.getElementById('date-field-hint').innerHTML = isModif
        ? 'Filtra por <strong>FMODIFICACION</strong> en Oracle (productos modificados).'
        : 'Filtra por <strong>FCREACION</strong> en Oracle (productos nuevos).';
    updateSummary();
});

function updateSummary() {
    const type             = syncType();
    const limit            = document.getElementById('input-limit').value;
    const mode             = document.getElementById('input-mode').value;
    const dateFrom         = document.getElementById('input-date-from').value;
    const erpModelId       = document.getElementById('input-erp-model-id').value;
    const dryRun           = document.getElementById('input-dry-run').checked;
    const registerOnly     = document.getElementById('input-register-only').checked;
    const webFilter        = document.getElementById('input-web-filter').value;

    const webFilterLabels  = { '1': 'publicados', '2': 'pendientes' };

    const isAllProducts = !erpModelId && (limit === '' || parseInt(limit) === 0);
    document.getElementById('s-type').textContent  = typeLabels[type] ?? type;
    document.getElementById('s-limit').textContent = erpModelId ? ('modelo #' + erpModelId) : (isAllProducts ? 'todos los' : limit);
    document.getElementById('s-mode').textContent  = mode;
    document.getElementById('limit-zero-hint').classList.toggle('d-none', !!erpModelId || !isAllProducts);
    document.getElementById('s-mode-fragment').style.display   = type === 'model' ? '' : 'none';
    document.getElementById('s-date-fragment').classList.toggle('d-none', !dateFrom);
    document.getElementById('s-web-fragment').classList.toggle('d-none', !webFilter);
    document.getElementById('s-dry-fragment').classList.toggle('d-none', !dryRun);
    document.getElementById('s-register-fragment').classList.toggle('d-none', !registerOnly);
    if (dateFrom) document.getElementById('s-date').textContent = dateFrom;
    if (webFilter) document.getElementById('s-web-label').textContent = webFilterLabels[webFilter] ?? webFilter;

    // Dim el límite si hay modelo específico
    document.getElementById('field-limit').style.opacity = erpModelId ? '.4' : '1';
}

updateExtraFields();
loadErpTotal(false);

// ── Log en tiempo real ─────────────────────────────────────────────────────
const logPanel = document.getElementById('live-log-panel');
let logLineIds = new Set();

const resultColors = { success: '#3fb950', failed: '#f85149', skipped: '#8b949e', warning: '#e3b341' };
const actionColors = { create: '#79c0ff', update: '#ffa657', skip: '#8b949e', delete: '#f85149' };

function appendLog(lines) {
    if (!lines || lines.length === 0) return;
    const newLines = lines.filter(l => !logLineIds.has(l.id));
    if (newLines.length === 0) return;

    const firstTime = logPanel.textContent.trim().startsWith('//');
    if (firstTime) logPanel.textContent = '';

    newLines.forEach(l => {
        logLineIds.add(l.id);
        const resColor   = resultColors[l.result] ?? '#e6edf3';
        const actColor   = actionColors[l.action] ?? '#e6edf3';
        const erpStr     = l.erp_id ? `<span style="color:#8b949e;">[ERP#${l.erp_id}]</span> ` : '';
        const actionStr  = `<span style="color:${actColor};">${(l.action ?? '?').padEnd(7)}</span>`;
        const resultStr  = `<span style="color:${resColor};">${l.result ?? '?'}</span>`;
        const msgStr     = l.msg ? ` <span style="color:#8b949e;">${escHtml(String(l.msg).slice(0, 120))}</span>` : '';
        const timeStr    = `<span style="color:#484f58;">${l.at ?? ''}</span>`;

        const line = document.createElement('span');
        line.innerHTML = `${timeStr} ${erpStr}${actionStr} → ${resultStr}${msgStr}\n`;
        logPanel.appendChild(line);
    });

    logPanel.scrollTop = logPanel.scrollHeight;
    document.getElementById('log-count').textContent = logLineIds.size + ' líneas';
}

function addSysLog(msg, color = '#8b949e') {
    if (logPanel.textContent.trim().startsWith('//')) logPanel.textContent = '';
    const span = document.createElement('span');
    span.style.color = color;
    span.textContent = '// ' + msg + '\n';
    logPanel.appendChild(span);
    logPanel.scrollTop = logPanel.scrollHeight;
}

function escHtml(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

document.getElementById('btn-clear-log').addEventListener('click', function () {
    logPanel.innerHTML = '<span style="color:#8b949e;">// Log limpiado\n</span>';
    logLineIds.clear();
    document.getElementById('log-count').textContent = '0 líneas';
});

// ── Ejecutar prueba ────────────────────────────────────────────────────────
document.getElementById('btn-run').addEventListener('click', function () {
    const type        = syncType();
    const limit       = document.getElementById('input-limit').value;
    const erpProvider = document.getElementById('input-erp-provider').value;
    const erpModelId  = document.getElementById('input-erp-model-id').value;
    const force       = $('#input-force').val() === '1';
    const mode        = document.getElementById('input-mode').value;
    const dateFrom    = document.getElementById('input-date-from').value || null;
    const dateField       = document.getElementById('input-date-field')?.value || 'creation';
    const registerOnly     = document.getElementById('input-register-only').checked;
    const dryRun           = document.getElementById('input-dry-run').checked;
    // Siempre trae todos los modelos (con y sin descripción ERP); el registro no depende de la descripción.
    const descriptionEmpty = false;
    const webFilter        = document.getElementById('input-web-filter')?.value || null;

    if (!type) { toastr.warning('Selecciona un tipo de sincronización'); return; }

    // Reset log panel
    logPanel.innerHTML = '';
    logLineIds.clear();
    document.getElementById('log-count').textContent = '0 líneas';
    addSysLog('Iniciando ejecución…', '#79c0ff');

    const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Ejecutando…');

    $.ajax({
        url: routes.test_run,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify({
            sync_type:          type,
            limit:              (!erpModelId && limit !== '' && parseInt(limit) > 0) ? parseInt(limit) : null,
            erp_provider_id:    erpProvider ? parseInt(erpProvider) : null,
            erp_model_id:       erpModelId ? parseInt(erpModelId) : null,
            force:              force ? 1 : 0,
            mode:               mode,
            date_from:          dateFrom,
            date_field:         dateField,
            register_only:      registerOnly ? 1 : 0,
            dry_run:            dryRun ? 1 : 0,
            description_empty:  descriptionEmpty ? 1 : 0,
            web_filter:         webFilter || null,
        }),
    })
    .done(function (data) {
        if (!data.success) {
            toastr.error(data.message || 'Error al ejecutar');
            addSysLog('Error: ' + (data.message || 'desconocido'), '#f85149');
            $btn.prop('disabled', false).html('Ejecutar prueba');
            const modal = bootstrap.Modal.getInstance(document.getElementById('execution-modal'));
            if (modal) modal.hide();
            return;
        }
        addSysLog('Batch #' + data.batch_id + ' encolado — esperando worker…', '#3fb950');
        startLiveProgress(data.batch_id, data.batch_name);
    })
    .fail(function (xhr) {
        $btn.prop('disabled', false).html('Ejecutar prueba');
        const msg = xhr.responseJSON?.message || xhr.statusText || 'Error de red';
        addSysLog('Error de red: ' + msg, '#f85149');
        toastr.error('Error: ' + msg);
        const modal = bootstrap.Modal.getInstance(document.getElementById('execution-modal'));
        if (modal) modal.hide();
    });
});

// ── Log del modal: añadir línea (jQuery, formato rico) ────────────────────
function appendModalLogLine(log) {
    const rc = {
        success: { icon: '✓', color: '#3fb950' },
        skipped: { icon: '↷', color: '#8b949e' },
        failed:  { icon: '✗', color: '#f85149' },
    }[log.result] ?? { icon: '·', color: '#8b949e' };

    const action = (log.action ?? log.result ?? '').toUpperCase();
    const erpId  = log.erp_id ? '#' + log.erp_id : '';

    const $row = $('<div class="mlog-line"></div>').css({ display:'flex', gap:'6px', alignItems:'baseline', padding:'2px 0', lineHeight:1.5 });
    $row.append($('<span>').css({ color:'#484f58', fontSize:'.65rem', minWidth:'50px', flexShrink:0 }).text(log.at ?? ''));
    $row.append($('<span>').css({ color:rc.color, fontWeight:700, fontSize:'.7rem', minWidth:'12px', flexShrink:0 }).text(rc.icon));
    $row.append($('<span>').css({ color:rc.color, fontWeight:600, fontSize:'.7rem', minWidth:'58px', flexShrink:0 }).text(action));
    $row.append($('<span>').css({ color:'#79c0ff', fontFamily:'monospace', fontSize:'.7rem', minWidth:'88px', flexShrink:0 }).text(erpId));
    $row.append($('<span>').css({ color: log.result === 'failed' ? '#f85149' : '#6e7681', fontSize:'.68rem', overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap', flex:1 }).text(log.msg ?? ''));

    const $panel = $('#modal-log-panel');
    $panel.append($row).scrollTop($panel[0].scrollHeight);
    $('#modal-log-count').text($panel.find('.mlog-line').length + ' líneas');
}

// ── Progreso en tiempo real (jQuery) ─────────────────────────────────────
let pollTimer = null;

const statusMap = {
    pending:   { txt: 'En cola',     cls: 'badge bg-secondary-subtle text-secondary' },
    running:   { txt: 'En progreso', cls: 'badge bg-warning-subtle text-warning'     },
    completed: { txt: 'Completado',  cls: 'badge bg-secondary-subtle text-secondary' },
    failed:    { txt: 'Fallido',     cls: 'badge bg-danger-subtle text-danger'        },
    cancelled: { txt: 'Cancelado',   cls: 'badge bg-secondary-subtle text-secondary' },
};

function setModalStatus(status) {
    const sc = statusMap[status] ?? { txt: status, cls: 'badge bg-secondary-subtle text-secondary' };
    $('.modal-status').text(sc.txt).attr('class', sc.cls);
    $('#live-status').text(sc.txt).attr('class', sc.cls);
}

function startLiveProgress(batchId, batchName) {
    currentBatchId = batchId;
    setCancelVisible(true);

    // Reset modal
    $('#modal-batch-name-label').text(batchName || 'Iniciando…');
    $('.modal-batch-id').text(batchId);
    setModalStatus('pending');
    $('.modal-processed, .modal-failed').text('0');
    $('.modal-progress-bar')
        .css({ width:'5%', background:'var(--pb-primary)' })
        .attr('class', 'progress-bar progress-bar-striped progress-bar-animated modal-progress-bar');
    $('#modal-log-panel').html('<div class="mlog-line" style="color:#484f58;font-size:.7rem;padding:2px 0;">// Batch #' + batchId + ' encolado — esperando worker…</div>');
    $('#modal-log-count').text('0 líneas');
    $('#btn-close-modal').prop('disabled', true);
    $('#btn-modal-cancel').removeClass('d-none');
    $('#modal-spin-icon').attr('class', 'fas fa-rotate fa-spin small').css('color', 'var(--pb-primary-dark)');

    new bootstrap.Modal(document.getElementById('execution-modal'), { backdrop:'static', keyboard:false }).show();

    $('#empty-state').remove();
    $('#live-bar').css('width', '5%').attr('class', 'progress-bar progress-bar-striped progress-bar-animated');
    $('#live-batch-name').text(batchName);
    $('#live-processed, #live-failed').text('0');
    $('#live-batch-id').text(batchId);

    if (pollTimer) clearInterval(pollTimer);

    pollTimer = setInterval(() => {
        $.getJSON(routes.progress.replace(':batchId', batchId))
        .done(data => {
            if (!data.success) return;
            const b = data.batch;

            // Contadores
            $('#live-processed').text(b.processed_items);
            $('#live-failed').text(b.failed_items);
            $('.modal-processed').text(b.processed_items);
            $('.modal-failed').text(b.failed_items);

            // Estado
            if (statusMap[b.status]) setModalStatus(b.status);

            // Barra
            const isTerminal = ['completed', 'failed', 'cancelled'].includes(b.status);
            const pct = isTerminal ? 100
                      : (b.status === 'running' && b.total_items > 0) ? b.progress_percentage
                      : (b.status === 'running' ? 50 : 5);
            $('#live-bar').css('width', pct + '%');

            // Logs → modal
            if (data.recent_logs && data.recent_logs.length) {
                appendLog(data.recent_logs);
                $.each(data.recent_logs, (i, log) => appendModalLogLine(log));
            }

            if (isTerminal) {
                clearInterval(pollTimer);
                currentBatchId = null;
                setCancelVisible(false);

                const isOk = b.status === 'completed';

                // Barra final
                $('#live-bar').css('background', isOk ? 'var(--pb-primary)' : '').attr('class', 'progress-bar ' + (isOk ? '' : 'bg-danger'));
                $('.modal-progress-bar')
                    .removeClass('progress-bar-striped progress-bar-animated')
                    .css({ width:'100%', background: isOk ? 'var(--pb-primary)' : '#dc3545' });

                // Botones e icono
                $('#btn-modal-cancel').addClass('d-none');
                $('#btn-close-modal').prop('disabled', false);
                $('#modal-spin-icon')
                    .attr('class', isOk ? 'fas fa-check small' : 'fas fa-xmark small')
                    .css('color', isOk ? 'var(--pb-primary-dark)' : '#dc3545');

                // Línea final en log
                const label   = statusMap[b.status]?.txt ?? b.status;
                const costStr = (b.ai_cost != null && b.ai_cost > 0)
                    ? `, coste IA $${parseFloat(b.ai_cost).toFixed(4)}`
                    : '';
                const finMsg  = `// Finalizado: ${label} — ${b.processed_items} ok, ${b.failed_items} err, ${b.duration_seconds ?? '?'}s${costStr}`;
                addSysLog(finMsg, isOk ? '#3fb950' : '#f85149');
                const $fl = $('<div class="mlog-line"></div>').css({ color: isOk ? '#3fb950' : '#f85149', fontWeight:700, padding:'2px 0', fontSize:'.7rem' }).text(finMsg);
                const $p  = $('#modal-log-panel');
                $p.append($fl).scrollTop($p[0].scrollHeight);

                const costToast = (b.ai_cost != null && b.ai_cost > 0) ? ` Coste IA: $${parseFloat(b.ai_cost).toFixed(4)}.` : '';
                if (isOk) toastr.success('Sincronización completada. ' + b.processed_items + ' procesados.' + costToast);
                else      toastr.error('Sincronización fallida. Revisa el log.' + costToast);

                $('#btn-run').prop('disabled', false).html('Ejecutar prueba');
            }
        }).fail(() => {});
    }, 2000);
}

// ── Cancelar sync ──────────────────────────────────────────────────────────
let currentBatchId = null;

document.getElementById('btn-cancel-sync').addEventListener('click', function () {
    if (!currentBatchId) return;
    const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Cancelando…');

    $.ajax({
        url: routes.cancel.replace(':batchId', currentBatchId),
        method: 'POST',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    })
    .done(function (data) {
        if (data.success) {
            addSysLog('Sync cancelado por el usuario', '#e3b341');
            toastr.warning('Sync cancelado');
        } else {
            toastr.error(data.message || 'No se pudo cancelar');
        }
        $btn.prop('disabled', false).html('Cancelar sync');
    })
    .fail(function () {
        $btn.prop('disabled', false).html('Cancelar sync');
    });
});

// Mostrar/ocultar botón cancelar según estado
function setCancelVisible(visible) {
    document.getElementById('cancel-wrap').classList.toggle('d-none', !visible);
}

// ── Presets (localStorage) ─────────────────────────────────────────────────
const PRESET_KEY = 'sync_test_presets_v1';

function getPresets() {
    try { return JSON.parse(localStorage.getItem(PRESET_KEY) || '{}'); } catch { return {}; }
}

function savePresets(obj) {
    localStorage.setItem(PRESET_KEY, JSON.stringify(obj));
}

function readCurrentConfig() {
    return {
        sync_type:         syncType(),
        limit:             document.getElementById('input-limit').value,
        erp_provider:      document.getElementById('input-erp-provider').value,
        erp_model_id:      document.getElementById('input-erp-model-id').value,
        force:             $('#input-force').val(),
        mode:              document.getElementById('input-mode').value,
        date_from:         document.getElementById('input-date-from').value,
        date_field:        document.getElementById('input-date-field').value,
        dry_run:           document.getElementById('input-dry-run').checked,
        register_only:     document.getElementById('input-register-only').checked,
        web_filter:        document.getElementById('input-web-filter').value,
    };
}

function applyConfig(cfg) {
    $('#input-sync-type').val(cfg.sync_type).trigger('change');
    document.getElementById('input-limit').value              = cfg.limit             ?? '';
    document.getElementById('input-erp-provider').value       = cfg.erp_provider      ?? '';
    document.getElementById('input-erp-model-id').value       = cfg.erp_model_id      ?? '';
    $('#input-force').val(cfg.force ?? '0').trigger('change');
    document.getElementById('input-mode').value                = cfg.mode             ?? 'filter';
    document.getElementById('input-date-from').value           = cfg.date_from        ?? '';
    document.getElementById('input-date-field').value          = cfg.date_field       ?? 'creation';
    document.getElementById('input-dry-run').checked           = !!cfg.dry_run;
    document.getElementById('input-register-only').checked     = !!cfg.register_only;
    document.getElementById('input-web-filter').value          = cfg.web_filter        ?? '';
    updateExtraFields();
}

function renderPresets() {
    const presets = getPresets();
    const $list = document.getElementById('preset-list');
    $list.innerHTML = '';

    if (!Object.keys(presets).length) {
        $list.innerHTML = '<span class="small text-muted fst-italic">Sin presets guardados</span>';
        return;
    }

    Object.keys(presets).forEach(name => {
        const wrap = document.createElement('div');
        wrap.className = 'd-inline-flex align-items-center gap-0 border rounded overflow-hidden';
        wrap.style.cssText = 'font-size:.76rem;';

        const loadBtn = document.createElement('button');
        loadBtn.type = 'button';
        loadBtn.className = 'btn btn-sm px-2 py-1 border-0 text-truncate';
        loadBtn.style.cssText = 'max-width:140px;background:transparent;';
        loadBtn.title = 'Cargar preset';
        loadBtn.textContent = name;
        loadBtn.addEventListener('click', () => { applyConfig(presets[name]); toastr.info('Preset "' + name + '" cargado'); });

        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className = 'btn btn-sm px-1 py-1 border-0 text-danger';
        delBtn.style.cssText = 'background:transparent;';
        delBtn.title = 'Eliminar preset';
        delBtn.innerHTML = '<i class="fas fa-xmark" style="font-size:.65rem;"></i>';
        delBtn.addEventListener('click', () => {
            const all = getPresets(); delete all[name]; savePresets(all); renderPresets();
        });

        wrap.append(loadBtn, delBtn);
        $list.appendChild(wrap);
    });
}

document.getElementById('btn-save-preset').addEventListener('click', function () {
    const name = document.getElementById('input-preset-name').value.trim();
    if (!name) { toastr.warning('Escribe un nombre para el preset'); return; }
    const all = getPresets();
    all[name] = readCurrentConfig();
    savePresets(all);
    document.getElementById('input-preset-name').value = '';
    renderPresets();
    toastr.success('Preset "' + name + '" guardado');
});

renderPresets();

// ── Historial ──────────────────────────────────────────────────────────────
document.getElementById('btn-refresh').addEventListener('click', () => location.reload());

// ── Modal de ejecución ─────────────────────────────────────────────────────
document.getElementById('btn-close-modal').addEventListener('click', function () {
    const modal = bootstrap.Modal.getInstance(document.getElementById('execution-modal'));
    if (modal) modal.hide();
    location.reload();
});

document.getElementById('btn-modal-cancel').addEventListener('click', function () {
    if (!currentBatchId) return;
    const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Cancelando…');

    $.ajax({
        url: routes.cancel.replace(':batchId', currentBatchId),
        method: 'POST',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    })
    .done(function (data) {
        if (data.success) {
            addSysLog('Sync cancelado por el usuario', '#e3b341');
            toastr.warning('Sync cancelado');
        } else {
            toastr.error(data.message || 'No se pudo cancelar');
        }
        $btn.prop('disabled', false).html('Cancelar');
    })
    .fail(function () {
        $btn.prop('disabled', false).html('Cancelar');
    });
});
</script>
@endpush
