/**
 * Payment Step Functionality
 * Handles payment option selection and validation
 */


console.log('🚀 Payment-step.js script loading...');

(function() {
    'use strict';

    console.log('🔧 IIFE executing...');

    // Verificar que jQuery esté disponible
    if (typeof jQuery === 'undefined') {
        console.error('❌ PaymentStepHandler: jQuery no está disponible');
        return;
    } else {
        console.log('✅ jQuery available');
    }

    if (typeof window.paymentStepHandler === 'undefined') {

        class PaymentStepHandler {
            constructor() {
                this.config = {
                    selectors: {
                        paymentOption: '.payment_option_select',
                        termsCheckbox: '.conditions_to_approve',
                        confirmButton: '#paymentConfirm',
                        alertDanger: '[data-alert="danger"]'
                    }
                };

                this.init();
            }

            init() {
                this.bindEvents();
            }

            bindEvents() {
                $(document).on('click.step-payment', '#paymentConfirm', this.handlePaymentConfirm.bind(this));
                $(document).on('change.step-payment', '.payment_option_select', this.handlePaymentSelection.bind(this));
                $(document).on('change.step-payment', '.conditions_to_approve', this.validateCheckoutConditions.bind(this));

                // GTM tracking on Next button for payment step
                $(document).on('click.step-payment', '.step-checkout-payment .next, .step-checkout-payment button[type="submit"]', this.handlePaymentNextClick.bind(this));

                // Handle clicks on payment option containers (labels y divs)
                $(document).on('click.step-payment', '.payment-option-item, .payment-option, .form-check-label', this.handlePaymentContainerClick.bind(this));

                // Initialize payment forms on load
                $(document).ready(() => {
                    this.initializePaymentForms();
                    this.validateCheckoutConditions(); // importante
                });

                // Initialize on window load as well
                $(window).on('load.step-payment', () => {
                    setTimeout(() => {
                        this.initializePaymentForms();
                        this.validateCheckoutConditions(); // importante
                    }, 100);
                });
            }

            /**
             * Handles payment method selection and triggers GTM tracking
             * @param {Event} e - Change event
             */
            handlePaymentSelection(e) {
                const $selectedOption = $(e.currentTarget);
                if ($selectedOption.is(':checked')) {
                    const paymentMethod = this.extractPaymentMethodName($selectedOption);

                    console.log('💳 Payment method selected:', paymentMethod);

                    // Store selected payment method for later GTM tracking (no tracking here)
                    this.selectedPaymentMethod = paymentMethod;

                    // Uncheck terms checkbox when payment method changes
                    $('.conditions_to_approve').prop('checked', false);
                    console.log('🔄 Terms checkbox unchecked due to payment method change (PaymentStepHandler)');

                    // Handle payment option change (show/hide forms)
                    this.handlePaymentOptionChange();

                    // Trigger PayPal Tools functions to update button states
                    this.updatePayPalButtonStates();
                }

                // Continue with original validation
                this.validateCheckoutConditions();
            }

            /**
             * Handles clicks on payment option containers (labels and divs)
             * @param {Event} e - Click event
             */
            handlePaymentContainerClick(e) {
                // Buscar el radio button asociado
                const $radio = $(e.currentTarget).find('.payment_option_select').first();
                let $targetRadio = $radio;

                // Si no se encuentra en el elemento clickeado, buscar por ID del label
                if (!$targetRadio.length && $(e.currentTarget).is('label')) {
                    const forId = $(e.currentTarget).attr('for');
                    if (forId) {
                        $targetRadio = $('#' + forId);
                    }
                }

                if ($targetRadio.length && !$targetRadio.is(':checked')) {
                    console.log('🖱️ Payment option clicked via container');
                    $targetRadio.prop('checked', true);

                    // Trigger change event
                    $targetRadio.trigger('change');
                }
            }

            /**
             * Checks if a payment option should always be visible (like PayPal BNPL)
             * @param {string} paymentId - The payment option ID
             * @returns {boolean}
             */
            isAlwaysVisiblePayment(paymentId) {
                // Get the module name from the radio button
                const $radio = $('#' + paymentId);
                const moduleName = $radio.attr('data-module-name');

                // PayPal modules that should always be visible
                const alwaysVisibleModules = [
                    'paypal_bnpl',
                    'paypal',
                    'paypal_official'
                ];

                return alwaysVisibleModules.includes(moduleName);
            }

            /**
             * Handles payment option change - shows/hides forms and additional info
             */
            handlePaymentOptionChange() {
                // Obtener la opción seleccionada
                const $selectedOption = $('.payment_option_select:checked');

                if ($selectedOption.length) {
                    const selectedId = $selectedOption.attr('id');
                    console.log('💳 Payment option selected:', selectedId);

                    // Ocultar todos los additional-information (INCLUYENDO PayPal)
                    // Solo se mostrarán cuando se seleccione específicamente ese método de pago
                    $('.js-additional-info').each((index, element) => {
                        const $element = $(element);
                        $element.addClass('d-none');
                        console.log('🙈 Hiding additional-info for payment change:', $element.attr('id'));
                    });

                    // Ocultar todos los formularios de pago (excepto PayPal)
                    $('.js-payment-option-form').each((index, element) => {
                        const $element = $(element);
                        const elementId = $element.attr('id');

                        if (elementId) {
                            // Extract payment ID from form ID
                            const paymentId = elementId.replace('pay-with-', '').replace('-form', '');

                            if (this.isAlwaysVisiblePayment(paymentId)) {
                                console.log('💳 Keeping PayPal form visible:', paymentId);
                                return; // Skip hiding PayPal
                            }
                        }

                        $element.addClass('d-none');
                    });

                    // Mostrar solo el del payment option seleccionado
                    const $targetInfo = $('#' + selectedId + '-additional-information');
                    if ($targetInfo.length) {
                        $targetInfo.removeClass('d-none');
                        console.log('✅ Showing additional info for:', selectedId);
                    } else {
                        console.log('ℹ️ No additional info found for:', selectedId);
                    }

                    // Mostrar el formulario de pago correspondiente
                    const $targetForm = $('#pay-with-' + selectedId + '-form');
                    if ($targetForm.length) {
                        $targetForm.removeClass('d-none');
                        console.log('✅ Showing payment form for:', selectedId);

                        // Trigger custom event for payment form shown (para HiPay y otros módulos)
                        $targetForm.trigger('payment-form-shown', [selectedId]);
                    } else {
                        console.log('ℹ️ No payment form found for:', selectedId);
                    }
                } else {
                    // Si no hay opción seleccionada, ocultar todos los formularios (excepto PayPal)
                    $('.js-payment-option-form').each((index, element) => {
                        const $element = $(element);
                        const elementId = $element.attr('id');

                        if (elementId) {
                            const paymentId = elementId.replace('pay-with-', '').replace('-form', '');

                            if (this.isAlwaysVisiblePayment(paymentId)) {
                                return; // Skip hiding PayPal
                            }
                        }

                        $element.addClass('d-none');
                    });

                    // Ocultar TODOS los additional-information cuando no hay selección
                    $('.js-additional-info').each((index, element) => {
                        const $element = $(element);
                        $element.addClass('d-none');
                    });

                    console.log('ℹ️ No payment option selected, hiding all forms (except PayPal)');
                }
            }

            /**
             * Initialize payment forms on page load
             */
            initializePaymentForms() {
                console.log('🎯 Initializing payment forms');

                // Hide ALL additional-information elements initially (including PayPal)
                // They should only be shown when payment method is selected
                $('.js-additional-info').each((index, element) => {
                    const $element = $(element);
                    $element.addClass('d-none');
                    console.log('🙈 Hiding additional-info on load:', $element.attr('id'));
                });

                // For payment forms, keep PayPal forms visible but hide others
                $('.js-payment-option-form').each((index, element) => {
                    const $element = $(element);
                    const elementId = $element.attr('id');

                    if (elementId) {
                        const paymentId = elementId.replace('pay-with-', '').replace('-form', '');

                        if (this.isAlwaysVisiblePayment(paymentId)) {
                            // Keep PayPal payment forms visible (for buttons)
                            $element.removeClass('d-none');
                            $element.show();
                            console.log('💳 Keeping PayPal form visible on load:', paymentId);
                        } else {
                            // Hide non-PayPal payment forms
                            $element.addClass('d-none');
                        }
                    }
                });

                // Check for preselected option
                const $preselected = $('.payment_option_select:checked');
                if ($preselected.length) {
                    console.log('🎯 Found preselected option:', $preselected.attr('id'));
                    this.handlePaymentOptionChange();
                    this.updatePayPalButtonStates();
                } else {
                    console.log('ℹ️ No payment option preselected - all additional-info hidden');
                }
            }

            /**
             * Handles Next button click - Triggers GTM tracking with fresh data
             */
            async handlePaymentNextClick(e) {
                try {
                    // Validate payment selection first
                    const isPaymentSelected = $('.payment_option_select:checked').length > 0;
                    const isTermsAccepted = $('.conditions_to_approve:checked').length > 0;

                    if (!isPaymentSelected || !isTermsAccepted) {
                        console.log('⚠️ Payment validation failed, skipping GTM tracking');
                        return; // Let original validation handle the error
                    }

                    // Get current payment method
                    const $selectedOption = $('.payment_option_select:checked');
                    const paymentMethod = this.extractPaymentMethodName($selectedOption);

                    console.log('🚀 Payment Next clicked - Triggering GTM with fresh data');

                    // GTM tracking with fresh backend data
                    if (window.GTMCheckoutHelper) {
                        await window.GTMCheckoutHelper.trackPaymentSelection($selectedOption[0]);
                    } else if (window.GTMCheckoutHelper) {
                        // Fallback using trackCheckoutEvent with minimal options
                        await window.GTMCheckoutHelper.trackCheckoutEvent('add_payment_info', {
                            options: {
                                checkout_step: '3',
                                payment_type: paymentMethod
                            }
                        });
                    }

                    console.log('✅ GTM payment tracking completed');

                } catch (error) {
                    console.error('❌ Error in payment Next GTM tracking:', error);
                }
            }

            /**
             * Extracts payment method name from selected option
             */
            extractPaymentMethodName($option) {
                // Try to get from label
                const labelFor = $option.attr('id');
                if (labelFor) {
                    const $label = $(`label[for="${labelFor}"]`);
                    if ($label.length) {
                        return $label.text().trim();
                    }
                }

                // Try to get from data attributes
                const moduleName = $option.data('module-name') || $option.attr('data-module-name');
                if (moduleName) {
                    const methodMapping = {
                        'ps_wirepayment': 'Transferencia bancaria',
                        'ps_cashondelivery': 'Pago contra reembolso',
                        'paypal': 'PayPal',
                        'credit_card': 'Tarjeta de crédito',
                        'sequra': 'Sequra',
                        'banlendismart': 'Financiación'
                    };
                    return methodMapping[moduleName] || moduleName;
                }

                // Fallback to value
                return $option.val() || 'Método de pago desconocido';
            }


            /**
             * Updates PayPal button states when payment method changes
             * Triggers PayPal Tools functions to enable/disable buttons properly
             */
            updatePayPalButtonStates() {
                console.log('💳 Updating PayPal button states');

                // Trigger PayPal Tools functions if they exist
                if (window.PaypalTools) {
                    // Call showElementsIfChecked to update visibility based on current selection
                    window.PaypalTools.showElementsIfChecked();

                    // Call hideElementTillChecked to update element states
                    window.PaypalTools.hideElementTillChecked();

                    console.log('✅ PayPal Tools functions triggered');
                }

                // Trigger Shortcut Tools functions for standard PayPal if they exist
                if (window.Shortcut && typeof window.Shortcut.showElementsIfChecked === 'function') {
                    window.Shortcut.showElementsIfChecked();
                    console.log('✅ PayPal Shortcut Tools functions triggered');
                }

                // Also handle terms checkbox state for PayPal buttons
                const isTermsAccepted = $('.conditions_to_approve:checked').length > 0;
                const $selectedPayment = $('.payment_option_select:checked');

                if ($selectedPayment.length) {
                    const moduleName = $selectedPayment.attr('data-module-name');
                    const isPayPalPayment = ['paypal_bnpl', 'paypal', 'paypal_official'].includes(moduleName);

                    if (isPayPalPayment) {
                        // Find PayPal button containers and enable/disable based on terms
                        // Handle both attribute styles: [paypal-button-container] and [paypal-button-container=""]
                        $('[paypal-bnpl-button-container], [paypal-button-container]').each((index, container) => {
                            const $container = $(container);

                            // Use PaypalTools if available
                            if (window.PaypalTools) {
                                if (isTermsAccepted) {
                                    window.PaypalTools.enable(container);
                                } else {
                                    window.PaypalTools.disable(container);
                                }
                            } else {
                                // Fallback: directly manipulate styles
                                if (isTermsAccepted) {
                                    $container.css({
                                        'pointer-events': '',
                                        'opacity': '1',
                                        'display': 'block'
                                    });
                                } else {
                                    $container.css({
                                        'pointer-events': 'none',
                                        'opacity': '0.5'
                                    });
                                }
                            }
                        });

                        // Also try to find containers by exact attribute match (empty value)
                        $('div[paypal-button-container=""], div[paypal-bnpl-button-container=""]').each((index, container) => {
                            const $container = $(container);

                            if (window.PaypalTools) {
                                if (isTermsAccepted) {
                                    window.PaypalTools.enable(container);
                                } else {
                                    window.PaypalTools.disable(container);
                                }
                            } else {
                                // Fallback: directly manipulate styles
                                if (isTermsAccepted) {
                                    $container.css({
                                        'pointer-events': '',
                                        'opacity': '1',
                                        'display': 'block'
                                    });
                                } else {
                                    $container.css({
                                        'pointer-events': 'none',
                                        'opacity': '0.5'
                                    });
                                }
                            }
                        });

                        console.log(`💳 PayPal payment ${moduleName} - Terms accepted: ${isTermsAccepted}`);
                    }
                }
            }

            getIsFreeOrder() {
                const $confirmBtn = $('#paymentConfirm');
                const dataIsFree = $confirmBtn.data('isFree') || $confirmBtn.data('is-free');
                const attrIsFree = $confirmBtn.attr('data-is-free');

                // Prioritize data attribute, then fallback to HTML attribute
                const isFreeValue = typeof dataIsFree !== 'undefined' ? dataIsFree : attrIsFree;

                // Convert to boolean - handle both string and number values
                const isFromAttribute = String(isFreeValue) === '1' || isFreeValue === 1 || isFreeValue === true;

                // PrestaShop standard: if payment-options are hidden via CSS, it's a free order
                const paymentOptionsHidden = $('.payment-options').hasClass('d-none') ||
                    $('.payment-options').hasClass('hidden-xs-up') ||
                    !$('.payment-options').is(':visible');

                return isFromAttribute || paymentOptionsHidden;
            }


            /**
             * Validates if both payment option and terms checkbox are selected
             * Updates UI accordingly
             * Now supports free orders (no payment method required)
             */
            validateCheckoutConditions() {
                console.log('✅ validateCheckoutConditions initialized');

                const $confirmBtn = $('#paymentConfirm');
                const isFreeOrder = this.getIsFreeOrder();

                const hasPaymentOptions = $('.payment_option_select').length > 0;
                const isPaymentSelected = $('.payment_option_select:checked').length > 0;
                const isTermsAccepted   = $('.conditions_to_approve:checked').length > 0;

                console.log('🔍 Debug validation state:', {
                    isFreeOrder,
                    hasPaymentOptions,
                    isPaymentSelected,
                    isTermsAccepted,
                    buttonDataAttr: $confirmBtn.attr('data-is-free'),
                    buttonDataProp: $confirmBtn.data('is-free')
                });

                // Caso duro: no hay métodos y NO es free -> bloquear
                if (!isFreeOrder && !hasPaymentOptions) {
                    console.warn('⛔ No hay métodos de pago y no es pedido free: bloquear');
                    $('[data-alert="danger"]').removeClass('d-none');
                    $confirmBtn.prop('disabled', true);
                    this.updatePayPalButtonStates();
                    return;
                }

                const isValid = isFreeOrder ? isTermsAccepted : (isPaymentSelected && isTermsAccepted);

                console.log('🔍 Validation result:', { isValid, isFreeOrder, isTermsAccepted, isPaymentSelected });

                if (isValid) {
                    $('[data-alert="danger"]').addClass('d-none');
                    $confirmBtn.prop('disabled', false);
                    $confirmBtn.removeClass('disabled'); // Remove CSS disabled class
                    console.log('✅ Payment validation passed');
                } else {
                    $('[data-alert="danger"]').removeClass('d-none');
                    $confirmBtn.prop('disabled', true);
                    $confirmBtn.addClass('disabled'); // Add CSS disabled class
                    console.log('❌ Payment validation failed');
                }

                this.updatePayPalButtonStates();
            }


            /**
             * Handles payment confirmation button click
             * Prevents form submission if conditions are not met
             * Now supports free orders (no payment method required)
             * @param {Event} e - Click event
             */
            handlePaymentConfirm(e) {

                console.log('✅ handlePaymentConfirm initialized');

                const $confirmBtn = $('#paymentConfirm');
                const isFreeOrder = this.getIsFreeOrder(); // <-- usa SIEMPRE el helper

                const isPaymentSelected = $('.payment_option_select:checked').length > 0;
                const isTermsAccepted   = $('.conditions_to_approve:checked').length > 0;
                const selectedPaymentMethod = $('.payment_option_select:checked').attr('data-module-name');

                console.log('💰 Payment confirmation validation:', {
                    isPaymentSelected,
                    isTermsAccepted,
                    isFreeOrder,
                    selectedPaymentMethod
                });

                const isValid = isFreeOrder ? isTermsAccepted : (isPaymentSelected && isTermsAccepted);

                if (!isValid) {
                    e.preventDefault();
                    e.stopPropagation();
                    $('[data-alert="danger"]').removeClass('d-none');
                    $confirmBtn.prop('disabled', true);
                    $confirmBtn.addClass('disabled'); // Add CSS disabled class
                    console.log(isFreeOrder ? '❌ Free order validation failed - terms not accepted'
                        : '❌ Paid order validation failed - missing payment method or terms');
                    return false;
                }

                if (isValid) {
                    $('[data-alert="danger"]').addClass('d-none');
                    $confirmBtn.prop('disabled', false);
                    $confirmBtn.removeClass('disabled'); // Remove CSS disabled class

                    // 🔧 GTM: add_payment_info on confirm (solo una vez)
                    try {
                        const $selected = $('.payment_option_select:checked');
                        const $confirmBtn = $('#paymentConfirm');

                        if ($selected.length && !$confirmBtn.data('gtmPaymentTracked')) {
                            console.log('🚀 Payment confirm - Triggering add_payment_info with fresh data');
                            if (window.GTMCheckoutHelper && typeof window.GTMCheckoutHelper.trackPaymentSelection === 'function') {
                                window.GTMCheckoutHelper.trackPaymentSelection($selected[0]); // incluye sync + push
                            }
                            $confirmBtn.data('gtmPaymentTracked', true);
                            console.log('✅ GTM add_payment_info sent before payment submit');
                        }
                    } catch (err) {
                        console.warn('⚠️ GTM add_payment_info tracking failed on confirm:', err);
                    }

                    if (!isFreeOrder && selectedPaymentMethod === 'credit_card') {
                        // Special handling for HiPay credit card payments
                        console.log('🏦 HiPay credit card selected - triggering tokenizer form');

                        // Check if HiPay form exists
                        const $hipayForm = $('#tokenizerForm');
                        if ($hipayForm.length) {
                            console.log('🏦 HiPay tokenizerForm found - triggering submit');
                            e.preventDefault();
                            e.stopPropagation();

                            // Ensure HiPay knows the payment method is selected
                            if (typeof window.myPaymentMethodSelected !== 'undefined') {
                                window.myPaymentMethodSelected = true;
                                console.log('🏦 Set myPaymentMethodSelected = true');
                            }

                            // Trigger HiPay form submission
                            $hipayForm.trigger('submit');
                            return false;
                        } else {
                            console.warn('⚠️ HiPay tokenizerForm not found - proceeding with normal flow');
                        }
                    }

                    // Normal flow for other payment methods or free orders
                    if (isFreeOrder) {
                        console.log('🆓 Free order - proceeding with terms validation only');

                        // Find the free order payment option dynamically
                        const $freeOrderRadio = $('input[data-module-name="free_order"]').first();
                        if ($freeOrderRadio.length) {
                            const freeOrderId = $freeOrderRadio.attr('id');
                            const $freeOrderButton = $('#pay-with-' + freeOrderId);

                            if ($freeOrderButton.length) {
                                console.log('📤 Clicking native free order button:', freeOrderId);
                                $freeOrderButton.click();
                            } else {
                                console.warn('⚠️ Free order button not found for:', freeOrderId);
                                // Fallback: submit the form directly
                                const $freeOrderForm = $('#pay-with-' + freeOrderId + '-form form').first();
                                if ($freeOrderForm.length) {
                                    console.log('📤 Submitting free order form directly');
                                    $freeOrderForm.submit();
                                }
                            }
                        } else {
                            console.warn('⚠️ Free order radio button not found');
                        }
                    } else {
                        console.log('💳 Normal payment method selected:', selectedPaymentMethod);
                    }
                } else {
                    e.preventDefault();
                    e.stopPropagation();
                    $('[data-alert="danger"]').removeClass('d-none');
                    $('#paymentConfirm').prop('disabled', true);

                    if (isFreeOrder) {
                        console.log('❌ Free order validation failed - terms not accepted');
                    } else {
                        console.log('❌ Paid order validation failed - missing payment method or terms');
                    }
                }
            }
        }


        window.PaymentStepHandler = PaymentStepHandler;

        if (!(window.paymentStepHandler instanceof PaymentStepHandler)) {
            window.paymentStepHandler = new PaymentStepHandler(window.checkoutManager);
        }
        console.log('📤 PaymentStepHandler instance ready');

        window.initPaymentStep = function () {
            console.log('🔧 Global initPaymentStep called');
            if (!(window.paymentStepHandler instanceof PaymentStepHandler)) {
                window.paymentStepHandler = new PaymentStepHandler(window.checkoutManager);
            }
            if (!window.paymentStepHandler.initialized) {
                window.paymentStepHandler.init();
            }
        };


    } // End of guard condition
})();