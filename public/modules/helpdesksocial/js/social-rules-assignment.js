/**
 * Reglas de asignación social (managers/social-rules/assignment.blade.php).
 * Extraido del <script> inline de esa vista: alta/edicion en el modal
 * #assignmentModal.
 *
 * Depende de que #assignmentForm lleve data-store-url y
 * data-update-url-template (con el placeholder "__ID__").
 *
 * OJO: la vista original usaba @section('scripts')/@endsection en vez de
 * @push('scripts')/@endpush — el layout solo expone @stack('scripts'), asi
 * que ese bloque nunca se inyectaba y resetAssignmentForm()/
 * editAssignmentRule() no existian nunca. Se corrige de paso al extraer
 * este fichero.
 */
(function () {
    'use strict';

    function resetAssignmentForm() {
        var $form = $('#assignmentForm');
        $form.attr('action', $form.data('store-url'));
        $form.find('input[name="_method"]').remove();
        $form[0].reset();
        $('#assignmentModalLabel').text('Nueva regla de asignación');
    }

    function editAssignmentRule(id, name, conditions, assigneeUserId, strategy, priority, isActive) {
        var $form = $('#assignmentForm');
        $form.attr('action', $form.data('update-url-template').replace('__ID__', id));
        if ($form.find('input[name="_method"]').length === 0) {
            $form.prepend('<input type="hidden" name="_method" value="PUT">');
        }
        $form.find('input[name="name"]').val(name);
        $form.find('input[name="priority"]').val(priority);
        $form.find('select[name="assignee_user_id"]').val(assigneeUserId);
        $form.find('select[name="assignment_strategy"]').val(strategy);
        $form.find('input[name="is_active"]').prop('checked', isActive === 1);
        $('#assignmentModalLabel').text('Editar regla de asignación');
        $('#assignmentModal').modal('show');
    }

    window.resetAssignmentForm = resetAssignmentForm;
    window.editAssignmentRule = editAssignmentRule;
})();
