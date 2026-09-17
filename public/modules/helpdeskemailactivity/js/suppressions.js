/**
 * Actividad de correo — Lista de supresion (settings/suppressions.blade.php).
 * Extraido del <script> inline de esa vista.
 */
(function () {
    'use strict';

    $(function () {
        $('#suppression-add-modal .form-select').select2({ width: '100%', dropdownParent: $('#suppression-add-modal') });
    });
})();
