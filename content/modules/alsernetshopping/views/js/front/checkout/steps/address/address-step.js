/**
 * Address Step Handler
 *
 * Manages delivery and invoice address functionality in the checkout process
 * Integrates with existing initAddressStep() and handleAddressStep() functions
 */
console.log('🚀 address-step.js script loading...');

(function() {
    'use strict';

    console.log('🔧 Address IIFE executing...');

    // Prevent redeclaration if class already exists
    if (typeof window.addressStepHandler === 'undefined') {

        class AddressStepHandler {

            constructor(checkoutManager) {
                this.manager = checkoutManager;
                this.initialized = false;
                this.selectedAddressDeliveryId = null;
                this.selectedAddressInvoiceId = null;
                this.selectedAddressType = 'delivery'; // Mantener para compatibilidad
                this.isShowingModal = false;
                this._submissionInProgress = false; // Prevent double submissions

                // NEW: flags anti-duplicados
                this._addAddressSubmitting = false;
                this._editAddressSubmitting = false;
                this._loadAddressesInFlight = false;
                this._openingAddForm = false;

                // Inicializar estrategias de modal si están disponibles
                if (typeof window.ModalStrategyContext === 'function') {
                    this.modalStrategies = new window.ModalStrategyContext();
                } else {
                    console.warn('⚠️ ModalStrategyContext not available, using server-side validation');
                    this.modalStrategies = null;
                }

                // Inicializar sistema de mensajes de validación
                this.initializeValidationMessages();

                // Configuration from existing implementation
                this.config = {
                    needInvoice: null,
                    vatField: null,
                    vatLabel: null,
                    useSameAddress: null,
                    optionsInvoice: null,
                    invoiceAddress: null,
                    addressRadio: null,
                    newInvoice: null,
                    buttonAddress: null,
                    fieldsInvoice: null,
                    isCheckedFromData: false,
                    isMandatory: false,
                    showOrderInvoiceAddress: false
                };
            }

            // Helper function to safely format messages
            formatMessage(message) {
                if (typeof $ !== 'undefined' && $.validator && $.validator.format) {
                    return $.validator.format(message);
                } else {
                    // Fallback: simple string replacement for single parameter
                    return function(value) {
                        return message.replace('{0}', value);
                    };
                }
            }

            initializeValidationMessages() {
                // Sistema de mensajes multiidioma para validaciones
                this.validationMessages = {
                    en: {
                        required: "This field is required.",
                        remote: "Please fix this field.",
                        email: "Please enter a valid email address.",
                        url: "Please enter a valid URL.",
                        date: "Please enter a valid date.",
                        dateISO: "Please enter a valid date (ISO).",
                        number: "Please enter a valid number.",
                        digits: "Please enter only digits.",
                        equalTo: "Please enter the same value again.",
                        maxlength: this.formatMessage("Please enter no more than {0} characters."),
                        minlength: this.formatMessage("Please enter at least {0} characters."),
                        rangelength: this.formatMessage("Please enter a value between {0} and {1} characters long."),
                        range: this.formatMessage("Please enter a value between {0} and {1}."),
                        max: this.formatMessage("Please enter a value less than or equal to {0}."),
                        min: this.formatMessage("Please enter a value greater than or equal to {0}."),
                        step: this.formatMessage("Please enter a multiple of {0}.")
                    },
                    es: {
                        required: "Este campo es obligatorio.",
                        remote: "Por favor, rellena este campo.",
                        email: "Por favor, escribe una dirección de correo válida.",
                        url: "Por favor, escribe una URL válida.",
                        date: "Por favor, escribe una fecha válida.",
                        dateISO: "Por favor, escribe una fecha (ISO) válida.",
                        number: "Por favor, escribe un número válido.",
                        digits: "Por favor, escribe sólo dígitos.",
                        creditcard: "Por favor, escribe un número de tarjeta válido.",
                        equalTo: "Por favor, escribe el mismo valor de nuevo.",
                        extension: "Por favor, escribe un valor con una extensión aceptada.",
                        maxlength: this.formatMessage("Por favor, no escribas más de {0} caracteres."),
                        minlength: this.formatMessage("Por favor, no escribas menos de {0} caracteres."),
                        rangelength: this.formatMessage("Por favor, escribe un valor entre {0} y {1} caracteres."),
                        range: this.formatMessage("Por favor, escribe un valor entre {0} y {1}."),
                        max: this.formatMessage("Por favor, escribe un valor menor o igual a {0}."),
                        min: this.formatMessage("Por favor, escribe un valor mayor o igual a {0}."),
                        nifES: "Por favor, escribe un NIF válido.",
                        nieES: "Por favor, escribe un NIE válido.",
                        cifES: "Por favor, escribe un CIF válido.",
                        lettersOnly: "Solo se permiten letras y espacios.",
                        numbersOnly: "Solo se permiten números.",
                        phoneNumbers: "Solo se permiten números y caracteres válidos (+, -, (, ))."
                    },
                    fr: {
                        required: "Ce champ est obligatoire.",
                        remote: "Veuillez corriger ce champ.",
                        email: "Veuillez fournir une adresse électronique valide.",
                        url: "Veuillez fournir une adresse URL valide.",
                        date: "Veuillez fournir une date valide.",
                        dateISO: "Veuillez fournir une date valide (ISO).",
                        number: "Veuillez fournir un numéro valide.",
                        digits: "Veuillez fournir seulement des chiffres.",
                        lettersOnly: "Veuillez fournir seulement des lettres.",
                        numbersOnly: "Veuillez fournir seulement des chiffres.",
                        phoneNumbers: "Veuillez fournir un numéro de téléphone valide."
                    },
                    pt: {
                        required: "Campo de preenchimento obrigatório.",
                        remote: "Por favor, corrija este campo.",
                        email: "Por favor, introduza um endereço eletrónico válido.",
                        url: "Por favor, introduza um URL válido.",
                        date: "Por favor, introduza uma data válida.",
                        dateISO: "Por favor, introduza uma data válida (ISO).",
                        number: "Por favor, introduza um número válido.",
                        digits: "Por favor, introduza apenas dígitos.",
                        lettersOnly: "Por favor, introduza apenas letras.",
                        numbersOnly: "Por favor, introduza apenas números.",
                        phoneNumbers: "Por favor, introduza um número de telefone válido."
                    }
                };

                // Obtener idioma actual
                this.currentLanguage = this.getCurrentLanguage();
                console.log('🌐 Current language detected:', this.currentLanguage);
            }

            getCurrentLanguage() {
                // Intentar obtener el idioma de diferentes fuentes
                if (window.checkoutNavigator?.getISO) {
                    return window.checkoutNavigator.getISO();
                }
                if (typeof iso !== 'undefined') {
                    return iso;
                }
                if (document.documentElement.lang) {
                    return document.documentElement.lang.substring(0, 2);
                }
                // Default fallback
                return 'es';
            }

            init() {
                if (this.initialized) return;

                this.initializeElements();
                this.bindEvents();
                this.setupJQueryValidation();
                this.initializeInvoiceOptions();
                this.loadCurrentAddressSelection(); // 🆕 Leer direcciones actuales
                this.checkAddressesAvailability(); // 🆕 Validar direcciones disponibles
                this.initialized = true;

                console.log('✅ Address step initialized');
            }

            initializeElements() {
                this.refreshElements(); // <- IMPORTANTE

                const $form = $('.step-checkout-address');
                this.config.needInvoice = $('#need_invoice');
                this.config.vatField = $('#field-vat_number');
                this.config.vatLabel = $('label[for="field-vat_number"]');
                this.config.useSameAddress = $('#use_same_address');
                this.config.optionsInvoice = $('.invoice-options');
                this.config.invoiceAddress = $('.invoice-addresses');
                this.config.addressRadio = $('input[name="address_invoice"]');
                this.config.newInvoice = $('.new-invoice');
                this.config.buttonAddress = $('.next');
                this.config.buttonPrevious = $('.previous');
                this.config.fieldsInvoice = $('.new-invoice .fields-wrapper');

                // Get configuration from data attributes
                this.config.isCheckedFromData = this.config.needInvoice.data('invoice-checked') == 1;
                this.config.isMandatory = this.config.needInvoice.data('invoice-mandatory') == 1;
                this.config.showOrderInvoiceAddress = $form.data('show-invoice-address') == 1;

                if (!this.config.isMandatory) {
                    const enabled = this.config.isCheckedFromData || this.config.needInvoice.is(':checked');
                    this.toggleInvoiceOptions(!!enabled);
                }
            }

            refreshElements() {
                const $form = $('.step-checkout-address');

                this.config.needInvoice    = $('#need_invoice');
                this.config.vatField       = $('#field-vat_number');
                this.config.vatLabel       = $('label[for="field-vat_number"]');
                this.config.useSameAddress = $('#use_same_address');

                this.config.optionsInvoice = $('.invoice-options');
                this.config.invoiceAddress = $('.invoice-addresses');

                // ⚠️ corregir posible typo en el name: 'address_invoice' (no 'address_invoide')
                this.config.addressRadio   = $('input[name="address_invoice"]');
                this.config.newInvoice     = $('.new-invoice');
                this.config.buttonAddress  = $('.next');
                this.config.buttonPrevious = $('.previous');
                this.config.fieldsInvoice  = $('.new-invoice .fields-wrapper');

                // re-lee flags desde data-attributes (por si re-render)
                if (this.config.needInvoice && this.config.needInvoice.length) {
                    this.config.isCheckedFromData       = +this.config.needInvoice.data('invoice-checked') === 1;
                    this.config.isMandatory             = +this.config.needInvoice.data('invoice-mandatory') === 1;
                    this.config.showOrderInvoiceAddress = +$form.data('show-invoice-address') === 1;
                }

                console.log('🔄 Elements refreshed:', {
                    needInvoiceExists: !!this.config.needInvoice?.length,
                    needInvoiceChecked: this.config.needInvoice?.is(':checked'),
                    formExists: !!$form.length
                });

                // 🆕 Rebind events after DOM refresh
                this.rebindCriticalEvents();
            }

            rebindCriticalEvents() {
                console.log('🔗 Rebinding critical events after DOM refresh');

                // Rebind need_invoice toggle - CRÍTICO para navegación step-to-step
                $(document).off('change.addressStepRefresh change.addressStep', '#need_invoice');
                $(document).off('change.addressInvoiceHandler', '#need_invoice');
                $(document).off('change', '#need_invoice');
                $(document).on('change.addressInvoiceHandler', '#need_invoice', (e) => {
                    console.log('🧾 need_invoice CHANGE event triggered');
                    console.log('🧾 Checkbox state:', $(e.target).is(':checked'));
                    console.log('🧾 Event details:', e);
                    if (typeof this.handleInvoiceToggle === 'function') {
                        this.handleInvoiceToggle(e);
                    }
                });

                // Handle click on label to simulate checkbox click
                $(document).off('click.invoiceLabelHandler', '.form-need_invoice');
                $(document).on('click.invoiceLabelHandler', '.form-need_invoice', (e) => {
                    console.log('🖱️ LABEL CLICKED - simulating checkbox click');
                    e.preventDefault();
                    e.stopPropagation();

                    const $checkbox = $('#need_invoice');
                    if ($checkbox.length) {
                        const currentState = $checkbox.is(':checked');
                        const newState = !currentState;
                        console.log('🖱️ Changing checkbox from', currentState, 'to', newState);
                        $checkbox.prop('checked', newState);
                        $checkbox.trigger('change');
                    }
                });

                // Rebind form submit - CRÍTICO para interceptar submit
                $(document).off('submit.addressStepRefresh', '.step-checkout-address');
                $(document).on('submit.addressStepRefresh', '.step-checkout-address', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🏠 AddressStepHandler intercepted form submit (rebind)');
                    this.handleFormSubmit();
                    return false;
                });

                // Rebind address selection - CRÍTICO para selección de direcciones
                $(document).off('click.addressStepRefresh', '.page-order .address-box-item');
                $(document).on('click.addressStepRefresh', '.page-order .address-box-item', (e) => {
                    if (typeof this.handleAddressSelection === 'function') {
                        this.handleAddressSelection(e);
                    }
                });
            }

            bindEvents() {
                console.log('🔗 AddressStepHandler bindEvents called');
                console.log('📋 this context:', this);
                console.log('📋 handleAddressSelection exists:', typeof this.handleAddressSelection);

                $(document).on('hidden.bs.modal', '.modal', function () {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css('padding-right', '');
                });

                // UNIFIED FORM SUBMISSION
                $(document)
                    .off('submit.addressStepForm', '.step-checkout-address')
                    .on('submit.addressStepForm', '.step-checkout-address', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('🏠 AddressStepHandler intercepted form submit');

                        if (this._submissionInProgress) {
                            console.warn('⚠️ Form submission already in progress, ignoring duplicate');
                            return false;
                        }

                        this.handleFormSubmit();
                        return false;
                    });

                // Limpieza de listeners previos
                $(document)
                    .off('click', '.page-order .address-box-item')
                    .off('click', '.address-selector .address-item')
                    .off('click.checkout-address', '.page-order .address-box-item, .address-selector .address-item')
                    .off('click.addressStep')
                    .off('submit.addressStep')
                    .off('change.addressStep');

                $(document)
                    .off(
                        'click.checkout-address',
                        '.page-order .address-box-item, .page-order .address-box-item .address-radio, .page-order .address-box-item .address-radio .form-check-input'
                    )
                    .on(
                        'click.checkout-address',
                        '.page-order .address-box-item, .page-order .address-box-item .address-radio, .page-order .address-box-item .address-radio .form-check-input',
                        (e) => {
                            try {
                                if (typeof this.handleAddressSelection === 'function') {
                                    this.handleAddressSelection(e);
                                } else {
                                    console.error('❌ handleAddressSelection method not found, this:', this);
                                }
                            } catch (error) {
                                console.error('❌ Error in handleAddressSelection:', error);
                            }
                        }
                    );

                $(document).on('change.addressStep', 'input[name="address_invoice"]', (e) => {
                    if (typeof this.handleInvoiceAddressRadioChange === 'function') this.handleInvoiceAddressRadioChange(e);
                });

                // Logger con namespace (evita duplicados)
                $(document)
                    .off('click.addressLog', '.add-address-checkout')
                    .on('click.addressLog', '.add-address-checkout', (e) => {
                        console.log('🌐 GLOBAL: Any .add-address-checkout clicked!', e.target);
                        console.log('🌐 Element classes:', e.target.className);
                        console.log('🌐 Element parent:', e.target.parentElement);
                        console.log('🌐 Event type:', e.type);
                        console.log('🌐 Current time:', new Date().toLocaleTimeString());
                    });

                setTimeout(() => {
                    const $buttons = $('.add-address-checkout');
                    console.log('🔍 Found .add-address-checkout buttons:', $buttons.length);
                    $buttons.each(function(index) {
                        console.log(`🔍 Button ${index + 1}:`, this);
                        console.log(`🔍 Button ${index + 1} classes:`, this.className);
                        console.log(`🔍 Button ${index + 1} parent:`, this.parentElement?.className);
                    });

                    const $pageOrderButtons = $('.page-order .add-address-checkout');
                    console.log('🔍 Found .page-order .add-address-checkout buttons:', $pageOrderButtons.length);
                }, 1000);

                const checkInterval = setInterval(() => {
                    const $buttons = $('.add-address-checkout');
                    console.log(`🕐 ${new Date().toLocaleTimeString()} - Found .add-address-checkout buttons:`, $buttons.length);
                    if ($buttons.length > 0) {
                        console.log('🎯 FOUND BUTTONS! Current page content:', $('.page-order').length > 0 ? 'page-order exists' : 'page-order missing');
                        $buttons.each(function(index) {
                            console.log(`🔍 Button ${index + 1}:`, this);
                            console.log(`🔍 Button ${index + 1} full selector path:`, $(this).parents().addBack().toArray().map(el => el.tagName + (el.className ? '.' + el.className.split(' ').join('.') : '')).join(' > '));
                        });
                        clearInterval(checkInterval);
                    }
                }, 2000);
                setTimeout(() => clearInterval(checkInterval), 10000);

                $(document).on('click.addressStep', '.page-order .add-address-checkout', (e) => {
                    console.log('🎯 SPECIFIC: Click event fired on .page-order .add-address-checkout', e.target);
                    console.log('🔍 this context:', this);
                    console.log('🔍 this constructor name:', this.constructor.name);
                    console.log('🔍 Available methods:', Object.getOwnPropertyNames(Object.getPrototypeOf(this)));
                    if (typeof this.handleAddAddressButton === 'function') {
                        console.log('✅ handleAddAddressButton method exists, calling it...');
                        this.handleAddAddressButton(e);
                    } else {
                        console.error('❌ handleAddAddressButton method not found!');
                        console.error('❌ this:', this);
                    }
                });

                // Botón unificado para agregar dirección desde modal de no hay direcciones
                $(document)
                    .off('click.addressStep', '.add-new-address')
                    .off('click.noAddressesModal', '.add-new-address')
                    .on('click.addressStep', '.add-new-address', (e) => {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        if (typeof this.handleAddNewAddressFromModal === 'function') this.handleAddNewAddressFromModal(e);
                    });

                // Primary handlers with .page-order context
                $(document).on('click.addressStep', '.page-order .edit-address-checkout', (e) => {
                    console.log('🔧 Edit address button clicked (checkout context)');
                    if (typeof this.handleEditAddressButton === 'function') this.handleEditAddressButton(e);
                });
                $(document).on('click.addressStep', '.page-order .remove-address-checkout', (e) => {
                    console.log('🔧 Remove address button clicked (checkout context)');
                    if (typeof this.handleRemoveAddressButton === 'function') this.handleRemoveAddressButton(e);
                });

                // Fallback handlers without .page-order (in case buttons are outside that context)
                $(document).on('click.addressStep', '.edit-address-checkout', (e) => {
                    // Only handle if not already handled by primary handler
                    if (!$(e.currentTarget).closest('.page-order').length) {
                        console.log('🔧 Edit address button clicked (fallback context)');
                        if (typeof this.handleEditAddressButton === 'function') this.handleEditAddressButton(e);
                    }
                });
                $(document).on('click.addressStep', '.remove-address-checkout', (e) => {
                    // Only handle if not already handled by primary handler
                    if (!$(e.currentTarget).closest('.page-order').length) {
                        console.log('🔧 Remove address button clicked (fallback context)');
                        if (typeof this.handleRemoveAddressButton === 'function') this.handleRemoveAddressButton(e);
                    }
                });
                $(document).on('click.addressStep', '.page-order .delete-confirm-address-checkout', (e) => {
                    if (typeof this.handleDeleteConfirmation === 'function') this.handleDeleteConfirmation(e);
                });

                // 🆕 Address change handlers - moved from CheckoutManager
                $(document).on('click.addressStep', '.change-delivery-invoice', (e) => {
                    if (typeof this.handleChangeDeliveryInvoice === 'function') this.handleChangeDeliveryInvoice(e);
                });
                $(document).on('click.addressStep', '.change-delivery-address', (e) => {
                    if (typeof this.handleChangeDeliveryAddress === 'function') this.handleChangeDeliveryAddress(e);
                });

                // Form submission buttons
                $(document).on('click.addressStep', '.page-order .save-add-address-checkout', (e) => {
                    if (typeof this.handleSaveAddAddress === 'function') this.handleSaveAddAddress(e);
                });
                $(document).on('click.addressStep', '.page-order .save-edit-address-checkout', (e) => {
                    if (typeof this.handleSaveEditAddress === 'function') this.handleSaveEditAddress(e);
                });

                $(document)
                    .off('keydown.addressStepEnter', '.step-checkout-address')
                    .on('keydown.addressStepEnter', '.step-checkout-address', (e) => {
                        if (e.key === 'Enter') {
                            const tag  = (e.target.tagName || '').toLowerCase();
                            const type = (e.target.type || '').toLowerCase();
                            if (tag !== 'textarea' && type !== 'submit' && type !== 'button') {
                                e.preventDefault();
                            }
                        }
                    });

                // Handle click on label to simulate checkbox click (initial binding)
                $(document).off('click.invoiceLabelHandler', '.form-need_invoice');
                $(document).on('click.invoiceLabelHandler', '.form-need_invoice', (e) => {
                    console.log('🖱️ LABEL CLICKED - simulating checkbox click (from bindEvents)');
                    e.preventDefault();
                    e.stopPropagation();

                    const $checkbox = $('#need_invoice');
                    if ($checkbox.length) {
                        const currentState = $checkbox.is(':checked');
                        const newState = !currentState;
                        console.log('🖱️ Changing checkbox from', currentState, 'to', newState);
                        $checkbox.prop('checked', newState);
                        $checkbox.trigger('change');
                    }
                });
            }

            setupJQueryValidation() {
                // Setup main address step form validation
                this.setupMainFormValidation();

                // Dynamic form validations will be setup when forms are loaded
                console.log('📋 jQuery validation setup completed');
            }

            // NEW: util para limpiar por completo los handlers del validator y evitar acumulación
            detachValidator($form) {
                try {
                    $form.removeData('validator');
                    $form.removeData('unobtrusiveValidation');

                    // Descuelga cualquier namespace que haya dejado Validate
                    $form.off('.validate');
                    // Corta submits previos por seguridad
                    $form.off('submit');

                    // Limpia handlers de inputs añadidos por validate
                    $form.find(':input').each(function () {
                        $(this).off('.validate');
                    });
                } catch (e) {
                    console.warn('detachValidator error:', e);
                }
            }

            setupMainFormValidation() {
                const $form = $('.step-checkout-address');

                // Check if jQuery Validate is available
                if (typeof $.fn.validate !== 'function') {
                    console.warn('⚠️ jQuery Validate plugin not available, skipping form validation setup');
                    return;
                }

                // Limpieza total antes de iniciar
                this.detachValidator($form);

                $form.validate({
                    ignore: "",
                    rules: {
                        id_address_delivery: { required: true },
                        id_address_invoice: {
                            required: {
                                depends: () => $('#need_invoice').is(':checked')
                            }
                        },
                        need_invoice: { required: false }
                    },
                    messages: {
                        id_address_delivery: { required: "Selecciona una dirección de envío." },
                        id_address_invoice: { required: "Selecciona una dirección de facturación." }
                    },
                    invalidHandler: (event, validator) => {
                        if (this.isShowingModal) {
                            return;
                        }
                        this.isShowingModal = true;
                        this.cleanupAllModals();

                        const hasDeliveryErr = validator.errorList.some(e => e.element.name === 'id_address_delivery');
                        const hasInvoiceErr  = validator.errorList.some(e => e.element.name === 'id_address_invoice');

                        if (hasDeliveryErr) {
                            setTimeout(() => this.showMissingDeliveryModal(), 120);
                        } else if (hasInvoiceErr) {
                            setTimeout(() => this.showMissingInvoiceModal(), 120);
                        } else {
                            this.isShowingModal = false;
                        }
                    },
                    showErrors: function () {},
                    submitHandler: this.handleFormSubmit.bind(this)
                });
            }

            // Invoice handling methods
            handleInvoiceToggle(event) {
                console.log('🔥 HANDLE INVOICE TOGGLE CALLED');
                console.log('🔥 Event target:', event.currentTarget);
                console.log('🔥 Current checkbox state:', $(event.currentTarget).is(':checked'));

                // Prevenir ejecuciones múltiples simultáneas
                if (this._invoiceToggleInProgress) {
                    console.warn('⚠️ handleInvoiceToggle already in progress, skipping duplicate call');
                    return;
                }
                this._invoiceToggleInProgress = true;

                try {
                    console.log('🔄 Refreshing elements...');
                    this.refreshElements(); // <- IMPORTANTE
                    if (!this.config.isMandatory) {
                        const enabled = $(event.currentTarget).is(':checked');
                        console.log('🔥 Enabled state:', enabled);
                        console.log('🔥 About to call toggleInvoiceOptions with:', enabled);
                        this.toggleInvoiceOptions(enabled);

                        // 🆕 Llamar al backend para actualizar need_invoice
                        this.setNeedInvoiceOnServer(enabled ? 1 : 0);

                        // 🆕 Validar inmediatamente si se activa need_invoice
                        if (enabled) {
                            setTimeout(() => {
                                if (this.modalStrategies && typeof this.modalStrategies.showApropriateModal === 'function') {
                                    this.modalStrategies.showApropriateModal();
                                } else {
                                    this.validateInvoiceAddressRequired();
                                }
                            }, 100);
                        }
                    }
                } finally {
                    setTimeout(() => { this._invoiceToggleInProgress = false; }, 200);
                }
            }

            toggleInvoiceOptions(enabled) {
                console.log('💼 TOGGLE INVOICE OPTIONS CALLED with enabled:', enabled);
                console.log('💼 Elements before refresh:');
                console.log('  - optionsInvoice exists:', !!this.config.optionsInvoice?.length);
                console.log('  - invoiceAddress exists:', !!this.config.invoiceAddress?.length);
                console.log('  - optionsInvoice classes:', this.config.optionsInvoice?.attr('class'));
                console.log('  - invoiceAddress classes:', this.config.invoiceAddress?.attr('class'));

                this.refreshElements(); // <- asegura nodos vivos

                console.log('💼 Elements after refresh:');
                console.log('  - optionsInvoice exists:', !!this.config.optionsInvoice?.length);
                console.log('  - invoiceAddress exists:', !!this.config.invoiceAddress?.length);

                this.config.vatField?.prop('required', enabled);
                this.config.vatLabel?.toggleClass('required', enabled);
                this.config.useSameAddress?.prop('checked', !enabled);

                console.log('💼 About to toggle classes. Current state:');
                console.log('  - optionsInvoice classes BEFORE:', this.config.optionsInvoice?.attr('class'));
                console.log('  - invoiceAddress classes BEFORE:', this.config.invoiceAddress?.attr('class'));

                this.config.optionsInvoice?.toggleClass('d-none', !enabled);
                this.config.invoiceAddress?.toggleClass('d-none', !enabled);

                console.log('💼 After toggling classes:');
                console.log('  - optionsInvoice classes AFTER:', this.config.optionsInvoice?.attr('class'));
                console.log('  - invoiceAddress classes AFTER:', this.config.invoiceAddress?.attr('class'));

                console.log('💼 Invoice options toggled:', {
                    enabled,
                    vatFieldExists: !!this.config.vatField?.length,
                    vatFieldValue: this.config.vatField?.val(),
                    vatFieldRequired: this.config.vatField?.prop('required')
                });
            }

            validateInvoiceAddressRequired() {
                console.log('🧾 Validando dirección de facturación requerida');

                if (this.isShowingModal) {
                    console.log('⏳ Modal ya mostrándose, saltando validación');
                    return;
                }

                const $form = $('.step-checkout-address');

                const invoiceSelected =
                    $form.find('input[name="id_address_invoice"]:checked').val() ||
                    $form.find('input[name="id_address_invoice"]').val();

                const vatValue = this.config.vatField?.val()?.trim() || '';

                console.log('📋 Validación de facturación:', {
                    invoiceSelected,
                    vatFieldExists: !!this.config.vatField?.length,
                    vatValue,
                    vatRequired: this.config.vatField?.prop('required')
                });

                if (!invoiceSelected) {
                    console.warn('⚠️ Facturación activada pero sin dirección seleccionada');
                    this.showMissingInvoiceModalImmediate();
                } else if (this.config.vatField?.length && this.config.vatField.prop('required') && !vatValue) {
                    console.warn('⚠️ VAT number requerido pero vacío');
                    this.config.vatField.focus();
                } else {
                    console.log('✅ Dirección de facturación y VAT validados correctamente');
                }
            }

            showMissingInvoiceModalImmediate() {
                if (this.isShowingModal) return;

                this.isShowingModal = true;
                this.cleanupAllModals();

                console.log('🚨 Mostrando modal de facturación faltante (activación inmediata)');

                setTimeout(() => {
                    const $modal = $('.missing-invoice-address');
                    if ($modal.length && !$modal.hasClass('show')) {
                        $modal.modal('show');
                        $modal.off('hidden.bs.modal.invoiceFlag').on('hidden.bs.modal.invoiceFlag', () => {
                            this.isShowingModal = false;
                        });
                    } else {
                        this.isShowingModal = false;
                    }
                }, 120);
            }

            initializeInvoiceOptions() {
                const forceEnable = this.config.isCheckedFromData || this.config.isMandatory;
                this.config.needInvoice.prop('checked', forceEnable);
                this.config.needInvoice.prop('disabled', this.config.isMandatory);
                this.toggleInvoiceOptions(forceEnable);
            }

            loadCurrentAddressSelection() {
                console.log('📋 Loading current address selection from DOM and Smarty variables');

                const $form = $('.step-checkout-address');

                let deliverySelected = null;
                let invoiceSelected = null;

                if (typeof window.current_delivery_address !== 'undefined' && window.current_delivery_address) {
                    deliverySelected = window.current_delivery_address;
                    console.log(`🔍 Smarty current_delivery_address found: ${deliverySelected}`);
                } else if (typeof current_delivery_address !== 'undefined' && current_delivery_address) {
                    deliverySelected = current_delivery_address;
                    console.log(`🔍 Global current_delivery_address found: ${deliverySelected}`);
                }

                if (typeof window.current_invoice_address !== 'undefined' && window.current_invoice_address) {
                    invoiceSelected = window.current_invoice_address;
                    console.log(`🔍 Smarty current_invoice_address found: ${invoiceSelected}`);
                } else if (typeof current_invoice_address !== 'undefined' && current_invoice_address) {
                    invoiceSelected = current_invoice_address;
                    console.log(`🔍 Global current_invoice_address found: ${invoiceSelected}`);
                }

                if (!deliverySelected) {
                    deliverySelected =
                        $form.find('input[name="id_address_delivery"]:checked').val() ||
                        $form.find('input[name="id_address_delivery"]').val() ||
                        $form.find('.address-box-item[data-type="delivery"].selected').data('id-address');

                    if (deliverySelected) {
                        console.log(`🔍 DOM delivery address found: ${deliverySelected}`);
                    }
                }

                if (!invoiceSelected) {
                    invoiceSelected =
                        $form.find('input[name="id_address_invoice"]:checked').val() ||
                        $form.find('input[name="id_address_invoice"]').val() ||
                        $form.find('.address-box-item[data-type="invoice"].selected').data('id-address');

                    if (invoiceSelected) {
                        console.log(`🔍 DOM invoice address found: ${invoiceSelected}`);
                    }
                }

                if (deliverySelected) {
                    this.selectedAddressDeliveryId = deliverySelected;
                    console.log(`✅ Current delivery address loaded: ${deliverySelected}`);
                }

                if (invoiceSelected) {
                    this.selectedAddressInvoiceId = invoiceSelected;
                    console.log(`✅ Current invoice address loaded: ${invoiceSelected}`);
                }

                console.log(`📋 Address selection summary:`, {
                    delivery: this.selectedAddressDeliveryId,
                    invoice: this.selectedAddressInvoiceId,
                    sources: {
                        delivery: deliverySelected ? 'backend/DOM' : 'not found',
                        invoice: invoiceSelected ? 'backend/DOM' : 'not found'
                    }
                });
            }

            checkAddressesAvailability() {
                console.log('🔍 Checking addresses availability...');

                if (this.hasBackendValidationRun()) {
                    console.log('⏭️ Backend validation already handled address validation, skipping frontend check');
                    return;
                }

                setTimeout(() => {
                    if (this.modalStrategies && typeof this.modalStrategies.showApropriateModal === 'function') {
                        console.log('✅ Using ModalStrategyContext for address validation');
                        this.modalStrategies.showApropriateModal();
                    } else if (this.modalStrategies) {
                        console.warn('⚠️ modalStrategies exists but showApropriateModal method is missing');
                        console.log('📋 Available methods:', Object.getOwnPropertyNames(Object.getPrototypeOf(this.modalStrategies)));
                        this.fallbackAddressValidation();
                    } else {
                        console.log('✅ Using server-side validation for address checks');
                    }
                }, 500);
            }

            /**
             * Check if backend validation already handled address validation
             * This prevents duplicate modals from showing
             */
            hasBackendValidationRun() {
                if (window.checkoutManager?.state?.validationStatus?.lastResult) {
                    console.log('🔍 Backend validation status:', window.checkoutManager.state.validationStatus.lastResult);
                    return true;
                }
                if ($('.modal.show').length > 0) {
                    console.log('🔍 Modal already showing, likely from backend validation');
                    return true;
                }
                if ($('#no-addresses-modal').length > 0 && $('#no-addresses-modal').hasClass('show')) {
                    console.log('🔍 No addresses modal already showing');
                    return true;
                }
                return false;
            }

            /**
             * Validación de direcciones de respaldo cuando ModalStrategyContext no está disponible
             */
            fallbackAddressValidation() {
                console.log('🔄 Running fallback address validation');

                const $form = $('.step-checkout-address');
                if (!$form.length) return;

                const isVirtual = ($form.data('is-virtual') === 1 || $form.data('is-virtual') === '1');
                const needInvoice = $('#need_invoice').is(':checked');

                const deliveryAddresses = $('.address-box-item[data-type="delivery"]').length;
                const invoiceAddresses = $('.address-box-item[data-type="invoice"]').length;

                console.log('📋 Fallback address analysis:', {
                    isVirtual,
                    needInvoice,
                    deliveryAddresses,
                    invoiceAddresses
                });

                if (!isVirtual && deliveryAddresses === 0) {
                    console.log('🚨 No delivery addresses found - triggering add address form');
                    setTimeout(() => {
                        this.handleAddAddressButton({
                            currentTarget: { dataset: { type: 'delivery' } },
                            preventDefault: () => {}
                        });
                    }, 200);
                    return;
                }

                if (needInvoice && invoiceAddresses === 0) {
                    console.log('🚨 Need invoice but no invoice addresses - triggering add invoice address form');
                    setTimeout(() => {
                        this.handleAddAddressButton({
                            currentTarget: { dataset: { type: 'invoice' } },
                            preventDefault: () => {}
                        });
                    }, 200);
                    return;
                }

                console.log('✅ Fallback validation complete - addresses seem OK');
            }

            // Address selection methods
            handleInvoiceAddressRadioChange(event) {
                const selectedValue = $(event.currentTarget).val();
                if (selectedValue === '1') {
                    this.loadNewAddressFields("invoice");
                }
            }

            async setAddressOnServer(addressId, type) {
                try {
                    const response = await $.ajax({
                        url: window.checkoutManager.endpoints.checkout.setaddress,
                        type: "POST",
                        data: {
                            id_address: addressId,
                            type: type
                        },
                        dataType: 'json'
                    });

                    if (response.status === 'success') {
                        this.showToast('success', response.message || 'Dirección actualizada correctamente');
                        if (window.checkoutManager) {
                            await window.checkoutManager.validate({ onlyOnError: true, autoNavigate: false });
                        }
                    } else {
                        this.showToast('error', response.message || 'Error al actualizar dirección');
                    }
                } catch (error) {
                    console.error('Address selection error:', error);
                    this.showToast('error', 'Error de conexión. Inténtalo de nuevo.');
                }
            }

            async setNeedInvoiceOnServer(needInvoice) {
                if (this._needInvoiceUpdateInProgress) {
                    console.warn('⚠️ setNeedInvoiceOnServer already in progress, skipping duplicate call');
                    return;
                }

                this._needInvoiceUpdateInProgress = true;

                try {
                    console.log('🧾 Updating need_invoice on server:', needInvoice);

                    const response = await $.ajax({
                        url: window.checkoutManager.endpoints.checkout.setneeinvoice,
                        type: "POST",
                        data: { need_invoice: needInvoice },
                        dataType: 'json'
                    });

                    if (response.status === 'success') {
                        console.log('✅ Need invoice updated successfully:', response.data);
                        if (window.checkoutManager) {
                            await window.checkoutManager.validate({ onlyOnError: true, autoNavigate: false });
                        }
                        if (needInvoice === 1) {
                            await this.loadCustomerAddresses();
                        }
                    } else {
                        console.error('❌ Error updating need invoice:', response.message);
                        this.showToast('error', response.message || 'Error al actualizar configuración de factura');
                    }
                } catch (error) {
                    console.error('Need invoice update error:', error);
                    this.showToast('error', 'Error de conexión. Inténtalo de nuevo.');
                } finally {
                    this._needInvoiceUpdateInProgress = false;
                }
            }

            updateAddressSelectionUI($selectedItem, addressType) {
                console.log(`🔄 Updating address selection UI: ${addressType}`, $selectedItem);

                const addressId =
                    $selectedItem.data('id-address') ||
                    $selectedItem.data('address-id');

                if (!addressId) {
                    console.warn('❌ No addressId in selected item');
                    return;
                }

                const $prev = $(`.address-box-item[data-type="${addressType}"].selected, .address-item[data-address-type="${addressType}"].selected`).first();
                this.previousSelection = {
                    type: addressType,
                    element: $prev
                };

                $(`.address-box-item[data-type="${addressType}"]`).removeClass('selected');
                $(`.address-item[data-address-type="${addressType}"]`).removeClass('selected');
                $selectedItem.addClass('selected');

                $(`input[name="address-${addressType}"][value="${addressId}"]`).prop('checked', true);
                $(`input[name="id_address_${addressType}"][value="${addressId}"]`).prop('checked', true);

                $(`input[name="id_address_${addressType}"]`).val(addressId).trigger('change');

                console.log(`✅ Address selection updated: ${addressType} - ${addressId}`);
            }

            handleAddAddressButton(event) {
                event.preventDefault();

                // Anti doble click / doble open
                if (this._openingAddForm) return;
                this._openingAddForm = true;

                try {
                    this.selectedAddressType = $(event.currentTarget).data('type') || 'delivery';
                    console.log('📍 Selected address type:', this.selectedAddressType);
                    this.loadNewAddressFields(this.selectedAddressType);
                } finally {
                    setTimeout(() => { this._openingAddForm = false; }, 300);
                }
            }

            handleAddNewAddressFromModal(event) {
                event.preventDefault();
                const type = $(event.currentTarget).data('type') || 'delivery';

                console.log(`➕ AddressStepHandler: Adding new address from modal: ${type}`);

                $('#no-addresses-modal, #dynamic-no-addresses-modal').modal('hide');
                $('.modal.show').modal('hide');

                setTimeout(() => {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                }, 100);

                setTimeout(() => {
                    this.selectedAddressType = type;
                    this.loadNewAddressFields(type);
                    this.isShowingModal = false;
                    if (this.modalStrategies) {
                        this.modalStrategies.reset?.();
                    }
                }, 300);
            }

            handleEditAddressButton(event) {
                event.preventDefault();
                event.stopPropagation(); // Prevent bubbling to address-box-item click handler

                const $addressBox = $(event.currentTarget).closest('.address-box-item');
                const addressId = $addressBox.data('id-address');
                const addressType = $addressBox.data('type');

                console.log(`✏️ Editing address: ${addressType} - ${addressId}`);
                this.loadEditAddressForm(addressId, addressType);
            }

            handleRemoveAddressButton(event) {
                event.preventDefault();
                event.stopPropagation(); // Prevent bubbling to address-box-item click handler

                const $addressBox = $(event.currentTarget).closest('.address-box-item');
                const addressId = $addressBox.data('id-address');
                const addressType = $addressBox.data('type');

                console.log(`🗑️ Remove address button clicked - ID: ${addressId}, Type: ${addressType}`);

                $('.delete-confirm-address-checkout').data('id-address', addressId).data('type', addressType);
                $('.modal').modal('hide');

                setTimeout(() => {
                    const $removeModal = $('.remove-address-checkout-modal');

                    if ($removeModal.length === 0) {
                        console.error('❌ Remove address modal not found');
                        return;
                    }

                    console.log('📋 Opening remove address modal');

                    $removeModal.modal({
                        backdrop: 'static',
                        keyboard: false
                    }).modal('show');

                    setTimeout(() => {
                        if (!$removeModal.hasClass('show')) {
                            console.log('🔧 Forcing modal to show manually');
                            $removeModal.addClass('show').css('display', 'block');
                            $('body').addClass('modal-open');

                            if ($('.modal-backdrop').length === 0) {
                                $('body').append('<div class="modal-backdrop fade show"></div>');
                            }
                        }
                    }, 100);
                }, 300);
            }

            handleDeleteConfirmation(event) {
                const $btn = $(event.currentTarget);
                const id_address = $btn.data('id-address');

                if (!id_address) {
                    console.warn('ID de dirección no encontrado');
                    return;
                }

                this.deleteAddress(id_address, $btn);
            }

            handleSaveAddAddress(event) {
                event.preventDefault();
                $(".add-address-checkout-form").submit();
            }

            handleSaveEditAddress(event) {
                event.preventDefault();
                $(".edit-address-checkout-form").submit();
            }

            preventEventBubbling(event) {
                event.stopPropagation();
            }

            async handleAddressSelection(event) {
                const $target = $(event.currentTarget);
                console.log('🎯 Click detected on:', $target[0]);

                if ($target.is('input[type="radio"]')) {
                    console.log('📻 Direct radio click detected, allowing natural behavior');
                } else {
                    event.preventDefault();
                }
                event.stopPropagation();

                let $addressBox, addressId, addressType, $radioInput;

                if ($target.hasClass('address-box-item') || $target.closest('.address-box-item').length) {
                    $addressBox = $target.hasClass('address-box-item') ? $target : $target.closest('.address-box-item');
                    addressId = $addressBox.data('id-address');
                    addressType = $addressBox.data('type') || 'delivery';
                    $radioInput = $addressBox.find('input[type="radio"]');
                    console.log('🏠 Address box detected:', { addressId, addressType, element: $addressBox[0], clickedOn: $target[0].className, radioInput: $radioInput[0] });
                } else if ($target.hasClass('address-item') || $target.closest('.address-item').length) {
                    $addressBox = $target.hasClass('address-item') ? $target : $target.closest('.address-item');
                    addressId = $addressBox.data('address-id') || $addressBox.data('id-address');
                    addressType = $addressBox.data('address-type') || $addressBox.data('type') || 'delivery';
                    $radioInput = $addressBox.find('input[type="radio"]');
                    console.log('📍 Address item detected:', { addressId, addressType, element: $addressBox[0], radioInput: $radioInput[0] });
                } else {
                    console.warn('❌ Unknown address selection element:', $target[0]);
                    return;
                }

                if ($addressBox.data('processing')) {
                    console.log('⏳ Address already processing, skipping');
                    return;
                }

                if (!addressId) {
                    console.warn('❌ No address ID found in element:', $addressBox[0]);
                    return;
                }

                if ($addressBox.hasClass('selected')) {
                    console.log('✅ Address already selected, skipping');
                    return;
                }

                console.log(`📍 Address selected: ${addressType} - ${addressId}`);
                $addressBox.data('processing', true);

                try {
                    if (addressType === 'delivery') {
                        this.selectedAddressDeliveryId = addressId;
                        console.log(`✅ Delivery address stored: ${addressId}`);
                    } else if (addressType === 'invoice') {
                        this.selectedAddressInvoiceId = addressId;
                        console.log(`✅ Invoice address stored: ${addressId}`);
                    }

                    this.selectedAddressType = addressType;

                    if ($radioInput.length > 0) {
                        $radioInput.prop('checked', true).trigger('change');
                        console.log('✅ Radio button checked:', $radioInput[0]);
                    } else {
                        console.warn('❌ No radio button found for address selection');
                    }

                    this.updateAddressSelectionUI($addressBox, addressType);

                    const response = await $.ajax({
                        url: window.checkoutManager.endpoints.checkout.setaddress,
                        type: "POST",
                        data: { id_address: addressId, type: addressType },
                        dataType: 'json'
                    });

                    if (response.status === 'success') {
                        this.showToast('success', response.message || 'Dirección actualizada correctamente');
                        if (window.checkoutManager) {
                            await window.checkoutManager.validate({ onlyOnError: true, autoNavigate: false });
                        }
                        await window.cart.loadCart();
                        await window.checkoutNavigator.loadCheckoutSummary();

                    } else {
                        this.revertAddressSelectionUI(addressType);
                        this.showToast('error', response.message || 'Error al actualizar dirección');
                    }
                } catch (error) {
                    console.error('Address selection error:', error);
                    this.revertAddressSelectionUI(addressType);
                    this.showToast('error', 'Error de conexión. Inténtalo de nuevo.');
                } finally {
                    $addressBox.removeData('processing');
                }
            }

            revertAddressSelectionUI(addressType) {
                if (this.previousSelection && this.previousSelection.type === addressType) {
                    const previousId = this.previousSelection.element?.data('id-address') || this.previousSelection.element?.data('address-id');

                    $(`.address-box-item[data-type="${addressType}"], .address-item[data-address-type="${addressType}"]`).removeClass('selected');

                    if (previousId) {
                        $(`.address-box-item[data-id-address="${previousId}"][data-type="${addressType}"], .address-item[data-address-id="${previousId}"][data-address-type="${addressType}"]`).addClass('selected');
                        $(`input[name="address-${addressType}"][value="${previousId}"], input[name="id_address_${addressType}"][value="${previousId}"]`).prop('checked', true);
                        $(`input[name="id_address_${addressType}"]`).val(previousId);
                    }
                }
            }

            // Address management with optimistic updates (single version)
            async updateAddress(addressData, optimistic = true) {
                const payload = {
                    id_address: addressData.id_address ?? addressData.id,
                    type: addressData.type || 'delivery'
                };

                if (this._updateInProgress) {
                    console.warn('Address update already in progress');
                    return;
                }
                this._updateInProgress = true;

                try {
                    if (optimistic && window.checkoutManager?.config?.optimisticUpdates) {
                        this.updateAddressUI(addressData);
                    }

                    const response = await $.ajax({
                        url: window.checkoutManager.endpoints.checkout.setaddress,
                        type: "POST",
                        data: payload,
                        dataType: 'json'
                    });

                    if (response.status === 'success') {
                        if (!optimistic) {
                            this.updateAddressUI(addressData);
                        }
                        this.showToast('success', response.message || 'Dirección actualizada correctamente');
                        if (window.checkoutManager) {
                            await window.checkoutManager.validate({ onlyOnError: true, autoNavigate: false });
                        }
                        return response;
                    } else {
                        this.showToast('warning', response.message || 'Error al actualizar dirección');
                        if (optimistic) {
                            this.revertAddressUI();
                        }
                        throw new Error(response.message || 'Address update failed');
                    }
                } catch (error) {
                    console.error('Error updating address:', error);
                    if (optimistic) {
                        this.revertAddressUI();
                    }
                    throw error;
                } finally {
                    this._updateInProgress = false;
                }
            }

            updateAddressUI(addressData) {
                this.previousState = {
                    addressSelector: $('.address-selector').html()
                };

                const addressId = addressData.id_address || addressData.id;
                $(`.address-item[data-address="${addressId}"], .address-item[data-address-id="${addressId}"], .address-box-item[data-id-address="${addressId}"]`).addClass('selected');
                $(`.address-item:not([data-address="${addressId}"]):not([data-address-id="${addressId}"]), .address-box-item:not([data-id-address="${addressId}"])`).removeClass('selected');
            }

            revertAddressUI() {
                if (this.previousState && this.previousState.addressSelector) {
                    $('.address-selector').html(this.previousState.addressSelector);
                    delete this.previousState.addressSelector;
                }
            }

            // Form loading and management methods
            async loadNewAddressFields(type = 'delivery') {
                console.log('🔄 loadNewAddressFields called with type:', type);
                try {
                    $('.add-address-checkout-modal').find('input[name="type"]').val(type);

                    const response = await $.ajax({
                        url: window.checkoutManager.endpoints.checkout.getaddaddressfields,
                        type: 'GET',
                        dataType: 'json'
                    });

                    if (response.status !== "success") {
                        alert(response.status === "warning" ? response.message : 'Ocurrió un error inesperado.');
                        return;
                    }

                    console.log('📋 Backend response data:', response.data);
                    console.log('📋 Default field from backend should be:', response.data.default ? 1 : 0);

                    const defaultSelectValue = response.data.default ? 1 : 0;

                    const defaultFieldIndex = response.fields.findIndex(field => field.name === 'default');
                    if (defaultFieldIndex !== -1) {
                        response.fields[defaultFieldIndex].value = defaultSelectValue;
                        console.log('🔧 Updated default field value to:', defaultSelectValue);
                    }

                    this.renderAddressForm(response.fields, 'add', response.data.country, defaultSelectValue);
                    this.setupAddAddressValidation(response.fields);
                } catch (error) {
                    console.error('Error loading new address fields:', error);
                    this.showToast('error', 'Error al cargar el formulario');
                }
            }

            async loadEditAddressForm(addressId, addressType) {
                try {
                    const response = await $.ajax({
                        url: `${window.checkoutManager.endpoints.checkout.getaddressfields}&id_address=${addressId}`,
                        type: 'GET',
                        dataType: 'json'
                    });

                    if (response.status === 'success') {
                        this.renderAddressForm(response.fields, 'edit');
                        this.setupEditAddressValidation(response.fields);
                        $('.save-edit-address-checkout').attr('data-id-address', addressId);
                        $('.edit-address-checkout-modal').modal('show');
                    }
                } catch (error) {
                    console.error('Error loading edit address form:', error);
                    this.showToast('error', 'Error al cargar el formulario de edición');
                }
            }

            renderAddressForm(fields, formType, preselectedCountry = null, preselectedDefault = null) {
                const formContainer = formType === 'add'
                    ? $('.add-address-checkout-form')
                    : $('.edit-address-checkout-form');

                formContainer.empty();

                console.log('🔍 renderAddressForm called with:', {formType, fieldsCount: fields.length});
                console.log('📋 Fields received:', fields);

                $.each(fields, (index, field) => {
                    console.log(`🔍 Rendering field: ${field.name}`, field);

                    const isConditionalField = field.name === 'id_state';

                    let fieldHtml = `<div class="form-group ${isConditionalField ? 'conditional-field' : ''}" data-field-name="${field.name}">
                <label for="${field.name}">${field.label} ${field.required ? '*' : ''}</label>`;

                    if (field.type === 'select') {
                        fieldHtml += `<select class="form-control select2" id="${field.name}" name="${field.name}" ${field.required ? 'required data-msg-required="' + field.label + ' es obligatorio."' : ''}>
                    <option value="">Selecciona una opción</option>`;

                        if (field.options && field.options.length > 0) {
                            $.each(field.options, (i, option) => {
                                let selected = '';
                                if (formType === 'edit') {
                                    selected = (option.value == field.value) ? 'selected' : '';
                                } else if (formType === 'add' && field.name === 'default') {
                                    selected = (option.value == field.value) ? 'selected' : '';
                                } else if (formType === 'add' && field.name === 'id_country') {
                                    selected = (option.value == field.value) ? 'selected' : '';
                                }
                                fieldHtml += `<option value="${option.value}" ${selected}>${option.label}</option>`;
                            });
                        } else {
                            console.log(`⚠️ No options found for select field: ${field.name}`);
                        }

                        fieldHtml += `</select>`;
                    } else {
                        const inputType = field.type === 'text' || !field.type ? 'text' : field.type;
                        const value = field.value ?? '';
                        console.log(`📝 Field ${field.name}: value="${value}", type="${field.type}"`);

                        let inputAttributes = '';
                        if (field.required) {
                            inputAttributes += ` required data-msg-required="${field.label} es obligatorio."`;
                        }

                        switch (field.name) {
                            case 'email':
                                inputAttributes += ` data-msg-email="Ingrese un email válido."`;
                                break;
                            case 'phone':
                            case 'phone_mobile':
                                inputAttributes += ` data-msg-phoneNumbers="Formato de teléfono inválido."`;
                                break;
                            case 'postcode':
                                inputAttributes += ` data-msg-postalCode="Ingrese un código postal válido."`;
                                break;
                            case 'firstname':
                            case 'lastname':
                                inputAttributes += ` data-msg-lettersOnly="Solo se permiten letras y espacios."`;
                                break;
                        }

                        fieldHtml += `<input type="${inputType}" class="form-control" id="${field.name}" name="${field.name}" value="${value}" ${inputAttributes}>`;
                    }

                    fieldHtml += `</div>`;
                    formContainer.append(fieldHtml);

                    console.log(`✅ Rendered field: ${field.name} (${field.type}) - Required: ${field.required}`);
                });

                if (formType === 'add') {
                    const forcedValues = {};
                    if (preselectedCountry) forcedValues['id_country'] = preselectedCountry;
                    if (preselectedDefault !== null) forcedValues['default'] = preselectedDefault;
                    this.initializeSelect2(formContainer, forcedValues);
                } else {
                    this.initializeSelect2(formContainer);
                }

                this.setupCountryChangeHandler(formContainer, formType);
                this.setupPostcodeValidationHandler(formContainer, formType);
            }

            initializeSelect2($container, forcedValues = {}) {
                $container.find('select.select2').each(function() {
                    const $select = $(this);
                    const fieldName = $select.attr('name');

                    $select.select2({ width: '100%', placeholder: 'Selecciona una opción' });

                    if (forcedValues[fieldName] !== undefined) {
                        console.log(`🔧 Select2: Forcing value for ${fieldName}:`, forcedValues[fieldName]);
                        $select.val(String(forcedValues[fieldName])).trigger('change.select2');
                    } else {
                        const preSelectedValue = $select.find('option[selected]').val();
                        if (preSelectedValue) {
                            $select.val(preSelectedValue).trigger('change.select2');
                        }
                    }
                });
            }

            setupCountryChangeHandler($container, formType) {
                const selector = formType === 'add' ? '.add-address-checkout-form' : '.edit-address-checkout-form';

                $container.off('change', '#id_country').on('change', '#id_country', async (event) => {
                    const selectedCountryId = $(event.currentTarget).val();
                    console.log(`🌍 Country changed to: ${selectedCountryId}`);

                    const $existingStateField = $(`${selector} #id_state`);
                    if ($existingStateField.length) {
                        $existingStateField.closest('.form-group').remove();
                        const validator = $container.data('validator');
                        if (validator && validator.settings?.rules?.id_state) {
                            delete validator.settings.rules.id_state;
                            delete validator.settings.messages.id_state;
                            console.log('🗑️ Removed id_state validation rules');
                        }
                    }

                    const $postcodeField = $(`${selector} #postcode`);
                    if ($postcodeField.length && $postcodeField.val().trim()) {
                        console.log('🔄 Triggering postcode revalidation for new country');
                        this.validatePostcodeField($postcodeField, selectedCountryId);
                    }

                    if (!selectedCountryId) return;

                    try {
                        const response = await $.ajax({
                            url: `${window.checkoutManager.endpoints.checkout.getstates}?id_country=${selectedCountryId}`,
                            type: 'GET',
                            dataType: 'json'
                        });

                        if (response.status === 'success' && response.options && response.options.length > 0) {
                            console.log(`🏛️ Found ${response.options.length} states for country ${selectedCountryId}`);

                            let fieldHtml = `<div class="form-group conditional-field" data-field-name="id_state">
                        <label for="id_state">${response.label} *</label>
                        <select class="form-control select2" id="id_state" name="id_state" required data-msg-required="${response.label} es obligatorio.">
                            <option value="">Selecciona una opción</option>`;

                            $.each(response.options, (i, option) => {
                                fieldHtml += `<option value="${option.value}">${option.label}</option>`;
                            });

                            fieldHtml += `</select></div>`;

                            $(`${selector} #id_country`).closest('.form-group').after(fieldHtml);

                            const $newStateField = $(`${selector} #id_state`);
                            $newStateField.select2({ width: '100%', placeholder: 'Selecciona una opción' });

                            const validator = $container.data('validator');
                            if (validator) {
                                validator.settings.rules.id_state = { required: true };
                                validator.settings.messages.id_state = { required: `${response.label} es obligatorio.` };
                                console.log('✅ Added validation rules for id_state');
                            }
                        } else {
                            console.log(`🏛️ No states found for country ${selectedCountryId} - field not required`);
                        }

                    } catch (error) {
                        console.error('❌ Error loading states:', error);
                    }
                });
            }

            /**
             * Setup postcode real-time validation handlers
             */
            setupPostcodeValidationHandler($container, formType) {
                const selector = formType === 'add'
                    ? '.add-address-checkout-form'
                    : '.edit-address-checkout-form';

                if ($container.data('postcode-bound')) return;
                $container.data('postcode-bound', true);

                $container
                    .off('blur.postcodeValidation keydown.postcodeValidation', '#postcode')
                    .on('blur.postcodeValidation', '#postcode', (event) => {
                        this._runPostcodeValidation($(event.currentTarget), selector);
                    })
                    .on('keydown.postcodeValidation', '#postcode', (event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            this._runPostcodeValidation($(event.currentTarget), selector);
                        }
                    });
            }

            _runPostcodeValidation($postcodeField, selector) {
                const postcode = $postcodeField.val().trim();

                const lastVal = $postcodeField.data('last-postcode') || '';
                if (postcode === lastVal) return;
                $postcodeField.data('last-postcode', postcode);

                this.clearPostcodeValidationFeedback($postcodeField);

                if (postcode.length === 0) return;

                const countryId = $(`${selector} #id_country`).val();
                if (countryId) {
                    this.validatePostcodeField($postcodeField, countryId);
                } else {
                    this.showPostcodeValidationFeedback(
                        $postcodeField,
                        'warning',
                        'Selecciona un país primero'
                    );
                }
            }

            /**
             * Validate postcode field using AJAX call
             */
            async validatePostcodeField($postcodeField, countryId) {
                const postcode = $postcodeField.val().trim();
                if (!postcode || !countryId) return;

                console.log(`📮 Validating postcode: "${postcode}" for country: ${countryId}`);

                try {
                    const response = await $.ajax({
                        url: window.checkoutManager.endpoints.checkout.validatepostcode,
                        type: 'GET',
                        data: { postcode: postcode, id_country: countryId },
                        dataType: 'json'
                    });

                    console.log('📮 Postcode validation response:', response);

                    if (response.valid === false) {
                        this.showPostcodeValidationFeedback($postcodeField, 'error', response.message || 'Código postal inválido');
                    }
                } catch (error) {
                    console.error('❌ Error validating postcode:', error);
                    this.showPostcodeValidationFeedback($postcodeField, 'error', 'Error al validar código postal');
                }
            }

            /**
             * Show postcode validation feedback
             */
            showPostcodeValidationFeedback($field, type, message, loading = false) {
                const $formGroup = $field.closest('.form-group');

                this.clearPostcodeValidationFeedback($field);

                let fieldClass = '';
                switch (type) {
                    case 'success': fieldClass = 'is-valid'; break;
                    case 'error':
                    case 'warning': fieldClass = 'is-invalid'; break;
                    case 'info': fieldClass = loading ? 'postcode-validating' : ''; break;
                }

                if (fieldClass) $field.addClass(fieldClass);

                const feedbackHtml = `<label class="error postcode-error">${message}</label>`;
                $formGroup.append(feedbackHtml);

                console.log(`📮 Postcode feedback: ${type} - ${message}`);
            }

            /**
             * Clear postcode validation feedback
             */
            clearPostcodeValidationFeedback($field) {
                const $formGroup = $field.closest('.form-group');
                $field.removeClass('is-valid is-invalid postcode-validating');
                $formGroup.find('label.error.postcode-error').remove();
            }

            setupAddAddressValidation(fields) {
                console.log('🔧 Setting up dynamic validation for add address form');
                console.log('📋 Fields received:', fields);

                const validationRules = {};
                const validationMessages = {};
                const currentLang = this.currentLanguage;

                if (this.validationMessages[currentLang]) {
                    $.extend($.validator.messages, this.validationMessages[currentLang]);
                    console.log(`🌐 Applied ${currentLang} validation messages`);
                }

                this.addCustomValidationMethods();

                $.each(fields, (index, field) => {
                    validationRules[field.name] = {};

                    if (field.required) {
                        validationRules[field.name].required = true;
                        validationMessages[field.name] = {};
                        validationMessages[field.name].required = `${field.label} es obligatorio.`;
                    }

                    this.addFieldTypeValidation(field, validationRules, validationMessages);
                    this.addFieldSpecificValidation(field, validationRules, validationMessages);

                    if (Object.keys(validationRules[field.name]).length === 0) {
                        delete validationRules[field.name];
                    }
                });

                console.log('📋 Final validation rules:', validationRules);
                console.log('📋 Final validation messages:', validationMessages);

                const $form = $(".add-address-checkout-form");

                // 🔧 limpieza total ANTES de (re)inicializar
                this.detachValidator($form);
                // evita submit duplicado por eventos previos
                $form.off('submit.addressAdd');

                $form.validate({
                    rules: validationRules,
                    messages: validationMessages,
                    submitHandler: this.handleAddAddressSubmit.bind(this),
                    errorClass: "error",
                    validClass: "valid",
                    errorElement: "label",
                    errorPlacement: function(error, element) {
                        error.addClass("invalid-feedback");
                        element.closest(".form-group").append(error);
                        element.addClass("is-invalid");
                    },
                    success: function(label, element) {
                        $(element).removeClass("is-invalid").addClass("is-valid");
                        $(element).closest(".form-group").find(".invalid-feedback").remove();
                    }
                });

                // Guardia extra (opcional) si alguien dispara submit manual
                $form.one('submit.addressAdd', (e) => { e.preventDefault(); });

                console.log('🎭 About to show add-address modal');
                const $modal = $('.add-address-checkout-modal');
                console.log('📋 Modal found:', $modal.length, 'elements');
                console.log('📋 Modal classes:', $modal.attr('class'));
                console.log('📋 Modal style:', $modal.attr('style'));

                // Small delay to ensure DOM is ready
                setTimeout(() => {
                    $modal.modal('show');
                    console.log('✅ Modal show command executed');

                    // Verify modal state after attempt
                    setTimeout(() => {
                        console.log('📋 Modal visible after show:', $modal.is(':visible'));
                        console.log('📋 Modal has show class:', $modal.hasClass('show'));
                        console.log('📋 Modal display style:', $modal.css('display'));
                    }, 100);
                }, 50);
            }

            setupEditAddressValidation(fields) {
                console.log('🔧 Setting up dynamic validation for edit address form');
                console.log('📋 Fields received:', fields);

                const validationRules = {};
                const validationMessages = {};
                const currentLang = this.currentLanguage;

                if (this.validationMessages[currentLang]) {
                    $.extend($.validator.messages, this.validationMessages[currentLang]);
                    console.log(`🌐 Applied ${currentLang} validation messages for edit form`);
                }

                this.addCustomValidationMethods();

                $.each(fields, (index, field) => {
                    validationRules[field.name] = {};

                    if (field.required) {
                        validationRules[field.name].required = true;
                        validationMessages[field.name] = {};
                        validationMessages[field.name].required = `${field.label} es obligatorio.`;
                    }

                    this.addFieldTypeValidation(field, validationRules, validationMessages);
                    this.addFieldSpecificValidation(field, validationRules, validationMessages);

                    if (Object.keys(validationRules[field.name]).length === 0) {
                        delete validationRules[field.name];
                    }
                });

                console.log('📋 Final edit validation rules:', validationRules);
                console.log('📋 Final edit validation messages:', validationMessages);

                const $form = $(".edit-address-checkout-form");

                // 🔧 limpieza total ANTES de (re)inicializar
                this.detachValidator($form);
                $form.off('submit.addressEdit');

                $form.validate({
                    rules: validationRules,
                    messages: validationMessages,
                    submitHandler: this.handleEditAddressSubmit.bind(this),
                    errorClass: "error",
                    validClass: "valid",
                    errorElement: "label",
                    errorPlacement: function(error, element) {
                        error.addClass("invalid-feedback");
                        element.closest(".form-group").append(error);
                        element.addClass("is-invalid");
                    },
                    success: function(label, element) {
                        $(element).removeClass("is-invalid").addClass("is-valid");
                        $(element).closest(".form-group").find(".invalid-feedback").remove();
                    }
                });

                $form.one('submit.addressEdit', (e) => { e.preventDefault(); });
            }

            /**
             * Add custom validation methods for form fields
             * Now using global validation methods from settings.js
             */
            addCustomValidationMethods() {
                if (typeof window.settings !== 'undefined' && typeof window.settings.addCustomValidationMethods === 'function') {
                    window.settings.addCustomValidationMethods();
                } else {
                    console.warn('⚠️ Settings not available, using fallback validation methods');
                    $.validator.addMethod("lettersandspace", function(value, element) {
                        return this.optional(element) || /^[a-zA-ZÀ-ÿ\u00f1\u00d1]+([a-zA-ZÀ-ÿ\u00f1\u00d1\s-]*[a-zA-ZÀ-ÿ\u00f1\u00d1])?$/.test(value);
                    }, "Solo se permiten letras, espacios y guiones para nombres compuestos.");

                    $.validator.addMethod("lettersOnly", function(value, element) {
                        return this.optional(element) || /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜçÇ\s]+$/i.test(value);
                    }, "Solo se permiten letras y espacios.");

                    $.validator.addMethod("numbersOnly", function(value, element) {
                        return this.optional(element) || /^\d+$/.test(value);
                    }, "Solo se permiten números.");

                    $.validator.addMethod("phoneNumbers", function(value, element) {
                        if (this.optional(element)) return true;
                        const regex = /^\+?[0-9]{1,3}?([ \-()]?[0-9]{1,4}){2,6}$/;
                        const digits = value.replace(/\D/g, "");
                        return regex.test(value) && digits.length >= 8 && digits.length <= 15;
                    }, "Ingrese un número de teléfono válido (8 a 15 dígitos).");

                    $.validator.addMethod("postalCode", function(value, element) {
                        if (this.optional(element)) return true;
                        return /^[A-Z0-9\s\-]{3,12}$/i.test(value);
                    }, "Ingrese un código postal válido.");
                }
            }

            addFieldTypeValidation(field, validationRules, validationMessages) {
                const currentLang = this.currentLanguage;
                const langMessages = this.validationMessages[currentLang] || this.validationMessages['es'];

                switch (field.type) {
                    case 'select':
                        if (field.required) {
                            validationRules[field.name] = validationRules[field.name] || {};
                            validationRules[field.name].required = true;
                            validationMessages[field.name] = validationMessages[field.name] || {};
                            validationMessages[field.name].required = `Debe seleccionar ${field.label.toLowerCase()}.`;
                        }
                        break;

                    case 'email':
                        validationRules[field.name] = validationRules[field.name] || {};
                        validationRules[field.name].email = true;
                        validationMessages[field.name] = validationMessages[field.name] || {};
                        validationMessages[field.name].email = langMessages.email || 'Ingrese un email válido.';
                        break;

                    case 'number':
                        validationRules[field.name] = validationRules[field.name] || {};
                        validationRules[field.name].number = true;
                        validationMessages[field.name] = validationMessages[field.name] || {};
                        validationMessages[field.name].number = langMessages.number || 'Ingrese un número válido.';
                        break;

                    case 'tel':
                    case 'phone':
                        validationRules[field.name] = validationRules[field.name] || {};
                        validationRules[field.name].phoneNumbers = true;
                        validationMessages[field.name] = validationMessages[field.name] || {};
                        validationMessages[field.name].phoneNumbers = langMessages.phoneNumbers || 'Ingrese un teléfono válido.';
                        break;

                    case 'text':
                    default:
                        break;
                }

                console.log(`📝 Added type validation for ${field.name} (${field.type}):`, validationRules[field.name]);
            }

            addFieldSpecificValidation(field, validationRules, validationMessages) {
                const currentLang = this.currentLanguage;
                const langMessages = this.validationMessages[currentLang] || this.validationMessages['es'];

                validationRules[field.name] = validationRules[field.name] || {};
                validationMessages[field.name] = validationMessages[field.name] || {};

                switch (field.name) {
                    case 'firstname':
                        validationRules[field.name].lettersandspace = true;
                        validationMessages[field.name].lettersandspace = langMessages.lettersandspace || 'Solo se permiten letras, espacios y guiones para nombres compuestos.';
                        break;

                    case 'lastname':
                        validationRules[field.name].lettersandspace = true;
                        validationMessages[field.name].lettersandspace = langMessages.lettersandspace || 'Solo se permiten letras, espacios y guiones para nombres compuestos.';
                        break;

                    case 'postcode':
                        validationRules[field.name].postalCode = true;
                        validationMessages[field.name].postalCode = langMessages.postalCode || 'Ingrese un código postal válido.';
                        break;

                    case 'phone':
                    case 'phone_mobile':
                        validationRules[field.name].phoneNumbers = true;
                        validationMessages[field.name].phoneNumbers = langMessages.phoneNumbers || 'Formato de teléfono inválido.';
                        break;

                    case 'vat_number':
                        if (field.required) {
                            validationRules[field.name].minlength = 5;
                            validationMessages[field.name].minlength = 'El número de IVA debe tener al menos 5 caracteres.';
                        }
                        break;

                    case 'email':
                        validationRules[field.name].email = true;
                        validationMessages[field.name].email = langMessages.email || 'Ingrese un email válido.';
                        break;

                    case 'id_country':
                        validationRules[field.name].required = true;
                        validationMessages[field.name].required = 'Debe seleccionar un país.';
                        break;

                    case 'id_state':
                        if (field.required) {
                            validationRules[field.name].required = true;
                            validationMessages[field.name].required = 'Debe seleccionar un estado/provincia.';
                        }
                        break;

                    default:
                        break;
                }

                console.log(`🎯 Added specific validation for ${field.name}:`, validationRules[field.name]);
            }

            async handleAddAddressSubmit() {
                if (this._addAddressSubmitting) return;
                this._addAddressSubmitting = true;

                try {
                    const $form = $('.add-address-checkout-form');
                    const type = $('.add-address-checkout-modal').find('input[name="type"]').val();
                    let formData = $form.serializeArray();
                    formData.push({ name: 'type', value: type });

                    const response = await $.ajax({
                        url: window.checkoutManager.endpoints.checkout.addaddress,
                        type: 'POST',
                        dataType: 'json',
                        data: formData
                    });

                    if (response.status === 'success') {
                        $('.add-address-checkout-modal').find('input[name="type"]').val('');
                        $('.add-address-checkout-modal').modal('hide');
                        this.showToast('success', response.message, response.operation);

                        this.config.newInvoice.addClass('d-none');
                        this.config.fieldsInvoice.empty();
                        this.config.invoiceAddress.removeClass('d-none');
                        this.config.buttonAddress.removeClass("d-none");

                        await this.loadCustomerAddresses();
                    }
                } catch (error) {
                    console.error('Error adding address:', error);
                    this.showToast('error', 'Error al agregar la dirección');
                } finally {
                    this._addAddressSubmitting = false;
                }
            }

            async handleEditAddressSubmit() {
                if (this._editAddressSubmitting) return;
                this._editAddressSubmitting = true;

                try {
                    const id_address = $('.save-edit-address-checkout').attr('data-id-address');
                    const formData = $('.edit-address-checkout-form').serialize();
                    if (!id_address) return;

                    const response = await $.ajax({
                        url: window.checkoutManager.endpoints.checkout.editaddress,
                        type: 'POST',
                        dataType: 'json',
                        data: formData + `&id_address=${id_address}`
                    });

                    if (response.status === 'success') {
                        $('.edit-address-checkout-modal').modal('hide');
                        await this.loadCustomerAddresses();
                    }
                } catch (error) {
                    console.error('Error editing address:', error);
                    this.showToast('error', 'Error al editar la dirección');
                } finally {
                    this._editAddressSubmitting = false;
                }
            }

            async deleteAddress(addressId, $btn) {
                try {
                    $btn.prop('disabled', true).text('Eliminando...');

                    const response = await $.ajax({
                        url: window.checkoutManager.endpoints.checkout.deleteaddress,
                        type: 'POST',
                        dataType: 'json',
                        data: { id_address: addressId }
                    });

                    if (response.status === 'success') {
                        $btn.data('id-address', '').data('type', '');
                        $('.remove-address-checkout-modal').modal('hide');
                        this.showToast('success', response.message, response.operation);
                        await this.loadCustomerAddresses();
                    } else {
                        this.showToast('error', response.message || 'No se pudo eliminar la dirección');
                    }
                } catch (error) {
                    console.error('Error deleting address:', error);
                    this.showToast('error', 'Error al comunicar con el servidor');
                } finally {
                    $btn.prop('disabled', false).text('Confirmar');
                }
            }

            async loadCustomerAddresses() {
                if (this._loadAddressesInFlight) return;
                this._loadAddressesInFlight = true;

                try {
                    const response = await $.ajax({
                        url: window.checkoutManager.endpoints.checkout.getaddress,
                        type: 'GET',
                        dataType: 'json'
                    });

                    if (response.status === 'success') {
                        $('.list-delivery-addresses .row').html(response.html_delivery);
                        $('.list-invoice-addresses .row').html(response.html_invoice);
                    } else {
                        const warning = `<div class="col-12 text-center py-4 text-muted">No se encontraron direcciones.</div>`;
                        $('.list-delivery-addresses .row').html(warning);
                        $('.list-invoice-addresses .row').html(warning);
                    }
                } catch (error) {
                    console.error('Error loading addresses:', error);
                } finally {
                    this.refreshElements();
                    const enabled = this.config.needInvoice?.is(':checked') === true;
                    this.toggleInvoiceOptions(!!enabled);
                    setTimeout(() => {
                        if (this.modalStrategies?.showApropriateModal) {
                            this.modalStrategies.showApropriateModal();
                        }
                    }, 100);
                    this._loadAddressesInFlight = false;
                }
            }

            showMissingDeliveryModal() {
                const $modal = $('.missing-delivery-address');
                if ($modal.length && !$modal.hasClass('show')) {
                    $modal.modal('show');
                    $modal.off('hidden.bs.modal.addressFlag').on('hidden.bs.modal.addressFlag', () => {
                        this.isShowingModal = false;
                    });
                }
            }

            cleanupAllModals() {
                $('.modal.show').modal('hide');
                setTimeout(() => {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                }, 100);
                console.log('🧹 All modals and backdrops cleaned');
            }

            showMissingInvoiceModal() {
                const $modal = $('.missing-invoice-address');
                if ($modal.length && !$modal.hasClass('show')) {
                    $modal.modal('show');
                    $modal.off('hidden.bs.modal.addressFlag').on('hidden.bs.modal.addressFlag', () => {
                        this.isShowingModal = false;
                    });
                }
            }

            async handleFormSubmit() {
                console.log('🏠 Address form submit started');

                if (this.isShowingModal) {
                    console.warn('⛔️ Form submit bloqueado - Modal activo');
                    return false;
                }

                if ($('.modal:visible, .modal.show').length > 0) {
                    console.warn('⛔️ Form submit bloqueado - Modal de validación visible');
                    return false;
                }

                if (this._submissionInProgress) {
                    console.warn('⛔️ Form submission already in progress, aborting duplicate');
                    return false;
                }

                this._submissionInProgress = true;

                try {
                    const $form       = $('.step-checkout-address');
                    const isVirtual   = ($form.data('is-virtual') === 1 || $form.data('is-virtual') === '1');
                    const $invoice    = $('#need_invoice');
                    const needInvoice = $invoice.is(':checked');

                    const deliverySelected =
                        $form.find('input[name="id_address_delivery"]:checked').val() ||
                        $form.find('input[name="id_address_delivery"]').val();

                    let invoiceSelected = null;
                    if (needInvoice) {
                        invoiceSelected = $form.find('input[name="id_address_invoice"]:checked').val() || $form.find('input[name="id_address_invoice"]').val();
                    }

                    console.log('📋 Validación de direcciones:', {
                        isVirtual,
                        deliverySelected,
                        needInvoice,
                        invoiceSelected,
                        deliveryRequired: !isVirtual,
                        invoiceRequired: needInvoice
                    });

                    if (!isVirtual && !deliverySelected) {
                        console.error('❌ DELIVERY ADDRESS REQUIRED - Bloqueando submit');
                        this.cleanupAllModals();
                        this.showMissingDeliveryModal();
                        this.isShowingModal = true;
                        return false;
                    }

                    if (needInvoice && !invoiceSelected) {
                        const availableInvoiceAddresses = $form.find('.address-box-item[data-type="invoice"]').length;
                        const hasNewInvoiceOption = $form.find('input[name="address_invoice"][value="1"]').length > 0;

                        console.log('📋 Invoice address analysis:', {
                            needInvoice: true,
                            invoiceSelected: false,
                            availableInvoiceAddresses,
                            hasNewInvoiceOption,
                            newInvoiceRadioExists: hasNewInvoiceOption
                        });

                        if (availableInvoiceAddresses === 0 && !hasNewInvoiceOption) {
                            console.warn('⚠️ No invoice addresses available and no "new address" option - triggering add address');
                            this.handleAddAddressButton({
                                currentTarget: { dataset: { type: 'invoice' } },
                                preventDefault: () => {}
                            });
                            return false;
                        }

                        console.error('❌ INVOICE ADDRESS REQUIRED - Bloqueando submit');
                        this.cleanupAllModals();
                        this.showMissingInvoiceModal();
                        this.isShowingModal = true;
                        return false;
                    }

                    console.log('✅ Ambas validaciones pasaron - continuando submit');

                    if (needInvoice && invoiceSelected) {
                        const selectedInvoiceAddress = $(`.address-box-item[data-id="${invoiceSelected}"]`);
                        const hasVATNumber = selectedInvoiceAddress.find('[data-field="vat_number"]').text().trim();

                        console.log('📋 VAT validation:', {
                            invoiceSelected,
                            hasVATNumber: !!hasVATNumber,
                            vatValue: hasVATNumber
                        });

                        if (!hasVATNumber) {
                            console.warn('⚠️ Selected invoice address may not have VAT number');
                        }
                    }

                    console.log('🔍 Skipping duplicate validation - already executed in pre-action');
                    console.log('✅ Proceeding to submit address step...');

                    const formData = $form.serializeArray();
                    formData.push({ name: $invoice.attr('name'), value: needInvoice ? '1' : '0' });

                    console.log('📋 Address form data being submitted:', formData);
                    console.log('🌐 Submitting to URL:', window.checkoutManager.endpoints.checkout.stepaddress);

                    const response = await $.ajax({
                        url: window.checkoutManager.endpoints.checkout.stepaddress,
                        type: "POST",
                        data: formData,
                        dataType: 'json'
                    });

                    console.log('📋 Address step response:', response);

                    if (response.status === "success") {
                        this.isShowingModal = false;
                        this.setupMainFormValidation();

                        console.log('✅ Address step completed, navigating to delivery...');

                        try {
                            if (window.GTMCheckoutHelper?.trackAddAddressInfo) {
                                await window.GTMCheckoutHelper.trackAddAddressInfo(); // enviará add_address_info con checkout_step '1'
                            }
                        } catch (e) {
                            console.warn('⚠️ GTM add_shipping_info (step 1) falló:', e);
                        }

                        if (window.checkoutNavigator?.loadCheckoutStep) {
                            window.checkoutNavigator.loadCheckoutStep('delivery', true, true);
                        } else if (window.checkoutNavigator?.navigateToStepDirect) {
                            window.checkoutNavigator.navigateToStepDirect('delivery', true);
                        } else {
                            console.error('❌ No navigation method available');
                        }
                    } else {
                        this.isShowingModal = false;
                        this.setupMainFormValidation();
                        $form.find('.response-output')
                            .addClass('alert alert-' + (response.status === 'warning' ? 'warning' : 'danger'))
                            .html(response.message)
                            .fadeIn();

                        setTimeout(() => {
                            $form.find('.response-output')
                                .fadeOut().removeClass('alert alert-warning alert-danger').html('');
                        }, 3000);
                    }
                } catch (error) {
                    console.error("❌ Address form submission error:", error);
                    this.isShowingModal = false;
                } finally {
                    this._submissionInProgress = false;
                }
            }

            // Utility methods
            showToast(type, message, title = '') {
                if (typeof toastr !== 'undefined') {
                    toastr[type](message, title, {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right",
                        timeOut: 5000,
                        extendedTimeOut: 3000
                    });
                } else {
                    console.log(`Toast ${type}: ${title} - ${message}`);
                }
            }

            handleChangeDeliveryAddress(event) {
                event.preventDefault();
                console.log('🔄 Changing delivery address from blocked modal');
                $('.modal').modal('hide');
                const $target = $('.delivery-addresses');
                if ($target.length) {
                    const top = $target.offset().top - 40;
                    window.scrollTo({ top, behavior: 'smooth' });
                }
            }

            handleChangeDeliveryInvoice(event) {
                event.preventDefault();
                console.log('🔄 Changing delivery/invoice address from modal');
                $('.modal').modal('hide');
                const $target = $('.invoice-addresses ');
                if ($target.length) {
                    const top = $target.offset().top - 40;
                    window.scrollTo({ top, behavior: 'smooth' });
                }
            }

            showNotification(message, type = 'info') {
                this.showToast(type, message);
            }

            // Cleanup method
            destroy() {
                this.detachValidator($('.step-checkout-address'));
                this.detachValidator($('.add-address-checkout-form'));
                this.detachValidator($('.edit-address-checkout-form'));
                $(document).off('.addressStep');
                $(document).off('.addressStepRefresh');
                $(document).off('.addressLog');
                this.initialized = false;
                console.log('🗑️ Address step destroyed');
            }
        }

        function handleAddressStep() {
            console.log('📍 handleAddressStep() called - handled by AddressStepHandler class');
        }

        // Export: constructor + single instancia segura
        window.AddressStepHandler = AddressStepHandler;

        // Crea la instancia si no existe o si por error hay otra cosa ahí
        if (!(window.addressStepHandler instanceof AddressStepHandler)) {
            window.addressStepHandler = new AddressStepHandler(window.checkoutManager);
        }
        console.log('📤 AddressStepHandler instance ready');

        // Mantén compatibilidad: solo inicializa
        window.initAddressStep = () => {
            if (!(window.addressStepHandler instanceof window.AddressStepHandler)) {
                window.addressStepHandler = new window.AddressStepHandler(window.checkoutManager);
            }
            window.addressStepHandler.refreshElements();
            if (!window.addressStepHandler.initialized) {
                window.addressStepHandler.init();
            } else {
                if (window.checkoutManager?.state?.validationStatus) {
                    window.checkoutManager.state.validationStatus.lastResult = null;
                }
                const enabled = $('#need_invoice').is(':checked');
                window.addressStepHandler.toggleInvoiceOptions(enabled);
            }
        };

    } // End of guard condition

})();
