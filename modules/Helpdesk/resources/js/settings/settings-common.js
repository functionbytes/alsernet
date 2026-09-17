/**
 * Helpdesk · Settings — utilidades JS compartidas por las pantallas de
 * modules/Helpdesk/resources/views/settings/.
 *
 * Fusion de los 3 archivos que existian duplicados por haberse creado cada
 * uno en un lote de migracion distinto (settings-common.js "lote 1/2",
 * settings-common-batch3.js "lote 3", settings-common-batch4.js "lote 4"),
 * cada uno con su propia API. Se conservan las TRES APIs publicas tal cual
 * estaban (window.HelpdeskSettingsCommon / window.HdSettingsCommon /
 * window.HDSettingsCommon) para no tener que tocar los ~45 scripts de
 * pagina que ya las consumen — solo cambia que ahora viven en un unico
 * archivo fisico en vez de 3 casi-identicos.
 *
 * OJO — el auto-init de la API "lote 1" (autoSelect2 + bindDeleteConfirm +
 * flash de window.HdPageFlash al cargar la pagina) NO se ejecuta si la
 * pagina define window.HdSettingsCommonSkipAutoInit = true ANTES de que
 * este script corra. Las paginas de los lotes 3 y 4 inicializan todo a
 * mano (llaman a HdSettingsCommon.* / HDSettingsCommon.* explicitamente),
 * asi que ahora que comparten este mismo archivo fisico ponen ese flag
 * para evitar un doble select2()/doble bind de .btn-delete. Las paginas
 * del lote 1 no lo definen, asi que el auto-init sigue funcionando exacto
 * a como funcionaba antes de la fusion.
 */
(function ($) {
    'use strict';

    /* =====================================================================
     * window.HelpdeskSettingsCommon — API "lote 1/2" (agent-settings,
     * attributes, audits, automation-rules, banners, brands, broadcasts,
     * features).
     * ===================================================================*/

    function onReady(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    /**
     * Inicializa select2 sobre cualquier select "clasico" de estas pantallas
     * (.form-select, .select2, .select2-filter-modal) que no lo este ya.
     */
    function autoSelect2() {
        $('.form-select, .select2, .select2-filter-modal').each(function () {
            const $el = $(this);

            if ($el.hasClass('select2-hidden-accessible')) {
                return;
            }

            const $modal = $el.closest('.modal');
            const allowClear = $el.data('select2-allow-clear');

            $el.select2({
                width: '100%',
                allowClear: allowClear === undefined ? false : !!allowClear,
                dropdownParent: $modal.length ? $modal : undefined,
                language: {
                    noResults: function () {
                        return 'Sin resultados';
                    },
                    searching: function () {
                        return 'Buscando...';
                    },
                },
            });
        });
    }

    /**
     * Boton de borrado generico: cualquier elemento con clase .btn-delete y
     * data-url (+ opcionalmente data-title o data-name) abre el modal de
     * core::components.delete (#delete-modal / #delete-form).
     */
    function bindDeleteConfirm() {
        $(document).on('click', '.btn-delete', function () {
            const $btn = $(this);
            const url = $btn.data('url');
            const title = $btn.data('title') || $btn.data('name');
            const modalSel = $btn.data('deleteModal') || '#delete-modal';
            const formSel = $btn.data('deleteForm') || '#delete-form';

            $(formSel).attr('action', url);

            if (title) {
                $(modalSel + ' .modal-title').text(title);
            }

            $(modalSel).modal('show');
        });
    }

    /**
     * Muestra los mensajes flash de sesion via toastr.
     */
    function flash(cfg) {
        cfg = cfg || {};

        if (typeof toastr === 'undefined') {
            return;
        }

        if (cfg.success) toastr.success(cfg.success, cfg.successTitle || 'Exito');
        if (cfg.error) toastr.error(cfg.error, cfg.errorTitle || 'Error');
        if (cfg.warning) toastr.warning(cfg.warning, cfg.warningTitle || 'Atencion');
        if (cfg.info) toastr.info(cfg.info, cfg.infoTitle || 'Informacion');
    }

    /**
     * Wiring generico del modal de "filtros avanzados" usado en los listados
     * (agent-settings, banners, brands...).
     */
    function filterModal(cfg) {
        if (!cfg) {
            return;
        }

        const fields = cfg.fields || [];

        $(cfg.applyBtn).on('click', function () {
            fields.forEach(function (field) {
                $(field.hidden).val($(field.modal).val());
            });
            $(cfg.modal).modal('hide');
            $(cfg.form).trigger('submit');
        });

        $(cfg.clearBtn).on('click', function () {
            fields.forEach(function (field) {
                $(field.modal).val(null).trigger('change');
            });
        });
    }

    /**
     * Wiring generico de la barra de acciones masivas flotante + su modal,
     * apoyado en window.BulkActions (core/js/bulk.js).
     */
    function bulk(cfg) {
        if (!cfg || !window.BulkActions) {
            return;
        }

        const bulkApi = window.BulkActions.init({ checkbox: cfg.checkbox || '.bulk-checkbox' });
        const $select = $(cfg.actionSelect || '#bulk-action-select');
        const $applyBtn = $(cfg.applyBtn || '#bulk-apply-btn');
        const $modal = $(cfg.modal || '#bulk-modal');
        const applyLabel = cfg.applyLabel || 'Aplicar';

        $modal.on('hide.bs.modal', function () {
            $select.val('').trigger('change');
            $applyBtn.prop('disabled', false).text(applyLabel);
            bulkApi.reset();
        });

        $applyBtn.on('click', function () {
            const action = $select.val();
            const ids = bulkApi.getIds();

            if (!action) {
                toastr.warning('Selecciona una acción.');
                return;
            }
            if (!ids.length) {
                toastr.warning(cfg.emptyMessage || 'Selecciona al menos un elemento.');
                return;
            }

            const run = function () {
                $applyBtn.prop('disabled', true).text('Procesando...');

                $.ajax({
                    url: cfg.url,
                    method: 'POST',
                    data: JSON.stringify({
                        action: action,
                        ids: ids,
                        _token: $('meta[name="csrf-token"]').attr('content'),
                    }),
                    contentType: 'application/json',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        $modal.modal('hide');
                        toastr.success(res.message);
                        setTimeout(function () {
                            location.reload();
                        }, 800);
                    },
                    error: function (xhr) {
                        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al procesar.');
                        $applyBtn.prop('disabled', false).text(applyLabel);
                    },
                });
            };

            const confirmMsg = cfg.confirmActions && cfg.confirmActions[action];

            if (confirmMsg && typeof window.__confirm === 'function') {
                window.__confirm(confirmMsg.replace('{count}', ids.length), run);
            } else {
                run();
            }
        });
    }

    if (window.HdSettingsCommonSkipAutoInit !== true) {
        onReady(function () {
            autoSelect2();
            bindDeleteConfirm();

            if (window.HdPageFlash) {
                flash(window.HdPageFlash);
            }
        });
    }

    window.HelpdeskSettingsCommon = {
        autoSelect2: autoSelect2,
        flash: flash,
        filterModal: filterModal,
        bulk: bulk,
    };

    /* =====================================================================
     * window.HdSettingsCommon — API "lote 3" (business, canned-replies,
     * companies, custom-fields, drip-campaigns, email, integrations,
     * macros, notifications, routing-rules, sla-policies, slack-integrations,
     * status-page, statuses).
     * ===================================================================*/

    function flashSession(config) {
        config = config || {};
        if (config.flashSuccess) {
            toastr.success(config.flashSuccess, config.flashSuccessTitle || 'Exito');
        }
        if (config.flashError) {
            toastr.error(config.flashError, config.flashErrorTitle || 'Error');
        }
    }

    function initFormSelect2(selector) {
        $(selector || '.form-select').select2({ width: '100%' });
    }

    function initDeleteModal() {
        $(document).on('click', '.delete-btn', function () {
            $('#delete-modal .modal-title').text($(this).data('title'));
            $('#delete-form').attr('action', $(this).data('url'));
        });
    }

    function initFilterModal(opts) {
        $(opts.selectSelector || '.select2-filter-modal').select2({
            dropdownParent: $('#' + opts.modalId),
            width: '100%',
        });

        $('#' + opts.applyBtnId).on('click', function () {
            opts.fields.forEach(function (f) {
                $(f.hidden).val($(f.modal).val());
            });
            $('#' + opts.modalId).modal('hide');
            $('#' + opts.formId).submit();
        });

        $('#' + opts.clearBtnId).on('click', function () {
            opts.fields.forEach(function (f) {
                $(f.modal).val(null).trigger('change');
            });
        });
    }

    function initBulkActions(opts) {
        var bulkHd = window.BulkActions.init({ checkbox: opts.checkboxSelector || '.bulk-checkbox' });

        $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

        $('#bulk-modal').on('hide.bs.modal', function () {
            $('#bulk-action-select').val('').trigger('change');
            $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            bulkHd.reset();
        });

        $('#bulk-apply-btn').on('click', function () {
            var action = $('#bulk-action-select').val();
            var ids = bulkHd.getIds();

            if (!action) {
                toastr.warning('Selecciona una acción.');
                return;
            }
            if (!ids.length) {
                toastr.warning('Selecciona al menos un ' + (opts.itemLabelSingular || 'elemento') + '.');
                return;
            }

            var proceed = function () {
                var $btn = $('#bulk-apply-btn');
                $btn.prop('disabled', true).text('Procesando...');

                $.ajax({
                    url: opts.bulkUrl,
                    method: 'POST',
                    data: JSON.stringify({
                        action: action,
                        ids: ids,
                        _token: $('meta[name="csrf-token"]').attr('content'),
                    }),
                    contentType: 'application/json',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        $('#bulk-modal').modal('hide');
                        toastr.success(res.message);
                        setTimeout(function () {
                            location.reload();
                        }, 800);
                    },
                    error: function (xhr) {
                        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al procesar.');
                        $btn.prop('disabled', false).text('Aplicar');
                    },
                });
            };

            if (action === 'delete' && opts.confirmDelete !== false) {
                var msg = '¿Eliminar ' + ids.length + ' ' + (opts.itemLabelPlural || 'elemento(s)') + '? Esta acción no se puede deshacer.';
                if (typeof window.__confirm === 'function') {
                    window.__confirm(msg, proceed);
                } else if (confirm(msg)) {
                    proceed();
                }
                return;
            }

            proceed();
        });
    }

    function initToggleStatus(selector, onSuccess) {
        $(document).on('click', selector, function () {
            var $el = $(this);
            $.ajax({
                url: $el.data('url'),
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    onSuccess($el, response);
                },
                error: function () {
                    toastr.error('No se pudo cambiar el estado.', 'Error');
                },
            });
        });
    }

    window.HdSettingsCommon = {
        flashSession: flashSession,
        initFormSelect2: initFormSelect2,
        initDeleteModal: initDeleteModal,
        initFilterModal: initFilterModal,
        initBulkActions: initBulkActions,
        initToggleStatus: initToggleStatus,
    };

    /* =====================================================================
     * window.HDSettingsCommon — API "lote 4" (surveys, tags, team,
     * uploading, views, webhooks, whatsapp-templates, whatsapp-usage,
     * workflows).
     * ===================================================================*/

    function hdInitFormSelect2(selector, options) {
        selector = selector || '.form-select';

        $(selector).select2($.extend({ width: '100%' }, options || {}));
    }

    function flashToastr(flashCfg) {
        flashCfg = flashCfg || {};

        if (flashCfg.success && window.toastr) {
            toastr.success(flashCfg.success, flashCfg.successTitle || 'Exito');
        }

        if (flashCfg.error && window.toastr) {
            toastr.error(flashCfg.error, flashCfg.errorTitle || 'Error');
        }
    }

    function wireDeleteButtonModal() {
        $(document).on('click', '.delete-btn', function () {
            $('#delete-modal .modal-title').text($(this).data('title'));
            $('#delete-form').attr('action', $(this).data('url'));
        });
    }

    window.HDSettingsCommon = {
        initFormSelect2: hdInitFormSelect2,
        flashToastr: flashToastr,
        wireDeleteButtonModal: wireDeleteButtonModal,
    };
})(jQuery);
