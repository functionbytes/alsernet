/**
 * Etiquetas sociales (managers/social-tags/index.blade.php). Extraido del
 * <script> inline de esa vista: alta/edicion en el modal #tagModal.
 *
 * Depende de que #tagForm lleve data-store-url y data-update-url-template
 * (con el placeholder "__ID__") — lo unico que este fichero no puede
 * resolver por su cuenta.
 *
 * OJO: la vista original usaba @section('scripts')/@endsection en vez de
 * @push('scripts')/@endpush — el layout solo expone @stack('scripts'), asi
 * que ese bloque nunca se inyectaba y resetTagForm()/editTag() no existian
 * nunca (los botones "Nueva etiqueta"/"Editar" no hacian nada). Se corrige
 * de paso al extraer este fichero.
 */
(function () {
    'use strict';

    function applyTagColors() {
        $('[data-tag-color]').each(function () {
            $(this).css('--hso-tag-color', $(this).data('tag-color'));
        });
    }

    function resetTagForm() {
        var $form = $('#tagForm');
        $form.attr('action', $form.data('store-url'));
        $form.find('input[name="_method"]').remove();
        $form[0].reset();
        $('#tagModalLabel').text('Nueva etiqueta');
    }

    function editTag(id, name, slug, color, description, isActive) {
        var $form = $('#tagForm');
        $form.attr('action', $form.data('update-url-template').replace('__ID__', id));
        if ($form.find('input[name="_method"]').length === 0) {
            $form.prepend('<input type="hidden" name="_method" value="PUT">');
        }
        $form.find('input[name="name"]').val(name);
        $form.find('input[name="slug"]').val(slug).trigger('input');
        $form.find('input[name="color"]').val(color).trigger('input');
        $form.find('textarea[name="description"]').val(description);
        $form.find('input[name="is_active"]').prop('checked', isActive === 1);
        $('#tagModalLabel').text('Editar etiqueta');
        $('#tagModal').modal('show');
    }

    window.resetTagForm = resetTagForm;
    window.editTag = editTag;

    $(applyTagColors);
})();
