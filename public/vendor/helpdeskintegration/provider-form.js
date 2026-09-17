/**
 * Formulario de proveedor de integracion (settings/providers/_form.blade.php).
 * Vista previa del icono FontAwesome mientras se escribe.
 *
 * Tras editar hay que copiarlo a public/vendor/helpdeskintegration/.
 */
$(document).ready(function () {
    $('#providerIconInput').on('input', function () {
        var value = $.trim($(this).val()) || 'fas fa-plug';
        $('#providerIconPreview i').attr('class', value);
    });
});
