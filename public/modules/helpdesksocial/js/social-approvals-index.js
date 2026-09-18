/**
 * Solicitudes de aprobación (managers/social-approvals/index.blade.php).
 * Extraido del <script> inline de esa vista.
 *
 * OJO — dos bugs reales encontrados y arreglados de paso al probar el
 * módulo en vivo (estuvo deshabilitado desde su creación, nadie los vio):
 *  1) @section('scripts')/@endsection en vez de @push('scripts')/@endpush
 *     — respondApproval() no existía nunca, los botones no hacían nada.
 *  2) El formulario construía su action como
 *     url('panel/helpdesk/social/approvals') + '/{id}/respond' — esa ruta
 *     NO existe en ningún sitio (ni en web.php ni en api.php; el backend
 *     real son dos endpoints JSON separados, /approve y /reject, bajo
 *     /api/helpdesk/social/approval-requests/{id}/...). El submit nativo
 *     habría dado 404 siempre. Se cambia a un submit AJAX contra el
 *     endpoint real correspondiente a la acción elegida.
 */
(function ($) {
    'use strict';

    var API_BASE = '/api/helpdesk/social/approval-requests';
    var currentId = null;

    window.respondApproval = function (id, action) {
        currentId = id;
        $('#approvalAction').val(action);
        $('#approvalResponseForm textarea[name="approver_note"]').val('');
        $('#approvalResponseModalLabel').text(action === 'approve' ? 'Aprobar solicitud' : 'Rechazar solicitud');
        $('#approvalResponseModal').modal('show');
    };

    $('#approvalResponseForm').on('submit', function (e) {
        e.preventDefault();
        if (!currentId) { return; }

        var action = $('#approvalAction').val() === 'approve' ? 'approve' : 'reject';
        var note = $.trim($('#approvalResponseForm textarea[name="approver_note"]').val());

        $.ajax({
            url: API_BASE + '/' + currentId + '/' + action,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { approver_note: note || undefined },
        }).done(function (res) {
            if (window.toastr) {
                toastr.success((res && res.message) || 'Solicitud respondida correctamente.');
            }
            window.location.reload();
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo enviar la respuesta.';
            if (window.toastr) { toastr.error(msg); }
        });
    });
})(jQuery);
