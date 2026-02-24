/**
 * OAuth Wizard - Manejo de popup OAuth de Google
 */

$(document).ready(function() {
    $('#connect-google').on('click', function(e) {
        e.preventDefault();

        const name = $('#name').val();
        if (!name.trim()) {
            toastr.error('Debes ingresar un nombre para la conexión');
            $('#name').focus();
            return;
        }

        // Construir URL con el nombre
        const form = $('#connection-form');
        const authUrl = form.attr('action') + '?' + form.serialize();

        // Abrir popup centrado
        const width = 600;
        const height = 700;
        const left = (screen.width - width) / 2;
        const top = (screen.height - height) / 2;

        const popup = window.open(
            authUrl,
            'GoogleAuth',
            `width=${width},height=${height},left=${left},top=${top},scrollbars=yes,resizable=yes`
        );

        if (!popup) {
            toastr.error('El popup fue bloqueado. Permite popups para este sitio.');
            return;
        }

        // Polling para detectar cuando cierra el popup
        const checkPopup = setInterval(function() {
            if (popup.closed) {
                clearInterval(checkPopup);
                // Redirect a ubicaciones después de conectar
                window.location.href = '/settings/reviews/locations';
            }
        }, 500);

        // Escuchar mensaje de callback del popup
        window.addEventListener('message', function(event) {
            // Verificar origen del mensaje
            if (event.origin !== window.location.origin) {
                return;
            }

            if (event.data.type === 'oauth-success') {
                clearInterval(checkPopup);
                if (popup && !popup.closed) {
                    popup.close();
                }
                toastr.success('Conexión exitosa con Google');
                setTimeout(() => {
                    window.location.href = '/settings/reviews/locations';
                }, 1000);
            } else if (event.data.type === 'oauth-error') {
                clearInterval(checkPopup);
                if (popup && !popup.closed) {
                    popup.close();
                }
                toastr.error(event.data.message || 'Error al conectar con Google');
            }
        });
    });
});
