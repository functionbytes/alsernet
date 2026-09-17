/**
 * Bandeja social (managers/social-inbox/index.blade.php). Extraido del
 * <script> inline de esa vista: listener Echo en vivo, filtros de
 * fila y etiquetado masivo.
 *
 * Depende de que la tabla (#social-inbox-table) lleve data-bulk-tag-url y
 * data-tags-url — lo unico que este fichero no puede resolver por su
 * cuenta.
 *
 * OJO: la vista original tenia DOS bugs que nunca se detectaron:
 *   1) @section('scripts')/@endsection en vez de @push('scripts')/@endpush
 *      — el layout solo expone @stack('scripts'), asi que este bloque
 *      nunca se inyectaba (sin listener en vivo, sin filtros, sin
 *      etiquetado masivo).
 *   2) Aun arreglando (1), toggleSelectAll/applyFilters/applyBulkTag
 *      vivian dentro del IIFE sin exponerse a window — los onclick/onchange
 *      inline de la vista (toggleSelectAll(), applyFilters(), etc.) las
 *      habrian llamado igualmente como ReferenceError. Se corrigen los dos
 *      de paso al extraer este fichero.
 */
(function () {
    'use strict';

    function setupSocialInboxListener() {
        if (typeof window.Echo === 'undefined') {
            console.warn('[SocialInbox] Echo not ready, retrying in 500ms');
            setTimeout(setupSocialInboxListener, 500);
            return;
        }

        var inboxChannel = window.Echo.private('helpdesk.social.inbox');
        console.log('[SocialInbox] Subscribed to private-helpdesk.social.inbox');

        inboxChannel.listen('.social.comment.received', function (e) {
            var comment = e.comment;
            console.log('[SocialInbox] New comment received', comment);

            // Show toast for high urgency comments
            if (comment.urgency === 'high' || comment.urgency === 'critical') {
                if (window.toastr) {
                    toastr.warning(
                        comment.author_name + ': ' + comment.body.substring(0, 60),
                        'Comentario ' + (comment.urgency === 'critical' ? 'crítico' : 'alta urgencia') + ' en ' + comment.platform,
                        { timeOut: 8000, closeButton: true }
                    );
                }
            }

            // Refresh the page to show the new comment
            window.location.reload();
        });

        inboxChannel.listen('.social.intent.classified', function (e) {
            console.log('[SocialInbox] Intent classified', e);
            // If the comment is already visible, update its badge
            var $row = $('tr[data-comment-id="' + e.comment_id + '"]');
            if ($row.length) {
                window.location.reload();
            }
        });

        inboxChannel.listen('.social.comment.replied', function (e) {
            console.log('[SocialInbox] Comment replied', e);
            window.location.reload();
        });
    }

    // Delay slightly to ensure Echo is bootstrapped
    setTimeout(setupSocialInboxListener, 300);

    function toggleSelectAll() {
        var checked = $('#selectAll').prop('checked');
        $('.comment-checkbox').prop('checked', checked);
    }

    function applyFilters() {
        var sentiment = $('#sentimentFilter').val();
        var status = $('#statusFilter').val();
        var showSla = $('#showSlaBreached').prop('checked');

        $('tr[data-comment-id]').each(function () {
            var $row = $(this);
            var visible = true;

            if (sentiment && $row.data('sentiment') !== sentiment) {
                visible = false;
            }

            if (status && $row.data('status') !== status) {
                visible = false;
            }

            if (showSla && $row.data('sla-breached') !== '1') {
                visible = false;
            }

            // .toggleClass('hso-step-hidden') en vez de jQuery .toggle():
            // evita fijar un style="display" inline en cada fila.
            $row.toggleClass('hso-step-hidden', !visible);
        });
    }

    function applyBulkTag() {
        var tagId = $('#bulkTagSelect').val();
        var commentIds = $('.comment-checkbox:checked').map(function () { return $(this).val(); }).get();

        if (!tagId) {
            if (window.toastr) { toastr.warning('Selecciona una etiqueta.'); }
            return;
        }

        if (commentIds.length === 0) {
            if (window.toastr) { toastr.warning('Selecciona al menos un comentario.'); }
            return;
        }

        $.ajax({
            url: $('#social-inbox-table').data('bulk-tag-url'),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { tag_id: tagId, comment_ids: commentIds },
            success: function () {
                if (window.toastr) { toastr.success('Etiquetas aplicadas correctamente.'); }
                window.location.reload();
            },
            error: function () {
                if (window.toastr) { toastr.error('Error al aplicar etiquetas.'); }
            }
        });
    }

    window.toggleSelectAll = toggleSelectAll;
    window.applyFilters = applyFilters;
    window.applyBulkTag = applyBulkTag;

    $(document).ready(function () {
        $.ajax({
            url: $('#social-inbox-table').data('tags-url'),
            method: 'GET',
            success: function (response) {
                var $select = $('#bulkTagSelect');
                $select.empty().append('<option value="">Etiqueta...</option>');
                (response.data || []).forEach(function (tag) {
                    $select.append('<option value="' + tag.id + '">' + tag.name + '</option>');
                });
            }
        });
    });
})();
