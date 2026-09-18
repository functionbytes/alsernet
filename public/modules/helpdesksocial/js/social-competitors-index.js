/**
 * Competidores sociales (managers/social-competitors/index.blade.php).
 * Extraido del <script> inline de esa vista: alta/edicion en el modal
 * #competitorModal.
 *
 * Depende de que #competitorForm lleve data-store-url y
 * data-update-url-template (con el placeholder "__ID__").
 *
 * OJO: la vista original usaba @section('scripts')/@endsection en vez de
 * @push('scripts')/@endpush — el layout solo expone @stack('scripts'), asi
 * que ese bloque nunca se inyectaba y resetCompetitorForm()/
 * editCompetitor() no existian nunca. Se corrige de paso al extraer este
 * fichero.
 */
(function () {
    'use strict';

    function resetCompetitorForm() {
        var $form = $('#competitorForm');
        $form.attr('action', $form.data('store-url'));
        $form.find('input[name="_method"]').remove();
        $form[0].reset();
        $('#competitorModalLabel').text('Nuevo competidor');
    }

    function editCompetitor(id, name, platform, username, profileUrl, isActive) {
        var $form = $('#competitorForm');
        $form.attr('action', $form.data('update-url-template').replace('__ID__', id));
        if ($form.find('input[name="_method"]').length === 0) {
            $form.prepend('<input type="hidden" name="_method" value="PUT">');
        }
        $form.find('input[name="name"]').val(name);
        $form.find('select[name="platform"]').val(platform);
        $form.find('input[name="username"]').val(username);
        $form.find('input[name="profile_url"]').val(profileUrl);
        $form.find('input[name="is_active"]').prop('checked', isActive === 1);
        $('#competitorModalLabel').text('Editar competidor');
        $('#competitorModal').modal('show');
    }

    window.resetCompetitorForm = resetCompetitorForm;
    window.editCompetitor = editCompetitor;
})();
