/**
 * Address Customer Handler
 *
 * Manages customer address functionality in the customer account area
 * Based on AddressStepHandler but adapted for customer context
 */
console.log('🚀 address-customer.js script loading...');

(function() {
    'use strict';

    console.log('🔧 Address Customer IIFE executing...');

    // Prevent redeclaration if class already exists
    if (typeof window.addressCustomerHandler === 'undefined') {

        class AddressCustomerHandler {

            constructor(customerManager = null) {
                this.manager = customerManager;
                this.initialized = false;
                this.selectedAddressId = null;
                this.isShowingModal = false;
                this._submissionInProgress = false; // Prevent double submissions

                // Anti-duplicate flags
                this._addAddressSubmitting = false;
                this._editAddressSubmitting = false;
                this._deleteAddressSubmitting = false;
                this._loadAddressesInFlight = false;
                this._openingAddForm = false;

                // Initialize modal strategies if available
                if (typeof window.ModalStrategyContext === 'function') {
                    this.modalStrategies = new window.ModalStrategyContext();
                } else {
                    console.warn('⚠️ ModalStrategyContext not available, using server-side validation');
                    this.modalStrategies = null;
                }

                // Initialize endpoints
                this.endpoints = this.getEndpoints();

                // Initialize validation message system
                this.initializeValidationMessages();

                // Customer-specific configuration
                this.config = {
                    defaultAddressField: null,
                    addressList: null,
                    addressForms: null,
                    deleteButtons: null,
                    editButtons: null,
                    addButtons: null,
                    isDefaultCheckbox: null
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
                // Multi-language validation message system
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
                        min: this.formatMessage("Please enter a value greater than or equal to {0}.")
                    },
                    es: {
                        required: "Este campo es obligatorio.",
                        remote: "Por favor, rellena este campo.",
                        email: "Por favor, escribe una dirección de correo válida.",
                        url: "Por favor, escribe una URL válida.",
                        date: "Por favor, escribe una fecha válida.",
                        dateISO: "Por favor, escribe una fecha válida (ISO).",
                        number: "Por favor, escribe un número válido.",
                        digits: "Por favor, escribe sólo dígitos.",
                        equalTo: "Por favor, escribe el mismo valor de nuevo.",
                        maxlength: this.formatMessage("Por favor, no escribas más de {0} caracteres."),
                        minlength: this.formatMessage("Por favor, no escribas menos de {0} caracteres."),
                        rangelength: this.formatMessage("Por favor, escribe un valor entre {0} y {1} caracteres."),
                        range: this.formatMessage("Por favor, escribe un valor entre {0} y {1}."),
                        max: this.formatMessage("Por favor, escribe un valor menor o igual a {0}."),
                        min: this.formatMessage("Por favor, escribe un valor mayor o igual a {0}.")
                    },
                    fr: {
                        required: "Ce champ est obligatoire.",
                        remote: "Veuillez remplir ce champ pour continuer.",
                        email: "Veuillez entrer une adresse email valide.",
                        url: "Veuillez entrer une URL valide.",
                        date: "Veuillez entrer une date valide.",
                        dateISO: "Veuillez entrer une date valide (ISO).",
                        number: "Veuillez entrer un nombre valide.",
                        digits: "Veuillez entrer seulement des chiffres.",
                        equalTo: "Veuillez entrer une nouvelle fois la même valeur.",
                        maxlength: this.formatMessage("Veuillez ne pas entrer plus de {0} caractères."),
                        minlength: this.formatMessage("Veuillez entrer au moins {0} caractères."),
                        rangelength: this.formatMessage("Veuillez entrer une valeur entre {0} et {1} caractères."),
                        range: this.formatMessage("Veuillez entrer une valeur entre {0} et {1}."),
                        max: this.formatMessage("Veuillez entrer une valeur inférieure ou égale à {0}."),
                        min: this.formatMessage("Veuillez entrer une valeur supérieure ou égale à {0}.")
                    }
                };

                // Set current language (default to Spanish for this context)
                this.currentLanguage = this.detectLanguage();
                this.setValidationLanguage(this.currentLanguage);
            }

            detectLanguage() {
                // Try to detect language from various sources
                if (window.prestashop && window.prestashop.language) {
                    return window.prestashop.language.iso_code || 'es';
                }
                if (document.documentElement.lang) {
                    return document.documentElement.lang.substring(0, 2);
                }
                if (navigator.language) {
                    return navigator.language.substring(0, 2);
                }
                return 'es'; // Default to Spanish
            }

            getEndpoints() {
                const segments = window.location.pathname.split('/');
                const iso = (segments[1] && segments[1].length === 2) ? segments[1] : 'es';
                const prefix = (iso.toLowerCase() !== 'es') ? `/${iso}` : '';
                const baseUrl = `${prefix}/modules/alsernetcustomer/routes`;

                return {
                    baseUrl,
                    iso,
                    customer: {
                        // Address management
                        addaddress: `${baseUrl}?modalitie=customer&action=addaddress&iso=${iso}`,
                        editaddress: `${baseUrl}?modalitie=customer&action=editaddress&iso=${iso}`,
                        deleteaddress: `${baseUrl}?modalitie=customer&action=deleteaddress&iso=${iso}`,
                        setdefaultaddress: `${baseUrl}?modalitie=customer&action=setdefaultaddress&iso=${iso}`,
                        getaddress: `${baseUrl}?modalitie=customer&action=getaddress&iso=${iso}`,
                        getaddresses: `${baseUrl}?modalitie=customer&action=getaddresses&iso=${iso}`,

                        // Address fields and validation
                        getaddressfields: `${baseUrl}?modalitie=customer&action=getaddressfields&iso=${iso}`,
                        getstates: `${baseUrl}?modalitie=customer&action=getstates&iso=${iso}`,
                        validatepostcode: `${baseUrl}?modalitie=customer&action=validatepostcode&iso=${iso}`,

                        // Form management
                        getaddform: `${baseUrl}?modalitie=customer&action=getaddform&iso=${iso}`,
                        geteditform: `${baseUrl}?modalitie=customer&action=geteditform&iso=${iso}`,

                        // Customer info
                        getcustomerinfo: `${baseUrl}?modalitie=customer&action=getcustomerinfo&iso=${iso}`,
                        updatecustomerinfo: `${baseUrl}?modalitie=customer&action=updatecustomerinfo&iso=${iso}`
                    }
                };
            }

            setValidationLanguage(language) {
                if (typeof $ !== 'undefined' && $.validator && this.validationMessages[language]) {
                    $.extend($.validator.messages, this.validationMessages[language]);
                    console.log(`🌐 Validation language set to: ${language}`);
                }
            }

            init() {
                if (this.initialized) {
                    console.log('⚠️ AddressCustomerHandler already initialized');
                    return;
                }

                console.log('🚀 Initializing AddressCustomerHandler...');

                this.refreshElements();
                this.bindEvents();
                this.initialized = true;

                console.log('✅ AddressCustomerHandler initialized');
            }

            refreshElements() {
                console.log('🔄 Refreshing customer address elements...');

                // Customer-specific selectors
                this.config.addressList = $('.customer-addresses, .address-list');
                this.config.addressForms = $('.address-form, .customer-address-form');
                this.config.deleteButtons = $('.delete-address, .btn-delete-address');
                this.config.editButtons = $('.edit-address, .btn-edit-address');
                this.config.addButtons = $('.add-address, .btn-add-address');
                this.config.isDefaultCheckbox = $('input[name="is_default"], .is-default-address');

                console.log('📋 Elements found:', {
                    addressList: this.config.addressList.length,
                    addressForms: this.config.addressForms.length,
                    deleteButtons: this.config.deleteButtons.length,
                    editButtons: this.config.editButtons.length,
                    addButtons: this.config.addButtons.length,
                    defaultCheckbox: this.config.isDefaultCheckbox.length
                });
            }

            bindEvents() {
                console.log('🔗 Binding customer address events...');

                // Unbind previous events to prevent duplicates
                $(document).off('.addressCustomer');

                // Add address button
                $(document).on('click.addressCustomer', '.btn-add-addresses', (e) => {
                    e.preventDefault();
                    this.handleAddAddress(e);
                });

                // Edit address button
                $(document).on('click.addressCustomer', '.edit-add-addresses', (e) => {
                    e.preventDefault();
                    this.handleEditAddress(e);
                });

                // Delete address button
                $(document).on('click.addressCustomer', '.delete-add-addresses', (e) => {
                    e.preventDefault();
                    this.handleDeleteAddress(e);
                });

                // Delete confirmation button
                $(document).on('click.addressCustomer', '#confirmDelete', (e) => {
                    e.preventDefault();
                    this.handleConfirmDelete(e);
                });

                // Save buttons
                $(document).on('click.addressCustomer', '#saveEditAddress', (e) => {
                    e.preventDefault();
                    $('#editAddressForm').submit();
                });

                $(document).on('click.addressCustomer', '#saveAddAddress', (e) => {
                    e.preventDefault();
                    $('#addAddressForm').submit();
                });

                // Address form submission
                $(document).on('submit.addressCustomer', '#editAddressForm, #addAddressForm', (e) => {
                    e.preventDefault();
                    this.handleAddressFormSubmission(e);
                });

                // Modal close events
                $(document).on('click.addressCustomer', '.modal .close, .modal .btn-close', (e) => {
                    this.closeModal();
                });

                // Country change for state loading
                $(document).on('change.addressCustomer', '#editAddressForm #id_country, #addAddressForm #id_country', (e) => {
                    this.handleCountryChange(e);
                });

                console.log('✅ Customer address events bound');
            }

            async handleAddAddress(event) {
                if (this._openingAddForm) {
                    console.log('⏳ Add form already opening, skipping');
                    return;
                }

                this._openingAddForm = true;
                console.log('➕ Opening add address form...');

                try {
                    const link = `/modules/alsernetcustomer/controllers/routes.php?action=getaddaddressfields&modalitie=address&iso=${this.endpoints.iso}`;
                    console.log('🔗 Making request to:', link);

                    const response = await $.ajax({
                        url: link,
                        type: 'GET',
                        dataType: 'json'
                    });

                    console.log('📥 Response received:', response);

                    if (response.status === 'success') {
                        console.log('✅ Success response, fields:', response.fields);
                        this.loadFormFields('#addAddressForm', response.fields, false);
                        $('#addAddressModal').modal('show');
                    } else {
                        console.log('❌ Error response:', response);
                        this.showToast('error', response.message || 'Error al cargar el formulario');
                    }
                } catch (error) {
                    console.error('❌ Error opening add address form:', error);
                    console.error('Error details:', error.responseText);
                    this.showToast('error', 'Error al abrir el formulario de dirección');
                } finally {
                    this._openingAddForm = false;
                }
            }

            async handleEditAddress(event) {
                const $button = $(event.currentTarget);
                const addressId = $button.data('id-address');

                if (!addressId) {
                    console.warn('❌ No address ID found for edit button');
                    return;
                }

                if (this._editAddressSubmitting) {
                    console.log('⏳ Edit already in progress, skipping');
                    return;
                }

                this._editAddressSubmitting = true;
                this.selectedAddressId = addressId;
                console.log(`✏️ Opening edit form for address: ${addressId}`);

                try {
                    const link = `/modules/alsernetcustomer/controllers/routes.php?action=getaddressfields&modalitie=address&id_address=${addressId}&iso=${this.endpoints.iso}`;

                    const response = await $.ajax({
                        url: link,
                        type: 'GET',
                        dataType: 'json'
                    });

                    if (response.status === 'success') {
                        this.loadFormFields('#editAddressForm', response.fields, true);
                        $('#saveEditAddress').attr('data-id-address', addressId);
                        $('#editAddressModal').modal('show');
                    } else {
                        this.showToast('error', response.message || 'Error al cargar la dirección');
                    }
                } catch (error) {
                    console.error('❌ Error opening edit address form:', error);
                    this.showToast('error', 'Error al abrir el formulario de edición');
                } finally {
                    this._editAddressSubmitting = false;
                }
            }

            async handleDeleteAddress(event) {
                const $button = $(event.currentTarget);
                const addressId = $button.data('id-address');

                if (!addressId) {
                    console.warn('❌ No address ID found for delete button');
                    return;
                }

                this.selectedAddressId = addressId;
                $('#confirmDelete').attr('data-id-address', addressId);
                $('.modal').modal('hide');
                $('#removeAddressModal').modal('show');
            }

            async handleConfirmDelete(event) {
                const $button = $(event.currentTarget);
                const addressId = $button.attr('data-id-address');

                if (!addressId) {
                    console.warn('❌ No address ID found for confirm delete');
                    return;
                }

                if (this._deleteAddressSubmitting) {
                    console.log('⏳ Delete already in progress, skipping');
                    return;
                }

                this._deleteAddressSubmitting = true;
                console.log(`🗑️ Deleting address: ${addressId}`);

                try {
                    const link = `/modules/alsernetcustomer/controllers/routes.php?action=deleteaddress&modalitie=address&iso=${this.endpoints.iso}`;

                    const response = await $.ajax({
                        url: link,
                        type: 'POST',
                        data: { id_address: addressId },
                        dataType: 'json'
                    });

                    if (response.status === 'success') {
                        $('#confirmDelete').attr('data-id-address', '');
                        $('#removeAddressModal').modal('hide');

                        this.showToast('success', response.message || 'Dirección eliminada correctamente');
                        this.loadCustomerAddresses();
                    } else {
                        this.showToast('error', response.message || 'Error al eliminar la dirección');
                    }
                } catch (error) {
                    console.error('❌ Error deleting address:', error);
                    this.showToast('error', 'Error de conexión. Inténtalo de nuevo.');
                } finally {
                    this._deleteAddressSubmitting = false;
                }
            }

            async handleSetDefaultAddress(event) {
                const $checkbox = $(event.currentTarget);
                const addressId = $checkbox.data('address-id') || $checkbox.closest('.address-item').data('address-id');

                if (!addressId) {
                    console.warn('❌ No address ID found for default checkbox');
                    return;
                }

                console.log(`⭐ Setting default address: ${addressId}`);

                try {
                    const response = await $.ajax({
                        url: this.endpoints.customer.setdefaultaddress,
                        type: 'POST',
                        data: {
                            ajax: 1,
                            id_address: addressId
                        },
                        dataType: 'json'
                    });

                    if (response.status === 'success' || response.success) {
                        this.showToast('success', response.message || 'Dirección predeterminada actualizada');
                        this.updateDefaultAddressUI(addressId);
                    } else {
                        this.showToast('error', response.message || 'Error al establecer dirección predeterminada');
                        // Revert checkbox state
                        $checkbox.prop('checked', !$checkbox.prop('checked'));
                    }
                } catch (error) {
                    console.error('❌ Error setting default address:', error);
                    this.showToast('error', 'Error de conexión. Inténtalo de nuevo.');
                    // Revert checkbox state
                    $checkbox.prop('checked', !$checkbox.prop('checked'));
                }
            }

            async handleAddressFormSubmission(event) {
                const $form = $(event.currentTarget);
                const isEdit = $form.attr('id') === 'editAddressForm';
                const submitKey = isEdit ? '_editAddressSubmitting' : '_addAddressSubmitting';

                if (this[submitKey]) {
                    console.log('⏳ Form submission already in progress');
                    return;
                }

                this[submitKey] = true;
                console.log(`📝 Submitting ${isEdit ? 'edit' : 'add'} address form...`);

                try {
                    // Validate form if validator is available
                    if ($form.data('validator') && !$form.valid()) {
                        console.log('❌ Form validation failed');
                        return;
                    }

                    let formData = $form.serialize();

                    // Add id_address for edit operations
                    if (isEdit && this.selectedAddressId) {
                        formData += `&id_address=${this.selectedAddressId}`;
                    }

                    const response = await $.ajax({
                        url: $form.attr('action'),
                        type: 'POST',
                        data: formData,
                        dataType: 'json'
                    });

                    if (response.status === 'success') {
                        const modalId = isEdit ? '#editAddressModal' : '#addAddressModal';
                        $(modalId).modal('hide');

                        this.showToast('success', response.message || response.operation || 'Dirección guardada correctamente');
                        await this.loadCustomerAddresses();
                    } else {
                        this.showToast('error', response.message || 'Error al guardar la dirección');

                        // Show field errors if available
                        if (response.errors) {
                            this.showFormErrors($form, response.errors);
                        }
                    }
                } catch (error) {
                    console.error('❌ Error submitting address form:', error);
                    this.showToast('error', 'Error de conexión. Inténtalo de nuevo.');
                } finally {
                    this[submitKey] = false;
                }
            }

            removeAddressFromDOM(addressId) {
                $(`.address-item[data-address-id="${addressId}"], .address-box[data-id="${addressId}"]`).fadeOut(300, function() {
                    $(this).remove();
                });
            }

            updateDefaultAddressUI(addressId) {
                // Remove default class from all addresses
                $('.address-item, .address-box').removeClass('is-default default-address');
                $('input[name="is_default"], .is-default-address').prop('checked', false);

                // Add default class to selected address
                $(`.address-item[data-address-id="${addressId}"], .address-box[data-id="${addressId}"]`)
                    .addClass('is-default default-address');
                $(`.address-item[data-address-id="${addressId}"] input[name="is_default"], .address-box[data-id="${addressId}"] .is-default-address`)
                    .prop('checked', true);
            }

            showFormErrors($form, errors) {
                // Clear previous errors
                $form.find('.error-message, .field-error').remove();
                $form.find('.has-error').removeClass('has-error');

                // Show new errors
                Object.keys(errors).forEach(fieldName => {
                    const $field = $form.find(`[name="${fieldName}"]`);
                    if ($field.length) {
                        $field.closest('.form-group, .field').addClass('has-error');
                        $field.after(`<div class="error-message text-danger">${errors[fieldName]}</div>`);
                    }
                });
            }

            closeModal() {
                if (this.modalStrategies) {
                    this.modalStrategies.closeModal();
                } else {
                    // Fallback: close Bootstrap modals
                    $('.modal').modal('hide');
                }
                this.isShowingModal = false;
            }

            // Load form fields dynamically from server response
            loadFormFields(formSelector, fields, isEdit = false) {
                console.log('📝 Loading form fields into:', formSelector);
                console.log('📋 Fields to load:', fields);
                console.log('✏️ Is edit mode:', isEdit);

                const $formContainer = $(formSelector);
                console.log('🎯 Form container found:', $formContainer.length);
                $formContainer.empty();

                let validationRules = {};
                let validationMessages = {};

                $.each(fields, function(index, field) {
                    console.log(`🏗️ Processing field ${index}:`, field);

                    let fieldHtml = `<div class="form-group">
                        <label for="${field.name}">${field.label} ${field.required ? '*' : ''}</label>`;

                    if (field.type === 'select') {
                        fieldHtml += `<select class="form-control select2" id="${field.name}" name="${field.name}">`;
                        fieldHtml += `<option value="">Selecciona una opción</option>`;

                        $.each(field.options, function(i, option) {
                            const selected = field.value == option.value ? 'selected' : '';
                            fieldHtml += `<option value="${option.value}" ${selected}>${option.label}</option>`;
                        });

                        fieldHtml += `</select>`;
                    } else {
                        fieldHtml += `<input type="text" class="form-control" id="${field.name}" name="${field.name}" value="${field.value ?? ''}">`;
                    }

                    fieldHtml += `</div>`;
                    $formContainer.append(fieldHtml);

                    if (field.required) {
                        validationRules[field.name] = { required: true };
                        validationMessages[field.name] = `${field.label} es obligatorio.`;
                    }
                });

                console.log('📋 Total fields added:', $formContainer.find('.form-group').length);
                console.log('🔧 Validation rules:', validationRules);

                // Initialize select2
                $formContainer.find('select.select2').select2({
                    width: '100%',
                    placeholder: 'Selecciona una opción'
                });

                // Apply validation
                $.extend($.validator.messages, this.validationMessages[this.endpoints.iso]);

                const $form = $formContainer.closest('form');
                console.log('📝 Form found for validation:', $form.length);

                $form.validate({
                    rules: validationRules,
                    messages: validationMessages
                    // Remove submitHandler to prevent infinite loop
                    // Our submit event listener will handle the actual submission
                });

                // Set form action based on edit mode
                const actionUrl = isEdit
                    ? `/modules/alsernetcustomer/controllers/routes.php?action=editaddress&modalitie=address&iso=${this.endpoints.iso}`
                    : `/modules/alsernetcustomer/controllers/routes.php?action=addaddress&modalitie=address&iso=${this.endpoints.iso}`;

                console.log('🎯 Setting form action to:', actionUrl);
                $form.attr('action', actionUrl);
            }

            // Handle country change to load states
            async handleCountryChange(event) {
                const $countrySelect = $(event.currentTarget);
                const selectedCountryId = $countrySelect.val();
                const $form = $countrySelect.closest('form');

                // Remove existing state field
                $form.find('#id_state').closest('.form-group').remove();

                if (selectedCountryId) {
                    try {
                        const response = await $.ajax({
                            url: `/modules/alsernetcustomer/controllers/routes.php?action=getstates&modalitie=address&id_country=${selectedCountryId}&iso=${this.endpoints.iso}`,
                            type: 'GET',
                            dataType: 'json'
                        });

                        if (response.status === 'success' && response.options && response.options.length > 0) {
                            let fieldHtml = `<div class="form-group">
                                <label for="id_state">${response.label} *</label>
                                <select class="form-control select2" id="id_state" name="id_state">
                                    <option value="">Selecciona una opción</option>`;

                            $.each(response.options, function(i, option) {
                                fieldHtml += `<option value="${option.value}">${option.label}</option>`;
                            });

                            fieldHtml += `</select></div>`;

                            $countrySelect.closest('.form-group').after(fieldHtml);

                            // Initialize select2 for new state field
                            $('#id_state').select2({
                                width: '100%',
                                placeholder: 'Selecciona una opción'
                            });
                        }
                    } catch (error) {
                        console.error('❌ Error loading states:', error);
                    }
                }
            }

            // Load customer addresses from server
            async loadCustomerAddresses() {
                try {
                    const response = await $.ajax({
                        url: `/modules/alsernetcustomer/controllers/routes.php?action=getaddresses&modalitie=address&iso=${this.endpoints.iso}`,
                        type: 'GET',
                        dataType: 'json'
                    });

                    const container = $('#list-address');
                    container.empty();

                    if (response.status === 'success' && response.html) {
                        // Backend returns rendered HTML, insert it directly
                        container.html(response.html);
                    } else {
                        // Show empty state message
                        container.html(`<div class="col-12 text-center py-4 text-muted">${response.message || 'No hay direcciones registradas'}</div>`);
                    }
                } catch (error) {
                    console.error('❌ Error loading addresses:', error);
                    $('#list-address').html(`<div class="col-12 text-center py-4 text-danger">Error al cargar las direcciones.</div>`);
                }
            }

            showToast(type, message) {
                // Try to use toastr first (like original)
                if (typeof toastr !== 'undefined') {
                    toastr[type](message, '', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                } else if (window.showToast && typeof window.showToast === 'function') {
                    window.showToast(type, message);
                } else if (window.prestashop && window.prestashop.notification) {
                    if (type === 'success') {
                        window.prestashop.notification.showSuccessMessage(message);
                    } else {
                        window.prestashop.notification.showErrorMessage(message);
                    }
                } else {
                    // Fallback: simple alert
                    console.log(`${type.toUpperCase()}: ${message}`);
                    if (type === 'error') {
                        alert(message);
                    }
                }
            }

            destroy() {
                console.log('🗑️ Destroying AddressCustomerHandler...');
                $(document).off('.addressCustomer');
                this.initialized = false;
                console.log('🗑️ Address customer handler destroyed');
            }
        }

        // Export the class
        window.AddressCustomerHandler = AddressCustomerHandler;

        // Create safe instance
        if (!(window.addressCustomerHandler instanceof AddressCustomerHandler)) {
            window.addressCustomerHandler = new AddressCustomerHandler(window.customerManager);
        }
        console.log('📤 AddressCustomerHandler instance ready');

        // Initialize when DOM is ready
        $(document).ready(function() {
            console.log('🔍 Checking for address page elements...');
            console.log('Elements found:', {
                'customer-addresses': $('.customer-addresses').length,
                'address-list': $('.address-list').length,
                'customer-account': $('.customer-account').length,
                'list-address': $('#list-address').length,
                'btn-add-addresses': $('.btn-add-addresses').length
            });

            // Check if we're on the addresses page
            if ($('.customer-addresses, .address-list, .customer-account, #list-address, .btn-add-addresses').length) {
                console.log('🎯 Address page detected, initializing handler...');

                if (!window.addressCustomerHandler.initialized) {
                    window.addressCustomerHandler.init();
                }

                // If there's a list-address container, load addresses automatically
                if ($('#list-address').length) {
                    console.log('📋 Address container found, loading addresses...');
                    setTimeout(function() {
                        if (window.addressCustomerHandler && window.addressCustomerHandler.initialized) {
                            console.log('🚀 Loading customer addresses...');
                            window.addressCustomerHandler.loadCustomerAddresses();
                        } else {
                            console.log('❌ Handler not ready after timeout');
                        }
                    }, 500);
                }
            } else {
                console.log('ℹ️ Not on address page, skipping initialization');
            }
        });

    } // End of guard condition

})();