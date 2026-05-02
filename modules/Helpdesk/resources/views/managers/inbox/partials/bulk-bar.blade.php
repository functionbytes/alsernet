{{-- Bulk action bar — shown when one or more conversations are selected --}}
<div class="bv-bulk-bar bv-hidden" id="bv-bulk-bar">
    <div class="bv-bulk-bar-info">
        <span id="bv-bulk-count">0</span> seleccionadas
        <button class="bv-bulk-deselect" id="bv-bulk-deselect" title="Cancelar selección">
            <i class="fas fa-xmark"></i>
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
    </div>
</div>

@push('scripts')
<script>
(function () {
    var bulkUrl = '{{ route("manager.helpdesk.conversations.bulk") }}';
    var csrf    = $('meta[name="csrf-token"]').attr('content');

    function getSelectedIds() {
        var ids = [];
        $('.bv-conv.selected').each(function () {
            var id = $(this).data('bv-conv-id');
            if (id) { ids.push(id); }
        });
        return ids;
    }

    function updateBulkBar() {
        var ids = getSelectedIds();
        var count = ids.length;
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
})();
</script>
@endpush
