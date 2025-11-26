/**
 * Delivery Step Handler
 *
 * Manages delivery options functionality in the checkout process
 */

(function() {
    'use strict';

    // Verificar que jQuery esté disponible
    if (typeof jQuery === 'undefined') {
        return;
    }

    if (typeof window.deliveryStepHandler === 'undefined') {

        class DeliveryStepHandler {

            constructor(checkoutManager) {
                this.manager = checkoutManager;
                this.initialized = false;
                this.selectedDeliveryOption = null;
                this.selectedCarrier = null;
                this.autoSelectionControlled = false; // Flag to control autoselections

                // Configuration
                this.config = {
                    deliveryOptions: null,
                    confirmButton: null,
                    deliveryStep: null,
                    deliveryForm: null
                };
            }

            init(force = false) {
                if (this.initialized && !force) {
                    return;
                }

                // Reset initialized flag to ensure clean start
                this.initialized = false;

                this.initializeElements();
                this.cleanupExistingErrors();
                this.bindEvents();
                this.handleDeliveryStep();
                this.setupFormValidation();

                // Detect pre-selected carrier and ensure content is loaded
                // Use setTimeout to ensure DOM is fully ready
                setTimeout(() => {
                    this.handlePreSelectedCarrier();
                    // Reengancha MR si aplica
                    setTimeout(() => this.ensureMondialRelayCore(), 150);
                }, 100);
                this.initialized = true;
            }

            cleanupExistingErrors() {
                // Remove any existing validation errors from the delivery form
                $('#js-delivery').find('.invalid-feedback').remove();
                $('#js-delivery').find('label.error').remove();
                $('#js-delivery').find('.is-invalid').removeClass('is-invalid');

                // Clean ALL child forms within js-delivery to prevent validation interference
                $('#js-delivery form').each(function() {
                    const $childForm = $(this);
                    $childForm.find('.error').remove();
                    $childForm.find('.is-invalid').removeClass('is-invalid');

                    // Remove validator from child forms to prevent parent validation interference
                    if ($childForm.data('validator')) {
                        $childForm.removeData('validator');
                    }
                });
            }

            initializeElements() {
                this.config.deliveryOptions = $('.delivery-option');
                this.config.confirmButton = $('.step-checkout-delivery .next');
                this.config.deliveryStep = $('.step-checkout-delivery');
                this.config.deliveryForm = $('.step-checkout-delivery');

                // Bind GTM tracking for shipping method selection
                this.bindShippingEvents();
            }

            bindEvents() {
                // Remove ALL existing delivery-related events to prevent duplicates
                $(document)
                    .off('.delivery')
                    .off('.deliveryStep')
                    .off('.deliveryStepPriority')
                    .off('click.deliveryStep')
                    .off('change.deliveryStep')
                    .off('submit.deliveryStep');

                $(document)
                    .off('.deliveryBlocker')
                    .on(
                        'click.deliveryBlocker mousedown.deliveryBlocker touchstart.deliveryBlocker',
                        '.carrier-extra-content, .carrier-extra-content *',
                        function (e) {
                            e.stopPropagation();
                        }
                    );

                $(document)
                    .off('.deliveryBlocker')
                    .on('click.deliveryBlocker mousedown.deliveryBlocker touchstart.deliveryBlocker',
                        '.carrier-extra-content, .carrier-extra-content *',
                        function (e) {
                            if ($(e.target).closest('#mondialrelay_content').length) {
                                return; // deja pasar eventos del widget MR
                            }
                            e.stopPropagation();
                        }
                    );

                $(document).on('keydown', function (e) {
                    const isEnter = (e.key === 'Enter' || e.keyCode === 13);
                    const isDeliveryOptionFocused = $(e.target).is('input[name^="delivery_option"]');
                    if (isEnter && isDeliveryOptionFocused) {
                        e.preventDefault();
                        return false;
                    }
                });

                // Simplified delivery option selection
                $(document)
                    .off('click.delivery change.delivery', '.delivery-options .delivery-option, .delivery_option_select, .delivery-content')
                    .on('click.delivery', '.delivery-options .delivery-option', (e) => {
                        const $container = $(e.currentTarget).closest('.delivery-option-item');
                        const $radio = $container.find('.delivery_option_select');

                        if ($radio.length && !$radio.is(':checked')) {
                            $radio.prop('checked', true).trigger('change');
                        }
                    })
                    .on('change.delivery', '.delivery_option_select', (e) => {
                        this.selectDeliveryOption($(e.currentTarget));

                        // Trigger form validation when delivery option is selected
                        setTimeout(() => {
                            if ($('#js-delivery').data('validator')) {
                                $('#js-delivery').valid();
                            }
                        }, 100);
                    });

                $(document).on('change', '#js-delivery [name^="delivery_option"]', () => {
                    setTimeout(() => this.ensureMondialRelayCore(), 120);
                });

                // Next button handler for delivery form validation
                $(document).on(
                    'click.deliveryStepPriority',
                    '#js-delivery button.next[type="submit"]:not(.btn-secondary), ' +
                    '#js-delivery .btn.next[type="submit"]:not(.btn-secondary), ' +
                    '.step-checkout-delivery form#js-delivery button.next:not(.btn-secondary), ' +
                    '.step-checkout-delivery form#js-delivery .btn.next:not(.btn-secondary)',
                    (e) => {
                        // Check if validation already in progress to prevent double handling
                        const $button = $(e.currentTarget);
                        if ($button.data('delivery-validation-in-progress')) {
                            return;
                        }

                        e.preventDefault();
                        e.stopImmediatePropagation();

                        $button.data('delivery-validation-in-progress', true);

                        const $form = $('#js-delivery');

                        // First check delivery option selection manually
                        const hasDeliverySelected = $('input[name^="delivery_option"]:checked').length > 0;

                        if (!hasDeliverySelected) {
                            this.showMissingDeliveryOptionModal();
                            $button.removeData('delivery-validation-in-progress');
                            return;
                        }

                        // Trigger jQuery validation to check all form fields except child forms
                        let isFormValid = true;
                        const validator = $form.data('validator');
                        if (validator) {
                            // Temporarily ignore ALL child form fields from validation
                            const originalIgnore = validator.settings.ignore;
                            validator.settings.ignore = originalIgnore + ', #js-delivery form input, #js-delivery form select, #js-delivery form textarea';

                            // Remove any existing validation errors from ALL child forms before validating
                            $('#js-delivery form').each(function() {
                                $(this).find('.error').remove();
                                $(this).find('.is-invalid').removeClass('is-invalid');
                            });

                            // Validate the form excluding child form elements
                            isFormValid = $form.valid();

                            // Restore original ignore setting
                            validator.settings.ignore = originalIgnore;
                        } else {
                            // Fallback if no validator
                            isFormValid = $form.valid();
                        }

                        if (!isFormValid) {
                            $button.removeData('delivery-validation-in-progress');
                            return;
                        }

                        // Additional carrier-specific validations
                        const selectedCarrierId = parseInt($('input[name^="delivery_option"]:checked').val(), 10);

                        // Debug carrier name
                        const $selectedOption = $('input[name^="delivery_option"]:checked');
                        const $container = $selectedOption.closest('.delivery-option-item');
                        const actualCarrierName = $container.find('.carrier-name').text().trim();

                        // Guard carriers (ID 78, 39) — validar sede
                        const isGuardCarrier = actualCarrierName &&
                            (actualCarrierName.toLowerCase().includes('guard') ||
                                actualCarrierName.toLowerCase().includes('guardia'));

                        if (selectedCarrierId === 78 || (isGuardCarrier && (selectedCarrierId === 78 || selectedCarrierId === 39))) {
                            const carrierName = actualCarrierName || 'Recogida en tienda';
                            const selectedStore = $('#kb_pickup_selected_store').val();
                            const deliveryConfirmation = $('#delivery_confirmation').val();

                            if (!selectedStore || selectedStore === '' || deliveryConfirmation !== 'yes') {
                                this.showPickupLocationModal(selectedCarrierId, carrierName);
                                $button.removeData('delivery-validation-in-progress');
                                return;
                            }
                        }
                        // Correos Express carrier (ID 66) — validar oficina
                        else if (selectedCarrierId === 66) {
                            const $selectedOffice = $('#cexSelected');
                            const isOfficeSelected = $selectedOffice.length && !$selectedOffice.hasClass('d-none') && $selectedOffice.html().trim() !== '';

                            if (!isOfficeSelected) {
                                this.showPickupLocationModal(selectedCarrierId, 'Correos Express');
                                $button.removeData('delivery-validation-in-progress');
                                return;
                            }
                        }

                        // All validations passed
                        $button.removeData('delivery-validation-in-progress');
                        $form.trigger('submit');
                    }
                );
            }

            selectDeliveryOption($radio) {
                if ($radio.data('processing')) return; // Simple duplicate prevention

                const $container = $radio.closest('.delivery-option-item');
                const carrierId = parseInt($container.data('carrier'));
                if (!carrierId) return;

                $radio.data('processing', true);
                this.selectedCarrier = carrierId;
                this.selectedDeliveryOption = $container;
                this.updateDeliverySelectionUI($container);

                // Use backend configuration for carrier types
                const config = window.carrierConfig || {};

                // Skip AJAX for carriers that don't need setdelivery call (currently none)
                if (config.skip_setdelivery && config.skip_setdelivery.includes(carrierId)) {
                    this.showCarrierContent(carrierId, null, $container);
                    $radio.removeData('processing');
                    return;
                }

                // All carriers go to setdelivery but handle response differently
                const iso = window.checkoutNavigator?.getISO?.();
                const module = window.checkoutNavigator?.getModuleUrlBase?.();

                $.ajax({
                    type: 'POST',
                    url: `${module}/alsernetshopping/routes?modalitie=checkout&action=setdelivery&iso=${iso}`,
                    data: { id_carrier: carrierId, token: prestashop.token },
                    dataType: 'json'
                })
                    .done((response) => {
                        // Errores del backend
                        if (response.errors && response.errors.hasError) {
                            if (window.ModalManager && response.errors.modal_html) {
                                window.ModalManager.handleModal(response.errors);
                            } else {
                                this.showToast('error', 'Error: ' + (response.errors.type || 'Error de validación'));
                            }
                            return;
                        }

                        if (response.status === 'error') {
                            this.showToast('error', response.message || 'Error al seleccionar método de envío');
                        }
                        this.showCarrierContent(carrierId, response, $container);
                        this.ensureMondialRelayCore();
                    })
                    .fail(() => {
                        this.showToast('error', 'Error al cambiar método de envío');
                    })
                    .always(() => {
                        $radio.removeData('processing');
                    });
            }

            updateDeliverySelectionUI($selectedOption) {
                $('.delivery-option-item').removeClass('selected');
                $selectedOption.addClass('selected');
            }

            showCarrierContent(carrierId, response, $container) {
                const config = window.carrierConfig || {};
                const cleanId = parseInt(String(carrierId).replace(/\D/g, ''), 10);

                let $extraContent = $(`.carrier-extra-contents`).filter(function () {
                    return parseInt(String($(this).data('carrier')).replace(/\D/g, ''), 10) === cleanId;
                });

                // Limpiar/ocultar antes de renderizar
                this.handleCarrierSelection(carrierId);

                if (response?.html && response.html.trim() !== '') {
                    if ($extraContent.length) {
                        $extraContent.html(response.html).removeClass('d-none');
                    }
                } else if (response?.message) {
                    if ($extraContent.length) {
                        $extraContent.removeClass('d-none');
                    }
                } else if (config.standard && config.standard.includes(cleanId)) {
                    $extraContent.removeClass('d-none');
                } else {
                    $extraContent.removeClass('d-none');
                }
            }

            handleCarrierSelection(selectedId) {
                const SPECIAL_CARRIER_IDS = [39, 66, 78, 101]; // siempre vaciar (empty)
                selectedId = parseInt(String(selectedId).replace(/\D/g, ''), 10);

                $('.carrier-extra-contents').each(function () {
                    const $el = $(this);
                    const cid = parseInt(String($el.data('carrier')).replace(/\D/g, ''), 10);

                    // Ocultar siempre
                    $el.addClass('d-none');

                    // Vaciar siempre los SPECIAL (sin importar el carrier seleccionado)
                    if (SPECIAL_CARRIER_IDS.includes(cid)) {
                        $el.empty();
                    }
                });
            }

            showMissingDeliveryOptionModal() {
                const $modal = $('#missing-delivery-option-modal');
                if ($modal.length && !$modal.hasClass('show')) {
                    $modal.modal('show');
                }
            }

            showToast(type, message, title = '') {
                if (typeof toastr !== 'undefined') {
                    toastr[type](message, title, {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right",
                        timeOut: 5000,
                        extendedTimeOut: 3000
                    });
                } else if (typeof window.settings?.showToast === 'function') {
                    window.settings.showToast(type, message, title);
                }
            }

            setupFormValidation() {
                const $form = $('#js-delivery');

                // Remove existing validator if present to allow reinitialization
                if ($form.data('validator')) {
                    $form.removeData('validator');
                }

                // Custom validation method for delivery option selection
                $.validator.addMethod("deliveryOptionRequired", function(value, element) {
                    // Check if any delivery option radio button is selected
                    const isSelected = $('input[name^="delivery_option"]:checked').length > 0;

                    // If no delivery option selected, show missing-delivery modal
                    if (!isSelected) {
                        setTimeout(() => {
                            this.showMissingDeliveryOptionModal();
                        }, 100);
                    }

                    return isSelected;
                }.bind(this), "Por favor, selecciona un método de envío");

                // Setup jQuery validation for delivery form
                if (typeof $.fn.validate === 'function') {
                    $('#js-delivery').validate({
                        // Ignore ALL child forms within js-delivery
                        ignore: '#js-delivery form input, #js-delivery form select, #js-delivery form textarea',
                        rules: {
                            'delivery_option_validation': {
                                deliveryOptionRequired: true
                            },
                            gift_message: {
                                required: false,
                                minlength: 3
                            }
                        },
                        messages: {
                            'delivery_option_validation': {
                                deliveryOptionRequired: "Por favor, selecciona un método de envío"
                            },
                            gift_message: {
                                minlength: $.validator.messages.minlength
                            }
                        },
                        errorElement: 'label',
                        errorClass: 'error',
                        validClass: 'is-valid',
                        errorPlacement: function(error, element) {
                            if (element.attr('name') === 'delivery_option_validation') {
                                return false;
                            } else if (element.attr('name') === 'gift_message') {
                                error.insertAfter(element);
                            } else {
                                error.insertAfter(element);
                            }
                        },
                        highlight: function(element) {
                            if (element.name === 'delivery_option_validation') {
                                $('.delivery-option-item').addClass('validation-error');
                            }
                        },
                        unhighlight: function(element) {
                            $(element).removeClass('is-invalid').addClass('is-valid');
                            if (element.name === 'delivery_option_validation') {
                                $('.delivery-option-item').removeClass('validation-error');
                            }
                        },
                        submitHandler: function(form) {
                            $(form).off('submit').submit();
                        }
                    });

                    // Add hidden input for delivery option validation
                    if (!$('#delivery_option_validation').length) {
                        $('#js-delivery').append('<input type="hidden" name="delivery_option_validation" id="delivery_option_validation" value="1">');
                    }
                }
            }

            handleDeliveryStep() {
                // Only handle delivery-specific UI interactions, not form submission
            }

            showMissingDeliveryModal() {
                const $modal = $('#delivery-delivery-modal');
                if ($modal.length && !$modal.hasClass('show')) {
                    $modal.modal('show');
                }
            }

            showPickupLocationModal(carrierId, carrierName) {
                let serviceName, locationText, buttonText;

                switch(carrierId) {
                    case 78:
                        serviceName = carrierName || 'Recogida en tienda';
                        locationText = 'una sede específica de recogida';
                        buttonText = 'Seleccionar sede';
                        break;
                    case 39:
                        serviceName = carrierName || 'Guardia Civil';
                        locationText = 'una sede específica de recogida';
                        buttonText = 'Seleccionar sede';
                        break;
                    case 66:
                        serviceName = carrierName || 'Correos Express';
                        locationText = 'una oficina de recogida';
                        buttonText = 'Seleccionar oficina';
                        break;
                    default:
                        serviceName = carrierName || 'Punto de recogida';
                        locationText = 'un punto de recogida';
                        buttonText = 'Seleccionar punto';
                }

                const $modal = $('#pickup-location-modal');
                if ($modal.length) {
                    $modal.find('#modal-service-name').text(serviceName);
                    $modal.find('#modal-location-text').text(locationText);
                    $modal.find('#modal-button-text').text(buttonText);
                    $modal.modal('show');
                }
            }

            /**
             * Bind GTM tracking events for shipping method selection
             */
            bindShippingEvents() {
                // Store shipping method selection (no immediate tracking)
                $(document).off('change.gtm-shipping').on('change.gtm-shipping', 'input[name^="delivery_option"]', (e) => {
                    const $selectedOption = $(e.currentTarget);
                    if ($selectedOption.is(':checked')) {
                        const shippingMethod = this.extractShippingMethodName($selectedOption);
                        this.selectedShippingMethod = shippingMethod;
                    }
                });

                // GTM tracking on Next button for delivery step
                $(document).off('click.gtm-delivery-next').on('click.gtm-delivery-next', '.step-checkout-delivery .next, .step-checkout-delivery button[type="submit"]', async (e) => {
                    await this.handleDeliveryNextClick(e);
                });
            }

            /**
             * Handle Next button click for delivery step
             */
            async handleDeliveryNextClick(e) {
                try {
                    const $selectedOption = $('input[name^="delivery_option"]:checked');
                    if ($selectedOption.length === 0) return;

                    const shippingMethod = this.extractShippingMethodName($selectedOption);

                    if (window.GTMCheckoutHelper) {
                        await window.GTMCheckoutHelper.trackShippingSelection($selectedOption[0]);
                    } else if (window.gtmExecuteWithBackendData) {
                        await window.gtmExecuteWithBackendData('add_shipping_info', {
                            shipping_tier: shippingMethod,
                            checkout_step: '2'
                        });
                    }
                } catch (error) {}
            }

            /**
             * Extracts shipping method name from selected option
             */
            extractShippingMethodName($option) {
                const $container = $option.closest('.delivery-option-item');

                const $label = $container.find('.carrier-name');
                if ($label.length) {
                    return $label.text().trim();
                }

                const analytic = $container.data('analytic');
                if (analytic) {
                    return analytic;
                }

                const labelFor = $option.attr('id');
                if (labelFor) {
                    const $radioLabel = $(`label[for="${labelFor}"]`);
                    if ($radioLabel.length) {
                        return $radioLabel.text().trim();
                    }
                }

                const carrierId = parseInt($option.val(), 10);
                const carrierMapping = {
                    39: 'Recogida en Guardia Civil',
                    66: 'Correos Express',
                    78: 'Recogida en tienda',
                    99: 'Envío a domicilio',
                    101: 'Entrega a dirección seleccionada'
                };

                return carrierMapping[carrierId] || `Método de envío ${carrierId}`;
            }

            /**
             * Ensure carrier content is visible by removing d-none class
             */
            ensureCarrierContentVisible(carrierId) {
                const cleanId = parseInt(String(carrierId).replace(/\D/g, ''), 10);

                let $extraContent = $(`.carrier-extra-contents`).filter(function () {
                    return parseInt(String($(this).data('carrier')).replace(/\D/g, ''), 10) === cleanId;
                });

                if ($extraContent.length === 0) {
                    $extraContent = $(`.carrier-extra-content`).filter(function () {
                        return parseInt(String($(this).data('carrier')).replace(/\D/g, ''), 10) === cleanId;
                    });
                }

                if ($extraContent.length > 0) {
                    $extraContent.removeClass('d-none');

                    const $container = $(`input[name^="delivery_option"][data-id="${carrierId}"]`).closest('.delivery-option-item');
                    this.updateDeliverySelectionUI($container);

                    if ($extraContent.html().trim() === '') {
                        const $radio = $(`input[name^="delivery_option"][data-id="${carrierId}"]`);
                        if ($radio.length > 0) {
                            $radio.removeData('processing');
                            this.selectDeliveryOption($radio);
                        }
                    }
                }
            }

            /**
             * Initialize store pickup carrier if it's pre-selected
             */
            initializeStorePickup() {
                try {
                    const $storePickupContent = $('#kb_pts_carrier_block');
                    if ($storePickupContent.length === 0) return;

                    if (typeof window.initializePreSelectedStore === 'function') {
                        window.initializePreSelectedStore();
                    } else {
                        setTimeout(() => {
                            const selectedStoreId = $('#kb_pickup_selected_store').val();
                            const deliveryConfirmation = $('#delivery_confirmation').val();
                            if (selectedStoreId && deliveryConfirmation === 'yes') {
                                $(document).trigger('store.preselected', [selectedStoreId]);
                            }
                        }, 100);
                    }
                } catch (error) {}
            }

            /**
             * Initialize CorreosExpress carrier if it's pre-selected
             */
            initializeCorreosExpress() {
                try {
                    const $correosContent = $('.correosexpress-address-wrapper');
                    if ($correosContent.length === 0) return;

                    if (typeof window.initializePreSelectedCorreosOffice === 'function') {
                        window.initializePreSelectedCorreosOffice();
                    } else {
                        setTimeout(() => {
                            const carrierSelected = $('input[name^="delivery_option"][data-id="66"]:checked').length > 0;
                            if (carrierSelected) {
                                // no-op
                            }
                        }, 100);
                    }
                } catch (error) {}
            }

            /**
             * Central autoselection control - sets which carrier is allowed to auto-select
             */
            setAllowedAutoSelectCarrier(carrierId) {
                window.DELIVERY_AUTOSELECT_CONTROL = {
                    allowedCarrierId: carrierId,
                    timestamp: Date.now()
                };

                this.disableOtherCarrierAutoSelections(carrierId);
                this.autoSelectionControlled = true;
            }

            /**
             * Disable autoselections for all carriers except the allowed one
             */
            disableOtherCarrierAutoSelections(allowedCarrierId) {
                const autoSelectCarriers = [39, 66, 78, 101];

                autoSelectCarriers.forEach(carrierId => {
                    if (carrierId !== allowedCarrierId) {
                        window[`DISABLE_AUTOSELECT_${carrierId}`] = true;
                    } else {
                        window[`DISABLE_AUTOSELECT_${carrierId}`] = false;
                    }
                });
            }

            /**
             * Check if a carrier is allowed to perform autoselection
             */
            static isCarrierAutoSelectAllowed(carrierId) {
                const control = window.DELIVERY_AUTOSELECT_CONTROL;
                const isAllowed = control && control.allowedCarrierId === carrierId;
                const isDisabled = window[`DISABLE_AUTOSELECT_${carrierId}`] === true;
                return isAllowed && !isDisabled;
            }

            /**
             * Handle pre-selected carrier from template (already checked in HTML)
             */
            handlePreSelectedCarrier() {
                const $checkedRadio = $('input[name^="delivery_option"]:checked');
                if ($checkedRadio.length === 0) {
                    return;
                }

                const $container = $checkedRadio.closest('.delivery-option-item');
                const carrierId = parseInt($container.data('carrier'));

                // Solo permitir autoselección para el carrier actual
                this.setAllowedAutoSelectCarrier(carrierId);

                // Update UI selection state
                this.updateDeliverySelectionUI($container);

                // Ensure carrier content is visible and loaded
                this.ensureCarrierContentVisible(carrierId);

                // Inicializaciones específicas
                if (carrierId === 78) {
                    setTimeout(() => this.initializeStorePickup(), 300);
                }
                if (carrierId === 66) {
                    setTimeout(() => this.initializeCorreosExpress(), 300);
                }

                this.ensureMondialRelayCore();
            }
            // --- BRIDGE ROBUSTO HACIA EL CORE MR ---
            ensureMondialRelayCore(forceHardReinit = false) {
                try {
                    const w = window.mondialrelayWidget;        // objeto del core (r.e)
                    if (!w) return;

                    const val = $('[name^="delivery_option"]:checked').val();
                    if (!val) return;

                    const carrierId = String(val).split(',')[0];
                    const mrIds = (window.MONDIALRELAY_NATIVE_RELAY_CARRIERS_IDS || []).map(String);
                    // fallback por si la lista no está
                    const isMR = mrIds.length
                        ? mrIds.includes(carrierId)
                        : ['98','100','107','108','109','110','111'].includes(carrierId);

                    if (!isMR) return;

                    // 1) Mover el contenedor global al carrier seleccionado
                    let $target = $('[name^="delivery_option"]:checked')
                        .closest('.delivery-option')
                        .next('.carrier-extra-content, .carrier-extra-contents');

                    if ($target.length) {
                        $target.removeClass('d-none').show();
                        // MUY IMPORTANTE: solo existe un #mondialrelay_content global. Lo movemos.
                        $('#mondialrelay_content').appendTo($target);
                    }

                    // 2) Determinar el modo esperado (APM/RELAY/HD, etc.)
                    const cm = window.MONDIALRELAY_CARRIER_METHODS || {};
                    const method = cm[carrierId];
                    const expected = method ? method.delivery_mode : undefined;

                    // 3) Si el modo cambia o forzamos, limpiar estado guardado
                    const currentMode = w.widget_current_params ? w.widget_current_params.ColLivMod : null;
                    if (forceHardReinit || (currentMode && expected && currentMode !== expected)) {
                        if (typeof w.resetSelectedRelay === 'function') w.resetSelectedRelay();
                        if (typeof w.resetSavedRelay === 'function') w.resetSavedRelay();
                        window.MONDIALRELAY_SELECTED_RELAY_IDENTIFIER = null;
                    }

                    // 4) Si el contenedor está vacío o nunca se inicializó, hacer RE-INIT en vez de update
                    const containerEmpty = $(w.widget_container).children().length === 0;

                    if (!w.loaded) {
                        // Aún no cargó el plugin => init (carga script y luego MR_ParcelShopPicker)
                        w.init({ ColLivMod: expected, carrierId });
                    } else if (containerEmpty || !w.initialized) {
                        // Tenía el script, pero el contenedor está vacío o marcado no inicializado
                        $(w.widget_container).empty();
                        w.initialized = false; // asegura que haga init real
                        w.init({ ColLivMod: expected, carrierId });
                    } else {
                        // Instancia viva => update normal
                        w.initOrUpdate({ ColLivMod: expected, carrierId });
                    }

                    // 5) Mostrar resumen o widget - mejorar detección de estado guardado
                    // Verificar múltiples fuentes de verdad para el estado guardado
                    const hasGlobalIdentifier = !!(window.MONDIALRELAY_SELECTED_RELAY_IDENTIFIER);
                    const hasWidgetSaved = !!(w.savedRelay);
                    const hasStoredRelay = hasGlobalIdentifier || hasWidgetSaved;

                    // Si hay un identificador global pero el widget no tiene savedRelay, sincronizar
                    if (hasGlobalIdentifier && !hasWidgetSaved) {
                        w.savedRelay = window.MONDIALRELAY_SELECTED_RELAY_IDENTIFIER;
                        console.log('🔄 Synced savedRelay from global identifier after refresh');
                    }

                    $(w.summary_container).toggle(hasStoredRelay);
                    $(w.widget_container).toggle(!hasStoredRelay);

                    // 6) Seguridad: si el widget está visible y no hay nada renderizado aún, lanza una búsqueda
                    if (!hasStoredRelay) {
                        setTimeout(() => { try { w.doSearch(); } catch(_) {} }, 60);
                    }
                } catch (e) {
                    console.warn('ensureMondialRelayCore error', e);
                }
            }

            destroy() {
                $(document)
                    .off('.deliveryStep')
                    .off('.deliveryStepPriority')
                    .off('.delivery')
                    .off('.gtm-shipping')
                    .off('.deliveryBlocker');
                this.initialized = false;
            }
        }


        window.DeliveryStepHandler = DeliveryStepHandler;

        if (!(window.deliveryStepHandler instanceof DeliveryStepHandler)) {
            window.deliveryStepHandler = new DeliveryStepHandler(window.checkoutManager);
        }

        window.initDeliveryStep = function () {
            if (!(window.deliveryStepHandler instanceof DeliveryStepHandler)) {
                window.deliveryStepHandler = new DeliveryStepHandler(window.checkoutManager);
            }
            if (!window.deliveryStepHandler.initialized) {
                window.deliveryStepHandler.init();
            }
        };

        let initDeliveryStep = window.initDeliveryStep = function () {
            if (!(window.deliveryStepHandler instanceof DeliveryStepHandler)) {
                window.deliveryStepHandler = new DeliveryStepHandler(window.checkoutManager);
            }
            if (!window.deliveryStepHandler.initialized) {
                window.deliveryStepHandler.init();
            }
        };


    }

    // *** FUNCIÓN GLOBAL PARA CONTROL DE AUTOSELECCIÓN ***
    window.isCarrierAutoSelectAllowed = function(carrierId) {
        try {
            const control = window.DELIVERY_AUTOSELECT_CONTROL;
            const isAllowed = control && control.allowedCarrierId === carrierId;
            const isDisabled = window[`DISABLE_AUTOSELECT_${carrierId}`] === true;
            const result = isAllowed && !isDisabled;
            console.log(`🔍 Global check - Carrier ${carrierId} autoselect allowed: ${result}`);
            return result;
        } catch (error) {
            console.error('Error checking autoselect permission:', error);
            return false;
        }
    };

    console.log('🎯 Global autoselect control function registered');
})();
