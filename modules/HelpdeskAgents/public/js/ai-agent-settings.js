/**
 * ai-agent-settings.js — HelpdeskAgents module
 *
 * Página "Agente IA" (managers/ai-agent/settings.blade.php): primer arranque,
 * botón "Guardar cambios" de la cabecera y carga perezosa de las pestañas
 * Etiquetas / Herramientas / Base de conocimiento.
 *
 * Depende de: jQuery, toastr, Bootstrap tooltips (globales) y
 * window.HelpdeskAgentsAiSettings.tabUrls (URLs de rutas, inyectadas por la
 * propia vista).
 */
(function ($) {
    'use strict';

    var tabUrls = (window.HelpdeskAgentsAiSettings && window.HelpdeskAgentsAiSettings.tabUrls) || {};

    $(document).ready(function () {
        // Primer arranque: la guía cede el sitio al formulario.
        $('#ais-setup-start').on('click', function () {
            document.getElementById('ais-setup').hidden = true;
            document.getElementById('ais-form-wrap').hidden = false;
            document.getElementById('ais-form-wrap').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        // El "Guardar cambios" de la cabecera envía el mismo formulario.
        $('#btn-save-top').on('click', function () {
            var form = document.getElementById('settingsForm');
            if (form) {
                form.requestSubmit();
            }
        });

        // Carga perezosa de pestañas: cada una pide su parcial la primera vez.
        var loadedTabs = { settings: true, tags: false, tools: false, knowledge: false };

        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            // Los ids de los tab-pane van prefijados "ai-tab-" (ver
            // settings.blade.php) para no chocar con el #tab-settings del
            // menú lateral global (Theme/includes/nav.blade.php).
            var tabName = $(e.target).attr('href').replace('#ai-tab-', '');

            if (loadedTabs[tabName] === false) {
                loadTabContent(tabName);
                loadedTabs[tabName] = true;
            }
        });

        function loadTabContent(tabName) {
            var container = $('#' + tabName + '-container');

            if (!tabUrls[tabName]) {
                return;
            }

            $.ajax({
                url: tabUrls[tabName],
                method: 'GET',
                success: function (response) {
                    container.html(response);
                    updateTabCounter(tabName);
                },
                error: function () {
                    container.html('<div class="alert alert-warning">Error al cargar el contenido. Vuelve a intentarlo.</div>');
                },
            });
        }

        function updateTabCounter(tabName) {
            var count = $('#' + tabName + '-container').find('[data-count-item]').length;
            $('#' + tabName + '-count').text(count);
        }

        window.showSuccess = function (message) { toastr.success(message, 'Éxito'); };
        window.showError = function (message) { toastr.error(message, 'Error'); };

        $('[data-bs-toggle="tooltip"]').tooltip();
    });
})(jQuery);
