{{-- Shortcode Picker Modal --}}
{{-- Usage: @include('shortcode::components.picker', ['targetSelector' => '#myTextarea']) --}}
@php $targetSelector = $targetSelector ?? '#content'; @endphp

<div class="modal fade" id="shortcodePickerModal" tabindex="-1" aria-labelledby="shortcodePickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header pb-2">
                <h5 class="modal-title fw-bold" id="shortcodePickerModalLabel">
                    <i class="fas fa-code me-2"></i>Insertar shortcode
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body p-0">
                <div class="row g-0" style="min-height: 420px;">

                    {{-- Panel izquierdo: lista de shortcodes --}}
                    <div class="col-5 border-end d-flex flex-column">
                        <div class="p-3 border-bottom">
                            <input type="search" class="form-control form-control-sm" id="shortcodeSearchInput"
                                   placeholder="Buscar shortcode..." autocomplete="off">
                        </div>
                        <div class="overflow-auto flex-grow-1" id="shortcodeList" style="max-height: 380px;">
                            <div class="text-center text-muted py-5">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                <p class="small mt-2 mb-0">Cargando shortcodes...</p>
                            </div>
                        </div>
                    </div>

                    {{-- Panel derecho: detalle del shortcode seleccionado --}}
                    <div class="col-7 d-flex flex-column">
                        <div id="shortcodeDetail" class="p-4 flex-grow-1">
                            <div class="text-center text-muted pt-5">
                                <i class="fas fa-arrow-left fa-2x opacity-25 mb-3"></i>
                                <p class="small">Selecciona un shortcode para ver sus detalles</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer pt-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCopyShortcode" disabled>
                    <i class="fas fa-copy me-1"></i>Copiar
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="btnInsertShortcode" disabled
                        data-target="{{ $targetSelector }}">
                    <i class="fas fa-plus me-1"></i>Insertar
                </button>
            </div>

        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    'use strict';

    let allShortcodes = [];
    let selectedShortcode = null;

    const $modal       = $('#shortcodePickerModal');
    const $list        = $('#shortcodeList');
    const $detail      = $('#shortcodeDetail');
    const $btnCopy     = $('#btnCopyShortcode');
    const $btnInsert   = $('#btnInsertShortcode');
    const $search      = $('#shortcodeSearchInput');

    // ── Load shortcodes on first open ──────────────────────────────────────
    // Preferimos la ruta del módulo Template (incluye los de BD); si no existe,
    // caemos al endpoint nativo del módulo Shortcode.
    @php
        $shortcodeEndpoint = \Illuminate\Support\Facades\Route::has('api.runtime-shortcodes')
            ? route('api.runtime-shortcodes')
            : route('api.shortcode.registered');
    @endphp
    const SHORTCODE_ENDPOINT = @json($shortcodeEndpoint);

    $modal.one('show.bs.modal', function () {
        $.ajax({
            url: SHORTCODE_ENDPOINT,
            method: 'GET',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        })
            .done(function (data) {
                allShortcodes = data;
                renderList(data);
            })
            .fail(function () {
                $list.html('<p class="text-danger small p-3">Error al cargar shortcodes.</p>');
            });
    });

    // ── Search filter ──────────────────────────────────────────────────────
    $search.on('input', function () {
        const q = $(this).val().toLowerCase();
        const filtered = allShortcodes.filter(s =>
            s.name.toLowerCase().includes(q) ||
            (s.description || '').toLowerCase().includes(q)
        );
        renderList(filtered);
    });

    // ── Render list ────────────────────────────────────────────────────────
    function renderList(shortcodes) {
        if (!shortcodes.length) {
            $list.html('<p class="text-muted p-3">No se encontraron shortcodes.</p>');
            return;
        }

        const items = shortcodes.map(function (s) {
            return `<button type="button"
                        class="shortcode-list-item d-flex flex-column align-items-start w-100 text-start px-3 py-2 border-0 border-bottom bg-transparent"
                        data-name="${escHtml(s.name)}">
                        <span class="fw-semibold small text-primary">[${escHtml(s.name)}]</span>
                        <span class="text-muted" style="font-size:0.78rem;">${escHtml(s.description || '')}</span>
                    </button>`;
        }).join('');

        $list.html(items);
    }

    // ── Select a shortcode ─────────────────────────────────────────────────
    $list.on('click', '.shortcode-list-item', function () {
        $list.find('.shortcode-list-item').removeClass('bg-light');
        $(this).addClass('bg-light');

        const name = $(this).data('name');
        selectedShortcode = allShortcodes.find(s => s.name === name) || null;

        if (!selectedShortcode) { return; }

        renderDetail(selectedShortcode);
        $btnCopy.prop('disabled', false);
        $btnInsert.prop('disabled', false);
    });

    // ── Render detail panel ────────────────────────────────────────────────
    function renderDetail(s) {
        const attrs = s.attributes && typeof s.attributes === 'object'
            ? Object.entries(s.attributes)
            : [];

        const attrRows = attrs.map(([key, desc]) =>
            `<tr><td class="pe-3"><code>${escHtml(key)}</code></td><td class="text-muted">${escHtml(desc)}</td></tr>`
        ).join('');

        const attrTable = attrRows
            ? `<table class="table table-sm table-borderless mb-0"><tbody>${attrRows}</tbody></table>`
            : '<p class="text-muted mb-0">Sin atributos.</p>';

        $detail.html(`
            <h6 class="fw-bold mb-1"><code>[${escHtml(s.name)}]</code></h6>
            <p class="text-muted mb-3">${escHtml(s.description || '')}</p>

            <div class="mb-3">
                <label class="form-label fw-semibold small mb-1">Ejemplo</label>
                <pre class="bg-light border rounded p-2 mb-0" style="font-size:0.78rem;white-space:pre-wrap;word-break:break-all;">${escHtml(s.example || '')}</pre>
            </div>

            <div>
                <label class="form-label fw-semibold small mb-1">Atributos</label>
                ${attrTable}
            </div>
        `);
    }

    // ── Copy to clipboard ──────────────────────────────────────────────────
    $btnCopy.on('click', function () {
        if (!selectedShortcode) { return; }

        navigator.clipboard.writeText(selectedShortcode.example || `[${selectedShortcode.name}]`)
            .then(function () {
                const $btn = $btnCopy;
                const orig = $btn.html();
                $btn.html('<i class="fas fa-check me-1"></i>Copiado');
                setTimeout(() => $btn.html(orig), 1500);
            });
    });

    // ── Insert at cursor ───────────────────────────────────────────────────
    $btnInsert.on('click', function () {
        if (!selectedShortcode) { return; }

        const targetSel = $(this).data('target') || '#content';
        const text = selectedShortcode.example || `[${selectedShortcode.name}]`;
        const $target = $(targetSel);

        if (!$target.length) {
            if (typeof toastr !== 'undefined') {
                toastr.warning('No se encontró el editor de destino.');
            }
            return;
        }

        const el = $target[0];
        if (el.tagName === 'TEXTAREA' || el.tagName === 'INPUT') {
            const start = el.selectionStart ?? el.value.length;
            const end   = el.selectionEnd ?? el.value.length;
            el.value = el.value.slice(0, start) + text + el.value.slice(end);
            el.selectionStart = el.selectionEnd = start + text.length;
            el.focus();
        } else {
            // Fallback: append to value or trigger change
            $target.val(($target.val() || '') + text).trigger('change');
        }

        bootstrap.Modal.getInstance($modal[0])?.hide();

        if (typeof toastr !== 'undefined') {
            toastr.success('Shortcode insertado.');
        }
    });

    // ── Reset on close ─────────────────────────────────────────────────────
    $modal.on('hidden.bs.modal', function () {
        selectedShortcode = null;
        $btnCopy.prop('disabled', true);
        $btnInsert.prop('disabled', true);
        $search.val('');
        $detail.html(`
            <div class="text-center text-muted pt-5">
                <i class="fas fa-arrow-left fa-2x opacity-25 mb-3"></i>
                <p class="small">Selecciona un shortcode para ver sus detalles</p>
            </div>`);
        $list.find('.shortcode-list-item').removeClass('bg-light');
    });

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
}());
</script>
@endpush
@endonce
