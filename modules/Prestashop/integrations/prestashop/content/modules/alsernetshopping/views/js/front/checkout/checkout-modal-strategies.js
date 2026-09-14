/**
 * Estrategias para manejo de modales de validación
 * Implementa el patrón Strategy para mejorar mantenibilidad
 */
(function () {
    'use strict';

    if (typeof window === 'undefined') return;
    if (typeof jQuery === 'undefined') {
        console.warn('jQuery no encontrado. Las estrategias de modales requieren jQuery.');
        return;
    }
    const $ = jQuery;

    // Error type constants for validation consistency
    const ERROR_TYPES = {
        NO_ADDRESSES_REGISTERED: 'no_addresses_registered',
        MISSING_DELIVERY_ADDRESS: 'missing_delivery_address',
        MISSING_INVOICE_ADDRESS: 'missing_invoice_address',
        MISSING_VAT: 'missing_vat',
        BLOCKED_PRODUCTS: 'blocked',
        PRODUCT_AVAILABILITY: 'product_availability'
    };

    // Clase base abstracta para estrategias de modal
    class ModalStrategy {
        constructor() {
            if (this.constructor === ModalStrategy) {
                throw new Error('ModalStrategy es una clase abstracta');
            }
        }

        /**
         * Obtiene el ID del modal asociado
         * @returns {string}
         */
        getModalId() {
            throw new Error('Método getModalId() debe ser implementado');
        }

        /**
         * Selectores de modales a remover antes de mostrar este
         * @returns {string}
         */
        getModalsToRemove() {
            return '#block-modal, #need-invoice-mandatory-modal, #missing-invoice-address-modal, #missing-vat-modal, #missing-delivery-address-modal';
        }

        /**
         * Configura los eventos específicos del modal
         * @returns {void}
         */
        bindEvents() {
            throw new Error('Método bindEvents() debe ser implementado');
        }

        /**
         * Procesa los datos antes de mostrar el modal
         * @param {Object} errorData
         * @param {Object} context
         */
        processData(errorData, context) {
            // Implementación base - puede ser sobrescrita
        }

        /**
         * Acciones adicionales después de mostrar el modal
         * @param {jQuery} $modal
         */
        onModalShown($modal) {
            // Implementación base - puede ser sobrescrita
            // console.log('Modal mostrado:', $modal.attr('id'));
        }
    }

    // Estrategia: no hay direcciones registradas
    class NoAddressesRegisteredModalStrategy extends ModalStrategy {
        getModalId() {
            return '#no-addresses-modal'; // Modal estático del template
        }

        bindEvents() {
            // Manejar el botón "Agregar Dirección" - FIXED MULTIPLE LISTENERS
            $(document)
                .off('click.modalStrategy', '#no-addresses-modal .add-new-address')
                .on('click.modalStrategy', '#no-addresses-modal .add-new-address', (e) => {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    const type = $(e.currentTarget).data('type') || 'delivery';

                    console.log(`➕ Adding new address from no-addresses modal: ${type}`);

                    // Quitar foco del botón antes de ocultar modal
                    $(e.currentTarget).blur();

                    // Cerrar el modal
                    $('#no-addresses-modal').modal('hide');

                    // Limpiar backdrops
                    setTimeout(() => {
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }, 100);

                    // Abrir formulario de nueva dirección
                    setTimeout(() => {
                        if (window.addressStepHandler && typeof window.addressStepHandler.loadNewAddressFields === 'function') {
                            console.log('✅ Using AddressStepHandler to load new address fields');
                            window.addressStepHandler.selectedAddressType = type;

                            // Check if we need to use handleAddAddressButton instead
                            if (typeof window.addressStepHandler.handleAddAddressButton === 'function') {
                                console.log('✅ handleAddAddressButton method exists, calling it...');
                                window.addressStepHandler.handleAddAddressButton({
                                    preventDefault: () => {},
                                    currentTarget: { dataset: { type: type } }
                                });
                            } else {
                                window.addressStepHandler.loadNewAddressFields(type);
                            }
                        } else {
                            console.warn('⚠️ AddressStepHandler not available, using fallback navigation');
                            // Fallback: navegar al paso de direcciones
                            if (window.checkoutNavigator?.navigateToStep) {
                                window.checkoutNavigator.navigateToStep('address', true, true);
                            }
                        }
                    }, 300);
                });
        }

        getModalsToRemove() {
            return '#block-modal, #need-invoice-mandatory-modal, #missing-invoice-address-modal, #missing-vat-modal, #missing-delivery-address-modal, #no-addresses-modal';
        }
    }

    // Estrategia: dirección de envío faltante
    class MissingDeliveryAddressModalStrategy extends ModalStrategy {
        getModalId() {
            return '#missing-delivery-address-modal';
        }

        bindEvents() {
            $(document)
                .off('click', '#missing-delivery-address-modal .btn-select-delivery-address')
                .on('click', '#missing-delivery-address-modal .btn-select-delivery-address', function (e) {
                    e.preventDefault();

                    // Quitar foco del botón antes de ocultar modal (accesibilidad)
                    $(this).blur();

                    // Cerrar modal completamente y limpiar backdrop
                    $('#missing-delivery-address-modal').modal('hide');

                    // Forzar limpieza inmediata del backdrop
                    setTimeout(() => {
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                        $('#missing-delivery-address-modal').remove();
                    }, 100);

                    // Navegar a delivery addresses
                    setTimeout(() => {
                        // Verificar si ya estamos en el step de addresses
                        const isAddressesStepOpen = $('#checkout-addresses-step').is(':visible') ||
                            $('.step-current').attr('id') === 'checkout-addresses-step' ||
                            $('#js-delivery').is(':visible');

                        if (isAddressesStepOpen) {
                            // Ya estamos en addresses, ir directamente a la sección de delivery
                            const $deliverySection = $('#js-delivery, #delivery-addresses');
                            if ($deliverySection.length) {
                                $deliverySection[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
                                // Enfocar el primer elemento interactivo
                                setTimeout(() => {
                                    $deliverySection.find('input[type="radio"]:first, select:first, button:first').focus();
                                }, 300);
                            }
                        } else {
                            // Cargar el step de addresses y luego ir a delivery
                            if (window.checkoutNavigator?.navigateToStep) {
                                window.checkoutNavigator?.navigateToStep('address', true, true).then(() => {
                                    // Una vez cargado, ir a la sección de delivery
                                    setTimeout(() => {
                                        const $deliverySection = $('#js-delivery, #delivery-addresses');
                                        if ($deliverySection.length) {
                                            $deliverySection[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
                                        }
                                    }, 200);
                                });
                            } else {
                                // Fallback: usar URL directa
                                window.location.href = window.location.href.split('?')[0] + '?step=addresses#delivery-addresses';
                            }
                        }
                    }, 150);
                });
        }

        getModalsToRemove() {
            return '#block-modal, #need-invoice-mandatory-modal, #missing-invoice-address-modal, #missing-vat-modal, #missing-delivery-address-modal';
        }
    }

    // Estrategia: dirección de facturación faltante
    class MissingInvoiceAddressModalStrategy extends ModalStrategy {
        getModalId() {
            return '#missing-invoice-address-modal';
        }

        bindEvents() {
            $(document)
                .off('click', '#missing-invoice-address-modal .btn-select-invoice-address')
                .on('click', '#missing-invoice-address-modal .btn-select-invoice-address', function (e) {
                    e.preventDefault();

                    // Quitar foco del botón antes de ocultar modal (accesibilidad)
                    $(this).blur();

                    // Cerrar modal completamente y limpiar backdrop
                    $('#missing-invoice-address-modal').modal('hide');

                    // Forzar limpieza inmediata del backdrop
                    setTimeout(() => {
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                        $('#missing-invoice-address-modal').remove();
                    }, 100);

                    // Ir al step de addresses para seleccionar dirección de facturación
                    setTimeout(() => {
                        if (window.checkoutNavigator?.navigateToStep) {
                            window.checkoutNavigator?.navigateToStep('address', true, true);
                        }

                        // Esperar a que se cargue el step y configurar para invoice address
                        setTimeout(() => {
                            // Marcar "I want an invoice" si no está marcado
                            const $needInvoice = $('#need_invoice');
                            if (!$needInvoice.is(':checked')) {
                                $needInvoice.prop('checked', true).trigger('change');
                            }

                            // Marcar "Use a different address" para mostrar invoice addresses
                            // NO usar .trigger('change') para evitar que se abra el modal de crear nueva dirección
                            const $invoiceRadio = $('input[name="address_invoide"][value="1"]');
                            if (!$invoiceRadio.is(':checked')) {
                                $invoiceRadio.prop('checked', true);
                            }

                            // Asegurar que la sección de invoice addresses sea visible
                            $('.invoice-addresses').removeClass('d-none');

                            // Hacer scroll suave al área de invoice addresses
                            setTimeout(() => {
                                const $invoiceSection = $('.invoice-addresses');
                                if ($invoiceSection.length && $invoiceSection.offset()) {
                                    $('html, body').animate({
                                        scrollTop: $invoiceSection.offset().top - 100
                                    }, 800);
                                }
                            }, 200);
                        }, 500);
                    }, 300);
                });
        }
    }

    // Estrategia: VAT number faltante
    class MissingVATModalStrategy extends ModalStrategy {
        getModalId() {
            return '#missing-vat-modal';
        }

        bindEvents() {
            // Botón para cambiar dirección de facturación
            // OBJETIVO: Ir al step addresses -> área específica de invoice delivery para seleccionar otra dirección
            $(document)
                .off('click', '#missing-vat-modal .btn-change-invoice-address')
                .on('click', '#missing-vat-modal .btn-change-invoice-address', function (e) {
                    e.preventDefault();

                    // Quitar foco del botón antes de ocultar modal (accesibilidad)
                    $(this).blur();

                    // Cerrar modal completamente y limpiar backdrop
                    $('#missing-vat-modal').modal('hide');

                    // Forzar limpieza inmediata del backdrop
                    setTimeout(() => {
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                        $('#missing-vat-modal').remove();
                    }, 100);

                    setTimeout(() => {

                        // Esperar un poco más para que el step se cargue completamente
                        setTimeout(() => {
                            // Marcar "I want an invoice" si no está marcado
                            const $needInvoice = $('#need_invoice');
                            if (!$needInvoice.is(':checked')) {
                                $needInvoice.prop('checked', true).trigger('change');
                            }

                            // Marcar "Use a different address" para mostrar invoice addresses
                            // NO usar .trigger('change') para evitar que se abra el modal de crear nueva dirección
                            const $invoiceRadio = $('input[name="address_invoide"][value="1"]');
                            if (!$invoiceRadio.is(':checked')) {
                                $invoiceRadio.prop('checked', true);
                            }

                            // Asegurar que la sección de invoice addresses sea visible
                            $('.invoice-addresses').removeClass('d-none');

                            // Hacer scroll suave al área de invoice addresses
                            setTimeout(() => {
                                const $invoiceSection = $('.invoice-addresses');
                                if ($invoiceSection.length && $invoiceSection.offset()) {
                                    $('html, body').animate({
                                        scrollTop: $invoiceSection.offset().top - 100
                                    }, 800);
                                }
                            }, 200);
                        }, 500);
                    }, 300);
                });

            // Botón para editar dirección actual
            // OBJETIVO: Abrir modal de edición de la dirección actual y enfocar campo VAT
            $(document)
                .off('click', '#missing-vat-modal .btn-edit-current-address')
                .on('click', '#missing-vat-modal .btn-edit-current-address', function (e) {
                    e.preventDefault();

                    const addressId = $(this).data('address-id');

                    // Quitar foco del botón antes de ocultar modal (accesibilidad)
                    $(this).blur();

                    console.log('🚪 Cerrando modal missing-vat-modal');

                    // 🔒 SUPPRESSION FLAG: Suprimir validaciones VAT mientras editamos
                    if (window.checkoutManager) {
                        window.checkoutManager.state.suppressVATValidation = true;
                        console.log('🔒 VAT validation suppressed for address editing');
                    }

                    // Cerrar modal completamente y limpiar backdrop - MEJORADO
                    const $modal = $('#missing-vat-modal');
                    if ($modal.length) {
                        $modal.modal('hide');

                        // Esperar a que termine la animación de cierre
                        $modal.one('hidden.bs.modal', () => {
                            console.log('✅ Modal cerrado completamente');
                        });
                    }

                    // Forzar limpieza inmediata del backdrop - MEJORADO
                    setTimeout(() => {
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');

                        // Limpiar cualquier modal de VAT que pueda quedar
                        $('.modal[id*="vat"]').remove();
                        $('.modal[class*="vat"]').remove();
                        $('#missing-vat-modal').remove();

                        console.log('🧹 Limpieza forzada de modales VAT completada');
                    }, 150);

                    // Abrir directamente el modal de edición (ya estamos en addresses)
                    setTimeout(() => {
                        if (addressId) {
                            console.log('Buscando botón de editar para dirección ID:', addressId);

                            // 🆕 Actualizar selectores para coincidir con AddressStepHandler
                            let $editButton = $(`.edit-address-checkout[data-id-address="${addressId}"]`).first();

                            if (!$editButton.length) {
                                $editButton = $(`.address-box-item[data-id-address="${addressId}"] .edit-address-checkout`).first();
                            }

                            if (!$editButton.length) {
                                $editButton = $(`.editAddressCheckout[data-id-address="${addressId}"]`).first();
                            }

                            if (!$editButton.length) {
                                $editButton = $(`[data-id-address="${addressId}"] .editAddressCheckout`).first();
                            }

                            if (!$editButton.length) {
                                $editButton = $(`.address-item[data-id="${addressId}"] .edit-address`).first();
                            }

                            // 🆕 Como último recurso, usar AddressStepHandler directamente
                            if (!$editButton.length && window.addressStepHandler) {
                                console.log('⚠️ Botón no encontrado, usando AddressStepHandler directamente');

                                // Buscar la dirección box por ID
                                const $addressBox = $(`.address-box-item[data-id-address="${addressId}"]`).first();
                                if ($addressBox.length) {
                                    console.log('✅ AddressBox encontrado, disparando handleEditAddressButton');

                                    // Simular evento de click en edit button
                                    const fakeEvent = {
                                        preventDefault: () => {},
                                        currentTarget: $addressBox.find('.edit-address-checkout, .editAddressCheckout').first()[0] || $addressBox[0]
                                    };

                                    window.addressStepHandler.handleEditAddressButton(fakeEvent);
                                    return; // Salir aquí para evitar los logs de abajo
                                }
                            }

                            console.log('Botón de editar encontrado:', $editButton.length > 0, $editButton);

                            if ($editButton.length) {
                                console.log('Haciendo click en botón de editar');
                                $editButton.trigger('click');

                                // Enfocar campo VAT number una vez que se abra el modal de edición
                                setTimeout(() => {
                                    console.log('Buscando campo VAT');
                                    const $vatField = $('#vat_number, input[name="vat_number"], .address-form input[name="vat_number"], .modal input[name="vat_number"]');
                                    console.log('Campo VAT encontrado:', $vatField.length > 0, $vatField);

                                    if ($vatField.length) {
                                        $vatField.focus().addClass('highlight-field');

                                        // Scroll hasta el campo si es necesario
                                        if ($vatField[0] && $vatField[0].scrollIntoView) {
                                            $vatField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                                        }

                                        // Remover highlight cuando el usuario comience a escribir
                                        $vatField.one('input keydown focus', function() {
                                            $(this).removeClass('highlight-field');
                                        });

                                        // 🔓 RESTORE VAT VALIDATION: Cuando el usuario comience a escribir en el campo VAT
                                        $vatField.one('input keydown focus', function() {
                                            if (window.checkoutManager) {
                                                window.checkoutManager.state.suppressVATValidation = false;
                                                console.log('🔓 VAT validation restored - user is interacting with VAT field');
                                            }
                                        });
                                    } else {
                                        console.warn('Campo VAT no encontrado, intentando de nuevo...');

                                        // Segundo intento después de más tiempo
                                        setTimeout(() => {
                                            const $vatField2 = $('input[type="text"][name*="vat"], input[placeholder*="VAT"], input[placeholder*="NIT"]');
                                            if ($vatField2.length) {
                                                $vatField2.focus().addClass('highlight-field');

                                                // Remover highlight cuando el usuario comience a escribir (segundo intento)
                                                $vatField2.one('input keydown focus', function() {
                                                    $(this).removeClass('highlight-field');
                                                });

                                                // 🔓 RESTORE VAT VALIDATION: Para el segundo intento también
                                                $vatField2.one('input keydown focus', function() {
                                                    if (window.checkoutManager) {
                                                        window.checkoutManager.state.suppressVATValidation = false;
                                                        console.log('🔓 VAT validation restored - user is interacting with VAT field (attempt 2)');
                                                    }
                                                });
                                            }
                                        }, 500);
                                    }
                                }, 1000); // Más tiempo para que se abra el modal de edición
                            } else {
                                console.warn('No se encontró el botón de editar para la dirección ID:', addressId);
                                console.log('Botones disponibles:', $('.editAddressCheckout, [data-id-address], .edit-address'));
                            }
                        }
                    }, 100);
                });
        }
    }

    // Estrategia: productos bloqueados
    class BlockedProductsModalStrategy extends ModalStrategy {
        getModalId() {
            return '#block-modal';
        }

        bindEvents() {
            // No es necesario añadir eventos aquí, se manejan en checkout-block.js
            // Si el manejador de bloques aún no está disponible, usar comportamiento legacy
            if (!window.checkoutBlockManager) {
                // Botón para eliminar producto
                $(document)
                    .off('click', '#block-modal .delete-to-product')
                    .on('click', '#block-modal .delete-to-product', function (e) {
                        e.preventDefault();

                        const $button = $(this);

                        // Quitar foco del botón antes de ocultar modal (accesibilidad)
                        $button.blur();
                        if (window.cart?.handleDeleteFromCart) {
                            window.cart.handleDeleteFromCart($button, false);
                        }

                        $('#block-modal').removeClass('in show').hide();
                        $('body').removeClass('modal-open').css('padding-right', '');
                        $('.modal-backdrop').remove();

                        if (window.checkoutNavigator?.loadCheckout) window.checkoutNavigator.loadCheckout();
                        if (window.cart?.loadCart) window.cart.loadCart();
                    });

                // Botón para cambiar dirección
                $(document)
                    .off('click', '#block-modal .change-address')
                    .on('click', '#block-modal .change-address', function (e) {
                        e.preventDefault();

                        // Quitar foco del botón antes de ocultar modal (accesibilidad)
                        $(this).blur();

                        if (window.checkoutNavigator?.navigateToStep) {
                            window.checkoutNavigator?.navigateToStep('address');
                        }
                        $('#block-modal').modal('hide');
                    });
            }
        }

        processData(errorData, context) {
            // Verificar si debemos suprimir el modal de bloqueados
            if (window.checkoutBlockManager?.shouldSuppressBlockedModal()) {
                errorData.suppressModal = true;
            }
        }

        onModalShown($modal) {
            // No es necesario hacer nada aquí para productos bloqueados
        }
    }

// Estrategia: disponibilidad de productos (legacy)
    class ProductAvailabilityModalStrategy extends ModalStrategy {
        getModalId() {
            return '#error-modal';
        }

        bindEvents() {
            console.log('🎯 Binding ProductAvailability modal events for #error-modal');

            // Botón para eliminar producto con stock insuficiente
            $(document)
                .off('click', '#error-modal .delete-to-product')
                .on('click', '#error-modal .delete-to-product', (e) => {
                    e.preventDefault();
                    const $button = $(e.currentTarget);

                    console.log('🗑️ Deleting product with insufficient stock');

                    // Obtener datos del producto del botón
                    const productData = {
                        id_cart: $button.data('id-cart'),
                        id_product: $button.data('id-product'),
                        id_product_attribute: $button.data('id-product-attribute') || 0
                    };

                    // Cerrar modal
                    $button.blur();
                    $('#error-modal').modal('hide');

                    // Limpiar backdrop
                    setTimeout(() => {
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }, 100);

                    // Eliminar producto del carrito
                    this.deleteProductFromCart(productData);
                });

            // Botón de cancelar
            $(document)
                .off('click', '#error-modal .btn-secondary, #error-modal [data-dismiss="modal"]')
                .on('click', '#error-modal .btn-secondary, #error-modal [data-dismiss="modal"]', function (e) {
                    e.preventDefault();
                    $(this).blur();
                    $('#error-modal').modal('hide');
                });
        }

        async deleteProductFromCart(productData) {
            try {
                console.log('🛒 Deleting product from cart:', productData);

                if (window.checkoutManager && typeof window.checkoutManager.makeRequest === 'function') {
                    // Usar el endpoint de checkout para eliminar producto
                    const response = await window.checkoutManager.makeRequest(
                        window.checkoutManager.endpoints.checkout.deleteproduct,
                        {
                            method: 'POST',
                            data: productData,
                            autoRetry: true
                        }
                    );

                    if (response?.status === 'success') {

                        console.log('✅ Product deleted successfully');

                        // Actualizar el checkout y revalidar
                        if (window.checkoutManager.updateCartSummaryAfterDeletion) {
                            await window.checkoutManager.updateCartSummaryAfterDeletion();
                        }

                        // Forzar revalidación sin cache
                        setTimeout(() => {
                            window.checkoutManager.validate({ force: true, autoNavigate: false });
                        }, 500);

                    } else {
                        throw new Error(response?.message || 'Failed to delete product');
                    }
                } else {
                    console.warn('CheckoutManager not available for product deletion');
                }

            } catch (error) {
                console.error('❌ Error deleting product:', error);
                if (typeof window.settings?.showToast === 'function') {
                    window.settings.showToast('error', 'Error al eliminar el producto');
                }
            }
        }

        getModalsToRemove() {
            return '#block-modal, #need-invoice-mandatory-modal, #missing-invoice-address-modal, #missing-vat-modal, #missing-delivery-address-modal, #error-modal';
        }
    }

// Contexto que maneja las estrategias
    class ModalStrategyContext {
        constructor() {
            // Mapa de tipos de error a estrategias
            this.strategies = {
                no_addresses_registered: new NoAddressesRegisteredModalStrategy(),
                missing_delivery_address: new MissingDeliveryAddressModalStrategy(),
                missing_invoice_address: new MissingInvoiceAddressModalStrategy(),
                missing_vat: new MissingVATModalStrategy(),
                blocked: new BlockedProductsModalStrategy(),
                product_availability: new ProductAvailabilityModalStrategy()
            };

            // Eventos genéricos para cerrar modales
            this.bindGenericEvents();
        }

        /**
         * Maneja la visualización del modal según el tipo de error
         * @param {Object} errorData
         */
        handleModal(errorData) {
            if (!errorData || !errorData.type) {
                console.warn('errorData inválido:', errorData);
                return;
            }

            // 🔒 CHECK: Suppress VAT validation if editing address
            if (errorData.type === 'missing_vat' && window.checkoutManager?.state?.suppressVATValidation) {
                console.log('🔒 VAT modal suppressed - user is editing address');
                return;
            }

            const strategy = this.strategies[errorData.type];
            if (!strategy) {
                console.warn('No se encontró estrategia para el tipo de error:', errorData.type);
                return;
            }

            // Solo limpiar backdrops, NO modales todavía
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css({
                'padding-right': '',
                'overflow': ''
            });

            // Remover específicamente el modal que vamos a crear para evitar duplicados
            const modalId = strategy.getModalId();
            $(modalId).remove();

            // Esperar un poco antes de agregar el nuevo modal
            setTimeout(() => {
                // Agregar el nuevo modal al DOM solo si no existe ya
                if (errorData.modal_html && !$(modalId).length) {
                    $('body').append(errorData.modal_html);
                }

                // Procesar datos específicos
                strategy.processData(errorData, this);

                // Obtener referencia al modal
                let $modal = $(modalId).first();

                if ($modal.length) {
                    console.log('✅ Modal encontrado:', modalId);

                    // Configurar eventos específicos
                    strategy.bindEvents();

                    // Asegurar que no hay backdrops antes de mostrar
                    setTimeout(() => {
                        // Solo limpiar backdrops, NO modales
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css({
                            'padding-right': '',
                            'overflow': ''
                        });

                        // Mostrar modal
                        console.log('🎯 Mostrando modal:', modalId);
                        console.log('📋 Modal state before show:', $modal.hasClass('show'), $modal.hasClass('in'), $modal.css('display'));

                        $modal.modal('show');

                        // Forzar clases Bootstrap 3 inmediatamente después de show()
                        setTimeout(() => {
                            if ($modal.hasClass('fade') && !$modal.hasClass('in')) {
                                console.log('🔧 Adding missing "in" class to modal');
                                $modal.addClass('in');
                            }
                            if ($('.modal-backdrop.fade').length > 0 && !$('.modal-backdrop').hasClass('in')) {
                                console.log('🔧 Adding missing "in" class to backdrop');
                                $('.modal-backdrop').addClass('in');
                            }
                        }, 10);

                        // Verificar estado después de show()
                        setTimeout(() => {
                            console.log('📋 Modal state after show:', $modal.hasClass('show'), $modal.hasClass('in'), $modal.css('display'));
                            console.log('📋 Modal visibility:', $modal.is(':visible'), $modal.length);
                            console.log('📋 Body modal-open:', $('body').hasClass('modal-open'));
                            console.log('📋 Backdrop exists:', $('.modal-backdrop').length);

                            // Si el modal no se está mostrando, intentar forzarlo
                            if (!$modal.is(':visible') || !$modal.hasClass('show') || !$modal.hasClass('in')) {
                                console.warn('⚠️ Modal not showing properly, attempting force display with Bootstrap 3 classes');
                                $modal.addClass('show in').css({'display': 'block'});
                                $('body').addClass('modal-open');
                                if ($('.modal-backdrop').length === 0) {
                                    $('body').append('<div class="modal-backdrop fade show in"></div>');
                                } else {
                                    // Fix existing backdrop
                                    $('.modal-backdrop').addClass('in');
                                }
                            }
                        }, 50);

                        // Acciones post-mostrar
                        strategy.onModalShown($modal);

                        // DESPUÉS de mostrar exitosamente, limpiar otros modales conflictivos
                        setTimeout(() => {
                            const modalsToRemove = strategy.getModalsToRemove();
                            if (modalsToRemove && modalsToRemove !== modalId) {
                                $(modalsToRemove).not(modalId).modal('hide');
                            }
                        }, 100);
                    }, 50);
                } else {
                    console.error('❌ Modal no encontrado en el DOM:', modalId);
                    console.log('📋 Available modals:', $('.modal').map(function() { return '#' + this.id; }).get());
                    console.log('📋 ErrorData type:', errorData.type);

                    // Debug: Verificar si el modal existe pero con diferente ID
                    const $allModals = $('.modal');
                    console.log('📋 All modals in DOM:', $allModals.length);
                    $allModals.each(function(i) {
                        console.log(`📋 Modal ${i}:`, this.id, this.className);
                    });
                }
            }, 150);
        }

        /**
         * Limpia completamente todos los modales y backdrops
         */
        cleanupAllModals() {
            // Cerrar todos los modales abiertos
            $('.modal.show').modal('hide');

            // Forzar eliminación de backdrops
            $('.modal-backdrop').remove();

            // Limpiar clases del body
            $('body').removeClass('modal-open').css({
                'padding-right': '',
                'overflow': ''
            });

            // Limpiar cualquier modal en fade
            $('.modal.fade.in, .modal.fade.show').removeClass('in show');

            console.log('🧹 All modals and backdrops cleaned');
        }

        /**
         * Configura eventos genéricos para todos los modales
         */
        bindGenericEvents() {
            // Clean event listeners first - ENHANCED CLEANUP
            $(document).off('click.modalStrategy hidden.bs.modal.modalStrategy click.addressStep click.noAddressesModal');

            // Event for dismiss buttons
            $(document)
                .on('click.modalStrategy', '[data-dismiss="modal"]', function () {
                    const $modal = $(this).closest('.modal');
                    $modal.modal('hide');
                });

            // Global cleanup when any modal is hidden
            $(document)
                .on('hidden.bs.modal.modalStrategy', '.modal', () => {
                    // Force cleanup after modal is hidden
                    setTimeout(() => {
                        this.cleanupAllModals();
                    }, 100);
                });
        }

        /**
         * Verifica si el tipo de error es soportado
         * @param {string} errorType
         * @returns {boolean}
         */
        supportsErrorType(errorType) {
            return Object.prototype.hasOwnProperty.call(this.strategies, errorType);
        }

        /**
         * Obtiene la lista de tipos de error soportados
         * @returns {Array<string>}
         */
        getSupportedErrorTypes() {
            return Object.keys(this.strategies);
        }

        /**
         * Analiza el estado actual y muestra el modal apropiado para direcciones
         * Este método es llamado desde AddressStepHandler
         */
        showApropriateModal() {
            console.log('🔍 ModalStrategyContext: Analyzing addresses for appropriate modal');

            // Evitar múltiples ejecuciones simultáneas
            if (this._modalAnalysisInProgress) {
                console.log('⏳ Modal analysis already in progress, skipping');
                return;
            }

            // CHECK: Skip if backend validation already handled this
            if (this.hasBackendValidationHandled()) {
                console.log('⏭️ Backend validation already handled, skipping ModalStrategyContext check');
                return;
            }

            this._modalAnalysisInProgress = true;

            // Limpiar cualquier modal existente primero
            this.cleanupAllModals();

            const $form = $('.step-checkout-address');
            if (!$form.length) {
                console.log('📋 Address form not found, skipping modal analysis');
                this._modalAnalysisInProgress = false;
                return;
            }

            const isVirtual = ($form.data('is-virtual') === 1 || $form.data('is-virtual') === '1');
            const needInvoice = $('#need_invoice').is(':checked');

            // Contar direcciones disponibles
            const deliveryAddresses = $('.address-box-item[data-type="delivery"]').length;
            const invoiceAddresses = $('.address-box-item[data-type="invoice"]').length;

            // Verificar direcciones seleccionadas
            const deliverySelected = $form.find('input[name="id_address_delivery"]:checked').val() ||
                $form.find('input[name="id_address_delivery"]').val();

            const invoiceSelected = needInvoice ? (
                $form.find('input[name="id_address_invoice"]:checked').val() ||
                $form.find('input[name="id_address_invoice"]').val()
            ) : null;

            console.log('📋 Address analysis:', {
                isVirtual,
                needInvoice,
                deliveryAddresses,
                invoiceAddresses,
                deliverySelected,
                invoiceSelected,
                deliveryRequired: !isVirtual,
                invoiceRequired: needInvoice
            });

            setTimeout(() => {
                // Prioridad 1: Sin direcciones registradas (más crítico)
                if (!isVirtual && deliveryAddresses === 0) {
                    console.log('🚨 No delivery addresses registered - showing no addresses modal');
                    this.handleModal({
                        type: ERROR_TYPES.NO_ADDRESSES_REGISTERED,
                        modal_html: this.generateNoAddressesModal('delivery')
                    });
                    this._modalAnalysisInProgress = false;
                    return;
                }

                // Prioridad 2: Need invoice pero no hay direcciones de facturación
                if (needInvoice && invoiceAddresses === 0) {
                    console.log('🚨 Need invoice but no invoice addresses - showing no addresses modal');
                    this.handleModal({
                        type: ERROR_TYPES.NO_ADDRESSES_REGISTERED,
                        modal_html: this.generateNoAddressesModal('invoice')
                    });
                    this._modalAnalysisInProgress = false;
                    return;
                }

                // Prioridad 3: Falta dirección de envío seleccionada
                if (!isVirtual && !deliverySelected) {
                    console.log('⚠️ Delivery address required but not selected - showing missing delivery modal');
                    this.handleModal({
                        type: ERROR_TYPES.MISSING_DELIVERY_ADDRESS,
                        modal_html: this.generateMissingDeliveryModal()
                    });
                    this._modalAnalysisInProgress = false;
                    return;
                }

                // Prioridad 4: Falta dirección de facturación seleccionada
                if (needInvoice && !invoiceSelected) {
                    console.log('⚠️ Invoice address required but not selected - showing missing invoice modal');
                    this.handleModal({
                        type: ERROR_TYPES.MISSING_INVOICE_ADDRESS,
                        modal_html: this.generateMissingInvoiceModal()
                    });
                    this._modalAnalysisInProgress = false;
                    return;
                }

                console.log('✅ All address requirements satisfied - no modal needed');
                this._modalAnalysisInProgress = false;
            }, 100);
        }

        /**
         * Genera el HTML del modal de "no hay direcciones registradas"
         * @param {string} type - 'delivery' o 'invoice'
         */
        generateNoAddressesModal(type) {
            const typeLabel = type === 'delivery' ? 'envío' : 'facturación';
            const typeTitle = type === 'delivery' ? 'Delivery' : 'Invoice';

            return `
                <div id="no-addresses-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title h6 text-sm-left">
                                    ${typeTitle} address required
                                </h4>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true"><i class="material-icons">close</i></span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 divide-right modal-content-descriptions">
                                        <div class="title">
                                            You don't have any ${typeLabel} addresses registered. Please add a new address to continue.
                                        </div>
                                    </div>
                                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 modal-content-buttons">
                                        <button type="button" class="btn btn-primary add-new-address w-100" data-type="${type}">
                                            Add new address
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        /**
         * Genera el HTML del modal de dirección de envío faltante
         */
        generateMissingDeliveryModal() {
            return `
                <div id="missing-delivery-address-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title h6 text-sm-left">
                                    Delivery address required
                                </h4>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true"><i class="material-icons">close</i></span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 divide-right modal-content-descriptions">
                                        <div class="title">
                                            Please select a delivery address to continue with your order.
                                        </div>
                                    </div>
                                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 modal-content-buttons">
                                        <button type="button" class="btn btn-primary btn-select-delivery-address w-100">
                                            Select delivery address
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        /**
         * Genera el HTML del modal de dirección de facturación faltante
         */
        generateMissingInvoiceModal() {
            return `
                <div id="missing-invoice-address-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title h6 text-sm-left">
                                    Invoice address required
                                </h4>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true"><i class="material-icons">close</i></span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 divide-right modal-content-descriptions">
                                        <div class="title">
                                            Please select an invoice address to continue with your order.
                                        </div>
                                    </div>
                                    <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 modal-content-buttons">
                                        <button type="button" class="btn btn-primary btn-select-invoice-address w-100">
                                            Select invoice address
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        /**
         * Check if backend validation already handled address validation
         * This prevents duplicate modals from showing
         */
        hasBackendValidationHandled() {
            // Check if CheckoutManager validation already processed errors
            if (window.checkoutManager?.state?.validationStatus?.lastResult === 'error') {
                console.log('🔍 Backend validation already processed errors');
                return true;
            }

            // Check if there's already a no-addresses modal showing
            const $existingModal = $('#no-addresses-modal, #dynamic-no-addresses-modal');
            if ($existingModal.length > 0 && ($existingModal.hasClass('show') || $existingModal.hasClass('in'))) {
                console.log('🔍 No addresses modal already showing from backend');
                return true;
            }

            // Check if any modal is currently showing (likely from backend)
            if ($('.modal.show').length > 0) {
                const showingModal = $('.modal.show').attr('id');
                console.log('🔍 Modal already showing from backend:', showingModal);
                return true;
            }

            return false; // No backend validation detected
        }

        /**
         * Resetea el estado de las estrategias
         */
        reset() {
            this._modalAnalysisInProgress = false;
            this.cleanupAllModals();
        }
    }

    // 🔓 GLOBAL LISTENER: Restore VAT validation when edit address modal is closed
    $(document).on('hidden.bs.modal', '.modal', function() {
        const $modal = $(this);

        // Check if this is an address edit modal
        if ($modal.find('input[name="vat_number"], .address-form').length > 0 ||
            $modal.attr('id')?.includes('address') ||
            $modal.hasClass('address-modal')) {

            // Restore VAT validation when address edit modal is closed
            if (window.checkoutManager?.state?.suppressVATValidation) {
                window.checkoutManager.state.suppressVATValidation = false;
                console.log('🔓 VAT validation restored - address modal closed');
            }
        }
    });

// Exportar para uso global
// Export to global scope
    window.ModalStrategyContext = ModalStrategyContext;
    window.CHECKOUT_ERROR_TYPES = ERROR_TYPES;
})();