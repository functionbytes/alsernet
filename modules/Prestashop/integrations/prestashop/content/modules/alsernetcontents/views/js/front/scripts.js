
$(document).ready(function() {

    $('.sticky-support').on('click', function() {
    
        var cmb_position = $('.hi-cmb-popup-opener').attr('data-position');
    
        $.magnificPopup.open({
            items: {
                src: '.hi-cmb-popup-' + cmb_position
            },
            type: 'inline',
            midClick: true,
            removalDelay: 0,
            mainClass: 'mfp-fade',
            closeOnBgClick: false, // Cambiado a false para manejar manualmente el cierre
            showCloseBtn: true,
            enableEscapeKey: true,
            modal: true
        }, 0);
    
        // Añadir evento para detectar clic fuera del popup
        $(document).on('click', function(event) {
            var popup = $('.hi-cmb-popup-' + cmb_position);
            // Verifica si el clic fue fuera del popup y no fue en el botón para abrir el popup
            if (!popup.is(event.target) && popup.has(event.target).length === 0 && !$(event.target).closest('.sticky-support').length) {
                $.magnificPopup.close();  // Cierra el popup
                $(document).off('click');  // Remueve el event listener
            }
        });
    
        return false;
    });
    
});
