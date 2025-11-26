/**
 * Enhanced Cart Management System
 * Optimized for speed, reliability, and maintainability
 */

/**
 * Enhanced Cart Management System
 * Optimized for speed, reliability, and maintainability
 */

class CartManager {
    constructor() {
        // Prevent multiple instances
        if (window.cartManagerInstance) {
            console.warn('CartManager instance already exists, returning existing instance');
            return window.cartManagerInstance;
        }

        this.config = {
            debounceDelay: 250,
            maxRetries: 3,
            retryDelay: 500,
            timeout: 10000,
            cacheTimeout: 30000
        };

        this.state = {
            activeRequests: new Map(),
            updateInProgress: new Set(),
            debounceTimers: new Map(),
            cache: new Map(),
            retryAttempts: new Map(),
            processingProducts: new Set(), // Track products being processed
            lastClickTimestamp: 0, // Global click timestamp tracking
            clickCooldown: 1000 // 1 second cooldown between clicks
        };

        this.endpoints = this.getEndpoints();
        window.cartManagerInstance = this;
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupGlobalErrorHandler();
        this.preloadCriticalData();
    }

    getEndpoints() {
        const segments = window.location.pathname.split('/');
        const iso = (segments[1] && segments[1].length === 2) ? segments[1] : 'es';
        const prefix = (iso.toLowerCase() !== 'es') ? `/${iso}` : '';
        const baseUrl = `${prefix}/modules/alsernetshopping/routes`;

        return {
            baseUrl,
            iso,
            cart: {
                init: `${baseUrl}?modalitie=cart&action=init&iso=${iso}`,
                count: `${baseUrl}?modalitie=cart&action=count&iso=${iso}`,
                summary: `${baseUrl}?modalitie=cart&action=summary&iso=${iso}`,
                add: `${baseUrl}?modalitie=cart&action=add&iso=${iso}`,
                update: `${baseUrl}?modalitie=cart&action=update&iso=${iso}`,
                delete: `${baseUrl}?modalitie=cart&action=delete&iso=${iso}`,
                modal: `${baseUrl}?modalitie=cart&action=modal&iso=${iso}`,
                modalcomplementary: `${baseUrl}?modalitie=cart&action=modalcomplementary&iso=${iso}`,
                coupon: `${baseUrl}?modalitie=cart&action=coupon&iso=${iso}`,
                deleteCoupon: `${baseUrl}?modalitie=cart&action=deletecoupon&iso=${iso}`
            }
        };
    }

    async makeRequest(url, options = {}) {
        const requestKey = `${url}-${JSON.stringify(options.data || {})}`;

        // ULTRA DEBUGGING: Log every single request
        const callStack = new Error().stack;
        console.log('🌐 MAKING REQUEST:', {
            url,
            data: options.data,
            method: options.method || 'GET',
            requestKey,
            callStack: callStack.split('\n').slice(1, 6).join('\n')
        });

        // Cancel duplicate requests
        if (this.state.activeRequests.has(requestKey)) {
            console.log('❌ CANCELLING DUPLICATE REQUEST:', requestKey);
            this.state.activeRequests.get(requestKey).abort();
        }

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.config.timeout);

        const requestOptions = {
            method: options.method || 'GET',
            signal: controller.signal,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            }
        };

        if (options.data) {
            if (requestOptions.method === 'GET') {
                const params = new URLSearchParams(options.data);
                url += (url.includes('?') ? '&' : '?') + params.toString();
            } else {
                requestOptions.body = new URLSearchParams(options.data);
            }
        }

        this.state.activeRequests.set(requestKey, controller);

        try {
            const response = await fetch(url, requestOptions);
            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            this.state.activeRequests.delete(requestKey);
            this.state.retryAttempts.delete(requestKey);

            return data;
        } catch (error) {
            clearTimeout(timeoutId);
            this.state.activeRequests.delete(requestKey);

            // Retry logic
            const attempts = this.state.retryAttempts.get(requestKey) || 0;
            if (attempts < this.config.maxRetries && !error.message.includes('cancelled')) {
                this.state.retryAttempts.set(requestKey, attempts + 1);
                await this.delay(this.config.retryDelay * (attempts + 1));
                return this.makeRequest(url, options);
            }

            throw error;
        }
    }

    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    debounce(key, callback, delay = this.config.debounceDelay) {
        if (this.state.debounceTimers.has(key)) {
            clearTimeout(this.state.debounceTimers.get(key));
        }

        const timerId = setTimeout(() => {
            this.state.debounceTimers.delete(key);
            callback();
        }, delay);

        this.state.debounceTimers.set(key, timerId);
    }

    getProductData(element) {
        const $element = $(element);
        // Si el propio elemento no tiene el atributo, sube al contenedor más cercano
        const $source = $element.data('id-product') ? $element : $element.closest('.item-product, .product-item');

        const product_quantity = document.getElementById('product_quantity');

        // Solo hacer blur si el input existe
        if (product_quantity) {
            product_quantity.blur();
        }

        // Asignar 1 si no existe el input; si existe, usar su valor (o 1 si está vacío)
        const minimalQuantity = product_quantity
            ? (product_quantity.value.trim() !== "" ? product_quantity.value.trim() : 1)
            : 1;


        const productData = {
            id_product: $source.data('id-product') || $source.attr('data-id-product') || null,
            id_product_attribute: $source.data('id-product-attribute') || $source.attr('data-id-product-attribute') || 0,
            id_cart: $source.data('id-cart') || $source.attr('data-id-cart') || null,
            id_customization: $source.data('id-customization') || $source.attr('data-id-customization') || 0,
            minimal_quantity: minimalQuantity
        };

        console.log('🔍 Product data extracted:', productData);
        console.log('🔍 Raw attributes:', {
            'data-id-cart': $source.attr('data-id-cart'),
            'data-id-product': $source.attr('data-id-product'),
            'data-id-product-attribute': $source.attr('data-id-product-attribute'),
            'data-id-customization': $source.attr('data-id-customization'),
            'data-minimal-quantity': $source.attr('data-minimal-quantity')
        });

        return productData;
    }


    async loadCart(fromProduct = false, skipSummary = false) {

        try {
            this.showLoading();

            const response = await this.makeRequest(this.endpoints.cart.init);

            if (response && response.status === 'success') {
                try {
                    this.renderCart(response, fromProduct);

                    if ($('body').hasClass('page-cart') && !skipSummary) {
                        await this.loadCartSummary();
                    }
                } catch (renderError) {
                    console.error('Error during cart rendering:', renderError);
                    this.renderEmptyCart();
                }
            } else {
                console.warn('Invalid or failed cart response:', response);
                this.handleCartError();
            }

            return response;
        } catch (error) {
            this.handleError(error, 'Failed to load cart');
            this.handleCartError();
        } finally {
            this.hideLoading();
        }
    }

    renderCart(response, fromProduct = false) {
        try {
            // Ensure response exists and has required data
            if (!response || typeof response !== 'object') {
                console.warn('Invalid cart response:', response);
                return this.renderEmptyCart();
            }

            // Render cart element with error boundary
            try {
                if (response.cart) {
                    const $cartElement = $(response.cart);
                    $('.cart-dropdown.cart-offcanvas').remove();

                    const $target = $('.page-wrapper').length ? $('.page-wrapper') : $('body');
                    $target.append($cartElement);

                    if (fromProduct) {
                        $('.cart-dropdown.cart-offcanvas').addClass('opened');
                    }
                } else {
                    console.warn('No cart HTML in response');
                }
            } catch (cartError) {
                console.error('Error rendering cart HTML:', cartError);
                // Continue with other operations even if cart HTML fails
            }

            // Update cart count with error boundary
            try {
                $('.cart-actions .cart-count, .sticky-cart .cart-count').text(response.count || 0);
            } catch (countError) {
                console.error('Error updating cart count:', countError);
            }

            // Show cart container on cart page with error boundary
            try {
                if ($('body').hasClass('page-cart')) {
                    $('.cart-container').removeClass('d-none');
                }
            } catch (containerError) {
                console.error('Error showing cart container:', containerError);
            }

            // Events are already bound in constructor - don't bind again!
            console.log('🔧 renderCart: Skipping bindCartEvents to prevent duplicate handlers');

        } catch (error) {
            console.error('Critical error in renderCart:', error);
            this.renderEmptyCart();
        }
    }

    async loadCartSummary() {
        try {


            const response = await this.makeRequest(this.endpoints.cart.summary);

            if (response && response.status === 'success') {
                try {
                    this.renderCartSummary(response);
                } catch (renderError) {
                    console.error('Error rendering cart summary:', renderError);
                    this.renderEmptyCheckout();
                }
            } else {
                console.warn('Invalid or failed cart summary response:', response);
                this.renderEmptyCheckout();
            }

            return response;
        } catch (error) {
            this.handleError(error, 'Failed to load cart summary');
        }
    }

    renderCartSummary(response) {
        try {
            if (!$('body').hasClass('page-cart')) return;

            // Show cart containers with error boundary
            try {
                $('.cart-container').removeClass('d-none');
                $('.cart-container-process').addClass('d-none');
            } catch (containerError) {
                console.error('Error managing cart containers:', containerError);
            }

            // Validate response
            if (!response || typeof response !== 'object') {
                console.warn('Invalid cart summary response:', response);
                this.renderEmptyCheckout();
                return;
            }

            // Handle empty cart FIRST (before checking errors)
            if (response.empty) {
                this.renderEmptyCheckout();
                return;
            }

            // ALWAYS render checkout content first, even if there are errors
            try {
                $('.checkout-container').removeClass('d-none');
                $('.checkout-empty-container').addClass('d-none');
            } catch (checkoutError) {
                console.error('Error managing checkout containers:', checkoutError);
            }

            // Render each section independently - PRIORITY: render content first
            this.renderSection('.container-products', response.products, 'products');
            this.renderSection('.container-summary', response.summary, 'summary');
            this.renderSection('.container-shipping', response.shipping, 'shipping');

            // AFTER rendering content, handle errors with delay to not block UI
            if (response.error && response.error.error) {
                setTimeout(() => {
                    try {
                        this.showErrorModal(response.error);
                    } catch (modalError) {
                        console.error('Error showing error modal:', modalError);
                        this.showError(response.error.message || 'Error in cart');
                    }
                }, 100); // Small delay to ensure content renders first
            }

        } catch (error) {
            console.error('Critical error in renderCartSummary:', error);
            this.renderEmptyCheckout();
        }
    }

    async updateQuantity(input, delta = 0, opts = {}) {
        const { manualTrack = null } = opts;   // 'add' | 'remove' | null
        const $input = $(input);
        const data = this.getProductData($input);
        const productKey = `${data.id_product}_${data.id_product_attribute}`;

        if (!data.id_product || this.state.updateInProgress.has(productKey)) return;

        const currentQty = parseInt($input.val(), 10) || 1;
        const newQty = Math.max(1, currentQty + delta);

        // Optimistic update
        $input.val(newQty);

        this.debounce(productKey, async () => {
            this.state.updateInProgress.add(productKey);

            try {
                const response = await this.makeRequest(this.endpoints.cart.update, {
                    data: {
                        id_product: data.id_product,
                        id_product_attribute: data.id_product_attribute,
                        id_cart: data.id_cart,
                        quantity: 1,
                        op: delta > 0 ? 'up' : 'down'
                    }
                });

                if (response.status === 'success') {
                    // 🔔 Tracking según lo pedido:
                    if (manualTrack === 'add') {
                        await this.trackAddToCart({ ...data, qty: 1 }, response);
                    } else if (manualTrack === 'remove') {
                        await this.trackRemoveFromCart({ ...data, qty: 1 }, response);
                    } else {
                        // fallback (si no se pide manual), mantiene tu flujo anterior
                        await this.trackUpdateQuantity(data, response, delta);
                    }

                    this.clearCache(['cart-init', 'cart-summary']);

                    if ($('body').hasClass('page-cart')) {
                        await this.loadCartSummary();
                    } else if ($('body').hasClass('page-order')) {
                        await this.loadCart();
                        // if (window.checkoutManager?.executeValidations) {
                        //      await window.checkoutManager.executeValidations(true, false);
                        //}
                    } else {
                        await this.loadCart();
                    }
                } else {
                    // Revert optimistic
                    $input.val(currentQty);
                    this.showError(response.message);
                }
            } catch (error) {
                // Revert optimistic
                $input.val(currentQty);
                this.handleError(error, 'Failed to update quantity');
            } finally {
                this.state.updateInProgress.delete(productKey);
            }
        });
    }


    /**
     * Detecta si un producto requiere personalización basándose en etiquetas CSS
     */
    isCustomizableProduct(element) {
        const $element = $(element);

        // 🎯 MÉTODO PRINCIPAL: Usar datos del backend para detectar view="custom"
        const productData = this.getProductData($element);
        if (productData && productData.view === 'custom') {
            console.log('✅ Product is customizable via view=custom:', {
                productId: productData.id_product,
                view: productData.view
            });
            return true;
        }

        // 🔄 FALLBACK: Métodos anteriores para compatibilidad
        const $productContainer = $element.closest('.product-item, .item-product, [data-id-product]');

        // Detectar por clases CSS específicas
        const customizableClasses = [
            'customizable-product',
            'idx-custom-product',
            'personalization-required',
            'custom-options-available'
        ];

        // Verificar si tiene alguna de las clases personalizables
        const hasCustomClass = customizableClasses.some(className =>
            $productContainer.hasClass(className)
        );

        // Verificar si existe el contenedor de personalización de IdxrCustomProduct
        const hasIdxContainer = $productContainer.find('#idxrcustomproduct_container, .idxrcustomproduct-options, [id^="idxrcustomproduct"], #submit_idxrcustomproduct, #component_steps_container').length > 0 ||
            $('#submit_idxrcustomproduct, #component_steps_container').length > 0;

        // Verificar por atributo de datos específico
        const hasCustomAttribute = $productContainer.attr('data-customizable') === 'true' ||
            $productContainer.attr('data-idx-custom') === 'true';

        const isCustomizable = hasCustomClass || hasIdxContainer || hasCustomAttribute;

        console.log('🔍 Customizable product detection (fallback):', {
            element: $element[0],
            container: $productContainer[0],
            hasCustomClass,
            hasIdxContainer,
            hasCustomAttribute,
            isCustomizable
        });

        return isCustomizable;
    }

    /**
     * Detecta si IdxrCustomProduct está disponible y configurado
     */
    isIdxrCustomProductAvailable() {
        const checks = [
            // JavaScript globals
            typeof window.idxrcustomproduct !== 'undefined',
            typeof window.IdxrCustomProduct !== 'undefined',
            typeof url_ajax !== 'undefined', // Variable global de IdxrCustomProduct

            // DOM elements
            $('.idxrcustomproduct-container').length > 0,
            $('#idxrcustomproduct_container').length > 0,
            $('#submit_idxrcustomproduct').length > 0,
            $('#component_steps_container').length > 0,
            $('.sel_opt').length > 0, // Opciones de personalización
            $('.sel_opt_extra').length > 0,

            // CSS classes that indicate customization
            $('.customizable-product').length > 0,
            $('[data-customizable]').length > 0,
            $('.idxr-custom').length > 0
        ];

        const available = checks.some(check => check);

        if (available) {
            console.log('✅ IdxrCustomProduct detected via:', {
                globals: typeof window.idxrcustomproduct !== 'undefined' || typeof url_ajax !== 'undefined',
                domElements: $('.sel_opt').length > 0,
                customClasses: $('.customizable-product').length > 0 || $('[data-customizable]').length > 0
            });
        }

        return available;
    }

    /**
     * Obtiene las opciones de personalización seleccionadas
     */
    getCustomizationData(element) {
        const $element = $(element);
        const customData = {};

        // 🎯 MÉTODO EXACTO COMO FRONT.JS ORIGINAL: Replicar lógica exacta
        // Buscar opciones seleccionadas (.sel_opt) - REPLICANDO FRONT.JS LÍNEAS 247-271
        $('.sel_opt').each(function () {
            const $opt = $(this);

            // 🔍 CONDICIONES EXACTAS DEL FRONT.JS ORIGINAL:
            // 1. No debe estar oculto: !$(this).parent().parent().hasClass("hidden")
            // 2. Debe tener contenido: $(this).html()
            // 3. No debe ser "false": $(this).html() != "false"
            if (!$opt.parent().parent().hasClass("hidden") && $opt.html() && $opt.html() !== "false") {
                const optValue = $opt.html();
                const optId = $opt.attr('id');

                if (optId) {
                    const parts = optId.split('_');
                    if (parts.length >= 3) {
                        const stepId = parts[2];

                        // Manejar múltiples opciones separadas por &
                        let optionsSelected = [];
                        if (optValue.indexOf("&") > 0) {
                            const options = optValue.split('&');
                            options.forEach(opt => {
                                optionsSelected.push(parseInt(opt.replace('amp;', '')));
                            });
                        } else {
                            optionsSelected.push(parseInt(optValue));
                        }

                        // Construir string exactamente como front.js
                        optionsSelected.forEach(value => {
                            let qty = '';
                            const qtyElement = $(`#option_${stepId}_${value}_qty`);
                            if (qtyElement.length && qtyElement.val() > 1) {
                                qty = qtyElement.val() + 'x';
                            }

                            customData[`step_${stepId}`] = qty + stepId + '_' + value;
                            console.log(`📋 Found option: step_${stepId} = ${qty}${stepId}_${value}`);
                        });
                    }
                }
            }
        });

        // Buscar información extra (.sel_opt_extra) - REPLICANDO FRONT.JS LÍNEAS 273-276
        $('.sel_opt_extra').each(function () {
            const $opt = $(this);
            const optValue = $opt.html();
            const optId = $opt.attr('id');

            if (optId && optValue) {
                const parts = optId.split('_');
                if (parts.length >= 4) {
                    const extraId = parseInt(parts[3]);
                    const jsonValue = JSON.stringify(optValue.replace('3x7r4', 'extra'));
                    customData[`extra_${extraId}`] = extraId + '_' + jsonValue;
                    console.log(`📋 Found extra: extra_${extraId} = ${extraId}_${jsonValue}`);
                }
            }
        });

        // Buscar archivos y inputs de personalización
        $('input[type="file"][name*="idxrcustomproduct"], select[name*="idxrcustomproduct"], input[name*="idxrcustomproduct"]:not([type="file"])').each(function () {
            const $input = $(this);
            const value = $input.val();
            if (value && value.trim() !== "") {
                const name = $input.attr('name') || $input.attr('id') || '';
                if ($input.attr('type') === 'file') {
                    customData.files = customData.files || [];
                    customData.files.push(value);
                    console.log(`📋 Found file: ${value}`);
                } else if (name.includes('step') || name.includes('option')) {
                    customData[name] = value;
                    console.log(`📋 Found input: ${name} = ${value}`);
                }
            }
        });

        return Object.keys(customData).length > 0 ? customData : null;
    }

    async addToCart(button) {

        const $btn = $(button).closest('[data-id-product]'); // ✅ asegura que sea el que tiene data-*
        if (!$btn.length) {
            this.showError('Product data missing');
            return;
        }

        // Prevent double clicks/submissions on the same button
        if ($btn.prop('disabled') || $btn.hasClass('loading') || $btn.data('processing')) {
            console.log('❌ Add to cart already in progress, ignoring duplicate request');
            return;
        }

        const data = this.getProductData($btn);

        if (!data.id_product) {
            this.showError('Product data missing');
            return;
        }

        // Create product key to prevent duplicate requests for same product
        const productKey = `${data.id_product}-${data.id_product_attribute}`;

        // Check if this SPECIFIC product is already being processed
        if (this.state.processingProducts.has(productKey)) {
            console.log('❌ Product already being added to cart, ignoring duplicate request for product:', productKey);
            return;
        }

        // 🎯 DETECCIÓN SIMPLIFICADA: Usar datos del producto
        const isCustomizable = this.isCustomizableProduct($btn);
        const hasIdxrCustomProduct = this.isIdxrCustomProductAvailable();
        const useCustomFlow = isCustomizable && hasIdxrCustomProduct;

        console.log('🔍 Product analysis:', {
            productKey,
            productView: data.view,
            isCustomizable,
            hasIdxrCustomProduct,
            useCustomFlow,
            buttonId: $btn.attr('id'),
            buttonClass: $btn.attr('class')
        });


        // AGGRESSIVE DEBUGGING: Log every single call attempt
        const callStack = new Error().stack;
        console.log('🚨 ADDTOCART CALLED:', {
            buttonClasses: $btn.attr('class'),
            buttonId: $btn.attr('id'),
            productKey: productKey,
            callStack: callStack.split('\n').slice(1, 4).join('\n')
        });

        // Mark product as being processed
        this.state.processingProducts.add(productKey);
        console.log('✅ Starting addToCart process for product:', productKey);

        // Mark button as processing to prevent rapid clicks
        $btn.prop('disabled', true).addClass('loading').data('processing', true);

        try {
            // Decidir qué flujo usar
            if (useCustomFlow) {
                await this.addCustomizableProductToCart($btn, data);
            } else {
                //console.log('⚡ Using standard AlsernetShopping flow');
                await this.addStandardProductToCart($btn,data);

            }


        } catch (error) {
            this.handleError(error, 'Failed to add product to cart');
        } finally {
            // Remove product from processing set
            this.state.processingProducts.delete(productKey);

            // Re-enable button after a short delay
            setTimeout(() => {
                $btn.prop('disabled', false).removeClass('loading').removeData('processing');
            }, 500);
        }
    }


    /**
     * Flujo estándar de AlsernetShopping
     */
    async addStandardProductToCart($btn, data) {

        await this.reloadModal(data, $btn);

        const response = await this.makeRequest(this.endpoints.cart.add, {
            method: 'POST',
            data
        });

        if (response.status === 'success') {
            // GTM tracking for add_to_cart (GTMCartHelper)
            await this.trackAddToCart(data, response);
            // Reload cart to show updated count and products
            await this.loadCart(false);

        } else {
            this.showError(response.message);
        }


    }

    /**
     * Flujo personalizado usando la estructura de IdxrCustomProduct - INTEGRADO CON BACKEND
     */
    async addCustomizableProductToCart(button, data) {
        const $btn = $(button);
        const customizationData = this.getCustomizationData($btn);

        console.log('🎨 PROCESSING CUSTOMIZABLE PRODUCT:', {
            buttonId: $btn.attr('id'),
            productData: data,
            customizationDataFound: !!customizationData,
            customizationData: customizationData,
            selOptElements: $('.sel_opt').length,
            selOptExtraElements: $('.sel_opt_extra').length
        });

        // Para productos con view="custom", siempre permitir (puede que los datos estén en elementos específicos)
        if (!customizationData && data.view !== 'custom') {
            this.showError('Please select customization options');
            return;
        }

        console.log('🎨 Processing customizable product with integrated backend flow:', {
            productData: data,
            customizationData: customizationData || {}
        });

        try {
            // Formatear datos para el backend
            const customizationString = this.formatCustomizationForIdxr(customizationData);
            const extraString = this.formatExtraDataForIdxr(customizationData);

            // Enviar directamente al endpoint add() con datos de personalización
            // El backend se encargará de todo el flujo de IdxrCustomProduct automáticamente
            const response = await this.makeRequest(this.endpoints.cart.add, {
                method: 'POST',
                data: {
                    id_product: data.id_product,
                    id_product_attribute: data.id_product_attribute || 0,
                    quantity: data.quantity || 1,
                    custom: customizationString,      // 🎨 Datos de personalización
                    extra: extraString                // 🎨 Datos extra
                }
            });

            if (response.status === 'success') {
                console.log('✅ Customizable product added via integrated backend flow');

                // Disparar tracking
                await this.trackAddToCart(data, response);

                // Limpiar cache y recargar
                this.clearCache();

                // Si el backend creó un producto personalizado, usar su ID para el modal
                const modalData = response.custom_product_id ?
                    { ...data, id_product: response.custom_product_id } : data;

                console.log('🎨 Custom product created with ID:', modalData.id_product);
                console.log('🎨 Response data:', response);

                await Promise.all([
                    this.loadCart(false),
                    this.reloadModal(modalData.id_product, modalData.id_product_attribute || 0)
                ]);

            } else {
                throw new Error(response.message || 'Failed to add customizable product');
            }

        } catch (error) {
            console.error('❌ Error adding customizable product:', error);

            // Si el backend sugiere fallback, intentar flujo estándar
            if (error.response?.fallback_recommended) {
                console.log('🔄 Backend recommended fallback, using standard flow');
                await this.addStandardProductToCart(data,$btn);
            } else {
                this.showError(error.message || 'Failed to add customizable product');
            }
        }
    }

    // 🎯 Métodos ensureCartExists() y createCustomizedProduct() eliminados
    // Ahora todo el flujo se maneja en el backend de forma integrada

    /**
     * Formatear datos de personalización para IdxrCustomProduct
     * 🎯 ACTUALIZADO: Los datos ya vienen en formato correcto del getCustomizationData
     */
    formatCustomizationForIdxr(customizationData) {
        const customizations = [];

        Object.keys(customizationData).forEach(key => {
            if (key.startsWith('step_')) {
                // Los datos ya vienen formateados como "qty+stepId_value" o "stepId_value"
                const value = customizationData[key];
                customizations.push(value);
            }
        });

        console.log('🎨 formatCustomizationForIdxr result:', customizations.join(','));
        return customizations.join(',');
    }

    /**
     * Formatear datos extra para IdxrCustomProduct
     * 🎯 ACTUALIZADO: Los datos ya vienen en formato correcto del getCustomizationData
     */
    formatExtraDataForIdxr(customizationData) {

        const extraData = [];

        Object.keys(customizationData).forEach(key => {
            if (key.startsWith('extra_')) {
                // Los datos ya vienen formateados como "extraId_jsonValue"
                const value = customizationData[key];
                extraData.push(value);
            }
        });

        console.log('🎨 formatExtraDataForIdxr result:', extraData.join('3x7r4'));
        return extraData.join('3x7r4');
    }

    testProductDetection() {
        console.log('🧪 Testing product detection...');

        $('.add-cart-product').each((index, button) => {
            const $btn = $(button);
            const data = this.getProductData($btn);
            const isCustomizable = this.isCustomizableProduct($btn);
            const hasIdxr = this.isIdxrCustomProductAvailable();

            console.log(`Product ${index + 1}:`, {
                productId: data.id_product,
                isCustomizable,
                hasIdxr,
                classes: $btn.closest('[data-id-product]').attr('class'),
                willUseCustomFlow: isCustomizable && hasIdxr
            });
        });
    }


    async deleteFromCart(button, fromProduct = false, $show = false) {
        const $btn = $(button);
        const data = this.getProductData($btn);

        if (!data.id_product) {
            this.showError('Product data missing');
            return;
        }

        try {

            const response = await this.makeRequest(this.endpoints.cart.delete, {
                data
            });

            if (response.status === 'success') {
                // GTM tracking for remove_from_cart (GTMCartHelper)
                await this.trackRemoveFromCart(data, response);

                this.clearCache();

                if ($('body').hasClass('page-cart')) {
                    await this.loadCart(fromProduct);
                    await this.loadCartSummary();
                    $('#delete-modal, #error-modal').modal('hide');
                } else if ($('body').hasClass('page-order')) {
                    // For checkout page, reload both cart and checkout summary
                    await this.loadCart(fromProduct);
                    if (window.checkoutManager?.updateCartSummary) {
                        await window.checkoutManager.updateCartSummary();
                    }
                    if (window.checkoutManager?.executeValidations) {
                        await window.checkoutManager.executeValidations(true, false);
                    }
                    $('#delete-modal, #error-modal').modal('hide');
                    $('.cart-dropdown').removeClass('opened');
                } else {
                    if (window.checkoutManager?.updateCartSummary) {
                        await window.checkoutManager.updateCartSummary();
                    }
                    await this.loadCart(fromProduct);
                }

                if ($show === true) {
                    this.showSuccess(response.message);
                }
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            this.handleError(error, 'Failed to delete product');
        }
    }

    async applyCoupon(button) {
        const $btn = $(button);
        const coupon = $('#coupon').val().trim();
        const confirmation = $('#confirmation').val().trim();
        const $errorLabel = $('label[for="coupon"].error');

        $errorLabel.hide().text('');

        if (!coupon) {
            $errorLabel.text('Please enter a code.').show();
            return;
        }

        $btn.prop('disabled', true).text('Applying...');

        try {
            const response = await this.makeRequest(this.endpoints.cart.coupon, {
                method: 'POST',
                data: { coupon, confirmation }
            });

            if (response.status === 'success') {
                $('#promotion-code').addClass('d-none');
                $('#coupon, #confirmation').val('');
                this.clearCache();
                await this.loadCart();
                window.checkoutManager.updateCartSummary();
                window.checkoutNavigator.loadCheckoutSummary();
            } else {
                $errorLabel.text(response.message || 'Invalid coupon').show();
            }
        } catch (error) {
            $errorLabel.text('Connection error. Please try again.').show();
            this.handleError(error, 'Failed to apply coupon');
        } finally {
            $btn.prop('disabled', false).text('Apply');
        }
    }

    async deleteCoupon(button) {
        const $btn = $(button);
        const ruleId = $btn.data('rule');
        const code = $btn.data('code');

        if (!ruleId || !code) {
            this.showError('Coupon data missing');
            return;
        }

        try {
            const response = await this.makeRequest(this.endpoints.cart.deleteCoupon, {
                data: { rule: ruleId, code }
            });

            if (response.status === 'success') {
                this.clearCache();
                await this.loadCart();
                this.showSuccess(response.message);
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            this.handleError(error, 'Failed to remove coupon');
        }
    }

    async addComplementaryToCart(button) {
        const $btn = $(button);
        const currentTime = Date.now();

        // GLOBAL COOLDOWN: Prevent any cart action within cooldown period
        if (currentTime - this.state.lastClickTimestamp < this.state.clickCooldown) {
            console.log('❌ GLOBAL COOLDOWN: Ignoring complementary request within cooldown period');
            return;
        }

        // Update global timestamp IMMEDIATELY
        this.state.lastClickTimestamp = currentTime;

        const data = this.getProductData($btn);
        const mainProductId = $btn.data('main-product-id');
        const mainProductAttribute = $btn.data('main-product-attribute');

        if (!data.id_product || !mainProductId) {
            this.showError('Product data missing for complementary product');
            return;
        }

        const productKey = `${data.id_product}-${data.id_product_attribute}`;

        if (this.state.processingProducts.has(productKey)) {
            console.log('❌ Complementary product already being added to cart');
            return;
        }

        this.state.processingProducts.add(productKey);

        // Show loading state
        const originalText = $btn.html();
        $btn.prop('disabled', true).addClass('loading').data('processing', true);

        try {
            const response = await this.makeRequest(this.endpoints.cart.add, {
                method: 'POST',
                data: {
                    ...data,
                    main_product_id: mainProductId,
                    main_product_attribute: mainProductAttribute
                }
            });

            if (response.status === 'success') {
                // GTM tracking for add_to_cart
                await this.trackAddToCart(data, response);

                this.clearCache();

                // Reload modal using MAIN product data (parent)
                await this.reloadComplementaryModal(mainProductId, mainProductAttribute);
            } else {
                this.showError(response.message);
                $btn.prop('disabled', false).removeClass('loading').removeData('processing').html(originalText);
            }
        } catch (error) {
            this.handleError(error, 'Failed to add complementary product to cart');
            $btn.prop('disabled', false).removeClass('loading').removeData('processing').html(originalText);
        } finally {
            this.state.processingProducts.delete(productKey);
        }
    }

    async reloadComplementaryModal(mainProductId, mainProductAttribute) {
        try {
            if (!mainProductId) {
                console.error('Missing main product data for complementary modal reload');
                return;
            }

            const response = await this.makeRequest(this.endpoints.cart.modalcomplementary, {
                data: {
                    id_product: mainProductId,
                    id_product_attribute: mainProductAttribute
                }
            });

            if (response.status === 'success' && response.data) {
                this.forceCloseAllModals();
                $('body').append(response.data);
                $('#shopping-modal').modal('show');
                this.loadCart(false);
                console.log('✅ Complementary modal reloaded with remaining products');
            } else {
                console.log('No more complementary products, closing modal');
            }
        } catch (error) {
            console.error('AJAX error reloading complementary modal:', error);
            this.forceCloseAllModals();
        }
    }

    async reloadCustomProductModal(productId, productAttribute = 0) {
        try {
            if (!productId) {
                console.error('Missing product data for custom product modal reload');
                return;
            }

            console.log('🎨 Reloading custom product modal:', {
                productId,
                productAttribute
            });

            const response = await this.makeRequest(this.endpoints.cart.modal, {
                data: {
                    id_product: productId,
                    id_product_attribute: productAttribute
                }
            });

            if (response.status === 'success' && response.data) {
                this.forceCloseAllModals();
                $('body').append(response.data);
                $('#shopping-modal').modal('show');
                this.loadCart(false);
                console.log('✅ Custom product modal reloaded successfully');
            } else {
                console.log('ℹ️ No custom product modal data available');
            }
        } catch (error) {
            console.error('AJAX error reloading custom product modal:', error);
            this.forceCloseAllModals();
        }
    }

    async reloadModal(data, $btn) {
        try {

            setTimeout(() => {
                $btn.prop('disabled', false).removeClass('loading').removeData('processing');
            }, 100);

            $('#shopping-modal').modal('show');

            //if ($('body').hasClass('product-id-60137')  || $('body').hasClass('product-id-55000') ) {
            // Opción 1: Mostrar modal

            // } else {
            // Opción 2: Redirigir
            //    window.location.href = 'index.php?controller=cart-confirmation&id_product='
            //       + data.id_product
            //       + '&id_product_attribute='
            //        + data.id_product_attribute;
            // }

        } catch (error) {
            this.handleError(error, 'Failed to load modal');
        }
    }

    async loadInitialModal() {
        // Solo ejecutar si estamos en la página de producto
        if (!$('.page-product-default').length) {
            console.log('ℹ️ Not in product page, skipping modal load');
            return;
        }

        try {
            // Leer los valores actuales del formulario
            const productId = $('#id_product').val() || $('input[name="id_product"]').val();
            const productAttribute = $('#id_product_attribute').val() || $('input[name="id_product_attribute"]').val() || 0;

            if (!productId) {
                console.log('ℹ️ Product ID not available yet');
                return;
            }

            console.log('🔄 Loading initial modal (hidden):', {
                productId: productId,
                productAttribute: productAttribute,
                source: 'form inputs'
            });

            const response = await this.makeRequest(this.endpoints.cart.modal, {
                data: {
                    id_product: productId,
                    id_product_attribute: productAttribute
                }
            });

            if (response.status === 'success' && response.data) {
                // Limpiar modales anteriores
                this.forceCloseAllModals();
                // Agregar el nuevo modal al DOM (pero NO mostrarlo)
                $('body').append(response.data);
                $('.add-cart-product').prop('disabled', false);
                console.log('✅ Initial modal loaded (hidden) successfully');
            } else {
                console.log('ℹ️ No modal data available');
            }
        } catch (error) {
            console.error('Error loading initial modal:', error);
        }
    }

    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    forceCloseAllModals(ignore = []) {
        // Solo las modales que controla el carrito (sin incluir fittingModal que es exclusiva)
        const managed = ['#shopping-modal', '#delete-modal', '#error-modal', '#delete-checkout-modal'];

        const toRemove = managed.filter(sel => !ignore.includes(sel));
        if (toRemove.length) {
            $(toRemove.join(',')).modal('hide').remove();

            // Solo remover backdrops de las modales que cerramos
            // Verificar si el modal fitting está abierto
            const fittingModalOpen = $('#fittingModal').is(':visible');
            if (!fittingModalOpen) {
                // Si fitting no está abierto, limpiar todos los backdrops
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css({ 'padding-right': '', 'overflow': '' });
            } else {
                // Si fitting está abierto, solo remover backdrops extra pero preservar el del fitting
                const backdrops = $('.modal-backdrop');
                if (backdrops.length > 1) {
                    // Mantener solo el último backdrop (del fitting)
                    backdrops.not(':last').remove();
                }
            }
        }

        // Limpieza de backdrop/body

    }

    // Cache management
    setCache(key, data) {
        this.state.cache.set(key, {
            data,
            timestamp: Date.now()
        });
    }

    getFromCache(key) {
        const cached = this.state.cache.get(key);
        if (!cached) return null;

        if (Date.now() - cached.timestamp > this.config.cacheTimeout) {
            this.state.cache.delete(key);
            return null;
        }

        return cached.data;
    }

    clearCache(keys = null) {
        if (keys) {
            keys.forEach(key => this.state.cache.delete(key));
        } else {
            this.state.cache.clear();
        }
    }

    // UI feedback methods
    showLoading() {
        $('.cart-loading').removeClass('d-none');
    }

    hideLoading() {
        $('.cart-loading').addClass('d-none');
    }

    showSuccess(message) {
        if (window.settings?.showToast) {
            window.settings.showToast('success', message, 'Cart');
        }
    }

    showError(message) {
        if (window.settings?.showToast) {
            window.settings.showToast('error', message, 'Cart');
        }
    }

    showErrorModal(errorData) {
        try {
            console.log('Showing error modal AFTER content render:', errorData);

            const $modal = $('#error-modal');
            if (!$modal.length) {
                console.error('Error modal not found in DOM');
                this.showError(errorData.message || 'Error occurred');
                return;
            }

            // Configure modal content
            $modal.find('.error-container').html(errorData.message || 'An error occurred');

            if (errorData.id_product) {
                const $deleteButton = $modal.find('.delete-to-product');
                $deleteButton.attr({
                    'data-id-cart': errorData.id_cart,
                    'data-id-product': errorData.id_product,
                    'data-id-product-attribute': errorData.id_product_attribute
                }).show();
            } else {
                $modal.find('.delete-to-product').hide();
            }

            // Show modal with additional delay to ensure content is fully rendered
            setTimeout(() => {
                $modal.modal('show');
            }, 50);

        } catch (error) {
            console.error('Error showing error modal:', error);
            // Fallback to toast notification
            this.showError(errorData?.message || 'An error occurred');
        }
    }

    handleCartError() {
        try {
            if ($('body').hasClass('page-cart')) {
                $('.cart-container-process').addClass('d-none');
                $('.cart-container').addClass('d-none');
            }
        } catch (error) {
            console.error('Error in handleCartError:', error);
        }
    }

    renderEmptyCart() {
        try {
            $('.cart-dropdown.cart-offcanvas').remove();
            $('.cart-actions .cart-count, .sticky-cart .cart-count').text('0');

            if ($('body').hasClass('page-cart')) {
                $('.cart-container').removeClass('d-none');
                this.renderEmptyCheckout();
            }
        } catch (error) {
            console.error('Error rendering empty cart:', error);
        }
    }

    renderEmptyCheckout() {
        try {
            $('.checkout-container').addClass('d-none');
            $('.checkout-empty-container').removeClass('d-none');
        } catch (error) {
            console.error('Error rendering empty checkout:', error);
        }
    }

    renderSection(selector, content, sectionName) {
        try {
            const $container = $(selector);
            if ($container.length) {
                if (content && content.trim()) {
                    $container.html(content);
                    console.log(`✓ Rendered ${sectionName} section successfully`);
                }
            } else {
                console.warn(`Container ${selector} not found for ${sectionName}`);
            }
        } catch (error) {
            console.error(`Error rendering ${sectionName} section:`, error);
            // Show fallback content instead of failing silently
            try {
                $(selector).html('<div class="alert alert-warning">Error loading ' + sectionName + '</div>');
            } catch (fallbackError) {
                console.error('Failed to render fallback for ' + sectionName, fallbackError);
            }
        }
    }

    handleError(error, context = '') {
        console.error(`Cart Error [${context}]:`, error);
        this.showError(error.message || 'An unexpected error occurred');
    }

    setupGlobalErrorHandler() {
        window.addEventListener('unhandledrejection', (event) => {
            if (event.reason?.message?.includes('cart')) {
                this.handleError(event.reason, 'Unhandled Promise');
                event.preventDefault();
            }
        });
    }

    preloadCriticalData() {
        // Don't preload here - let $(document).ready handle it to avoid duplicate calls
        console.log('🔧 preloadCriticalData: Skipping to prevent duplicate loadCart calls');
    }

    bindEvents() {
        // ULTRA AGGRESSIVE CLEANUP - Remove ALL potential conflicting handlers
        $(document).off('.cart');
        $(document).off('click', '.add-cart-product');
        $(document).off('click', '.add-complementary');
        $(document).off('click', '.btn-cart');
        $(document).off('click', '.add-to-cart');
        $(document).off('click', '.btn-add-cart');
        $(document).off('click', '.type-add-to-cart');
        $('.add-cart-product, .add-complementary, .btn-cart, .add-to-cart, .btn-add-cart, .type-add-to-cart').off('click.cart click');

        console.log('🧹 ULTRA AGGRESSIVE: Cleaned ALL potential conflicting event listeners');

        // Quantity controls (ONLY for cart page - checkout handled by CheckoutManager)
        $(document).on('click.cart', '.page-cart .le-quantity .plus', async (e) => {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const $input = $btn.siblings('.cart-product-quantity');

            // ▶️ Plus: rastrear como add_to_cart
            this.updateQuantity($input, 1, { manualTrack: 'add' });
        });

        $(document).on('click.cart', '.page-cart .le-quantity .minus', async (e) => {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const $input = $btn.siblings('.cart-product-quantity');
            const currentQty = parseInt($input.val(), 10);

            if (currentQty <= 1) {
                // Aquí NO trackeamos; el tracking se hace al confirmar el borrado en el modal
                this.showDeleteModal($btn);
            } else {
                // ▶️ Minus: rastrear como remove_from_cart
                this.updateQuantity($input, -1, { manualTrack: 'remove' });
            }
        });

        // Add to cart - Separate handlers to prevent double execution
        $(document).on('click.cart', '.add-cart-product', (e) => {
            e.preventDefault();
            e.stopImmediatePropagation();

            const btn = e.currentTarget; // ✅ el que coincide con el selector
            const $btn = $(btn);

            if ($btn.prop('disabled') || $btn.hasClass('loading') || $btn.data('processing')) {
                console.log('❌ Button already processing, preventing duplicate click');
                return false;
            }

            console.log('✅ Calling addToCart from add-cart-product handler');
            this.addToCart(btn); // ✅ pasa el button correcto
            return false;
        });


        $(document).on('click.cart', '.add-complementary', (e) => {
            e.preventDefault();
            e.stopImmediatePropagation();

            const btn = e.currentTarget; // ✅
            const $btn = $(btn);
            if ($btn.prop('disabled') || $btn.hasClass('loading') || $btn.data('processing')) {
                console.log('❌ Button already processing, preventing duplicate click');
                return false;
            }

            const mainProductId = $btn.data('main-product-id');

            if (mainProductId) {
                console.log('✅ Calling addComplementaryToCart with parent reload');
                this.addComplementaryToCart(btn); // ✅
            } else {
                console.log('✅ Calling standard addToCart from add-complementary handler');
                this.addToCart(btn); // ✅
            }
            return false;
        });

        // Delete from cart
        $(document).on('click.cart', '.cart-dropdown .btn-close.delete-to-product', (e) => {
            e.preventDefault();
            this.deleteFromCart(e.target, true, false);
        });

        $(document).on('click.cart', '.list-product-main .cart-button.delete-to-cart, .cart-summary .cart-button.delete-to-cart, .cart-products-lists .delete-to-cart', (e) => {
            e.preventDefault();
            this.showDeleteModal(e.target);
        });

        $(document).on('click.cart', '.page-cart .delete-modal .delete-to-product, .page-cart .error-modal .delete-to-product', (e) => {
            e.preventDefault();
            this.deleteFromCart(e.target, false, true);
        });

        // Event handler for checkout delete modal - DISABLED for page-order
        // This is handled by checkout-manager.js for better UX (no page refresh)
        // $(document).on('click.cart', '.page-order #delete-checkout-modal .delete-to-product', (e) => {
        //     e.preventDefault();
        //     console.log('🗑️ Delete confirmed from checkout modal');
        //     this.deleteFromCart(e.target, true, true);
        // });

        // Coupon management
        $(document).on('click.cart', '.actions', (e) => {
            e.preventDefault();
            $('#promotion-code').removeClass('d-none');
        });

        $(document).on('hidden.bs.modal', '.modal', function () {
            setTimeout(() => {
                if ($('.modal.show').length === 0) {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                }
            }, 100);
        });

        $(document).on('click.cart', '.promotion-code-close', (e) => {
            e.preventDefault();
            $('#promotion-code').addClass('d-none');
        });

        $(document).on('click.cart', '#coupon-apply', (e) => {
            e.preventDefault();
            this.applyCoupon(e.target);
        });

        $(document)
            .off('click.checkout-coupon', '.remove-voucher')
            .on('click.checkout-coupon', '.remove-voucher', (e) => {
                e.preventDefault();
                this.deleteCoupon(e.currentTarget);
            });

        // Cart toggle
        $(document).on('click.cart', '.cart-toggle', (e) => {
            e.preventDefault();

            const $dropdown = $('.cart-dropdown');
            const wasOpen = $dropdown.hasClass('opened');

            // Abrir el dropdown
            $dropdown.addClass('opened');

            // Solo trackear si se acaba de abrir (evita duplicados por múltiples clics)
            if (!wasOpen) {
                // Pequeño debounce por si hay doble click rápido
                this.debounce('trackViewCartOnOpen', async () => {
                    try {
                        if (window.GTMCartHelper?.trackViewCart) {
                            await window.GTMCartHelper.trackViewCart('view_cart');
                        } else if (typeof dataLayer !== 'undefined') {
                            dataLayer.push({
                                event: 'view_cart',
                                page_type: 'cart'
                            });
                        } else {
                            console.warn('GTMCartHelper y dataLayer no disponibles para view_cart');
                        }
                    } catch (err) {
                        console.error('❌ Error en trackViewCart al abrir carrito:', err);
                    }
                }, 200);
            }
        });

        $(document).on('click.cart', '.cart-overlay, .dropdown-menu-close, .cart-close', (e) => {
            e.preventDefault();
            $('.cart-dropdown').removeClass('opened');
        });

        // Modal cleanup
        $('.delete-modal').off('hidden.bs.modal.cart').on('hidden.bs.modal.cart', function () {
            const $deleteButton = $(this).find('.delete-to-product');
            $deleteButton.removeAttr('data-id-cart data-id-product data-id-product-attribute').removeData('id-cart id-product id-product-attribute');
        });

        // ===== DETECCIÓN DE CAMBIOS DE VARIANTES EN PÁGINA DE PRODUCTO =====
        if ($('.page-product-default').length) {
            console.log('🛍️ Product page detected - Setting up variant change listeners');

            // Detectar cambios en variantes (radio buttons y selects)
            $(document).on('change.cart', '.product-variants input[type="radio"], .js-product-variants input[type="radio"], .product-variants select, .product-variations select', async (e) => {
                const selectedValue = $(e.target).val();
                console.log('🔄 Variant changed:', {
                    type: e.target.type,
                    value: selectedValue,
                    name: e.target.name
                });
            });

            // Detectar cuando PrestaShop completa el AJAX de actualización de variante
            $(document).on('ajaxComplete.cart', async (event, xhr, settings) => {
                if (settings.url && settings.url.includes('controller=product') && $('.page-product-default').length) {

                    const newProductId = $('#id_product').val();
                    const newProductAttribute = $('#id_product_attribute').val();

                    console.log('📦 Updated product data:', {
                        id_product: newProductId,
                        id_product_attribute: newProductAttribute
                    });

                    await this.loadInitialModal();
                }
            });
        }
    }

    showDeleteModal(element) {
        const data = this.getProductData(element);
        console.log('🔍 showDeleteModal called with data:', data);

        // For checkout page, use #delete-checkout-modal, otherwise use #delete-modal
        const modalId = $('body').hasClass('page-order') ? '#delete-checkout-modal' : '#delete-modal';
        const $modal = $(modalId);
        const $modalButton = $modal.find('.delete-to-product');

        console.log('🔍 Looking for modal:', modalId);
        console.log('🔍 Modal found:', $modal.length > 0);

        if ($modal.length === 0) {
            console.error('❌ Delete modal not found:', modalId);
            return;
        }

        $modalButton.removeAttr('data-id-cart data-id-product data-id-product-attribute')
            .removeData('id-cart id-product id-product-attribute');

        $modalButton.attr({
            'data-id-cart': data.id_cart,
            'data-id-product': data.id_product,
            'data-id-product-attribute': data.id_product_attribute
        });

        console.log('🎯 Showing modal:', modalId);
        $modal.modal('show');
    }

    /**
     * GTM tracking for add_to_cart events (delegado a GTMCartHelper)
     */
    async trackAddToCart(productData /*, response */) {
        try {
            if (window.GTMCartHelper) {
                await window.GTMCartHelper.trackAddToCart(productData);
            } else if (window.gtmExecuteWithBackendData) {
                await window.gtmExecuteWithBackendData('add_to_cart', {
                    item_id: productData.id_product,
                    quantity: parseInt(productData.qty || 1)
                });
            } else if (window.gtmExecuteFromAnywhere) {
                await window.gtmExecuteFromAnywhere('add_to_cart', {
                    options: {
                        item_id: productData.id_product,
                        quantity: parseInt(productData.qty || 1)
                    }
                });
            } else if (typeof dataLayer !== 'undefined') {
                dataLayer.push({ event: 'add_to_cart', item_id: productData.id_product });
            }
            console.log('✅ GTM add_to_cart (GTMCartHelper)');
        } catch (error) {
            console.error('❌ Error tracking add_to_cart:', error);
        }
    }

    /**
     * GTM tracking for remove_from_cart events (delegado a GTMCartHelper)
     */
    async trackRemoveFromCart(productData /*, response */) {
        try {
            if (window.GTMCartHelper) {
                await window.GTMCartHelper.trackRemoveFromCart(productData);
            } else if (window.gtmExecuteFromAnywhere) {
                await window.gtmExecuteFromAnywhere('remove_from_cart', {
                    options: {
                        item_id: productData.id_product,
                        quantity: parseInt(productData.qty || 1)
                    }
                });
            } else if (typeof dataLayer !== 'undefined') {
                dataLayer.push({ event: 'remove_from_cart', item_id: productData.id_product });
            }
            console.log('✅ GTM remove_from_cart (GTMCartHelper)');
        } catch (error) {
            console.error('❌ Error tracking remove_from_cart:', error);
        }
    }

    /**
     * GTM tracking for cart quantity updates (delegado a GTMCartHelper)
     */
    async trackUpdateQuantity(productData /*, response */, delta) {
        try {
            if (window.GTMCartHelper) {
                await window.GTMCartHelper.trackQuantityChange(productData, delta);
            } else if (window.gtmExecuteFromAnywhere) {
                const evt = delta > 0 ? 'add_to_cart' : 'remove_from_cart';
                await window.gtmExecuteFromAnywhere(evt, {
                    options: {
                        item_id: productData.id_product,
                        quantity: Math.abs(delta)
                    }
                });
            } else if (typeof dataLayer !== 'undefined') {
                dataLayer.push({
                    event: (delta > 0 ? 'add_to_cart' : 'remove_from_cart'),
                    item_id: productData.id_product,
                    quantity: Math.abs(delta)
                });
            }
            console.log(`✅ GTM quantity_change (${delta > 0 ? 'up' : 'down'}) (GTMCartHelper)`);
        } catch (error) {
            console.error('❌ Error tracking cart quantity update:', error);
        }
    }
}

// ULTRA DEBUG: Intercept ALL fetch calls to track duplicate requests
const originalFetch = window.fetch;
window.fetch = function (...args) {
    const [url] = args;
    if (url.includes('modalitie=cart&action=add')) {
        const callStack = new Error().stack;
        console.log('🚨 DIRECT FETCH CALL TO ADD CART:', {
            url,
            args: args[1],
            callStack: callStack.split('\n').slice(1, 8).join('\n')
        });
    }
    return originalFetch.apply(this, args);
};

// ULTRA DEBUG: Intercept jQuery AJAX calls
$(document).ajaxSend(function (event, xhr, settings) {
    if (settings.url && settings.url.includes('modalitie=cart&action=add')) {
        const callStack = new Error().stack;
        console.log('🚨 JQUERY AJAX CALL TO ADD CART:', {
            url: settings.url,
            data: settings.data,
            method: settings.method,
            callStack: callStack.split('\n').slice(1, 8).join('\n')
        });
    }
});

// Initialize cart manager
window.cart = new CartManager();

// Add the missing reloadCarts function that's called by wishlist.js
window.reloadCarts = function () {
    console.log('🔄 reloadCarts called from external source (likely wishlist.js)');
    if (window.cart) {
        window.cart.loadCart();
    }
};

// Function to update cart counter specifically
window.updateCartCounter = async function () {
    console.log('🔢 updateCartCounter called');
    if (window.cart && window.cart.endpoints) {
        try {
            const response = await fetch(window.cart.endpoints.cart.init);
            const data = await response.json();
            if (data && data.count !== undefined) {
                $('.cart-actions .cart-count, .sticky-cart .cart-count').text(data.count);
                console.log('✅ Cart counter updated to:', data.count);
            }
        } catch (error) {
            console.error('❌ Error updating cart counter:', error);
        }
    }
};

// jQuery compatibility layer for existing code
Object.assign(window.cart, {
    loadCart: window.cart.loadCart.bind(window.cart),
    handleAddToCart: window.cart.addToCart.bind(window.cart),
    handleDeleteFromCart: window.cart.deleteFromCart.bind(window.cart),
    handleDeleteActionFromCart: window.cart.showDeleteModal.bind(window.cart),
    handleApplyCoupon: window.cart.applyCoupon.bind(window.cart),
    handleDeleteVoucher: window.cart.deleteCoupon.bind(window.cart),
    updateQuantity: window.cart.updateQuantity.bind(window.cart),
    reloadModals: window.cart.reloadModal.bind(window.cart),
    reloadComplementaryModals: window.cart.reloadComplementaryModal.bind(window.cart),
    reloadCustomProductModals: window.cart.reloadCustomProductModal.bind(window.cart),
    bindCartEvents: window.cart.bindEvents.bind(window.cart),
    // 🆕 Métodos híbridos para IdxrCustomProduct
    isCustomizableProduct: window.cart.isCustomizableProduct.bind(window.cart),
    isIdxrCustomProductAvailable: window.cart.isIdxrCustomProductAvailable.bind(window.cart),
    testProductDetection: window.cart.testProductDetection.bind(window.cart),
    getCustomizationData: window.cart.getCustomizationData.bind(window.cart)

});

// Auto-load cart on DOM ready
$(document).ready(async () => {
    await window.cart.loadCart();

    // Si estamos en la página de producto, cargar el modal inicial (oculto)
    if ($('.page-product-default').length) {
        await window.cart.loadInitialModal();
    }
});



// 🧪 Función de test global para debugging
window.testHybridCart = function() {
    console.log('🧪 TESTING HYBRID CART SYSTEM');
    console.log('================================');

    // Sistema híbrido simplificado - usa clases estándar

    // Test básico
    console.log('✅ CartManager loaded:', typeof window.cart !== 'undefined');
    console.log('✅ isCustomizableProduct:', typeof window.cart.isCustomizableProduct === 'function');
    console.log('✅ testProductDetection:', typeof window.cart.testProductDetection === 'function');
    console.log('✅ IdxrCustomProduct available:', window.cart.isIdxrCustomProductAvailable());

    // Test de endpoints
    console.log('✅ Add endpoint:', !!window.cart.endpoints?.cart?.add);
    console.log('✅ Update endpoint:', !!window.cart.endpoints?.cart?.update);
    console.log('✅ Delete endpoint:', !!window.cart.endpoints?.cart?.delete);

    // Test de detección de productos
    if (typeof window.cart.testProductDetection === 'function') {
        console.log('🔍 Running product detection test...');
        window.cart.testProductDetection();
    }

    console.log('🧪 Test complete!');
};

// 🧪 Función para debuggear opciones de IdxrCustomProduct
window.debugIdxrOptions = function() {
    console.log('🔍 DEBUGGING IDXRCUSTOMPRODUCT OPTIONS');
    console.log('=====================================');

    // Buscar elementos sel_opt
    console.log('📋 sel_opt elements:');
    $('.sel_opt').each(function(index) {
        const $opt = $(this);
        console.log(`  ${index + 1}. ID: ${$opt.attr('id')}, Value: "${$opt.html()}"`);
    });

    // Buscar elementos sel_opt_extra
    console.log('📋 sel_opt_extra elements:');
    $('.sel_opt_extra').each(function(index) {
        const $opt = $(this);
        console.log(`  ${index + 1}. ID: ${$opt.attr('id')}, Value: "${$opt.html()}"`);
    });

    // Buscar inputs de IdxrCustomProduct
    console.log('📋 IdxrCustomProduct inputs:');
    $('input[name*="idxrcustomproduct"], select[name*="idxrcustomproduct"]').each(function(index) {
        const $input = $(this);
        console.log(`  ${index + 1}. Name: ${$input.attr('name')}, Value: "${$input.val()}"`);
    });

    // Test del método getCustomizationData para productos personalizables
    const customButtons = $('.add-cart-product[data-view="custom"]');
    if (customButtons.length > 0) {
        console.log('🎨 Testing getCustomizationData:');
        const customData = window.cart.getCustomizationData(customButtons.first());
        console.log('Result:', customData);
    }

    console.log('🔍 Debug complete!');
};

