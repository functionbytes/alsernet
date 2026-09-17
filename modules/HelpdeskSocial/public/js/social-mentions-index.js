/**
 * Menciones sociales (managers/social-mentions/index.blade.php). Extraido
 * del <script> inline de esa vista.
 *
 * OJO — dos bugs reales encontrados y arreglados de paso al probar el
 * módulo en vivo (estuvo deshabilitado desde su creación, nadie los vio):
 *  1) @section('scripts')/@endsection en vez de @push('scripts')/@endpush
 *     — markMentionAsReviewed() no existía nunca.
 *  2) Llamaba a POST .../mentions/{id}/review, una ruta que no existe en
 *     ningún sitio (ni web.php ni api.php). El backend real es el update
 *     estándar del recurso: PUT /api/helpdesk/social/mentions/{id} con
 *     {status: 'reviewed'} (UpdateSocialMentionRequest). Se cambia a esa
 *     llamada real.
 */
(function () {
    'use strict';

    function markMentionAsReviewed(id) {
        $.ajax({
            url: '/api/helpdesk/social/mentions/' + id,
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { status: 'reviewed' },
            success: function () {
                if (window.toastr) {
                    toastr.success('Mención marcada como revisada.');
                }
                $('tr[data-mention-id="' + id + '"] td:nth-child(6) .badge').removeClass('bg-warning text-dark').addClass('bg-info').text('Reviewed');
            },
            error: function () {
                if (window.toastr) {
                    toastr.error('No se pudo actualizar la mención.');
                }
            }
        });
    }

    window.markMentionAsReviewed = markMentionAsReviewed;
})();
