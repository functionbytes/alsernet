/**
 * HelpdeskBirthday — reconciliation.js
 *
 * Logic for modules/HelpdeskBirthday/resources/views/campaigns/reconciliation.blade.php
 * (selección/recuento de bonos a marcar en gestión). Vanilla JS, sin dependencias.
 */
(function () {
    'use strict';

    var form = document.getElementById('bd-reconcile-form');

    if (!form) {
        return;
    }

    var checks = function () { return Array.from(form.querySelectorAll('.bd-reconcile-check')); };
    var label = document.getElementById('bd-reconcile-count');
    var modalCount = document.getElementById('bd-reconcile-modal-count');

    function refresh() {
        var n = checks().filter(function (c) { return c.checked; }).length;

        if (label) {
            label.textContent = n === 0
                ? 'Ninguno seleccionado'
                : (n === 1 ? '1 seleccionado' : n + ' seleccionados');
        }

        if (modalCount) {
            modalCount.textContent = n;
        }
    }

    var all = document.getElementById('bd-check-all');

    if (all) {
        all.addEventListener('change', function () {
            checks().forEach(function (c) { c.checked = all.checked; });
            refresh();
        });
    }

    form.addEventListener('change', function (event) {
        if (event.target.classList.contains('bd-reconcile-check')) {
            refresh();
        }
    });

    // Sin nada marcado no se abre la confirmación: enviar el formulario
    // vacío devolvería un error de validación en vez de decirlo aquí.
    form.addEventListener('submit', function (event) {
        if (checks().filter(function (c) { return c.checked; }).length === 0) {
            event.preventDefault();
            event.stopPropagation();

            if (label) {
                label.textContent = 'Selecciona al menos un bono.';
            }
        }
    });

    refresh();
})();
