{{-- Bulk action bar — shown when one or more conversations are selected --}}
<div class="bv-bulk-bar bv-hidden" id="bv-bulk-bar">
    <div class="bv-bulk-bar-info">
        <span id="bv-bulk-count">0</span> seleccionadas
        <button class="bv-bulk-deselect" id="bv-bulk-deselect" title="Cancelar selección" aria-label="Cancelar selección">
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>
    </div>
    <div class="bv-bulk-bar-actions">
        <button class="bv-bulk-btn" data-bv-bulk-action="archive" title="Archivar seleccionadas">
            <i class="fas fa-box-archive"></i> Archivar
        </button>
        <button class="bv-bulk-btn" data-bv-bulk-action="close" title="Cerrar seleccionadas">
            <i class="fas fa-check"></i> Cerrar
        </button>
        <button class="bv-bulk-btn" data-bv-bulk-action="mark_read" title="Marcar como leído">
            <i class="fas fa-envelope-open"></i> Marcar leído
        </button>
        <button class="bv-bulk-btn" data-bv-bulk-action="assign" title="Asignar seleccionadas" data-bv-modal="assign">
            <i class="fas fa-user-plus"></i> Asignar
        </button>
        @can('helpdesk.macros.use')
        <div class="dropdown d-inline-block" id="bv-bulk-macro-wrap">
            <button class="bv-bulk-btn dropdown-toggle" type="button" id="bv-bulk-macro-toggle"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                    title="Aplicar macro a las seleccionadas">
                <i class="fas fa-wand-magic-sparkles"></i> Aplicar macro
            </button>
            <div class="dropdown-menu dropdown-menu-end p-0" id="bv-bulk-macro-menu" aria-labelledby="bv-bulk-macro-toggle">
                <div class="dropdown-item-text text-muted small px-3 py-2" id="bv-bulk-macro-loading">
                    Cargando macros…
                </div>
            </div>
        </div>
        @endcan
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    var bulkUrl = '{{ route("manager.helpdesk.conversations.bulk") }}';
    var csrf    = $('meta[name="csrf-token"]').attr('content');

    function getSelectedIds() {
        var ids = [];
        $('[data-bv-bulk-select]:checked').each(function () {
            var id = $(this).closest('.bv-conv').data('bv-conv-id');
            if (id) { ids.push(id); }
        });
        return ids;
    }

    function updateBulkBar() {
        var count = $('[data-bv-bulk-select]:checked').length;
        $('#bv-bulk-count').text(count);
        if (count > 0) {
            $('#bv-bulk-bar').removeClass('bv-hidden');
        } else {
            $('#bv-bulk-bar').addClass('bv-hidden');
        }
    }

    function executeBulkAction(action, ids, payload) {
        $.ajax({
            url: bulkUrl,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: action, ids: ids, payload: payload || {} }),
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        }).done(function (resp) {
            ids.forEach(function (id) {
                $('.bv-conv[data-bv-conv-id="' + id + '"]').fadeOut(200, function () { $(this).remove(); });
            });
            $('#bv-bulk-bar').addClass('bv-hidden');
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se pudo ejecutar la acción';
            toastr.error(msg);
        });
    }

    // Mostrar/ocultar barra al cambiar selección
    $(document).on('change', '[data-bv-bulk-select]', function () {
        updateBulkBar();
    });

    // También actualizar cuando se agrega/quita clase selected por JS externo
    $(document).on('bv:selection-changed', function () {
        updateBulkBar();
    });

    // Deseleccionar todo
    $(document).on('click', '#bv-bulk-deselect', function () {
        $('.bv-conv.selected').removeClass('selected');
        $('[data-bv-bulk-select]').prop('checked', false);
        $('#bv-bulk-bar').addClass('bv-hidden');
    });

    // Ejecutar acción bulk
    $(document).on('click', '[data-bv-bulk-action]', function () {
        var action = $(this).data('bv-bulk-action');
        var ids    = getSelectedIds();
        if (!ids.length) { return; }

        if (action === 'assign') {
            var agentId = prompt('Ingresa el ID del agente al que deseas asignar las conversaciones:');
            if (!agentId || !agentId.trim()) { return; }
            executeBulkAction(action, ids, { agent_id: agentId.trim() });
        } else {
            executeBulkAction(action, ids, {});
        }
    });

    // ─── Aplicar macro en bloque ──────────────────────────────────────────
    var macroPickerUrl = '{{ route("manager.helpdesk.conversations.macros-picker") }}';
    var bulkMacroUrl   = '{{ route("manager.helpdesk.conversations.bulk-macro") }}';
    var macrosLoaded   = false;

    function renderMacroMenu(macros) {
        var $menu = $('#bv-bulk-macro-menu');
        $menu.empty();
        if (!macros || !macros.length) {
            $menu.append('<div class="dropdown-item-text text-muted small px-3 py-2">No hay macros disponibles.</div>');
            return;
        }
        $menu.append('<div class="dropdown-header">Más usados</div>');
        macros.forEach(function (m) {
            var badge = m.usados
                ? '<span class="badge bg-secondary ms-2">' + m.usageCount + '</span>'
                : '';
            var $item = $('<button type="button" class="dropdown-item d-flex align-items-center justify-content-between gap-2"></button>')
                .attr('data-bv-macro-id', m.id);
            $item.append($('<span></span>').text(m.name));
            $item.append($(badge || '<span></span>'));
            $menu.append($item);
        });
    }

    function loadMacros() {
        if (macrosLoaded) { return; }
        $.get(macroPickerUrl, { sort: 'used' })
            .done(function (resp) {
                macrosLoaded = true;
                renderMacroMenu(resp && resp.macros ? resp.macros : []);
            })
            .fail(function () {
                $('#bv-bulk-macro-menu').html('<div class="dropdown-item-text text-danger small px-3 py-2">No se pudieron cargar los macros.</div>');
            });
    }

    // Cargar macros la primera vez que se abre el dropdown
    $(document).on('click', '#bv-bulk-macro-toggle', loadMacros);

    // Aplicar el macro elegido a las conversaciones seleccionadas
    $(document).on('click', '#bv-bulk-macro-menu [data-bv-macro-id]', function () {
        var macroId = $(this).data('bv-macro-id');
        var ids     = getSelectedIds();
        if (!macroId || !ids.length) { return; }

        $.ajax({
            url: bulkMacroUrl,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ macro_id: macroId, conversation_ids: ids }),
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        }).done(function (resp) {
            if (resp && resp.success) {
                toastr.success(resp.message || 'Macro aplicado');
            } else {
                toastr.warning((resp && resp.message) || 'No se aplicó el macro a ninguna conversación.');
            }
            $('#bv-bulk-bar').addClass('bv-hidden');
            $('.bv-conv.selected').removeClass('selected');
            $('[data-bv-bulk-select]').prop('checked', false);
            // Reuse the inbox list-refresh pipeline already wired in index.blade.php
            window.dispatchEvent(new CustomEvent('inbox:incoming-message'));
        }).fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                $.each(xhr.responseJSON.errors, function (field, msgs) {
                    toastr.error(msgs[0]);
                });
                return;
            }
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se pudo aplicar el macro';
            toastr.error(msg);
        });
    });
})();
</script>
@endpush
@endonce
