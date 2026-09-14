document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('customModal');
    modal.style.display = 'block';

    // Cerrar el modal cuando se hace clic en el botón de cerrar
    var closeButton = document.getElementById('modalCloseButton');
    closeButton.onclick = function () {
        modal.style.display = 'none';
    }

    // Cerrar el modal cuando se hace clic fuera del contenido del modal
    window.onclick = function (event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
});