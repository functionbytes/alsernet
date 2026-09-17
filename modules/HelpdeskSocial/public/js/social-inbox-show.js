/**
 * Responder comentario (managers/social-inbox/show.blade.php). Extraido del
 * <script> inline de esa vista: expone el id/plataforma del comentario y
 * arranca la carga de plantillas guardadas + etiquetas disponibles.
 *
 * loadSavedReplies()/loadAvailableTags()/removeTag()/addTag()/
 * requestApproval()/postNote() viven ahora en social-inbox-enhanced.js
 * (cargado justo antes que este fichero) — el backend de etiquetas/notas/
 * aprobación ya existía completo vía API; solo faltaba attachTag/detachTag
 * en SocialInboxController y el propio JS, que nunca se había escrito.
 * useAiSuggestion() sigue siendo un stub inerte: no hay backend de
 * sugerencias IA en ningún sitio del módulo.
 */
(function () {
    'use strict';

    var $root = $('#social-comment-show');
    window.currentCommentId = $root.data('comment-id');
    window.currentPlatform = $root.data('platform');

    $(document).ready(function () {
        if (typeof window.loadSavedReplies === 'function') { window.loadSavedReplies(); }
        if (typeof window.loadAvailableTags === 'function') { window.loadAvailableTags(); }
    });
})();
