/**
 * ai-agent-settings-tab.js — HelpdeskAgents module
 *
 * Pestaña "Configuración" del agente IA (partials/settings-tab.blade.php,
 * incluida de forma estática en managers/ai-agent/settings.blade.php, no por
 * AJAX). Controla mostrar/ocultar la clave de API, refrescar la lista de
 * modelos al cambiar de proveedor y el botón "Probar" conexión.
 *
 * Depende de: toastr (global) y window.HelpdeskAgentsAiSettings.settingsTab
 * (proveedores/modelos y URL de prueba, inyectados por la propia vista).
 */
(function () {
    'use strict';

    var config = (window.HelpdeskAgentsAiSettings && window.HelpdeskAgentsAiSettings.settingsTab) || {};
    var providers = config.providers || {};

    document.addEventListener('DOMContentLoaded', function () {
        // Mostrar u ocultar la clave
        var toggleBtn = document.getElementById('toggleApiKey');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                var input = document.getElementById('api_key');
                var icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        }

        // Al cambiar de proveedor se recarga la lista de modelos
        var providerSelect = document.getElementById('provider');
        if (providerSelect) {
            providerSelect.addEventListener('change', function () {
                var provider = this.value;
                var modelSelect = document.getElementById('model');
                modelSelect.innerHTML = '<option value="">Seleccionar modelo</option>';

                if (provider && providers[provider]) {
                    // providers[provider].models es {valor: etiqueta} (p.ej.
                    // {"claude-opus-5": "Claude Opus 5"}), no un array — con
                    // .forEach() directo tiraba TypeError y el <select> se
                    // quedaba vacío en cuanto se elegía proveedor.
                    Object.keys(providers[provider].models).forEach(function (value) {
                        var option = document.createElement('option');
                        option.value = value;
                        option.textContent = providers[provider].models[value];
                        modelSelect.appendChild(option);
                    });
                }
            });
        }

        // Probar conexión
        var testBtn = document.getElementById('testConnection');
        if (testBtn) {
            testBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var provider = document.getElementById('provider').value;
                var model = document.getElementById('model').value;
                var apiKey = document.getElementById('api_key').value;

                if (!provider || !model) {
                    toastr.warning('Selecciona proveedor y modelo antes de probar', 'Falta información');
                    return;
                }

                var btn = this;
                var originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Probando…';

                fetch(config.testUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ provider: provider, model: model, api_key: apiKey }),
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success) {
                            toastr.success(data.message, 'Conexión verificada');
                        } else {
                            toastr.error(data.message, 'Error');
                        }
                    })
                    .catch(function (err) {
                        toastr.error('Error al conectar: ' + err.message, 'Error');
                    })
                    .finally(function () {
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    });
            });
        }
    });
})();
