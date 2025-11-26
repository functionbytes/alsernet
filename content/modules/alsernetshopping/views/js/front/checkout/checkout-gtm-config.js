
(function() {
    'use strict';

    console.log('🧾 GTM Checkout Config loading...');

    // =========================================================================
    // UTILIDADES
    // =========================================================================

    function getISOFromPath() {
        const segments = (window.location.pathname || '').split('/');
        const iso = (segments[1] && segments[1].length === 2) ? segments[1].toLowerCase() : 'es';
        const prefix = (iso !== 'es') ? `/${iso}` : '';
        return { iso, prefix };
    }

    /**
     * Endpoints para checkout (misma forma que Cart: byEvent duplica &type intencionalmente)
     */
    function getEndpointsCheckout() {
        const { iso, prefix } = getISOFromPath();
        const baseUrl = `${prefix}/modules/alsernetshopping/routes`;

        const gtmBase = `${baseUrl}?modalitie=gtp&action=init&iso=${iso}`;
        const gtm = {
            base: gtmBase,

            // helper dinámico: soporta cualquier evento nuevo
            byEvent: (evt) =>
                `${gtmBase}&type=${encodeURIComponent(evt)}&type=${encodeURIComponent(evt)}`,

            // eventos típicos de checkout
            begin_checkout:    `${gtmBase}&type=begin_checkout`,
            add_shipping_info: `${gtmBase}&type=add_shipping_info`,
            add_payment_info:  `${gtmBase}&type=add_payment_info`,
            checkout_progress: `${gtmBase}&type=checkout_progress`,
            checkout_error:    `${gtmBase}&type=checkout_error`,
            checkout_success:  `${gtmBase}&type=checkout_success`,
            purchase:          `${gtmBase}&type=purchase`
        };

        return { baseUrl, iso, gtm };
    }

    // Publica endpoints globales (como en Cart)
    window.GTM_ENDPOINTS_CHECKOUT = getEndpointsCheckout();

    // =========================================================================
    // CONFIGURACIÓN (paralela a Cart)
    // =========================================================================
    window.GTMCheckoutConfig = {
        // Mapeo semántico de eventos
        events: {
            begin_checkout:    'begin_checkout',
            add_shipping_info: 'add_shipping_info',
            add_payment_info:  'add_payment_info',
            checkout_progress: 'checkout_progress',
            checkout_error:    'checkout_error',
            checkout_success:  'checkout_success',
            purchase:          'purchase'
        },

        // Pasos de checkout (para step_navigation)
        checkoutSteps: {
            login:    { number: '0', title: 'Iniciar Sesión' },
            address:  { number: '1', title: 'Direcciones' },
            delivery: { number: '2', title: 'Método de Envío' },
            payment:  { number: '3', title: 'Método de Pago' }
        },

        // Mapeo métodos de pago/envío (igual que tenías)
        paymentMethods: {
            ps_wirepayment:     'Transferencia bancaria',
            ps_cashondelivery:  'Pago contra reembolso',
            paypal:             'PayPal',
            credit_card:        'Tarjeta de crédito',
            local_payment_hipay:'HiPay',
            sequra:             'Sequra',
            banlendismart:      'Financiación',
            inespay:            'Pago móvil'
        },
        shippingMethods: {
            39:  'Recogida en Guardia Civil',
            66:  'Correos Express',
            78:  'Recogida en tienda',
            99:  'Envío a domicilio',
            100: 'Mondial Relay',
            101: 'Entrega a dirección seleccionada',
            107: 'InPost Punto Pack',
            108: 'InPost Locker',
            109: 'InPost Premium',
            110: 'InPost Express',
            111: 'InPost Plus'
        },

        // Flags tracking (mismo patrón que Cart)
        tracking: {
            enabled: true,
            debug: window.location.hostname === 'localhost' || window.location.search.includes('gtm_debug=1'),
            syncDataBeforeEvent: true,
            skipSyncOnError: true
        },

        // Compat backwards para módulos que lean backend.routeUrl
        backend: {
            routeUrl: window.GTM_ENDPOINTS_CHECKOUT.gtm.base,
            modalitie: 'gtp',
            action: 'init',
            includeCartData: true,
            includeCheckoutData: true
        }
    };

    // =========================================================================
    // HELPER PRINCIPAL (paralelo a GTMCartHelper)
    // =========================================================================
    window.GTMCheckoutHelper = {

        // ---------------- Contexto e items ----------------
        resolveContext(gtmData) {
            const cartData     = gtmData?.cartData ?? {};
            const customerData = gtmData?.customerData ?? {};
            return {
                user_id:   customerData.user_id ?? '',
                user_type: customerData.user_type ?? '',
                country:   customerData.country ?? '',
                page_type: customerData.page_type || 'checkout',
                currency:  cartData.currency || window.GTM_CART_LAST_CURRENCY || 'EUR'
            };
        },

        resolveBaseItem(gtmData) {
            const baseItems = Array.isArray(gtmData?.cartData?.items) ? gtmData.cartData.items : [];
            const toNum = (v, def = 0) => Number.isFinite(+v) ? +v : def;

            return baseItems.map(p => {
                const selector = `input[data-product-id="${String(p.item_id)}"]`;
                const input    = document.querySelector(selector);
                const qtyDom   = input ? parseFloat(input.value) : undefined;

                return {
                    item_id:        String(p.item_id ?? ''),
                    item_unique_id: String(
                        p.item_unique_id ??
                        (p.id_product && p.id_product_attribute
                            ? `${p.id_product}-${p.id_product_attribute}`
                            : p.id_product ?? '')
                    ),
                    item_name:      p.item_name ?? '',
                    item_brand:     p.item_brand ?? '',
                    item_category:  p.item_category ?? '',
                    item_variant:   p.item_variant ?? '',
                    item_variant2:  p.item_variant2 ?? '',
                    item_list_name: p.item_list_name ?? '',
                    price:          toNum(p.price, 0),
                    discount:       toNum(p.discount, 0),
                    quantity:       Number.isFinite(qtyDom) ? Math.max(0, qtyDom)
                        : toNum(p.quantity, 1)
                };
            }).filter(i => i.quantity > 0);
        },

        // ---------------- Utilidades de nombre ----------------
        getPaymentMethodName(element) {
            const $el = $(element);

            const labelFor = $el.attr('id');
            if (labelFor) {
                const $label = $(`label[for="${labelFor}"]`);
                if ($label.length) return $label.text().trim();
            }

            const moduleName = $el.data('module-name') || $el.attr('data-module-name');
            if (moduleName && window.GTMCheckoutConfig.paymentMethods[moduleName]) {
                return window.GTMCheckoutConfig.paymentMethods[moduleName];
            }

            const value = $el.val();
            if (value && window.GTMCheckoutConfig.paymentMethods[value]) {
                return window.GTMCheckoutConfig.paymentMethods[value];
            }

            return moduleName || value || 'Método de pago desconocido';
        },

        getShippingMethodName(element) {
            const $el = $(element);
            const carrierId = parseInt($el.val(), 10);
            const $container = $el.closest('.delivery-option-item');

            const $carrierName = $container.find('.carrier-name');
            if ($carrierName.length) return $carrierName.text().trim();

            const analytic = $container.data('analytic');
            if (analytic) return analytic;

            if (window.GTMCheckoutConfig.shippingMethods[carrierId]) {
                return window.GTMCheckoutConfig.shippingMethods[carrierId];
            }

            return `Método de envío ${carrierId}`;
        },

        async syncFreshCheckoutData(type = null, extra = {}) {

            if (!window.GTMCheckoutConfig.tracking?.syncDataBeforeEvent) {
                return true;
            }


            try {
                const endpoints = window.GTM_ENDPOINTS_CHECKOUT || getEndpointsCheckout();
                let baseStr;

                if (type && endpoints.gtm[type]) {
                    // Endpoint directo (declarado)
                    baseStr = endpoints.gtm[type];
                } else if (type) {
                    // Endpoint dinámico para eventos no declarados
                    baseStr = endpoints.gtm.byEvent(type);
                } else {
                    // Sin evento: base + type=cart
                    baseStr = `${endpoints.gtm.base}&type=checkout`;
                }


                const url = new URL(baseStr, window.location.origin);

                url.searchParams.set('includeCartData', window.GTMCheckoutConfig.backend.includeCartData ? '1' : '0');
                url.searchParams.set('includeCheckoutData', window.GTMCheckoutConfig.backend.includeCheckoutData ? '1' : '0');
                url.searchParams.set('timestamp', Date.now().toString());
                url.searchParams.set('forceRefresh', '1');

                if (extra && typeof extra === 'object') {
                    const { payment_type, shipping_tier, transaction_id, value, checkout_step } = extra;
                    if (payment_type)   url.searchParams.set('payment_type', payment_type);
                    if (shipping_tier)  url.searchParams.set('shipping_tier', shipping_tier);
                    if (transaction_id) url.searchParams.set('transaction_id', transaction_id);
                    if (Number.isFinite(+value)) url.searchParams.set('value', String(+value));
                    if (checkout_step)  url.searchParams.set('checkout_step', String(checkout_step));
                }

                console.log('🔄 Sync GTM Checkout (GET):', url.toString());

                const response = await fetch(url.toString(), {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' },
                    credentials: 'same-origin',
                    cache: 'no-store'
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);

                const ct = response.headers.get('content-type') || '';
                if (!ct.includes('application/json')) {
                    console.warn('⚠️ Respuesta no JSON');
                    return false;
                }

                const text = await response.text();
                if (!text.trim()) {
                    console.warn('⚠️ Respuesta vacía');
                    return false;
                }

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.warn('⚠️ JSON inválido:', e.message);
                    return false;
                }

                if (result.status !== 'success') {
                    throw new Error(result.message || 'Checkout backend sync failed');
                }

                // Propagar/Cachear
                if (window.gtmBackendConnector && result.gtmData) {
                    window.gtmBackendConnector.setCachedData('checkout_gtm_data_sync', result.gtmData);
                }
                if (window.gtmManager?.updateData) {
                    window.gtmManager.updateData(result.gtmData);
                }

                window.GTM_CART_LAST_CURRENCY =
                    (result?.gtmData?.cartData?.currency) || window.GTM_CART_LAST_CURRENCY || 'EUR';

                console.log('✅ GTM checkout data synchronized');
                return result;

            } catch (error) {
                console.error('❌ Error syncing GTM checkout data:', error);
                if (window.GTMCheckoutConfig.tracking.skipSyncOnError) return false;
                throw error;
            }
        },

        // ---------------------------------------------------------------------
        // Dispatcher de eventos (unificado, igual que Cart)
        // ---------------------------------------------------------------------
        async trackCheckoutEvent(type, data = {}) {
            if (!window.GTMCheckoutConfig.tracking.enabled) {
                console.log('🚫 GTM checkout tracking disabled');
                return;
            }

            try {
                const { _alreadySynced, ...cleanData } = data || {};
                console.log(`🧾 GTM Checkout: Tracking ${type}`, cleanData);

                const shouldSync = window.GTMCheckoutConfig.tracking.syncDataBeforeEvent && !_alreadySynced;
                let syncResult = null;

                if (shouldSync) {
                    try {
                        syncResult = await this.syncFreshCheckoutData(type, (cleanData && cleanData.options) || {});
                        console.log('✅ GTM checkout data synced successfully:', syncResult);
                    } catch (syncError) {
                        console.warn('⚠️ GTM checkout data sync failed, continuing with cached data:', syncError.message);
                    }
                }

                const options = cleanData.options || {};

                // Canal unificado (misma prioridad que Cart) - Simplificado
                if (window.gtmExecuteWithBackendData) {
                    console.log('🔄 Using gtmExecuteWithBackendData for checkout tracking');
                    return await window.gtmExecuteWithBackendData(type, options);
                } else if (window.gtmExecuteFromAnywhere) {
                    console.log('🔄 Using gtmExecuteFromAnywhere for checkout tracking');
                    return await window.gtmExecuteFromAnywhere(type, cleanData);
                } else if (window.gtmManager && window.gtmManager.executeEvent) {
                    console.log('🔄 Using gtmManager.executeEvent for checkout tracking');
                    return window.gtmManager.executeEvent(type, options);
                } else if (typeof dataLayer !== 'undefined') {
                    console.log('🔄 Using dataLayer for checkout tracking');
                    dataLayer.push({ event: type, ...options });
                    return { success: true, fallback: 'dataLayer' };
                } else {
                    console.warn('⚠️ No GTM API available for checkout tracking');
                    return { success: false, error: 'GTM API not available' };
                }

            } catch (error) {
                console.error(`❌ Error tracking checkout ${type}:`, error);
                return { success: false, error: error.message };
            }
        },

        // ---------------------------------------------------------------------
        // Eventos específicos de Checkout (patrón similar a Cart)
        // ---------------------------------------------------------------------

        /**
         * Track begin_checkout con datos completos del backend
         */
        async trackBeginCheckout() {
            const res = await this.syncFreshCheckoutData('begin_checkout', { checkout_step: '1' });
            const gtmData = res?.gtmData || {};
            const ctx = this.resolveContext(gtmData);
            const items = this.resolveBaseItem(gtmData);

            const payload = {
                user_id: ctx.user_id,
                user_type: ctx.user_type,
                country: ctx.country,
                page_type: ctx.page_type,
                checkout_step: '1',
                ecommerce: {
                    currency: ctx.currency,
                    items
                }
            };

            return await this.trackCheckoutEvent('begin_checkout', {
                options: payload,
                _alreadySynced: true
            });
        },

        /**
         * Track add_shipping_info con datos completos del backend
         */
        async trackAddShippingInfo() {
            const res = await this.syncFreshCheckoutData('add_shipping_info', { checkout_step: '1' });
            const gtmData = res?.gtmData || {};
            const ctx = this.resolveContext(gtmData);
            const items = this.resolveBaseItem(gtmData);

            const payload = {
                user_id: ctx.user_id,
                user_type: ctx.user_type,
                country: ctx.country,
                page_type: ctx.page_type,
                checkout_step: '1',
                ecommerce: {
                    currency: ctx.currency,
                    items
                }
            };

            return await this.trackCheckoutEvent('add_shipping_info', {
                options: payload,
                _alreadySynced: true
            });
        },

        /**
         * Track add_address_info con datos completos del backend
         */
        async trackAddAddressInfo() {
            const res = await this.syncFreshCheckoutData('add_address_info', { checkout_step: '1' });
            const gtmData = res?.gtmData || {};
            const ctx = this.resolveContext(gtmData);
            const items = this.resolveBaseItem(gtmData);

            const payload = {
                user_id: ctx.user_id,
                user_type: ctx.user_type,
                country: ctx.country,
                page_type: ctx.page_type,
                checkout_step: '1',
                ecommerce: {
                    currency: ctx.currency,
                    items
                }
            };

            return await this.trackCheckoutEvent('add_address_info', {
                options: payload,
                _alreadySynced: true
            });
        },

        /**
         * Navegación por pasos → dispara page_view / begin_checkout / add_payment_info…
         */
        async trackStepNavigation(step) {
            const stepCfg = window.GTMCheckoutConfig.checkoutSteps[step];
            if (!stepCfg) {
                console.log(`ℹ️ Paso no reconocido: ${step}`);
                return;
            }

            // Regla funcional: al entrar a delivery → begin_checkout
            // al entrar a payment → add_payment_info (contexto de “selección”)
            let type;
            if (step === 'delivery') type = 'begin_checkout';
            else if (step === 'payment') type = 'add_payment_info';
            else type = 'checkout_progress';

            const res     = await this.syncFreshCheckoutData(type, { checkout_step: stepCfg.number });
            const gtmData = res?.gtmData || {};
            const ctx     = this.resolveContext(gtmData);

            const payload = {
                user_id:       ctx.user_id,
                user_type:     ctx.user_type,
                country:       ctx.country,
                page_type:     ctx.page_type,
                page_title:    stepCfg.title,
                page_url:      window.location.href,
                checkout_step: stepCfg.number,
                ecommerce: {
                    currency: ctx.currency
                }
            };

            return await this.trackCheckoutEvent(type, {
                options: payload,
                _alreadySynced: true
            });
        },

        /**
         * Selección de pago
         */
        async trackPaymentSelection(element) {
            const paymentMethod = this.getPaymentMethodName(element);

            const res     = await this.syncFreshCheckoutData('add_payment_info', {
                payment_type:  paymentMethod,
                checkout_step: 3
            });
            const gtmData = res?.gtmData || {};
            const ctx     = this.resolveContext(gtmData);
            const items   = this.resolveBaseItem(gtmData);

            const payload = {
                user_id:       ctx.user_id,
                user_type:     ctx.user_type,
                country:       ctx.country,
                page_type:     ctx.page_type,
                checkout_step: '3',
                payment_type:  paymentMethod,
                ecommerce: {
                    currency: ctx.currency,
                    items
                }
            };

            return await this.trackCheckoutEvent('add_payment_info', {
                options: payload,
                _alreadySynced: true
            });
        },

        /**
         * Selección de envío
         */
        async trackShippingSelection(element) {
            const shippingMethod = this.getShippingMethodName(element);

            const res     = await this.syncFreshCheckoutData('add_shipping_info', {
                shipping_tier: shippingMethod,
                checkout_step: 2
            });
            const gtmData = res?.gtmData || {};
            const ctx     = this.resolveContext(gtmData);
            const items   = this.resolveBaseItem(gtmData);

            const payload = {
                user_id:       ctx.user_id,
                user_type:     ctx.user_type,
                country:       ctx.country,
                page_type:     ctx.page_type,
                checkout_step: '2',
                shipping_tier: shippingMethod,
                ecommerce: {
                    currency: ctx.currency,
                    items
                }
            };

            return await this.trackCheckoutEvent('add_shipping_info', {
                options: payload,
                _alreadySynced: true
            });
        },

        /**
         * Track purchase event - called from order confirmation page
         */
        async trackPurchase(orderData = {}) {
            const { transaction_id, value, currency, payment_type, shipping_tier } = orderData;

            console.log('🛒 GTMCheckoutHelper.trackPurchase called with:', orderData);

            try {
                // Prepare purchase data
                const purchaseData = {
                    transaction_id: transaction_id || '',
                    value: Number.isFinite(+value) ? +value : 0,
                    currency: currency || 'EUR',
                    payment_type: payment_type || '',
                    shipping_tier: shipping_tier || '',
                    checkout_step: 'completed'
                };

                console.log('🛒 Executing purchase with data:', purchaseData);

                // Try different GTM APIs in order of preference (same logic as trackCheckoutEvent)
                if (window.gtmExecuteWithBackendData) {
                    console.log('🔄 Using gtmExecuteWithBackendData for purchase tracking');
                    const result = await window.gtmExecuteWithBackendData('purchase', purchaseData);
                    console.log('✅ Purchase tracking completed:', result);
                    return result;
                } else if (window.gtmExecuteFromAnywhere) {
                    console.log('🔄 Using gtmExecuteFromAnywhere for purchase tracking');
                    const result = await window.gtmExecuteFromAnywhere('purchase', { options: purchaseData });
                    console.log('✅ Purchase tracking completed:', result);
                    return result;
                } else if (window.gtmManager && window.gtmManager.executeEvent) {
                    console.log('🔄 Using gtmManager.executeEvent for purchase tracking');
                    const result = window.gtmManager.executeEvent('purchase', purchaseData);
                    console.log('✅ Purchase tracking completed:', result);
                    return result;
                } else if (typeof dataLayer !== 'undefined') {
                    console.log('🔄 Using dataLayer for purchase tracking');
                    dataLayer.push({
                        event: 'purchase',
                        ...purchaseData
                    });
                    return { success: true, fallback: 'dataLayer' };
                } else {
                    console.warn('⚠️ No GTM API available for purchase tracking');
                    return { success: false, error: 'GTM API not available' };
                }

            } catch (error) {
                console.error('❌ Error in GTMCheckoutHelper.trackPurchase:', error);

                // Final fallback to direct dataLayer push
                if (typeof dataLayer !== 'undefined') {
                    console.log('🔄 Fallback: pushing to dataLayer directly');
                    dataLayer.push({
                        event: 'purchase',
                        transaction_id: transaction_id || '',
                        value: Number.isFinite(+value) ? +value : 0,
                        currency: currency || 'EUR',
                        payment_type: payment_type || '',
                        shipping_tier: shipping_tier || ''
                    });
                    return { success: true, fallback: 'dataLayer' };
                }

                throw error;
            }
        },

        // ---------------------------------------------------------------------
        // Init
        // ---------------------------------------------------------------------
        init() {
            console.log('🧾 GTM Checkout Helper initialized');

            if (window.GTMCheckoutConfig.tracking.debug) {
                console.log('🔍 GTM Checkout Debug mode enabled');
                window.GTMCheckoutDebug = {
                    config:                  window.GTMCheckoutConfig,
                    endpoints:               window.GTM_ENDPOINTS_CHECKOUT,
                    helper:                  window.GTMCheckoutHelper,
                    trackCheckoutEvent:      this.trackCheckoutEvent.bind(this),
                    trackBeginCheckout:      this.trackBeginCheckout.bind(this),
                    trackAddShippingInfo:    this.trackAddShippingInfo.bind(this),
                    trackStepNavigation:     this.trackStepNavigation.bind(this),
                    trackPaymentSelection:   this.trackPaymentSelection.bind(this),
                    trackShippingSelection:  this.trackShippingSelection.bind(this),
                    trackPurchase:           this.trackPurchase.bind(this),
                    syncFreshCheckoutData:   this.syncFreshCheckoutData.bind(this)
                };
            }
        }
    };

    // =========================================================================
    // AUTO-INIT
    // =========================================================================
    $(document).ready(function() {
        window.GTMCheckoutHelper.init();
        if ($('body').attr('id') === 'product') {
            console.log('El bodyV2 tiene el id #product');
            // $blocks.each(function () {

            // const $el = $(this);
            // if ($el.data('ga-fired') === 1) return;

            // const category = $el.data('category') || '';
            // const type     = $el.data('type') || '';

            const isProductPage = $('body').is('#product, .page-product');
            if (!isProductPage) return;

            // Extrae el id_product de las clases del body: "product-id-12345"
            const getProductIdFromBody = () => {
                for (const cls of document.body.classList) {
                    const m = /^product-id-(\d+)$/.exec(cls);
                    if (m) return m[1];
                }
                // Fallbacks por si acaso
                const input = document.querySelector('input[name="id_product"]');
                if (input && /^\d+$/.test(input.value)) return input.value;
                return null;
            };

            const pid = getProductIdFromBody();
            if (!pid) {
                console.warn('[GTM] No se encontró product-id-<n> en <body>.');
                return;
            }

            const { iso } = getISOFromPath(); // tu helper ya definido arriba
            const link = `/modules/alsernetproducts/controllers/routes.php?type=viewproduct&iso=`+iso+`&id_product=${encodeURIComponent(pid)}`
            console.log('link => '+link);
            $.ajax({
                cache: true,
                url: link
            }).done(async function(results) {   // 👈 hacemos async la función

                if(results.status === "success") {

                    window.CartGTMHelper = window.CartGTMHelper || window.GTMCartHelper;

                    const helper = await window.waitFor(
                        () => window.GTMCartHelper || window.CartGTMHelper,
                        { timeout: 1500 }
                    );

                    let rawItems = [];
                    try {
                        rawItems = Array.isArray(results.data.product_analytics)
                            ? results.data.product_analytics
                            : JSON.parse(results.data.product_analytics || '[]');
                    } catch { rawItems = []; }

                    const items = rawItems
                        .map(i => {
                            const item_id   = String(i.item_id ?? i.id ?? i.product_id ?? '');
                            const item_name = String(i.item_name ?? i.name ?? '');
                            return item_id && item_name ? {
                                item_id,
                                item_name,
                                item_unique_id: String(i.item_unique_id ?? i.unique_id ?? item_id),
                                item_brand:     i.item_brand ?? i.brand ?? '',
                                item_category:  i.item_category ?? i.category ?? '',
                                item_variant:   i.item_variant ?? i.variant ?? '',
                                item_variant2:  i.item_variant2 ?? i.variant2 ?? '',
                                price:          Number.isFinite(+i.price)    ? +i.price    : undefined,
                                discount:       Number.isFinite(+i.discount) ? +i.discount : undefined,
                                quantity:       Number.isFinite(+i.quantity) ? +i.quantity : 1
                            } : null;
                        })
                        .filter(Boolean);

                    const list_name = results.data.list_name ?? '';
                    const list_id   = results.data.list_id   ?? '';

                    if (helper && typeof helper.trackViewItemList === 'function') {
                        if (items.length) {
                            try {
                                await helper.trackViewItem(
                                    { list_name, list_id },
                                    items
                                );
                            } catch (e) {
                                console.warn('trackViewItemList falló:', e);
                            }
                        } else {
                            console.warn('view_item: no hay ítems válidos para enviar.');
                        }
                    } else {
                        console.warn('CartGTMHelper/GTMCartHelper no disponible; se continúa sin tracking.');
                    }
                }
            }).fail(function() {
                console.log("Error en la carga de datos.");
            });

            // });
        }

        if ($('body').is('#category, .page-category')) {
            (async function() {
                try {
                    // Evitar dobles envíos
                    const $list = $('#js-product-list, .products').first();
                    if (!$list.length || $list.data('ga-fired') === 1) return;

                    // Esperar helper GTM (mismo patrón que producto)
                    window.CartGTMHelper = window.CartGTMHelper || window.GTMCartHelper;
                    const helper = await window.waitFor(
                        () => window.GTMCartHelper || window.CartGTMHelper,
                        { timeout: 1500 }
                    );
                    if (!helper || typeof helper.trackViewItemList !== 'function') {
                        console.warn('CartGTMHelper/GTMCartHelper no disponible para view_item_list en categoría.');
                        return;
                    }

                    // list_id desde las clases del <body> (category-id-XXXX si existe)
                    const bodyClass = document.body.className || '';
                    const matchId = bodyClass.match(/(?:category-id-|product-id-category-)(\d+)/i);
                    const list_id = matchId ? matchId[1] : '';

                    // list_name desde el H1 o fallback en breadcrumb
                    const list_name =
                        ($('.category-title, h1.h1, h1[itemprop="name"], h1').first().text() || '')
                            .trim() ||
                        ($('.breadcrumb li.active, .breadcrumb .breadcrumb-item.active').last().text() || '').trim() ||
                        'Listado de productos';

                    // Parseo robusto de productos en el listado
                    const toNumber = (v) => {
                        const n = typeof v === 'number' ? v : parseFloat(String(v).replace(/[^\d.,-]/g,'').replace('.', '').replace(',', '.'));
                        return Number.isFinite(n) ? n : undefined;
                    };

                    const items = [];
                    // Compat con plantillas habituales de PrestaShop
                    ($list.find('.product-miniature, .js-product-miniature, li.ajax_block_product, .product').toArray()).forEach(el => {
                        const $p = $(el);

                        const id  = String($p.data('id-product') ?? $p.attr('data-id-product') ?? '');
                        const idAttr = String($p.data('id-product-attribute') ?? $p.attr('data-id-product-attribute') ?? '');
                        const name = ($p.find('.product-title a, .product-title, h3 a, h3').first().text() || '').trim();
                        const brand = ($p.find('[data-brand], .product-brand').first().attr('data-brand') || $p.find('.product-brand').first().text() || '').trim();
                        const category = list_name;
                        const priceAttr = $p.find('[data-price-amount]').attr('data-price-amount');
                        const priceTxt  = priceAttr ?? ($p.find('.price, .product-price').first().text() || '').trim();

                        if (id && name) {
                            items.push({
                                item_id: id,
                                item_unique_id: idAttr ? `${id}-${idAttr}` : id,
                                item_name: name,
                                item_brand: brand || '',
                                item_category: category || '',
                                item_variant: idAttr || '',
                                price: toNumber(priceTxt),
                                quantity: 1
                            });
                        }
                    });

                    if (!items.length) {
                        console.warn('view_item_list (categoría): no se encontraron ítems válidos en el DOM.');
                        return;
                    }

                    console.log('🧾 Enviando view_item_list (categoría):', { list_id, list_name, itemsCount: items.length });
                    await helper.trackViewItemList({ list_name, list_id }, items);
                    $list.data('ga-fired', 1);
                } catch (err) {
                    console.warn('view_item_list (categoría) falló:', err);
                }
            })();
        }
    });

    console.log('✅ GTM Checkout Config loaded');
})();
