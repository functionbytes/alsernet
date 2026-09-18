/**
 * Políticas SLA sociales (managers/social-sla/index.blade.php). Extraido
 * del <script> inline de esa vista: alta/edicion en el modal #slaModal.
 *
 * Depende de que #slaForm lleve data-store-url y data-update-url-template
 * (con el placeholder "__ID__").
 *
 * OJO: la vista original usaba @section('scripts')/@endsection en vez de
 * @push('scripts')/@endpush — el layout solo expone @stack('scripts'), asi
 * que ese bloque nunca se inyectaba y resetSlaForm()/editSla() no existian
 * nunca. Se corrige de paso al extraer este fichero.
 */
(function () {
    'use strict';

    function resetSlaForm() {
        var $form = $('#slaForm');
        $form.attr('action', $form.data('store-url'));
        $form.find('input[name="_method"]').remove();
        $form[0].reset();
        $('#slaModalLabel').text('Nueva política SLA');
    }

    function editSla(id, name, platform, priority, responseTime, resolutionTime, isActive) {
        var $form = $('#slaForm');
        $form.attr('action', $form.data('update-url-template').replace('__ID__', id));
        if ($form.find('input[name="_method"]').length === 0) {
            $form.prepend('<input type="hidden" name="_method" value="PUT">');
        }
        $form.find('input[name="name"]').val(name);
        $form.find('select[name="platform"]').val(platform);
        $form.find('select[name="priority"]').val(priority);
        $form.find('input[name="response_time_minutes"]').val(responseTime);
        $form.find('input[name="resolution_time_minutes"]').val(resolutionTime);
        $form.find('input[name="is_active"]').prop('checked', isActive === 1);
        $('#slaModalLabel').text('Editar política SLA');
        $('#slaModal').modal('show');
    }

    window.resetSlaForm = resetSlaForm;
    window.editSla = editSla;
})();
