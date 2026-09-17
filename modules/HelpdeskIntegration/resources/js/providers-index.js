/**
 * Catalogo de proveedores de integracion (settings/providers/index.blade.php).
 * Depende de window.HiProvidersIndexFlash (session flash) que el propio
 * Blade inyecta como datos, no como logica.
 *
 * Tras editar hay que copiarlo a public/vendor/helpdeskintegration/.
 */
$(document).ready(function () {
    var flash = window.HiProvidersIndexFlash || {};

    $(document).on('click', '.btn-delete', function () {
        var url = $(this).data('url');
        var name = $(this).data('name');
        $('#hiDeleteForm').attr('action', url);
        $('#hiDeleteItemName').text(name);
        $('#hiDeleteModal').modal('show');
    });

    $(document).on('click', '.btn-toggle', function () {
        var url = $(this).data('url');
        $('#toggleForm').attr('action', url).submit();
    });

    if (flash.success) {
        toastr.success(flash.success, 'Éxito');
    }
    if (flash.error) {
        toastr.error(flash.error, 'Error');
    }
});
