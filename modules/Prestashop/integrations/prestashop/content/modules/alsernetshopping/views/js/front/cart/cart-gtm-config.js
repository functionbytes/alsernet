
(function() {
    'use strict';

    console.log('🛒 GTM Cart Config loading...');

    // =========================================================================
    // UTILIDADES Y MEJORAS DE RENDIMIENTO
    // =========================================================================

    /**
     * Cache inteligente con múltiples estrategias
     */
    const GTMCache = {
        data: new Map(),

        getCurrentStrategy() {
            return window.GTMCartConfig?.cache?.strategies?.[window.GTMCartConfig?.cache?.strategy] || {
                duration: 0,
                cacheAll: false
            };
        },

        getDuration(eventType) {
            const strategy = this.getCurrentStrategy();

            if (strategy.name === 'Híbrido inteligente') {
                const isStatic = strategy.staticEvents?.includes(eventType);
                return isStatic ? strategy.staticDuration : strategy.dynamicDuration;
            }

            return strategy.duration;
        },

        shouldCache(eventType) {
            const strategy = this.getCurrentStrategy();

            // Estrategia B: Never cache
            if (strategy.duration === 0) return false;

            // Estrategia A: Cache everything
            if (strategy.cacheAll) return true;

            // Estrategia C: Cache selectivo
            if (strategy.name === 'Híbrido inteligente') {
                return strategy.staticEvents?.includes(eventType) || strategy.dynamicEvents?.includes(eventType);
            }

            return false;
        },

        set(key, value, eventType = null) {
            if (!this.shouldCache(eventType)) {
                console.log(`📋 Cache SKIP for ${eventType} (strategy: ${this.getCurrentStrategy().name})`);
                return;
            }

            this.data.set(key, {
                value,
                timestamp: Date.now(),
                eventType
            });

            console.log(`💾 Cache SET for ${eventType} (duration: ${this.getDuration(eventType)}ms)`);

            // Invalidar cache relacionado si es necesario (Estrategia C)
            if (this.getCurrentStrategy().invalidateOnCartChange && this.isCartModifyingEvent(eventType)) {
                this.invalidateCartRelated(eventType);
            }
        },

        get(key, eventType = null) {
            if (!this.shouldCache(eventType)) {
                return null;
            }

            const cached = this.data.get(key);
            if (!cached) return null;

            const maxAge = this.getDuration(cached.eventType || eventType);
            const age = Date.now() - cached.timestamp;

            if (age > maxAge) {
                this.data.delete(key);
                console.log(`🗑️ Cache EXPIRED for ${cached.eventType} (age: ${age}ms, max: ${maxAge}ms)`);
                return null;
            }

            console.log(`⚡ Cache HIT for ${cached.eventType} (age: ${age}ms)`);
            return cached.value;
        },

        clear() {
            this.data.clear();
            console.log('🧹 Cache cleared completely');
        },

        isCartModifyingEvent(eventType) {
            return ['add_to_cart', 'remove_from_cart'].includes(eventType);
        },

        invalidateCartRelated(triggerEvent) {
            let invalidated = 0;
            for (const [key, data] of this.data) {
                if (key.includes('view_cart') || key.includes('cart_') || this.isCartModifyingEvent(data.eventType)) {
                    this.data.delete(key);
                    invalidated++;
                }
            }
            if (invalidated > 0) {
                console.log(`🔄 Cache invalidated ${invalidated} cart-related entries due to ${triggerEvent}`);
            }
        },

        generateKey(type, extra = {}) {
            return `${type || 'default'}_${JSON.stringify(extra)}`;
        },

        // Debug utilities
        getStats() {
            const strategy = this.getCurrentStrategy();
            return {
                strategy: window.GTMCartConfig?.cache?.strategy,
                strategyName: strategy.name,
                totalEntries: this.data.size,
                entries: Array.from(this.data.entries()).map(([key, data]) => ({
                    key,
                    eventType: data.eventType,
                    age: Date.now() - data.timestamp,
                    maxAge: this.getDuration(data.eventType)
                }))
            };
        }
    };

    /**
     * Rate limiting para proteger el backend
     */
    const RateLimiter = {
        requests: [],
        maxRequests: 15, // máximo 15 requests por minuto
        timeWindow: 60000, // 1 minuto

        canMakeRequest() {
            const now = Date.now();
            this.requests = this.requests.filter(time => now - time < this.timeWindow);

            if (this.requests.length >= this.maxRequests) {
                console.warn('⚠️ Rate limit exceeded for GTM requests');
                return false;
            }

            this.requests.push(now);
            return true;
        }
    };

    /**
     * Métricas de rendimiento
     */
    const PerformanceMetrics = {
        events: new Map(),

        start(eventName) {
            this.events.set(eventName, performance.now());
        },

        end(eventName) {
            const startTime = this.events.get(eventName);
            if (startTime) {
                const duration = performance.now() - startTime;
                console.log(`⏱️ GTM ${eventName} completed in ${duration.toFixed(2)}ms`);
                this.events.delete(eventName);
                return duration;
            }
            return null;
        }
    };

    /**
     * Debounce para eventos rápidos
     */
    const DebounceManager = {
        timers: new Map(),

        debounce(key, callback, delay = 300) {
            // Cancelar timer anterior si existe
            const existingTimer = this.timers.get(key);
            if (existingTimer) {
                clearTimeout(existingTimer);
            }

            // Crear nuevo timer
            const timer = setTimeout(() => {
                callback();
                this.timers.delete(key);
            }, delay);

            this.timers.set(key, timer);
        },

        cancel(key) {
            const timer = this.timers.get(key);
            if (timer) {
                clearTimeout(timer);
                this.timers.delete(key);
            }
        }
    };

    // =========================================================================
    // UTILIDADES BÁSICAS
    // =========================================================================

    /**
     * Obtiene el ISO desde la URL actual
     * - Si la primera carpeta tiene 2 letras, se usa como ISO; en otro caso 'es'
     * - Retorna { iso, prefix }
     */
    function getISOFromPath() {
        const segments = (window.location.pathname || '').split('/');
        const iso = (segments[1] && segments[1].length === 2) ? segments[1].toLowerCase() : 'es';
        const prefix = (iso !== 'es') ? `/${iso}` : '';
        return { iso, prefix };
    }

    /**
     * Construye endpoints GTM con soporte para eventos dinámicos
     */
    function getEndpoints() {
        const { iso, prefix } = getISOFromPath();
        const baseUrl = `${prefix}/modules/alsernetshopping/routes`;

        const gtmBase = `${baseUrl}?modalitie=gtp&action=init&iso=${iso}`;
        const gtm = {
            base: gtmBase,

            // helper dinámico: soporta cualquier evento nuevo
            byEvent: (evt) =>
                `${gtmBase}&type=${encodeURIComponent(evt)}`,

            add_to_cart:       `${gtmBase}&type=add_to_cart`,
            remove_from_cart:  `${gtmBase}&type=remove_from_cart`,
            view_cart:         `${gtmBase}&type=view_cart`,
            view_item:         `${gtmBase}&type=view_item`,
            view_item_list:    `${gtmBase}&type=view_item_list`,
            select_item:       `${gtmBase}&type=select_item`,
            view_promotion:    `${gtmBase}&type=view_promotion`,
            select_promotion:  `${gtmBase}&type=select_promotion`,
            refund:           `${gtmBase}&type=refund`,

        };

        return { baseUrl, iso, gtm };
    }

    // Publica endpoints globales (útil para otros módulos)
    window.GTM_ENDPOINTS = getEndpoints();

    // =========================================================================
    // CONFIGURACIÓN
    // =========================================================================
    window.GTMCartConfig = {
        // Mapeo semántico de eventos
        events: {
            add_to_cart:      'add_to_cart',
            remove_from_cart: 'remove_from_cart',
            view_cart:        'view_cart',
            view_item:        'view_item',
            view_item_list:   'view_item_list',
            select_item:      'select_item',
            view_promotion:   'view_promotion',
            select_promotion: 'select_promotion',
            refund:          'refund'
        },

        // Flags de tracking
        tracking: {
            enabled: true,
            debug: window.location.hostname === 'localhost' || window.location.search.includes('gtm_debug=1'),
            syncDataBeforeEvent: true,
            skipSyncOnError: true
        },

        // Estrategias de cache
        cache: {
            strategy: 'C', // A, B, o C
            strategies: {
                A: {
                    name: 'Máximo rendimiento',
                    description: 'Cache completo - Muy pocas requests, riesgo de datos desactualizados',
                    duration: 5000,
                    cacheAll: true,
                    invalidateOnCartChange: false
                },
                B: {
                    name: 'Máxima precisión',
                    description: 'Sin cache - Siempre datos actualizados, más requests al backend',
                    duration: 0,
                    cacheAll: false,
                    invalidateOnCartChange: false
                },
                C: {
                    name: 'Híbrido inteligente',
                    description: 'Cache selectivo - Balance perfecto rendimiento/precisión',
                    duration: 5000,
                    cacheAll: false,
                    invalidateOnCartChange: true,
                    // Configuración específica para estrategia C
                    staticEvents: ['view_item', 'view_item_list', 'view_promotion', 'select_promotion'],
                    dynamicEvents: ['add_to_cart', 'remove_from_cart', 'view_cart'],
                    staticDuration: 30000,  // 30 segundos para eventos estáticos
                    dynamicDuration: 1000   // 1 segundo para eventos dinámicos
                }
            }
        },

        // Compatibilidad retro: algunos módulos leen esta sección
        backend: {
            routeUrl: window.GTM_ENDPOINTS.gtm.base, // mantiene forma previa
            modalitie: 'gtp',
            action: 'init',
            includeCartData: true
        }
    };

    // =========================================================================
    // HELPER PRINCIPAL
    // =========================================================================
    window.GTMCartHelper = {

        /**
         * Validación mejorada de datos
         */
        validateProductData(productData, context = 'general') {
            if (!productData || typeof productData !== 'object') {
                console.error('❌ GTM Validation: ProductData debe ser un objeto', { productData, context });
                return false;
            }

            const required = ['id_product'];
            const optional = ['qty', 'name', 'price', 'id_product_attribute', 'brand', 'category'];

            // Validar campos requeridos
            const missing = required.filter(field => !productData[field]);
            if (missing.length > 0) {
                console.warn(`⚠️ GTM Validation (${context}): Faltan campos requeridos:`, missing);
                return false;
            }

            // Validar tipos de datos
            if (productData.qty !== undefined && (!Number.isFinite(+productData.qty) || +productData.qty <= 0)) {
                console.warn(`⚠️ GTM Validation (${context}): qty debe ser un número positivo`, productData.qty);
            }

            if (productData.price !== undefined && !Number.isFinite(+productData.price)) {
                console.warn(`⚠️ GTM Validation (${context}): price debe ser un número`, productData.price);
            }

            return true;
        },

        validateExternalItems(items, context = 'general') {
            if (!Array.isArray(items)) {
                console.error(`❌ GTM Validation (${context}): Items debe ser un array`, items);
                return false;
            }

            if (items.length === 0) {
                console.warn(`⚠️ GTM Validation (${context}): Array de items está vacío`);
                return false;
            }

            const requiredItemFields = ['item_id', 'item_name'];
            let validItems = 0;

            items.forEach((item, index) => {
                const missingFields = requiredItemFields.filter(field => !item[field]);
                if (missingFields.length === 0) {
                    validItems++;
                } else {
                    console.warn(`⚠️ GTM Validation (${context}): Item ${index} faltan campos:`, missingFields);
                }
            });

            return validItems > 0;
        },

        /**
         * Cambiar estrategia de cache en runtime
         */
        setCacheStrategy(strategy) {
            const validStrategies = ['A', 'B', 'C'];
            if (!validStrategies.includes(strategy)) {
                console.error(`❌ Invalid cache strategy: ${strategy}. Valid: ${validStrategies.join(', ')}`);
                return false;
            }

            const oldStrategy = window.GTMCartConfig.cache.strategy;
            window.GTMCartConfig.cache.strategy = strategy;

            // Limpiar cache al cambiar estrategia
            GTMCache.clear();

            const newStrategyInfo = window.GTMCartConfig.cache.strategies[strategy];
            console.log(`🔄 Cache strategy changed: ${oldStrategy} → ${strategy}`);
            console.log(`📋 New strategy: ${newStrategyInfo.name}`);
            console.log(`📝 Description: ${newStrategyInfo.description}`);

            return true;
        },

        /**
         * Obtener información de la estrategia actual
         */
        getCacheStrategyInfo() {
            const currentStrategy = window.GTMCartConfig.cache.strategy;
            const strategyInfo = window.GTMCartConfig.cache.strategies[currentStrategy];

            return {
                current: currentStrategy,
                name: strategyInfo.name,
                description: strategyInfo.description,
                settings: strategyInfo,
                stats: GTMCache.getStats()
            };
        },

        /**
         * Logging estructurado para errores
         */
        logStructuredError(error, context = {}) {
            const errorInfo = {
                message: error.message,
                stack: error.stack,
                context: context,
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent,
                url: window.location.href
            };

            console.error('🚨 GTM Structured Error:', errorInfo);

            // Enviar a servicio de monitoring si existe
            if (window.errorReporting && typeof window.errorReporting.report === 'function') {
                window.errorReporting.report(error, { context: 'GTM_Cart', ...context });
            }

            return errorInfo;
        },

        resolveContext(gtmData) {
            const cartData     = gtmData?.cartData ?? {};
            const customerData = gtmData?.customerData ?? {};
            return {
                user_id:   customerData.user_id ?? '',
                user_type: customerData.user_type ?? '',
                country:   customerData.country ?? '',
                page_type: 'cesta',
                currency:  cartData.currency || window.GTM_CART_LAST_CURRENCY || 'EUR'
            };
        },

        resolveBaseItem(gtmData, productData) {
            const items = gtmData?.cartData?.items;
            if (!Array.isArray(items)) return {};

            const pid  = String(productData.id_product ?? '');
            const puid = String(
                productData.id_product_attribute
                    ? `${productData.id_product}-${productData.id_product_attribute}`
                    : productData.id_product ?? ''
            );

            return items.find(it =>
                String(it.item_unique_id) === puid || String(it.item_id) === pid
            ) || {};
        },

        buildItem(productData, base = {}, quantity = 1) {
            const pid  = String(productData.id_product ?? base.item_id ?? '');
            const puid = String(
                productData.id_product_attribute
                    ? `${productData.id_product}-${productData.id_product_attribute}`
                    : productData.id_product ?? base.item_unique_id ?? pid
            );

            const toNum = (v, def = 0) => Number.isFinite(+v) ? +v : def;
            const toPrice = (v, def = 0) => {
                const num = toNum(v, def);
                return Number.isFinite(num) ? parseFloat(num.toFixed(2)) : def;
            };

            return {
                item_id:        pid,
                item_unique_id: puid,
                item_name:      productData.name ?? base.item_name ?? '',
                item_brand:     productData.brand ?? base.item_brand ?? '',
                item_category:  productData.category ?? base.item_category ?? '',
                item_variant:   productData.variant ?? base.item_variant ?? '',
                item_variant2:  productData.item_variant2 ?? base.item_variant2 ?? '',
                item_list_name: base.item_list_name ?? '',
                price:          toPrice(productData.price ?? base.price, 0),
                discount:       toPrice(productData.discount ?? base.discount, 0),
                quantity:       toNum(quantity, 1)
            };
        },

        readQtyFromDOM(itemId, fallbackQty) {
            const input = document.querySelector(`input[data-product-id="${String(itemId)}"]`);
            const val = input ? parseFloat(input.value) : NaN;
            return Number.isFinite(val) ? val : fallbackQty;
        },

        // ---------------------------------------------------------------------
        // Sync con el backend (usa endpoints por evento)
        // ---------------------------------------------------------------------
        /**
         * Sincroniza datos GTM con el backend - Con cache, rate limiting y métricas
         */
        async syncFreshCartData(type = null, extra = {}) {
            if (!window.GTMCartConfig.tracking?.syncDataBeforeEvent) {
                return true;
            }

            const metricName = `sync_${type || 'default'}`;
            PerformanceMetrics.start(metricName);

            try {
                // 1. Verificar cache primero (según estrategia)
                const cacheKey = GTMCache.generateKey(type, extra);
                const cached = GTMCache.get(cacheKey, type);
                if (cached) {
                    console.log(`⚡ Using cached GTM data for ${type || 'default'} (strategy: ${window.GTMCartConfig.cache.strategy})`);
                    PerformanceMetrics.end(metricName);
                    return cached;
                }

                // 2. Verificar rate limiting
                if (!RateLimiter.canMakeRequest()) {
                    console.warn('⚠️ Rate limit reached, using stale cache or fallback');
                    PerformanceMetrics.end(metricName);
                    return false;
                }

                // 3. Resolver endpoint según el evento
                const endpoints = window.GTM_ENDPOINTS || getEndpoints();
                let baseStr;

                if (type && endpoints.gtm[type]) {
                    baseStr = endpoints.gtm[type];
                } else if (type) {
                    baseStr = endpoints.gtm.byEvent(type);
                } else {
                    baseStr = `${endpoints.gtm.base}&type=cart`;
                }

                const url = new URL(baseStr, window.location.origin);

                // Parámetros comunes
                url.searchParams.set('includeCartData', window.GTMCartConfig.backend.includeCartData ? '1' : '0');
                url.searchParams.set('timestamp', Date.now().toString());
                url.searchParams.set('forceRefresh', '1');

                // Extras opcionales
                if (extra && typeof extra === 'object') {
                    const { payment_type, shipping_tier, transaction_id, value } = extra;
                    if (payment_type) url.searchParams.set('payment_type', payment_type);
                    if (shipping_tier) url.searchParams.set('shipping_tier', shipping_tier);
                    if (transaction_id) url.searchParams.set('transaction_id', transaction_id);
                    if (Number.isFinite(+value)) url.searchParams.set('value', String(+value));
                }

                console.log('🔄 Sync GTM (GET):', url.toString());

                const response = await fetch(url.toString(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                    timeout: 10000 // 10 segundos timeout
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);

                const ct = response.headers.get('content-type') || '';
                if (!ct.includes('application/json')) {
                    throw new Error('Respuesta no JSON');
                }

                const text = await response.text();
                if (!text.trim()) {
                    throw new Error('Respuesta vacía');
                }

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    throw new Error(`JSON inválido: ${e.message}`);
                }

                if (result.status !== 'success') {
                    throw new Error(result.message || 'Cart backend sync failed');
                }

                // 4. Guardar en cache (según estrategia)
                GTMCache.set(cacheKey, result, type);

                // Cachear/propagar (si existen)
                if (window.gtmBackendConnector && result.gtmData) {
                    window.gtmBackendConnector.setCachedData('cart_gtm_data_sync', result.gtmData);
                }
                if (window.gtmManager?.updateData) {
                    window.gtmManager.updateData(result.gtmData);
                }

                // Guardar moneda para eventos rápidos
                window.GTM_CART_LAST_CURRENCY =
                    (result?.gtmData?.cartData?.currency) ||
                    window.GTM_CART_LAST_CURRENCY ||
                    'EUR';

                const strategyName = GTMCache.getCurrentStrategy().name;
                console.log(`✅ GTM data synchronized and processed (strategy: ${strategyName})`);
                PerformanceMetrics.end(metricName);
                return result;

            } catch (error) {
                this.logStructuredError(error, {
                    method: 'syncFreshCartData',
                    type: type,
                    extra: extra,
                    cacheKey: GTMCache.generateKey(type, extra)
                });

                PerformanceMetrics.end(metricName);

                // Intentar con cache stale como fallback
                const staleCache = GTMCache.data.get(GTMCache.generateKey(type, extra));
                if (staleCache) {
                    console.warn('🔄 Using stale cached data due to sync error');
                    return staleCache.value;
                }

                return false;
            }
        },

        // ---------------------------------------------------------------------
        // Dispatcher de eventos (con sync condicional)
        // ---------------------------------------------------------------------
        async trackCartEvent(type, data = {}) {
            if (!window.GTMCartConfig.tracking.enabled) {
                console.log('🚫 GTM cart tracking disabled');
                return { success: false, reason: 'tracking_disabled' };
            }

            const metricName = `track_${type}`;
            PerformanceMetrics.start(metricName);

            try {
                // Evitar propagar flag interno
                const { _alreadySynced, ...cleanData } = data || {};
                console.log(`🛒 GTM Cart: Tracking ${type}`, cleanData);

                // Sync previo (solo si no está hecho)
                const shouldSync = window.GTMCartConfig.tracking.syncDataBeforeEvent && !_alreadySynced;
                if (shouldSync) {
                    try {
                        await this.syncFreshCartData(type);
                    } catch (syncError) {
                        console.warn('⚠️ GTM cart data sync failed, continuing with cached data:', syncError.message);
                    }
                }

                const options = cleanData.options || {};

                // Canal unificado de GTM (prioridades)
                let result;
                if (window.gtmExecuteWithBackendData) {
                    result = await window.gtmExecuteWithBackendData(type, options);
                } else if (window.gtmExecuteFromAnywhere) {
                    result = await window.gtmExecuteFromAnywhere(type, cleanData);
                } else if (window.gtmManager && window.gtmManager.executeEvent) {
                    result = window.gtmManager.executeEvent(type, options);
                } else if (typeof dataLayer !== 'undefined') {
                    dataLayer.push({ event: type, ...options });
                    result = { success: true, fallback: 'dataLayer' };
                } else {
                    console.warn('⚠️ No GTM API available for cart tracking');
                    result = { success: false, error: 'GTM API not available' };
                }

                PerformanceMetrics.end(metricName);
                return result;

            } catch (error) {
                this.logStructuredError(error, {
                    method: 'trackCartEvent',
                    eventType: type,
                    data: data,
                    timestamp: Date.now()
                });

                PerformanceMetrics.end(metricName);
                return { success: false, error: error.message };
            }
        },

        // ---------------------------------------------------------------------
        // Eventos específicos (add/remove/view/update)
        // ---------------------------------------------------------------------
        async trackAddToCart(productData) {
            // Validar datos de entrada
            if (!this.validateProductData(productData, 'trackAddToCart')) {
                return { success: false, error: 'Invalid product data' };
            }

            try {
                // Sincroniza para obtener datos completos del backend
                const res = await this.syncFreshCartData('add_to_cart');
                const gtmData = res?.gtmData || {};

                const ctx = this.resolveContext(gtmData);
                const baseIt = this.resolveBaseItem(gtmData, productData);
                const qty = parseInt(productData.qty || 1, 10);
                const item = this.buildItem(productData, baseIt, qty);

                const payload = {
                    user_id: ctx.user_id,
                    user_type: ctx.user_type,
                    country: ctx.country,
                    page_type: 'ficha_producto',
                    ecommerce: {
                        currency: ctx.currency,
                        items: [item]
                    }
                };

                return await this.trackCartEvent('add_to_cart', {
                    options: payload,
                    _alreadySynced: true
                });

            } catch (error) {
                this.logStructuredError(error, {
                    method: 'trackAddToCart',
                    productData: productData
                });
                return { success: false, error: error.message };
            }
        },

        async trackRemoveFromCart(productData) {
            // Validar datos de entrada
            if (!this.validateProductData(productData, 'trackRemoveFromCart')) {
                return { success: false, error: 'Invalid product data' };
            }

            try {
                const res = await this.syncFreshCartData('remove_from_cart');
                const gtmData = res?.gtmData || {};

                const ctx = this.resolveContext(gtmData);
                const baseIt = this.resolveBaseItem(gtmData, productData);
                const qty = parseInt(productData.qty || 1, 10);
                const item = this.buildItem(productData, baseIt, qty);

                const payload = {
                    user_id: ctx.user_id,
                    user_type: ctx.user_type,
                    country: ctx.country,
                    page_type: 'cesta',
                    ecommerce: {
                        currency: ctx.currency,
                        items: [item]
                    }
                };

                return await this.trackCartEvent('remove_from_cart', {
                    options: payload,
                    _alreadySynced: true
                });

            } catch (error) {
                this.logStructuredError(error, {
                    method: 'trackRemoveFromCart',
                    productData: productData
                });
                return { success: false, error: error.message };
            }
        },

        async trackQuantityChange(productData, delta) {
            // Validar datos de entrada
            if (!this.validateProductData(productData, 'trackQuantityChange')) {
                return { success: false, error: 'Invalid product data' };
            }

            const productId = productData.id_product;
            const debounceKey = `qty_change_${productId}`;

            // Usar debounce para evitar spam de eventos de cantidad
            return new Promise((resolve) => {
                DebounceManager.debounce(debounceKey, async () => {
                    try {
                        const type = delta > 0 ? 'add_to_cart' : 'remove_from_cart';
                        const res = await this.syncFreshCartData(type);
                        const gtmData = res?.gtmData || {};

                        const ctx = this.resolveContext(gtmData);
                        const baseIt = this.resolveBaseItem(gtmData, productData);

                        // Preferir la cantidad REAL visible en el DOM
                        const qtyDom = this.readQtyFromDOM(productId ?? baseIt.item_id, Math.abs(delta));
                        const item = this.buildItem(productData, baseIt, qtyDom);

                        const payload = {
                            user_id: ctx.user_id,
                            user_type: ctx.user_type,
                            country: ctx.country,
                            page_type: ctx.page_type,
                            ecommerce: {
                                currency: ctx.currency,
                                items: [item]
                            }
                        };

                        const result = await this.trackCartEvent(type, {
                            options: payload,
                            _alreadySynced: true
                        });

                        resolve(result);

                    } catch (error) {
                        this.logStructuredError(error, {
                            method: 'trackQuantityChange',
                            productData: productData,
                            delta: delta
                        });
                        resolve({ success: false, error: error.message });
                    }
                }, 500); // 500ms de debounce para cambios de cantidad
            });
        },

        /**
         * Track cart view usando gtmData del backend y cantidades del DOM
         * - Lee inputs: input[data-product-id="ITEM_ID"]
         * - Si no existe input, usa quantity del backend
         */
        async trackViewCart(name_evento = 'view_cart') {
            try {
                // Sync explícito (para traer items, totales, usuario, etc.)
                const res = await this.syncFreshCartData(name_evento);
                const gtmData = res && res.gtmData ? res.gtmData : null;

                // Asegurar estructuras
                const cartData     = (gtmData && gtmData.cartData)     ? gtmData.cartData     : {};
                const customerData = (gtmData && gtmData.customerData) ? gtmData.customerData : {};
                const baseItems    = Array.isArray(cartData.items) ? cartData.items : [];

                // Armar items estilo foreach Smarty (actualizando qty desde DOM si existe)
                const dataItems = baseItems.map(p => {
                    const selector = `input[data-product-id="${String(p.item_id)}"]`;
                    const input    = document.querySelector(selector);
                    const qtyDom   = input ? parseFloat(input.value) : undefined;

                    const price    = Number.isFinite(+p.price)    ? parseFloat(+p.price.toFixed(2))    : 0;
                    const discount = Number.isFinite(+p.discount) ? parseFloat(+p.discount.toFixed(2)) : 0;
                    const qty      = Number.isFinite(qtyDom) ? Math.max(0, qtyDom)
                        : Number.isFinite(+p.quantity) ? +p.quantity
                            : 1;

                    return {
                        item_id:        String(p.item_id ?? ''),
                        item_name:      p.item_name ?? '',
                        item_brand:     p.item_brand ?? '',
                        item_category:  p.item_category ?? '',
                        item_variant:   p.item_variant ?? '',
                        item_variant2:  p.item_variant2 ?? '',
                        item_list_name: p.item_list_name ?? '',
                        item_list_id: p.item_list_id ?? '',
                        price,
                        discount,
                        quantity: qty
                    };
                }).filter(i => i.quantity > 0);

                // Payload final (igual a tu snippet, con contexto de página/usuario)
                const payload = {
                    user_id:    customerData.user_id ?? '',
                    user_type:  customerData.user_type ?? '',
                    country:    customerData.country ?? '',
                    page_type:  'cesta',
                    ecommerce: {
                        currency: cartData.currency || window.GTM_CART_LAST_CURRENCY || 'EUR',
                        items: dataItems
                    }
                };

                // Enviar por el canal unificado y marcar como ya sincronizado
                return await this.trackCartEvent(name_evento, {
                    options: payload,
                    _alreadySynced: true
                });

            } catch (error) {
                console.error('❌ Error en trackViewCart:', error);

                // Fallback mínimo
                if (typeof dataLayer !== 'undefined') {
                    dataLayer.push({
                        event: 'view_cart',
                        page_type: 'cesta',
                        ecommerce: { currency: window.GTM_CART_LAST_CURRENCY || 'EUR', items: [] }
                    });
                    return { success: true, fallback: 'dataLayer-minimal' };
                }

                return { success: false, error: error.message };
            }
        },

        // ---------------------------------------------------------------------
        // Eventos adicionales de productos y promociones
        // ---------------------------------------------------------------------

        /**
         * Track view_item - Vista de producto individual
         * @param {Object} productData - Datos del producto desde el backend o externos
         * @param {Object} externalItems - Items externos si no vienen del backend
         */
        async trackViewItem(productData, externalItems = null) {
            try {
                // Sincronizar una sola vez con backend para contexto
                const res = await this.syncFreshCartData('view_item');
                const gtmData = res?.gtmData || {};
                const ctx = this.resolveContext(gtmData);

                let items = [];

                if (externalItems) {
                    // Usar items externos específicos
                    items = Array.isArray(externalItems) ? externalItems : [externalItems];
                    console.log('🔍 Using external items for view_item:', items);
                } else {
                    // Crear item desde productData
                    const baseItem = {};
                    items = [this.buildItem(productData, baseItem, 1)];
                }

                const payload = {
                    user_id: ctx.user_id,
                    user_type: ctx.user_type,
                    country: ctx.country,
                    page_type: 'ficha_producto',
                    ecommerce: {
                        currency: ctx.currency,
                        items
                    }
                };

                return await this.trackCartEvent('view_item', {
                    options: payload,
                    _alreadySynced: true
                });

            } catch (error) {
                console.error('❌ Error en trackViewItem:', error);
                return { success: false, error: error.message };
            }
        },

        /**
         * Track view_item_list - Vista de lista de productos
         * @param {Object} listData - Datos de la lista (nombre, etc.)
         * @param {Array} externalItems - Items externos obligatorio para listas
         */
        async trackViewItemList(listData = {}, externalItems = []) {
            try {
                // Validar items externos
                if (!this.validateExternalItems(externalItems, 'trackViewItemList')) {
                    return { success: false, error: 'Invalid external items' };
                }

                console.log('📋 Tracking view_item_list with external items:', externalItems);

                const res = await this.syncFreshCartData('view_item_list');
                const gtmData = res?.gtmData || {};
                const ctx = this.resolveContext(gtmData);

                const payload = {
                    user_id: ctx.user_id,
                    user_type: ctx.user_type,
                    country: ctx.country,
                    page_type: 'categoria',
                    ecommerce: {
                        currency: ctx.currency,
                        item_list_name: listData.list_name || '',
                        item_list_id: listData.list_id || '',
                        items: externalItems
                    }
                };

                return await this.trackCartEvent('view_item_list', {
                    options: payload,
                    _alreadySynced: true
                });

            } catch (error) {
                console.error('❌ Error en trackViewItemList:', error);
                return { success: false, error: error.message };
            }
        },

        /**
         * Track select_item - Selección de producto desde lista
         */
        async trackSelectItem(productData, listData = {}) {
            try {
                const res = await this.syncFreshCartData('select_item');
                const gtmData = res?.gtmData || {};
                const ctx = this.resolveContext(gtmData);
                const baseItem = {};
                const item = this.buildItem(productData, baseItem, 1);

                const payload = {
                    user_id: ctx.user_id,
                    user_type: ctx.user_type,
                    country: ctx.country,
                    page_type: 'categoria',
                    item_list_name: listData.list_name || 'Product List',
                    item_list_id: listData.list_id || '',
                    ecommerce: {
                        currency: ctx.currency,
                        items: [item]
                    }
                };

                return await this.trackCartEvent('select_item', {
                    options: payload,
                    _alreadySynced: true
                });

            } catch (error) {
                console.error('❌ Error en trackSelectItem:', error);
                return { success: false, error: error.message };
            }
        },

        /**
         * Track view_promotion - Vista de promoción
         */
        async trackViewPromotion(promotionData) {
            try {
                const res = await this.syncFreshCartData('view_promotion');
                const gtmData = res?.gtmData || {};
                const ctx = this.resolveContext(gtmData);

                const payload = {
                    user_id: ctx.user_id,
                    user_type: ctx.user_type,
                    country: ctx.country,
                    page_type: 'home',
                    creative_name: promotionData.creative_name || '',
                    creative_slot: promotionData.creative_slot || '',
                    promotion_id: promotionData.promotion_id || '',
                    promotion_name: promotionData.promotion_name || '',
                    ecommerce: {
                        currency: ctx.currency,
                        items: promotionData.items || []
                    }
                };

                return await this.trackCartEvent('view_promotion', {
                    options: payload,
                    _alreadySynced: true
                });

            } catch (error) {
                console.error('❌ Error en trackViewPromotion:', error);
                return { success: false, error: error.message };
            }
        },

        /**
         * Track select_promotion - Selección de promoción
         */
        async trackSelectPromotion(promotionData) {
            try {
                const res = await this.syncFreshCartData('select_promotion');
                const gtmData = res?.gtmData || {};
                const ctx = this.resolveContext(gtmData);

                const payload = {
                    user_id: ctx.user_id,
                    user_type: ctx.user_type,
                    country: ctx.country,
                    page_type: 'home',
                    creative_name: promotionData.creative_name || '',
                    creative_slot: promotionData.creative_slot || '',
                    promotion_id: promotionData.promotion_id || '',
                    promotion_name: promotionData.promotion_name || '',
                    ecommerce: {
                        currency: ctx.currency,
                        items: promotionData.items || []
                    }
                };

                return await this.trackCartEvent('select_promotion', {
                    options: payload,
                    _alreadySynced: true
                });

            } catch (error) {
                console.error('❌ Error en trackSelectPromotion:', error);
                return { success: false, error: error.message };
            }
        },

        /**
         * Track refund - Reembolso
         */
        async trackRefund(refundData) {
            try {
                const res = await this.syncFreshCartData('refund', {
                    transaction_id: refundData.transaction_id,
                    value: refundData.value
                });
                const gtmData = res?.gtmData || {};
                const ctx = this.resolveContext(gtmData);

                const payload = {
                    user_id: ctx.user_id,
                    user_type: ctx.user_type,
                    country: ctx.country,
                    page_type: 'order_refund',
                    transaction_id: refundData.transaction_id || '',
                    value: refundData.value || 0,
                    currency: refundData.currency || ctx.currency,
                    ecommerce: {
                        currency: refundData.currency || ctx.currency,
                        transaction_id: refundData.transaction_id || '',
                        value: refundData.value || 0,
                        items: refundData.items || []
                    }
                };

                return await this.trackCartEvent('refund', {
                    options: payload,
                    _alreadySynced: true
                });

            } catch (error) {
                console.error('❌ Error en trackRefund:', error);
                return { success: false, error: error.message };
            }
        },

        // ---------------------------------------------------------------------
        // Init
        // ---------------------------------------------------------------------
        init() {
            console.log('🛒 GTM Cart Helper initialized');

            // Log de estrategia de cache actual
            const strategyInfo = this.getCacheStrategyInfo();
            console.log(`📋 Cache Strategy: ${strategyInfo.current} - ${strategyInfo.name}`);
            console.log(`📝 ${strategyInfo.description}`);

            // Disparo automático en la página de carrito
            if (document.body.classList.contains('page-cart')) {
                setTimeout(() => { this.trackViewCart(); }, 1000);
            }

            // Modo debug
            if (window.GTMCartConfig.tracking.debug) {
                console.log('🔍 GTM Cart Debug mode enabled');
                window.GTMCartDebug = {
                    config:               window.GTMCartConfig,
                    endpoints:            window.GTM_ENDPOINTS,
                    helper:               window.GTMCartHelper,
                    cache:                GTMCache,
                    rateLimiter:          RateLimiter,
                    performanceMetrics:   PerformanceMetrics,
                    debounceManager:      DebounceManager,
                    trackCartEvent:       this.trackCartEvent.bind(this),
                    trackAddToCart:       this.trackAddToCart.bind(this),
                    trackRemoveFromCart:  this.trackRemoveFromCart.bind(this),
                    trackViewCart:        this.trackViewCart.bind(this),
                    trackViewItem:        this.trackViewItem.bind(this),
                    trackViewItemList:    this.trackViewItemList.bind(this),
                    trackSelectItem:      this.trackSelectItem.bind(this),
                    trackViewPromotion:   this.trackViewPromotion.bind(this),
                    trackSelectPromotion: this.trackSelectPromotion.bind(this),
                    trackRefund:          this.trackRefund.bind(this),
                    syncFreshCartData:    this.syncFreshCartData.bind(this),
                    validateProductData:  this.validateProductData.bind(this),
                    validateExternalItems: this.validateExternalItems.bind(this),
                    // Utilidades de debug
                    clearCache: () => GTMCache.clear(),
                    getCacheStats: () => GTMCache.getStats(),
                    setCacheStrategy: this.setCacheStrategy.bind(this),
                    getCacheStrategyInfo: this.getCacheStrategyInfo.bind(this),
                    getRateLimitStats: () => ({ requests: RateLimiter.requests.length, maxRequests: RateLimiter.maxRequests }),
                    getPerformanceStats: () => ({ activeEvents: Array.from(PerformanceMetrics.events.keys()) }),
                    // Quick strategy switchers
                    useMaxPerformance: () => this.setCacheStrategy('A'),
                    useMaxPrecision: () => this.setCacheStrategy('B'),
                    useSmartHybrid: () => this.setCacheStrategy('C')
                };
            }
        }
    };

    // =========================================================================
    // EXTENSIÓN DE CartManager (si existe)
    // =========================================================================
    if (window.CartManager) {
        const originalCartManagerPrototype = window.CartManager.prototype;

        originalCartManagerPrototype.trackAddToCart = async function(productData, response) {
            return await window.GTMCartHelper.trackAddToCart(productData);
        };

        originalCartManagerPrototype.trackRemoveFromCart = async function(productData, response) {
            return await window.GTMCartHelper.trackRemoveFromCart(productData);
        };

        originalCartManagerPrototype.trackUpdateQuantity = async function(productData, response, delta) {
            return await window.GTMCartHelper.trackQuantityChange(productData, delta);
        };

        console.log('✅ CartManager GTM methods enhanced');
    }

    // =========================================================================
    // AUTO-INIT
    // =========================================================================
    $(document).ready(function() {
        window.GTMCartHelper.init();
    });

    console.log('✅ GTM Cart Config loaded');

})();
