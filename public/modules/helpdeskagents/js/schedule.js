/**
 * schedule.js — HelpdeskAgents module
 *
 * Página "Horarios de agentes" (settings/schedule/index.blade.php): turnos,
 * ausencias y guardias. Inicializa los selects, el modal de borrado
 * compartido y recuerda la pestaña activa entre recargas (tras enviar un
 * formulario, la página vuelve a cargar y perdería la pestaña sin esto).
 *
 * Depende de: jQuery, select2, toastr, Bootstrap tabs (globales) y
 * window.HelpdeskAgentsSchedule (mensajes flash, inyectados por la propia
 * vista).
 */
(function ($) {
    'use strict';

    var flash = window.HelpdeskAgentsSchedule || {};
    var ACTIVE_TAB_KEY = 'scheduleActiveTab';

    $(document).ready(function () {
        $('.form-select').select2({ width: '100%' });

        if (flash.success) {
            toastr.success(flash.success, 'Éxito');
        }
        if (flash.error) {
            toastr.error(flash.error, 'Error');
        }

        $(document).on('click', '.delete-btn', function () {
            $('#delete-modal .modal-title').text($(this).data('title'));
            $('#delete-form').attr('action', $(this).data('url'));
        });

        // Restore active tab on page reload (after form submit)
        var activeTab = localStorage.getItem(ACTIVE_TAB_KEY);
        if (activeTab) {
            var tabEl = document.querySelector('#scheduleTabs button[data-bs-target="' + activeTab + '"]');
            if (tabEl) {
                new bootstrap.Tab(tabEl).show();
            }
        }

        document.querySelectorAll('#scheduleTabs button[data-bs-toggle="tab"]').forEach(function (btn) {
            btn.addEventListener('shown.bs.tab', function (e) {
                localStorage.setItem(ACTIVE_TAB_KEY, e.target.getAttribute('data-bs-target'));
            });
        });
    });
})(jQuery);
