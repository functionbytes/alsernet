$(document).ready(function(){
    $('body').on('focus', '.product_autocomplete', function(){
        if (!$(this).data('autocomplete-initialized')) {
            $(this).autocomplete({
                source: ajaxProductsUrl,
                minLength: 2,
                select: function(event, ui) {
                    // Encuentra el hidden dentro del mismo contenedor flex
                    $(this).siblings('input.product_id_hidden').val(ui.item.id);
                    $(this).val(ui.item.label);
                    return false;
                }
            });
            $(this).data('autocomplete-initialized', true);
        }
    });
});