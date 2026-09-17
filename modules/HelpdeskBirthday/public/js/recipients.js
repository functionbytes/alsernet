/**
 * HelpdeskBirthday — recipients.js
 *
 * Logic for modules/HelpdeskBirthday/resources/views/campaigns/recipients.blade.php
 * (previsualización del correo enviado, en el modal #bd-email-modal). Vanilla JS,
 * depende de Bootstrap 5 (bootstrap.Modal).
 */
(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('.bd-view-email');

        if (!trigger) {
            return;
        }

        var url = trigger.dataset.emailUrl;

        document.getElementById('bd-email-frame').src = url;
        document.getElementById('bd-email-open').href = url;
        document.getElementById('bd-email-to').textContent = 'Para: ' + trigger.dataset.emailTo;

        bootstrap.Modal.getOrCreateInstance(document.getElementById('bd-email-modal')).show();
    });

    // Soltar el iframe al cerrar: si no, el correo sigue cargado de fondo.
    var emailModal = document.getElementById('bd-email-modal');
    if (emailModal) {
        emailModal.addEventListener('hidden.bs.modal', function () {
            document.getElementById('bd-email-frame').src = 'about:blank';
        });
    }
})();
