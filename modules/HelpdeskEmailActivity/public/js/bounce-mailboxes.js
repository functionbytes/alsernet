/**
 * Actividad de correo — Buzones de rebote (settings/bounce-mailboxes.blade.php).
 * Extraido del <script> inline de esa vista: alta/edicion de buzones IMAP
 * (un unico modal de edicion, rellenado con los data-* de la fila elegida).
 */
(function () {
    'use strict';

    $(function () {
        $('#bounce-mailbox-add-modal .form-select').select2({ width: '100%', dropdownParent: $('#bounce-mailbox-add-modal') });
        $('#bounce-mailbox-edit-modal .form-select').select2({ width: '100%', dropdownParent: $('#bounce-mailbox-edit-modal') });

        var editModalEl = document.getElementById('bounce-mailbox-edit-modal');
        var editModal = new bootstrap.Modal(editModalEl);
        var $editForm = $('#bounce-mailbox-edit-form');

        $(document).on('click', '.js-edit-mailbox', function () {
            var data = $(this).data('mailbox');
            var updateUrl = $(this).data('update-url');

            $editForm.attr('action', updateUrl);
            $editForm.find('[name="label"]').val(data.label || '');
            $editForm.find('[name="host"]').val(data.host || '');
            $editForm.find('[name="port"]').val(data.port || 993);
            $editForm.find('[name="encryption"]').val(data.encryption || 'ssl').trigger('change');
            $editForm.find('[name="username"]').val(data.username || '');
            $editForm.find('[name="password"]').val('');
            $editForm.find('[name="folder"]').val(data.folder || 'INBOX');
            $editForm.find('[name="enabled"]').val(data.enabled ? '1' : '0').trigger('change');

            var $scope = $editForm.find('[name="module_scope[]"]');
            $scope.val(data.module_scope || []).trigger('change');

            editModal.show();
        });
    });
})();
