/**
 * Partial settings/canned-replies/_form.blade.php.
 */
$(document).ready(function () {
    // 'select.select2': el contenedor que genera select2 hereda esa clase y un
    // selector por clase acabaria reinicializandose sobre si mismo.
    $('select.select2').select2({ width: '100%', minimumResultsForSearch: Infinity });
});
