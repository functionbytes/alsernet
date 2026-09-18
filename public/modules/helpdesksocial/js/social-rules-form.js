/**
 * Alta/edicion de reglas automaticas (managers/social-rules/create.blade.php
 * y managers/social-rules/edit.blade.php). Extraido de los <script> inline
 * de esas vistas — comparten el mismo comportamiento; #deleteRuleBtn solo
 * existe en la vista de edicion, asi que el handler no hace nada en alta.
 */
(function () {
    'use strict';

    $(document).ready(function () {
        $('.select2').select2({ width: '100%' });

        $('#deleteRuleBtn').on('click', function () {
            if (confirm('¿Seguro que quieres eliminar esta regla? Esta acción no se puede deshacer.')) {
                $('#deleteRuleForm').submit();
            }
        });
    });
})();
