/**
 * GTM Unified Manager - Versión Unificada
 * Combina GTM Backend Connector y GTM Usage Helper
 * @version 2.0 Unified
 * @author Tu equipo de desarrollo
 */

/**
 * GTM Backend Connector - Conecta con el backend PHP
 */
class GTMBackendConnector {
    constructor(options = {}) {
        // Configuración
        this.config = {
            routesUrl: options.routesUrl || '/modules/alsernetshopping/controllers/front/routes.php',
            cacheTimeout: options.cacheTimeout || 30000, // 30 segundos
            maxRetries: options.maxRetries || 3,
            retryDelay: options.retryDelay || 1000, // 1 segundo
            requestTimeout: options.requestTimeout || 10000, // 10 segundos
            enableDebug: options.enableDebug || false
        };

        // Cache mejorado con TTL
        this.cache = new Map();
        this.requestQueue = new Map();

        // Métricas y monitoreo
        this.metrics = {
            requests: 0,
            cacheHits: 0,
            cacheMisses: 0,
            errors: 0,
            lastSync: null
        };

        // Eventos personalizados
        this.eventEmitter = this.createEventEmitter();

        this.log('🔗 GTM Backend Connector v2.0: Initialized');
    }

    /**
     * Sistema de eventos personalizado
     */
    createEventEmitter() {
        const listeners = new Map();
        return {
            on: (event, callback) => {
                if (!listeners.has(event)) listeners.set(event, []);
                listeners.get(event).push(callback);
            },
            emit: (event, data) => {
                if (listeners.has(event)) {
                    listeners.get(event).forEach(callback => {
                        try {
                            callback(data);
                        } catch (error) {
                            console.error(`Error in event listener for ${event}:`, error);
                        }
                    });
                }
            },
            off: (event, callback) => {
                if (listeners.has(event)) {
                    const callbacks = listeners.get(event);
                    const index = callbacks.indexOf(callback);
                    if (index > -1) callbacks.splice(index, 1);
                }
            }
        };
    }

    /**
     * Logging mejorado con niveles
     */
    log(message, level = 'info', data = null) {
        if (!this.config.enableDebug && level === 'debug') return;

        const timestamp = new Date().toISOString();
        const prefix = {
            info: '📦',
            success: '✅',
            warning: '⚠️',
            error: '❌',
            debug: '🔍'
        }[level] || 'ℹ️';

        console[level === 'error' ? 'error' : 'log'](`${prefix} [${timestamp}] ${message}`, data || '');
    }

    /**
     * Validador de datos mejorado
     */
    validateResponse(data) {
        if (!data || typeof data !== 'object') {
            throw new Error('Invalid response format: not an object');
        }

        if (data.status !== 'success') {
            throw new Error(data.message || 'Request failed');
        }

        if (!data.gtmData) {
            throw new Error('Missing gtmData in response');
        }

        // Validar estructura de gtmData
        const { gtmData } = data;
        if (!gtmData.cartData || !gtmData.customerData) {
            this.log('Warning: Incomplete GTM data structure', 'warning', gtmData);
        }

        return true;
    }

    /**
     * Cliente HTTP mejorado con reintentos y timeout
     */
    async makeRequest(formData, retryCount = 0) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.config.requestTimeout);

        try {
            this.metrics.requests++;
            this.log(`🔄 Making request (attempt ${retryCount + 1})`, 'debug');

            const response = await fetch(this.config.routesUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                },
                credentials: 'same-origin',
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Invalid response content type');
            }

            const data = await response.json();
            this.validateResponse(data);

            return data;

        } catch (error) {
            clearTimeout(timeoutId);

            if (error.name === 'AbortError') {
                throw new Error('Request timeout');
            }

            // Reintentar en caso de error
            if (retryCount < this.config.maxRetries && this.shouldRetry(error)) {
                this.log(`🔄 Retrying request in ${this.config.retryDelay}ms...`, 'warning');
                await this.delay(this.config.retryDelay);
                return this.makeRequest(formData, retryCount + 1);
            }

            this.metrics.errors++;
            throw error;
        }
    }

    /**
     * Determina si un error es recuperable
     */
    shouldRetry(error) {
        const retryableErrors = [
            'fetch',
            'network',
            'timeout',
            'HTTP 5', // 5xx errors
            'HTTP 429' // Rate limit
        ];

        return retryableErrors.some(pattern =>
            error.message.toLowerCase().includes(pattern.toLowerCase())
        );
    }

    /**
     * Utilidad para delay
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Cache mejorado con TTL y limpieza automática
     */
    getCachedData(key) {
        if (!this.cache.has(key)) {
            this.metrics.cacheMisses++;
            return null;
        }

        const cached = this.cache.get(key);
        const now = Date.now();

        if (now - cached.timestamp > this.config.cacheTimeout) {
            this.cache.delete(key);
            this.metrics.cacheMisses++;
            return null;
        }

        this.metrics.cacheHits++;
        this.log(`📦 Cache hit for key: ${key}`, 'debug');
        return cached.data;
    }

    setCachedData(key, data) {
        this.cache.set(key, {
            data,
            timestamp: Date.now()
        });

        // Limpieza automática del cache
        this.cleanExpiredCache();
    }

    cleanExpiredCache() {
        const now = Date.now();
        for (const [key, cached] of this.cache.entries()) {
            if (now - cached.timestamp > this.config.cacheTimeout) {
                this.cache.delete(key);
            }
        }
    }

    /**
     * Prevención de requests duplicados
     */
    async deduplicateRequest(key, requestFn) {
        if (this.requestQueue.has(key)) {
            this.log(`⏳ Waiting for ongoing request: ${key}`, 'debug');
            return await this.requestQueue.get(key);
        }

        const promise = requestFn();
        this.requestQueue.set(key, promise);

        try {
            const result = await promise;
            return result;
        } finally {
            this.requestQueue.delete(key);
        }
    }

    /**
     * Obtiene datos GTM actualizados del backend
     */
    async fetchGTMData(options = {}) {
        const {
            forceRefresh = false,
            includeTemplates = false,
            timeout = null
        } = options;

        const cacheKey = `gtm_data_${includeTemplates ? 'with_templates' : 'basic'}`;

        // Verificar cache
        if (!forceRefresh) {
            const cachedData = this.getCachedData(cacheKey);
            if (cachedData) {
                this.eventEmitter.emit('dataFetched', { source: 'cache', data: cachedData });
                return cachedData;
            }
        }

        // Deduplicar requests
        return await this.deduplicateRequest(cacheKey, async () => {
            try {
                this.log('🔄 Fetching fresh GTM data from server', 'info');
                this.eventEmitter.emit('fetchStart', { cacheKey });

                const formData = new FormData();
                formData.append('modalitie', 'gtp');
                formData.append('action', 'init');
                formData.append('includeTemplates', includeTemplates ? '1' : '0');
                formData.append('timestamp', Date.now().toString());

                if (forceRefresh) {
                    formData.append('forceRefresh', '1');
                }

                if (timeout) {
                    const originalTimeout = this.config.requestTimeout;
                    this.config.requestTimeout = timeout;

                    try {
                        const data = await this.makeRequest(formData);
                        this.config.requestTimeout = originalTimeout;

                        const gtmData = data.gtmData;
                        this.setCachedData(cacheKey, gtmData);
                        this.metrics.lastSync = new Date().toISOString();

                        this.log('✅ GTM data fetched successfully', 'success');
                        this.eventEmitter.emit('dataFetched', { source: 'server', data: gtmData });

                        return gtmData;
                    } finally {
                        this.config.requestTimeout = originalTimeout;
                    }
                } else {
                    const data = await this.makeRequest(formData);
                    const gtmData = data.gtmData;

                    this.setCachedData(cacheKey, gtmData);
                    this.metrics.lastSync = new Date().toISOString();

                    this.log('✅ GTM data fetched successfully', 'success');
                    this.eventEmitter.emit('dataFetched', { source: 'server', data: gtmData });

                    return gtmData;
                }

            } catch (error) {
                this.log(`❌ Error fetching GTM data: ${error.message}`, 'error');
                this.eventEmitter.emit('fetchError', { error, cacheKey });

                // Retornar datos del cache expirado si están disponibles
                const expiredData = this.cache.get(cacheKey);
                if (expiredData) {
                    this.log('📦 Using expired cache data as fallback', 'warning');
                    return expiredData.data;
                }

                return this.getFallbackData();
            }
        });
    }

    /**
     * Obtiene datos específicos para un evento
     */
    async fetchGTMDataForEvent(eventType, options = {}) {
        if (!eventType) {
            throw new Error('eventType is required');
        }

        const cacheKey = `event_${eventType}_${JSON.stringify(options)}`;

        try {
            this.log(`🎯 Fetching GTM data for event: ${eventType}`, 'info');
            this.eventEmitter.emit('eventFetchStart', { eventType, options });

            const formData = new FormData();
            formData.append('modalitie', 'gtp');
            formData.append('action', 'init');
            formData.append('eventType', eventType);
            formData.append('timestamp', Date.now().toString());

            // Agregar opciones específicas del evento
            Object.entries(options).forEach(([key, value]) => {
                if (value !== null && value !== undefined) {
                    formData.append(key, String(value));
                }
            });

            const data = await this.makeRequest(formData);
            const result = {
                gtmData: data.gtmData,
                eventOptions: data.eventOptions || options,
                eventType: data.eventType || eventType,
                serverTimestamp: data.timestamp
            };

            this.log(`✅ Event data for ${eventType} fetched successfully`, 'success');
            this.eventEmitter.emit('eventDataFetched', { eventType, result });

            return result;

        } catch (error) {
            this.log(`❌ Error fetching event data for ${eventType}: ${error.message}`, 'error');
            this.eventEmitter.emit('eventFetchError', { eventType, error, options });

            return {
                gtmData: this.getFallbackData(),
                eventOptions: options,
                eventType: eventType,
                isError: true,
                error: error.message
            };
        }
    }

    /**
     * Datos de fallback mejorados
     */
    getFallbackData() {
        this.log('⚠️ Using fallback GTM data', 'warning');

        const fallbackData = {
            cartData: {
                currency: 'EUR',
                total_value: 0,
                tax: 0,
                shipping: 0,
                total_discounts: 0,
                transaction_id: '',
                affiliation: 'Online Store',
                items: [],
                timestamp: new Date().toISOString(),
                source: 'fallback'
            },
            customerData: {
                user_id: '',
                user_type: 'guest',
                country: 'ES',
                page_type: document.body.id || 'general',
                checkout_step: '0',
                payment_type: '',
                shipping_tier: '',
                current_delivery_address: null,
                current_invoice_address: null,
                timestamp: new Date().toISOString(),
                source: 'fallback'
            }
        };

        this.eventEmitter.emit('fallbackUsed', fallbackData);
        return fallbackData;
    }

    /**
     * Health check del conector
     */
    async healthCheck() {
        try {
            const startTime = performance.now();
            const formData = new FormData();
            formData.append('modalitie', 'gtp');
            formData.append('action', 'health');

            await this.makeRequest(formData);
            const responseTime = performance.now() - startTime;

            return {
                status: 'healthy',
                responseTime: Math.round(responseTime),
                metrics: this.getMetrics(),
                cache: this.getCacheStatus()
            };

        } catch (error) {
            return {
                status: 'unhealthy',
                error: error.message,
                metrics: this.getMetrics(),
                cache: this.getCacheStatus()
            };
        }
    }

    /**
     * Obtener métricas del conector
     */
    getMetrics() {
        return {
            ...this.metrics,
            cacheEfficiency: this.metrics.requests > 0
                ? Math.round((this.metrics.cacheHits / (this.metrics.cacheHits + this.metrics.cacheMisses)) * 100)
                : 0,
            errorRate: this.metrics.requests > 0
                ? Math.round((this.metrics.errors / this.metrics.requests) * 100)
                : 0
        };
    }

    /**
     * Obtener estado del cache
     */
    getCacheStatus() {
        const entries = Array.from(this.cache.entries()).map(([key, value]) => ({
            key,
            age: Date.now() - value.timestamp,
            expired: (Date.now() - value.timestamp) > this.config.cacheTimeout
        }));

        return {
            size: this.cache.size,
            entries: entries,
            timeout: this.config.cacheTimeout,
            activeRequests: this.requestQueue.size
        };
    }

    /**
     * Limpia la cache y métricas
     */
    clearCache() {
        this.cache.clear();
        this.requestQueue.clear();
        this.log('🗑️ Cache and request queue cleared', 'info');
        this.eventEmitter.emit('cacheCleared');
    }

    /**
     * Reinicia las métricas
     */
    resetMetrics() {
        this.metrics = {
            requests: 0,
            cacheHits: 0,
            cacheMisses: 0,
            errors: 0,
            lastSync: null
        };
        this.log('📊 Metrics reset', 'info');
    }

    /**
     * Configurar el conector
     */
    configure(newConfig) {
        this.config = { ...this.config, ...newConfig };
        this.log('⚙️ Configuration updated', 'info', this.config);
        this.eventEmitter.emit('configChanged', this.config);
    }

    /**
     * Destructor para limpieza
     */
    destroy() {
        this.clearCache();
        this.resetMetrics();
        this.log('🔚 GTM Backend Connector destroyed', 'info');
        this.eventEmitter.emit('destroyed');
    }
}

/**
 * GTM Usage Helper - Ejemplos de uso y APIs simplificadas
 */
class GTMUsageHelper {
    constructor() {
        this.config = {
            defaultCurrency: 'EUR',
            defaultCountry: 'ES',
            enableLogging: true,
            autoRefreshData: true,
            fallbackTimeout: 5000
        };

        this.eventQueue = [];
        this.isInitialized = false;

        this.init();
    }

    async init() {
        // Esperar a que GTM Manager y Backend Connector estén disponibles
        await this.waitForDependencies();
        this.setupEventListeners();
        this.isInitialized = true;

        // Procesar eventos en cola
        this.processQueuedEvents();

        this.log('✅ GTM Usage Helper v2.0: Initialized successfully');
    }

    async waitForDependencies() {
        return new Promise((resolve) => {
            const checkDependencies = () => {
                if (window.gtmManager && window.gtmBackendConnector) {
                    resolve();
                } else {
                    setTimeout(checkDependencies, 100);
                }
            };
            checkDependencies();
        });
    }

    log(message, level = 'info', data = null) {
        if (!this.config.enableLogging) return;

        const timestamp = new Date().toISOString();
        const prefix = {
            info: '📚',
            success: '✅',
            warning: '⚠️',
            error: '❌'
        }[level] || 'ℹ️';

        console[level === 'error' ? 'error' : 'log'](`${prefix} [GTM Usage] ${message}`, data || '');
    }

    processQueuedEvents() {
        while (this.eventQueue.length > 0) {
            const { method, args } = this.eventQueue.shift();
            try {
                this[method](...args);
            } catch (error) {
                this.log(`Error processing queued event: ${error.message}`, 'error');
            }
        }
    }

    queueEvent(method, ...args) {
        if (this.isInitialized) {
            this[method](...args);
        } else {
            this.eventQueue.push({ method, args });
        }
    }

    /**
     * Actualizar datos del carrito y cliente
     */
    async updateGTMData(customData = {}) {
        try {
            // Obtener datos frescos del backend si está habilitado
            if (this.config.autoRefreshData) {
                const backendResult = await window.gtmManager.updateDataFromBackend();
                if (backendResult.success) {
                    this.log('📦 Data updated from backend successfully');

                    // Combinar con datos personalizados si existen
                    if (Object.keys(customData).length > 0) {
                        window.gtmManager.updateData(customData);
                        this.log('📝 Custom data merged with backend data');
                    }

                    return backendResult.data;
                }
            }

            // Fallback: usar datos locales
            const fallbackData = this.createSampleData(customData);
            window.gtmManager.updateData(fallbackData);
            this.log('📋 Using local/sample data', 'warning');

            return fallbackData;

        } catch (error) {
            this.log(`Error updating GTM data: ${error.message}`, 'error');
            throw error;
        }
    }

    createSampleData(customData = {}) {
        const defaultCartData = {
            currency: this.config.defaultCurrency,
            total_value: 150.75,
            tax: 21.11,
            shipping: 5.95,
            total_discounts: 10.00,
            transaction_id: `ORDER_${Date.now()}`,
            affiliation: 'Online Store',
            timestamp: new Date().toISOString(),
            items: [
                {
                    item_id: 'PROD_001',
                    item_unique_id: 'UNIQUE_001',
                    item_name: 'Producto Ejemplo 1',
                    item_brand: 'Marca A',
                    item_category: 'Categoría 1',
                    item_variant: 'Color Rojo',
                    item_variant2: 'Talla M',
                    item_list_name: 'Lista Principal',
                    price: 75.00,
                    discount: 5.00,
                    quantity: 2
                },
                {
                    item_id: 'PROD_002',
                    item_unique_id: 'UNIQUE_002',
                    item_name: 'Producto Ejemplo 2',
                    item_brand: 'Marca B',
                    item_category: 'Categoría 2',
                    item_variant: 'Color Azul',
                    item_variant2: 'Talla L',
                    item_list_name: 'Lista Secundaria',
                    price: 45.50,
                    discount: 2.25,
                    quantity: 1
                }
            ]
        };

        const defaultCustomerData = {
            user_id: window.userId || `GUEST_${Date.now()}`,
            user_type: window.userType || 'guest',
            country: this.config.defaultCountry,
            page_type: document.body.id || 'general',
            checkout_step: '0',
            payment_type: '',
            shipping_tier: '',
            current_delivery_address: null,
            current_invoice_address: null,
            timestamp: new Date().toISOString()
        };

        return {
            cartData: { ...defaultCartData, ...customData.cartData },
            customerData: { ...defaultCustomerData, ...customData.customerData }
        };
    }

    /**
     * Ejecutar add_to_cart mejorado
     */
    async executeAddToCart(options = {}) {
        try {
            this.log('🛒 Executing add_to_cart event');

            const result = await window.gtmExecuteWithBackendData('add_to_cart', {
                force: options.force || false,
                item_data: options.item_data,
                ...options
            });

            if (result.success) {
                this.log('✅ add_to_cart executed successfully', 'success');
            } else {
                this.log(`⚠️ add_to_cart executed with fallback: ${result.error}`, 'warning');
            }

            return result;

        } catch (error) {
            this.log(`❌ Error executing add_to_cart: ${error.message}`, 'error');
            throw error;
        }
    }

    /**
     * Ejecutar begin_checkout mejorado
     */
    async executeBeginCheckout(options = {}) {
        try {
            this.log('🚀 Executing begin_checkout event');

            const result = await window.gtmExecuteWithBackendData('begin_checkout', {
                force: true, // Siempre forzar para checkout
                checkout_step: '1',
                ...options
            });

            if (result.success) {
                this.log('✅ begin_checkout executed successfully', 'success');
            } else {
                this.log(`⚠️ begin_checkout executed with fallback: ${result.error}`, 'warning');
            }

            return result;

        } catch (error) {
            this.log(`❌ Error executing begin_checkout: ${error.message}`, 'error');
            throw error;
        }
    }

    /**
     * Ejecutar add_payment_info mejorado
     */
    async executeAddPaymentInfo(paymentMethod, options = {}) {
        if (!paymentMethod) {
            throw new Error('Payment method is required');
        }

        try {
            this.log(`💳 Executing add_payment_info with method: ${paymentMethod}`);

            const result = await window.gtmExecuteWithBackendData('add_payment_info', {
                payment_type: paymentMethod,
                checkout_step: '3',
                ...options
            });

            if (result.success) {
                this.log('✅ add_payment_info executed successfully', 'success');
            } else {
                this.log(`⚠️ add_payment_info executed with fallback: ${result.error}`, 'warning');
            }

            return result;

        } catch (error) {
            this.log(`❌ Error executing add_payment_info: ${error.message}`, 'error');
            throw error;
        }
    }

    /**
     * Ejecutar add_shipping_info mejorado
     */
    async executeAddShippingInfo(shippingMethod, options = {}) {
        if (!shippingMethod) {
            throw new Error('Shipping method is required');
        }

        try {
            this.log(`🚚 Executing add_shipping_info with method: ${shippingMethod}`);

            const result = await window.gtmExecuteWithBackendData('add_shipping_info', {
                shipping_tier: shippingMethod,
                checkout_step: '2',
                ...options
            });

            if (result.success) {
                this.log('✅ add_shipping_info executed successfully', 'success');
            } else {
                this.log(`⚠️ add_shipping_info executed with fallback: ${result.error}`, 'warning');
            }

            return result;

        } catch (error) {
            this.log(`❌ Error executing add_shipping_info: ${error.message}`, 'error');
            throw error;
        }
    }

    /**
     * Ejecutar purchase mejorado
     */
    async executePurchase(transactionId, options = {}) {
        if (!transactionId) {
            throw new Error('Transaction ID is required');
        }

        try {
            this.log(`🎉 Executing purchase with transaction: ${transactionId}`);

            const result = await window.gtmExecuteWithBackendData('purchase', {
                transaction_id: transactionId,
                affiliation: 'Online Store',
                ...options
            });

            if (result.success) {
                this.log('✅ purchase executed successfully', 'success');

                // Evento adicional para conversiones importantes
                this.triggerConversionTracking(transactionId, options);
            } else {
                this.log(`⚠️ purchase executed with fallback: ${result.error}`, 'warning');
            }

            return result;

        } catch (error) {
            this.log(`❌ Error executing purchase: ${error.message}`, 'error');
            throw error;
        }
    }

    /**
     * Tracking adicional para conversiones
     */
    triggerConversionTracking(transactionId, options) {
        try {
            // Aquí puedes agregar tracking adicional para Facebook Pixel, etc.
            this.log(`📈 Conversion tracking triggered for: ${transactionId}`);

            // Ejemplo de evento personalizado
            window.gtmManager?.executeEvent('conversion_completed', {
                transaction_id: transactionId,
                conversion_type: 'purchase',
                timestamp: new Date().toISOString(),
                ...options
            });

        } catch (error) {
            this.log(`Error in conversion tracking: ${error.message}`, 'error');
        }
    }

    /**
     * Sistema de acciones mejorado con validación
     */
    async executeGTMByAction(action, data = {}) {
        if (!action) {
            throw new Error('Action is required');
        }

        const actionMap = {
            'cart_add': () => this.executeAddToCart(data.options),
            'checkout_start': () => this.executeBeginCheckout(data.options),
            'address_added': () => this.executeEventWithData('add_address_info', data),
            'shipping_selected': () => this.executeAddShippingInfo(data.shippingMethod, data.options),
            'payment_selected': () => this.executeAddPaymentInfo(data.paymentMethod, data.options),
            'purchase_completed': () => this.executePurchase(data.transactionId, data.options),
            'cart_view': () => this.executeEventWithData('view_cart', data),
            'cart_remove': () => this.executeEventWithData('remove_from_cart', data),
            'page_view': () => this.executeEventWithData('page_view', data),
            'user_engagement': () => this.executeEventWithData('user_engagement', data)
        };

        const actionHandler = actionMap[action];

        if (!actionHandler) {
            this.log(`⚠️ Unknown action: ${action}`, 'warning');
            return { success: false, error: 'Unknown action' };
        }

        try {
            this.log(`🎯 Executing action: ${action}`);
            const result = await actionHandler();
            return result || { success: true };

        } catch (error) {
            this.log(`❌ Error executing action ${action}: ${error.message}`, 'error');
            return { success: false, error: error.message };
        }
    }

    async executeEventWithData(eventType, data) {
        // Actualizar datos si se proporcionan
        if (data.cartData || data.customerData) {
            await this.updateGTMData({ cartData: data.cartData, customerData: data.customerData });
        }

        return await window.gtmExecuteWithBackendData(eventType, data.options || {});
    }

    /**
     * Event listeners DOM mejorados
     */
    setupEventListeners() {
        this.log('🎧 Setting up DOM event listeners');

        // Delegación de eventos mejorada
        document.addEventListener('click', this.handleClickEvents.bind(this));
        document.addEventListener('change', this.handleChangeEvents.bind(this));
        document.addEventListener('submit', this.handleSubmitEvents.bind(this));

        // Eventos personalizados del navegador
        window.addEventListener('beforeunload', this.handlePageUnload.bind(this));

        // Visibility API para tracking de engagement
        document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
    }

    async handleClickEvents(event) {
        const target = event.target;

        try {
            // Botón añadir al carrito
            if (target.closest('.add-to-cart')) {
                event.preventDefault();
                const productData = this.extractProductData(target);
                await this.executeAddToCart({ item_data: productData });
            }

            // Botón comenzar checkout
            else if (target.closest('#continuenew, .begin-checkout')) {
                await this.executeBeginCheckout();
            }

            // Botón finalizar compra
            else if (target.closest('.complete-purchase')) {
                const transactionId = target.dataset.transactionId || `ORDER_${Date.now()}`;
                await this.executePurchase(transactionId);
            }

            // Botón ver carrito
            else if (target.closest('.view-cart')) {
                await this.executeEventWithData('view_cart');
            }

            // Botón remover del carrito
            else if (target.closest('.remove-from-cart')) {
                const productId = target.dataset.productId;
                await this.executeEventWithData('remove_from_cart', {
                    options: { item_id: productId }
                });
            }

        } catch (error) {
            this.log(`Error handling click event: ${error.message}`, 'error');
        }
    }

    async handleChangeEvents(event) {
        const target = event.target;

        try {
            // Cambio en método de pago
            if (target.matches('.payment_option_select, input[name="payment-option"]')) {
                const paymentLabel = document.querySelector(`label[for="${target.id}"]`);
                const paymentMethod = paymentLabel ? paymentLabel.textContent.trim() : target.value;

                if (paymentMethod) {
                    await this.executeAddPaymentInfo(paymentMethod);
                }
            }

            // Cambio en método de envío
            else if (target.matches('.delivery_option_select, input[name="delivery-option"]')) {
                const shippingLabel = document.querySelector(`label[for="${target.id}"]`);
                let shippingMethod = shippingLabel ? shippingLabel.textContent.trim() : target.value;

                // Normalizar texto
                const shippingNormalizations = {
                    'Shipment to the selected address': 'Envío a domicilio',
                    'Pick up in store': 'Recogida en tienda',
                    'Express delivery': 'Envío express'
                };

                shippingMethod = shippingNormalizations[shippingMethod] || shippingMethod;

                if (shippingMethod) {
                    await this.executeAddShippingInfo(shippingMethod);
                }
            }

            // Cambio en cantidad de productos
            else if (target.matches('.product-quantity, .js-cart-line-product-quantity')) {
                // Debounce para evitar múltiples eventos
                clearTimeout(this.quantityTimeout);
                this.quantityTimeout = setTimeout(async () => {
                    await this.executeEventWithData('cart_update', {
                        options: {
                            item_id: target.dataset.productId,
                            new_quantity: target.value
                        }
                    });
                }, 500);
            }

        } catch (error) {
            this.log(`Error handling change event: ${error.message}`, 'error');
        }
    }

    async handleSubmitEvents(event) {
        const form = event.target;

        try {
            // Formulario de checkout
            if (form.matches('.checkout-form')) {
                await this.executeEventWithData('form_submit', {
                    options: { form_type: 'checkout' }
                });
            }

            // Formulario de contacto
            else if (form.matches('.contact-form')) {
                await this.executeEventWithData('form_submit', {
                    options: { form_type: 'contact' }
                });
            }

        } catch (error) {
            this.log(`Error handling submit event: ${error.message}`, 'error');
        }
    }

    handlePageUnload() {
        // Tracking de salida de página
        window.gtmManager?.executeEvent('page_unload', {
            timestamp: new Date().toISOString(),
            time_on_page: Date.now() - (window.pageStartTime || Date.now())
        });
    }

    handleVisibilityChange() {
        const eventType = document.hidden ? 'page_hidden' : 'page_visible';
        window.gtmManager?.executeEvent(eventType, {
            timestamp: new Date().toISOString()
        });
    }

    /**
     * Extractor de datos del carrito desde DOM mejorado
     */
    getCurrentCartDataFromDOM() {
        try {
            const cartData = {
                currency: this.config.defaultCurrency,
                total_value: 0,
                tax: 0,
                shipping: 0,
                total_discounts: 0,
                items: []
            };

            // Extraer totales si están disponibles
            const totalElement = document.querySelector('.cart-total, .total-amount');
            if (totalElement) {
                const totalText = totalElement.textContent.replace(/[^0-9.,]/g, '');
                cartData.total_value = parseFloat(totalText.replace(',', '.')) || 0;
            }

            // Extraer items del carrito
            const productElements = document.querySelectorAll(
                '.product-line, .cart-item, .checkout-product'
            );

            productElements.forEach((item, index) => {
                const productData = this.extractProductData(item);
                if (productData.item_id && productData.item_name) {
                    cartData.items.push(productData);
                }
            });

            this.log(`📦 Extracted ${cartData.items.length} items from DOM`);
            return cartData;

        } catch (error) {
            this.log(`Error extracting cart data from DOM: ${error.message}`, 'error');
            return this.createSampleData().cartData;
        }
    }

    extractProductData(element) {
        const productData = {
            item_id: '',
            item_unique_id: '',
            item_name: '',
            item_brand: '',
            item_category: '',
            item_variant: '',
            item_variant2: '',
            item_list_name: 'Cart',
            price: 0,
            discount: 0,
            quantity: 1
        };

        try {
            // ID del producto
            productData.item_id = element.getAttribute('data-id-product') ||
                element.getAttribute('data-product-id') ||
                element.querySelector('[data-product-id]')?.getAttribute('data-product-id') || '';

            productData.item_unique_id = productData.item_id;

            // Nombre del producto
            const nameSelectors = [
                '.product-line-info .label',
                '.product-name',
                '.item-name',
                'h3', 'h4', '.name'
            ];

            for (const selector of nameSelectors) {
                const nameElement = element.querySelector(selector);
                if (nameElement) {
                    productData.item_name = nameElement.textContent.trim();
                    break;
                }
            }

            // Precio
            const priceSelectors = ['.price', '.product-price', '.item-price', '.amount'];
            for (const selector of priceSelectors) {
                const priceElement = element.querySelector(selector);
                if (priceElement) {
                    const priceText = priceElement.textContent.replace(/[^0-9.,]/g, '').replace(',', '.');
                    productData.price = parseFloat(priceText) || 0;
                    break;
                }
            }

            // Cantidad
            const quantitySelectors = [
                '.js-cart-line-product-quantity',
                '.product-quantity',
                '.quantity input',
                '.qty'
            ];

            for (const selector of quantitySelectors) {
                const quantityElement = element.querySelector(selector);
                if (quantityElement) {
                    productData.quantity = parseInt(quantityElement.value || quantityElement.textContent) || 1;
                    break;
                }
            }

            // Marca (si está disponible)
            const brandElement = element.querySelector('.brand, .product-brand, .manufacturer');
            if (brandElement) {
                productData.item_brand = brandElement.textContent.trim();
            }

            // Categoría (si está disponible)
            const categoryElement = element.querySelector('.category, .product-category');
            if (categoryElement) {
                productData.item_category = categoryElement.textContent.trim();
            }

        } catch (error) {
            this.log(`Error extracting product data: ${error.message}`, 'error');
        }

        return productData;
    }

    /**
     * API pública mejorada
     */
    async executeFromAnywhere(eventType, customData = {}) {
        if (!eventType) {
            throw new Error('Event type is required');
        }

        try {
            // Obtener datos actuales del carrito si no se proporcionan
            const cartData = customData.cartData || this.getCurrentCartDataFromDOM();

            // Datos del cliente
            const customerData = customData.customerData || {
                user_id: window.userId || '',
                user_type: window.userType || 'guest',
                country: window.country || this.config.defaultCountry,
                page_type: document.body.id || 'general',
                timestamp: new Date().toISOString()
            };

            // Actualizar datos en el manager
            await this.updateGTMData({ cartData, customerData });

            // Ejecutar evento
            const result = await window.gtmExecuteWithBackendData(eventType, customData.options || {});

            this.log(`✅ Event ${eventType} executed from anywhere`, 'success');
            return result;

        } catch (error) {
            this.log(`❌ Error executing ${eventType} from anywhere: ${error.message}`, 'error');
            throw error;
        }
    }

    /**
     * Configuración dinámica
     */
    configure(newConfig) {
        this.config = { ...this.config, ...newConfig };
        this.log('⚙️ GTM Usage Helper configuration updated', 'info');
    }

    /**
     * Health check
     */
    async healthCheck() {
        try {
            const dependencies = {
                gtmManager: !!window.gtmManager,
                gtmBackendConnector: !!window.gtmBackendConnector,
                gtmExecuteWithBackendData: !!window.gtmExecuteWithBackendData
            };

            const allHealthy = Object.values(dependencies).every(Boolean);

            return {
                status: allHealthy ? 'healthy' : 'unhealthy',
                dependencies,
                queuedEvents: this.eventQueue.length,
                isInitialized: this.isInitialized
            };

        } catch (error) {
            return {
                status: 'error',
                error: error.message
            };
        }
    }
}

// Inicialización unificada
(function() {
    'use strict';

    console.log('🚀 GTM Unified Manager v2.0: Starting initialization...');

    // Crear instancias globales
    window.gtmBackendConnector = new GTMBackendConnector({
        enableDebug: window.location.hostname === 'localhost' || window.location.search.includes('gtm_debug=1')
    });

    window.gtmUsageHelper = new GTMUsageHelper();

    // Crear GTM Manager básico si no existe
    if (!window.gtmManager) {
        window.gtmManager = {
            data: {},

            updateData: function(newData) {
                this.data = { ...this.data, ...newData };
                console.log('📦 GTM Manager: Data updated', this.data);
            },

            executeEvent: function(eventType, options = {}) {
                console.log(`🎯 GTM Manager: Executing event ${eventType}`, options);

                // Try to push to dataLayer if available
                if (typeof dataLayer !== 'undefined') {
                    dataLayer.push({
                        event: eventType,
                        ...options,
                        ...this.data
                    });
                }

                return { success: true, eventType, options };
            },

            log: function(message, level = 'info') {
                console.log(`[GTM Manager] ${message}`);
            }
        };

        console.log('📦 GTM Manager: Basic instance created');
    }

    // Extensión del GTM Manager
    if (window.gtmManager) {
        // Método mejorado para actualizar datos desde el backend
        window.gtmManager.updateDataFromBackend = async function(options = {}) {
            try {
                const gtmData = await window.gtmBackendConnector.fetchGTMData(options);
                this.updateData(gtmData);
                this.log?.('✅ GTM Manager: Data updated from backend successfully');
                return { success: true, data: gtmData };
            } catch (error) {
                this.log?.(`❌ GTM Manager: Failed to update from backend - ${error.message}`, 'error');
                return { success: false, error: error.message };
            }
        };

        // Método mejorado para ejecutar eventos con datos frescos del backend
        window.gtmManager.executeEventWithBackendData = async function(eventType, options = {}) {
            try {
                this.log?.(`🚀 GTM Manager: Executing ${eventType} with backend data`);

                const eventData = await window.gtmBackendConnector.fetchGTMDataForEvent(eventType, options);

                if (eventData.isError) {
                    this.log?.(`⚠️ GTM Manager: Using fallback data for ${eventType}`, 'warning');
                }

                // Actualizar datos en el manager
                this.updateData(eventData.gtmData);

                // Ejecutar evento con opciones específicas
                const result = this.executeEvent(eventType, eventData.eventOptions);

                this.log?.(`✅ GTM Manager: ${eventType} executed successfully`);
                return { success: true, eventData, result };

            } catch (error) {
                this.log?.(`❌ GTM Manager: Failed to execute ${eventType} - ${error.message}`, 'error');

                // Fallback: ejecutar con datos actuales
                try {
                    const result = this.executeEvent(eventType, options);
                    return { success: false, fallback: true, result, error: error.message };
                } catch (fallbackError) {
                    return { success: false, fallback: false, error: fallbackError.message };
                }
            }
        };
    }

    // Función global mejorada
    window.gtmExecuteWithBackendData = async function(eventType, options = {}) {
        if (!window.gtmManager) {
            console.warn('⚠️ GTM Manager not available');
            return { success: false, error: 'GTM Manager not available' };
        }

        if (window.gtmManager.executeEventWithBackendData) {
            return await window.gtmManager.executeEventWithBackendData(eventType, options);
        } else {
            console.warn('⚠️ GTM Manager: executeEventWithBackendData method not available, using fallback');
            try {
                await window.gtmManager.updateDataFromBackend();
                const result = window.gtmManager.executeEvent(eventType, options);
                return { success: true, fallback: true, result };
            } catch (error) {
                return { success: false, error: error.message };
            }
        }
    };

    // API global simplificada para compatibilidad hacia atrás
    window.gtmExecuteFromAnywhere = async function(eventType, customData = {}) {
        return await window.gtmUsageHelper.executeFromAnywhere(eventType, customData);
    };

    // APIs específicas para eventos comunes
    window.gtmAddToCart = async function(options = {}) {
        return await window.gtmUsageHelper.executeAddToCart(options);
    };

    window.gtmBeginCheckout = async function(options = {}) {
        return await window.gtmUsageHelper.executeBeginCheckout(options);
    };

    window.gtmPurchase = async function(transactionId, options = {}) {
        return await window.gtmUsageHelper.executePurchase(transactionId, options);
    };

    window.gtmExecuteByAction = async function(action, data = {}) {
        return await window.gtmUsageHelper.executeGTMByAction(action, data);
    };

    // Marcar tiempo de inicio de página
    window.pageStartTime = Date.now();

    // Auto-inicialización cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', async function() {
        console.log('🎯 GTM Unified Manager v2.0: Initializing...');

        try {
            // Health check inicial
            const health = await window.gtmBackendConnector.healthCheck();
            console.log('🏥 GTM Backend Health:', health);

            // Cargar datos iniciales
            if (window.gtmManager) {
                const result = await window.gtmManager.updateDataFromBackend();
                if (result.success) {
                    console.log('✅ GTM Unified Manager v2.0: Initial data loaded successfully');
                } else {
                    console.warn('⚠️ GTM Unified Manager v2.0: Initial data load failed, using fallback');
                }
            }

            // Configurar listeners de eventos para debugging
            if (window.gtmBackendConnector.config.enableDebug) {
                window.gtmBackendConnector.eventEmitter.on('dataFetched', (data) => {
                    console.log('📡 GTM Data Fetched:', data);
                });

                window.gtmBackendConnector.eventEmitter.on('fetchError', (data) => {
                    console.error('📡 GTM Fetch Error:', data);
                });
            }

            // Evento inicial de page_view
            setTimeout(async () => {
                try {
                    if (window.gtmUsageHelper.isInitialized) {
                        await window.gtmUsageHelper.executeEventWithData('page_view', {
                            customerData: {
                                page_type: document.body.id || 'general',
                                page_title: document.title,
                                page_url: window.location.href
                            }
                        });
                    }
                } catch (error) {
                    console.warn('Initial page_view tracking failed:', error);
                }
            }, 1000);

        } catch (error) {
            console.error('❌ GTM Unified Manager v2.0: Initialization failed:', error);
        }
    });

    // Exponer funciones de utilidad para debugging
    window.gtmDebug = {
        getMetrics: () => window.gtmBackendConnector.getMetrics(),
        getCacheStatus: () => window.gtmBackendConnector.getCacheStatus(),
        clearCache: () => window.gtmBackendConnector.clearCache(),
        healthCheck: () => window.gtmBackendConnector.healthCheck(),
        resetMetrics: () => window.gtmBackendConnector.resetMetrics(),
        usageHelper: () => window.gtmUsageHelper,
        backendConnector: () => window.gtmBackendConnector
    };

    console.log('🎉 GTM Unified Manager v2.0: Loaded successfully');
})();