/**
 * Checkout Block Manager
 * Maneja la lógica de productos bloqueados en el checkout
 */
(function () {
    'use strict';

    if (typeof window === 'undefined') return;
    if (typeof jQuery === 'undefined') {
        console.warn('jQuery no encontrado. El manejador de productos bloqueados requiere jQuery.');
        return;
    }
    const $ = jQuery;

    class CheckoutBlockManager {
        constructor() {

            this.state = {
                suppressBlockedModal: false,
                activeRequests: new Map(),
                updatingSummary: false,   // ✅ guard para evitar doble summary
                deletingItem: false       // ✅ guard para evitar doble delete
            };
            this.init();
        }

        init() {
            this.bindEvents();
            console.log('✅ CheckoutBlockManager inicializado');
        }

        bindEvents() {
            // 🔧 PRIORITY: Remove any existing handlers first to avoid conflicts
            $(document).off('click.checkout-block', '.delete-blocked-product');
            $(document).off('click', '.block-modal .delete-blocked-product');
            $(document).off('click', '.delete-blocked-product');

            console.log('🔧 Binding CheckoutBlockManager events with priority');

            // Eventos para el modal de productos bloqueados - USE NAMESPACE for priority
            $(document).on('click.checkout-block-manager', '.block-modal .delete-blocked-product', this.handleBlockedProductDelete.bind(this));
            $(document).on('click.checkout-block-manager', '.block-modal .change-blocked-delivery', this.handleChangeDeliveryAddress.bind(this));

            console.log('✅ CheckoutBlockManager events bound with namespace: checkout-block-manager');
        }

        // Método para cambiar dirección desde el modal de bloqueados

        handleChangeDeliveryAddress(event) {
            event.preventDefault();

            try {
                $('#block-modal').modal('hide');
                $('#block-modal').on('hidden.bs.modal.delivery-redirect', () => {
                    this.navigateToDeliveryAddresses();
                });

            } catch (error) {
                console.error('Error navigating to delivery address:', error);
                this.showNotification('Error navigating to addresses', 'error');
            }
        }


        async navigateToDeliveryAddresses() {
            try {

                console.log('Navigating to delivery addresses without validations...');

                // Method 1: Use CheckoutNavigator's direct navigation
                if (window.checkoutNavigator && typeof window.checkoutNavigator.navigateToStepOnly === 'function') {
                    try {
                        await window.checkoutNavigator.navigateToStepOnly('address', true);
                        console.log('✅ Successfully navigated to addresses step (no validations, accordion opened)');
                        return;
                    } catch (stepError) {
                        console.warn('CheckoutNavigator.navigateToStepDirect failed:', stepError);
                    }
                }

            } catch (error) {
                console.error('Error in navigateToDeliveryAddresses:', error);
                this.showNotification('Please navigate to the Addresses section manually', 'error');
            }
        }

        async handleBlockedProductDelete(event) {
            console.log('🗑️ handleBlockedProductDelete called');
            event.preventDefault();

            // ✅ Evita reentradas (doble click/duplicados por bubbling)
            if (this.state.deletingItem) {
                console.log('⏳ Deletion already in progress, skipping duplicate click');
                return;
            }

            const $btn = $(event.currentTarget);
            console.log('🔍 Delete button clicked:', $btn[0]);

            const productData = this.getProductDataFromButton($btn);

            if (!productData.id_product) {
                console.error('❌ Product data missing:', productData);
                this.showNotification('Product data missing', 'error');
                return;
            }

            console.log('✅ Product data valid, proceeding with deletion:', productData);

            try {
                this.state.deletingItem = true;

                // Usa CheckoutManager.makeRequest si existe
                let response;
                if (window.checkoutManager && typeof window.checkoutManager.makeRequest === 'function') {
                    console.log('🌐 Making request to:', window.checkoutManager.endpoints.checkout.deleteproduct);
                    console.log('🌐 Request data:', productData);

                    response = await window.checkoutManager.makeRequest(
                        window.checkoutManager.endpoints.checkout.deleteproduct,
                        { method: 'POST', data: productData, autoRetry: true }
                    );
                } else {
                    // Fallback fetch
                    const formData = new URLSearchParams(productData);
                    const res = await fetch(this.endpoints.deleteproduct, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData
                    });
                    response = await res.json();
                }

                console.log('🌐 Server response:', response);

                if (response.status === 'success') {
                    const $productItem = $btn.closest('.blocked-product-item');
                    const productId = productData.id_product;
                    const productAttrId = productData.id_product_attribute || 0;

                    console.log(`Product ${productId}:${productAttrId} removed successfully`);

                    $productItem.fadeOut(() => {
                        $productItem.remove();
                        this.checkIfAllBlockedProductsRemoved();
                    });

                    // ✅ RUTA ÚNICA DE REFRESCO PARA EVITAR DOBLE SUMMARY:
                    // Nada de cart.loadCart(), nada de validate() directo, nada de loadCheckoutSummary() aquí.
                    if (window.checkoutManager?.updateCartSummaryAfterDeletion) {
                        // Internamente ya limpia caches y decide cómo refrescar
                        await window.checkoutManager.updateCartSummaryAfterDeletion();
                    }

                    // if (window.checkoutManager?.revalidateFromAddresses) {
                    // Ejecuta validación centralizada (esta podría actualizar summary UNA vez)
                    //   await window.checkoutManager.revalidateFromAddresses();
                    // }

                } else {
                    this.showNotification(response.message || 'Failed to remove product', 'error');
                }

            } catch (error) {
                console.error('Error deleting blocked product:', error);
                this.showNotification('Connection error. Please try again.', 'error');
            } finally {
                this.state.deletingItem = false;
                $btn.removeClass('loading').prop('disabled', false);
            }
        }

        // Método para mostrar mensajes de error
        // Método para mostrar mensajes de error
        showErrorMessage(message) {
            if (typeof window.settings?.showToast === 'function') {
                window.settings.showToast('error', message);
            } else {
                console.error('Toast not available:', message);
                alert(message); // Fallback
            }
        }

        // Método para saber si debemos suprimir el modal de bloqueados
        shouldSuppressBlockedModal() {
            return this.state.suppressBlockedModal;
        }

        // Helper methods
        showNotification(message, type = 'info') {
            if (typeof window.settings?.showToast === 'function') {
                window.settings.showToast(type, message);
            } else {
                console.log(`${type.toUpperCase()}: ${message}`);
                if (type === 'error') {
                    alert(`Error: ${message}`);
                }
            }
        }

        getProductDataFromButton($btn) {
            const productData = {
                id_cart: $btn.data('id-cart') || $btn.attr('data-id-cart'),
                id_product: $btn.data('id-product') || $btn.attr('data-id-product'),
                id_product_attribute: $btn.data('id-product-attribute') || $btn.attr('data-id-product-attribute') || 0,
                id_customization: $btn.data('id-customization') || $btn.attr('data-id-customization') || 0,
                minimal_quantity: $btn.data('minimal-quantity') || $btn.attr('data-minimal-quantity') || 1
            };

            console.log('🔍 Product data extracted from button:', productData);
            console.log('🔍 Button attributes:', {
                'data-id-cart': $btn.attr('data-id-cart'),
                'data-id-product': $btn.attr('data-id-product'),
                'data-id-product-attribute': $btn.attr('data-id-product-attribute'),
                'data-id-customization': $btn.attr('data-id-customization'),
                'data-minimal-quantity': $btn.attr('data-minimal-quantity')
            });

            return productData;
        }

        checkIfAllBlockedProductsRemoved() {
            const $remainingProducts = $('.blocked-product-item');
            if ($remainingProducts.length === 0) {
                console.log('All blocked products removed, closing modal');
                $('#block-modal').modal('hide');
            }
        }

    }

    // Inicializar y exportar a ámbito global
    window.checkoutBlockManager = new CheckoutBlockManager();
})();