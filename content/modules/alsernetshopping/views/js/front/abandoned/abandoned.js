/**
 * Sistema completo de tracking y gestión de carritos abandonados
 * 
 * Funcionalidades:
 * - Tracking avanzado de comportamiento de usuario
 * - Detección de triggers inteligentes
 * - Gestión de modales personalizados
 * - Integración con analytics
 */

(function($) {
    'use strict';

    // Configuración global
    const CONFIG = {
        apiEndpoints: {
            base: window.abandonment_ajax_url || window.prestashop?.urls?.base_url + 'module/alsernetshopping/routes',
            checkSystemStatus: 'check_system_status',
            registerAbandonment: 'register_abandonment', 
            updateBehavior: 'update_behavior',
            getModalConfig: 'get_modal_config',
            registerModalInteraction: 'register_modal_interaction',
            markRecovered: 'mark_recovered',
            applyDiscount: 'apply_discount'
        },
        tracking: {
            enabled: true,
            sessionDuration: 30 * 60 * 1000, // 30 minutos
            inactivityThreshold: 30 * 1000, // 30 segundos
            scrollThreshold: 80, // 80% de scroll
            exitIntentSensitivity: 'medium'
        },
        debug: window.abandonment_debug || false
    };

    /**
     * Clase principal del sistema de carritos abandonados
     */
    class AbandonedCartSystem {
        constructor(options = {}) {
            this.config = $.extend(true, {}, CONFIG, options);
            this.sessionData = {
                startTime: Date.now(),
                lastActivity: Date.now(),
                pageViews: 0,
                productViews: 0,
                categoryViews: 0,
                scrollDepth: 0,
                clickCount: 0,
                searchQueries: [],
                cartInteractions: 0,
                exitIntentTriggered: false,
                mobileInteractions: {
                    touches: 0,
                    swipes: 0,
                    orientationChanges: 0
                }
            };
            
            this.triggers = new TriggerManager(this);
            this.modalManager = new ModalManager(this);
            this.behaviorTracker = new BehaviorTracker(this);
            this.cartTracker = new CartTracker(this);
            
            this.abandonmentId = null;
            this.isActive = false;
            
            this.init();
        }

        init() {
            // Verificar si el sistema está habilitado desde el servidor
            this.checkSystemStatus()
                .then(isActive => {
                    if (!isActive) {
                        this.log('Sistema de carritos abandonados deshabilitado desde configuración');
                        return;
                    }
                    
                    this.initializeSystem();
                })
                .catch(error => {
                    this.log('Error verificando estado del sistema:', error);
                });
        }

        checkSystemStatus() {
            return this.makeApiCall(this.config.apiEndpoints.base, {
                modalitie: 'abandonment',
                action: this.config.apiEndpoints.checkSystemStatus
            }).then(response => {
                // Guardar configuración de triggers para uso posterior
                if (response.trigger_configuration) {
                    this.triggerConfiguration = response.trigger_configuration;
                }
                return response.system_active === true;
            }).catch(() => {
                // Si hay error, asumir que está deshabilitado
                return false;
            });
        }

        initializeSystem() {
            if (!this.config.tracking.enabled) {
                this.log('Sistema de tracking deshabilitado');
                return;
            }

            this.log('Inicializando sistema de carritos abandonados');
            
            // Inicializar componentes
            this.behaviorTracker.init();
            this.cartTracker.init();
            this.triggers.init();
            this.modalManager.init();
            
            // Configurar eventos globales
            this.setupGlobalEvents();
            
            // Verificar carrito existente
            this.checkExistingCart();
            
            // Iniciar tracking de sesión
            this.startSessionTracking();
            
            this.isActive = true;
            this.log('Sistema inicializado correctamente');
        }

        setupGlobalEvents() {
            // Eventos de ventana
            $(window).on('beforeunload.abandonmentSystem', () => {
                this.handlePageUnload();
            });

            $(window).on('focus.abandonmentSystem', () => {
                this.handleWindowFocus();
            });

            $(window).on('blur.abandonmentSystem', () => {
                this.handleWindowBlur();
            });

            // Eventos de actividad
            $(document).on('click.abandonmentSystem', (e) => {
                this.updateActivity();
                this.sessionData.clickCount++;
                this.behaviorTracker.trackClick(e);
            });

            $(document).on('scroll.abandonmentSystem', () => {
                this.updateActivity();
                this.behaviorTracker.trackScroll();
            });

            $(document).on('keydown.abandonmentSystem', () => {
                this.updateActivity();
            });

            // Eventos móviles
            if (this.isMobileDevice()) {
                this.setupMobileEvents();
            }

            // Eventos específicos del carrito
            this.setupCartEvents();
        }

        setupMobileEvents() {
            $(document).on('touchstart.abandonmentSystem', () => {
                this.sessionData.mobileInteractions.touches++;
                this.updateActivity();
            });

            $(document).on('touchmove.abandonmentSystem', (e) => {
                this.behaviorTracker.trackSwipe(e);
            });

            $(window).on('orientationchange.abandonmentSystem', () => {
                this.sessionData.mobileInteractions.orientationChanges++;
            });
        }

        setupCartEvents() {
            // Eventos de modificación del carrito
            $(document).on('click', '.add-to-cart, .btn-add-cart', (e) => {
                this.cartTracker.trackAddToCart(e);
            });

            $(document).on('click', '.remove-from-cart, .btn-remove', (e) => {
                this.cartTracker.trackRemoveFromCart(e);
            });

            $(document).on('change', 'input[name*="qty"], .cart-quantity', (e) => {
                this.cartTracker.trackQuantityChange(e);
            });

            // Eventos de navegación del checkout
            $(document).on('click', '.checkout-step, .step-title', (e) => {
                this.cartTracker.trackCheckoutProgress(e);
            });
        }

        checkExistingCart() {
            const cart = this.cartTracker.getCurrentCart();
            if (cart && cart.products && cart.products.length > 0) {
                this.log('Carrito existente detectado:', cart);
                this.registerAbandonment('cart');
            }
        }

        startSessionTracking() {
            // Actualizar datos cada minuto
            setInterval(() => {
                this.updateSessionData();
                this.sendBehaviorUpdate();
            }, 60000);

            // Verificar inactividad cada 10 segundos
            setInterval(() => {
                this.checkInactivity();
            }, 10000);
        }

        updateActivity() {
            this.sessionData.lastActivity = Date.now();
        }

        updateSessionData() {
            this.sessionData.sessionDuration = Date.now() - this.sessionData.startTime;
        }

        checkInactivity() {
            const inactiveTime = Date.now() - this.sessionData.lastActivity;
            
            if (inactiveTime > this.config.tracking.inactivityThreshold) {
                this.triggers.checkTimeBasedTriggers(inactiveTime);
            }
        }

        registerAbandonment(stage = 'cart') {
            if (this.abandonmentId) {
                this.log('Abandono ya registrado:', this.abandonmentId);
                return Promise.resolve();
            }

            const cart = this.cartTracker.getCurrentCart();
            if (!cart || !cart.products || cart.products.length === 0) {
                this.log('No hay productos en carrito para registrar abandono');
                return Promise.resolve();
            }

            const data = {
                modalitie: 'abandonment',
                action: this.config.apiEndpoints.registerAbandonment,
                stage: stage,
                cart_data: cart,
                behavior_data: this.getBehaviorData(),
                session_data: this.getSessionData()
            };

            return this.makeApiCall(this.config.apiEndpoints.base, data)
                .then(response => {
                    if (response.status === 'success') {
                        this.abandonmentId = response.abandonment_id;
                        this.log('Abandono registrado:', this.abandonmentId);
                        
                        // Configurar triggers disponibles
                        this.triggers.setupTriggers(response.triggers_available);
                        
                        // Emitir evento personalizado
                        $(document).trigger('abandonmentRegistered', {
                            id: this.abandonmentId,
                            stage: stage,
                            userSegment: response.user_segment
                        });
                    }
                    return response;
                })
                .catch(error => {
                    this.log('Error registrando abandono:', error);
                });
        }

        sendBehaviorUpdate() {
            if (!this.abandonmentId) return;

            const data = {
                modalitie: 'abandonment',
                action: this.config.apiEndpoints.updateBehavior,
                abandonment_id: this.abandonmentId,
                behavior_data: this.getBehaviorData(),
                session_data: this.getSessionData()
            };

            this.makeApiCall(this.config.apiEndpoints.base, data)
                .catch(error => {
                    this.log('Error actualizando comportamiento:', error);
                });
        }

        getBehaviorData() {
            return {
                ...this.sessionData,
                currentUrl: window.location.href,
                referrer: document.referrer,
                userAgent: navigator.userAgent,
                screenResolution: `${screen.width}x${screen.height}`,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                language: navigator.language
            };
        }

        getSessionData() {
            return {
                sessionId: this.getSessionId(),
                startTime: new Date(this.sessionData.startTime).toISOString(),
                duration: this.sessionData.sessionDuration || 0,
                pageViews: this.sessionData.pageViews,
                isNewSession: this.isNewSession()
            };
        }

        getSessionId() {
            let sessionId = sessionStorage.getItem('abandonment_session_id');
            if (!sessionId) {
                sessionId = 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                sessionStorage.setItem('abandonment_session_id', sessionId);
            }
            return sessionId;
        }

        isNewSession() {
            return !sessionStorage.getItem('abandonment_session_started');
        }

        showModal(modalType, config = {}) {
            return this.modalManager.showModal(modalType, config);
        }

        handlePageUnload() {
            if (this.abandonmentId) {
                // Enviar datos finales de forma síncrona
                const formData = new FormData();
                formData.append('modalitie', 'abandonment');
                formData.append('action', 'final_update');
                formData.append('abandonment_id', this.abandonmentId);
                formData.append('behavior_data', JSON.stringify(this.getBehaviorData()));
                
                navigator.sendBeacon(this.config.apiEndpoints.base, formData);
            }
        }

        handleWindowFocus() {
            this.updateActivity();
            this.log('Ventana enfocada');
        }

        handleWindowBlur() {
            this.log('Ventana desenfocada');
        }

        isMobileDevice() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }

        makeApiCall(url, data) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: data,
                    dataType: 'json',
                    timeout: 10000
                })
                .done(resolve)
                .fail((xhr, status, error) => {
                    reject(new Error(`API call failed: ${status} - ${error}`));
                });
            });
        }

        log(...args) {
            if (this.config.debug) {
                console.log('[AbandonedCartSystem]', ...args);
            }
        }

        destroy() {
            this.isActive = false;
            
            // Limpiar eventos
            $(window).off('.abandonmentSystem');
            $(document).off('.abandonmentSystem');
            
            // Destruir componentes
            this.behaviorTracker.destroy();
            this.cartTracker.destroy();
            this.triggers.destroy();
            this.modalManager.destroy();
            
            this.log('Sistema destruido');
        }
    }

    /**
     * Gestor de triggers inteligentes
     */
    class TriggerManager {
        constructor(system) {
            this.system = system;
            this.activeTriggers = new Map();
            this.triggerHistory = [];
        }

        init() {
            this.setupExitIntentDetection();
            this.setupScrollTriggers();
        }

        setupTriggers(availableTriggers) {
            this.system.log('Configurando triggers:', availableTriggers);
            
            // Filtrar triggers habilitados desde configuración
            const enabledTriggers = availableTriggers.filter(trigger => {
                return this.isTriggerTypeEnabled(trigger.type);
            });
            
            if (enabledTriggers.length === 0) {
                this.system.log('No hay triggers habilitados en la configuración');
                return;
            }
            
            enabledTriggers.forEach(trigger => {
                this.scheduleTrigger(trigger);
            });
        }

        isTriggerTypeEnabled(triggerType) {
            // Esta información debería venir del servidor
            const triggerConfig = this.system.triggerConfiguration || {};
            
            switch(triggerType) {
                case 'exit_intent':
                    return triggerConfig.exit_intent_enabled !== false;
                case 'time_based':
                    return triggerConfig.time_based_triggers_enabled !== false;
                case 'scroll_based':
                    return triggerConfig.scroll_triggers_enabled !== false;
                case 'behavior_based':
                    return triggerConfig.behavior_triggers_enabled !== false;
                default:
                    return true; // Por defecto habilitado
            }
        }

        scheduleTrigger(triggerConfig) {
            const { type, delay, conditions } = triggerConfig;
            
            if (this.activeTriggers.has(type)) {
                this.system.log('Trigger ya activo:', type);
                return;
            }

            if (delay > 0) {
                // Trigger con delay
                const timeoutId = setTimeout(() => {
                    this.executeTrigger(triggerConfig);
                }, delay * 1000);
                
                this.activeTriggers.set(type, { timeoutId, config: triggerConfig });
            } else {
                // Trigger inmediato
                this.activeTriggers.set(type, { config: triggerConfig });
            }

            this.system.log('Trigger programado:', type, 'delay:', delay);
        }

        setupExitIntentDetection() {
            let lastMouseY = 0;
            let exitIntentTriggered = false;

            $(document).on('mousemove.exitIntent', (e) => {
                const currentMouseY = e.clientY;
                
                if (currentMouseY < 50 && lastMouseY > currentMouseY && !exitIntentTriggered) {
                    const velocity = lastMouseY - currentMouseY;
                    
                    if (velocity > this.getExitIntentThreshold()) {
                        exitIntentTriggered = true;
                        this.system.sessionData.exitIntentTriggered = true;
                        this.triggerExitIntent();
                    }
                }
                
                lastMouseY = currentMouseY;
            });
        }

        getExitIntentThreshold() {
            const sensitivity = this.system.config.tracking.exitIntentSensitivity;
            const thresholds = {
                low: 100,
                medium: 50,
                high: 20
            };
            return thresholds[sensitivity] || 50;
        }

        triggerExitIntent() {
            this.system.log('Exit intent detectado');
            
            if (this.canTrigger('exit_intent')) {
                this.executeTrigger({
                    type: 'exit_intent',
                    conditions: { detected: true }
                });
            }
        }

        setupScrollTriggers() {
            let maxScroll = 0;
            
            $(window).on('scroll.scrollTrigger', () => {
                const scrollTop = $(window).scrollTop();
                const documentHeight = $(document).height() - $(window).height();
                const scrollPercent = (scrollTop / documentHeight) * 100;
                
                if (scrollPercent > maxScroll) {
                    maxScroll = scrollPercent;
                    this.system.sessionData.scrollDepth = scrollPercent;
                    
                    if (scrollPercent >= this.system.config.tracking.scrollThreshold) {
                        this.triggerScrollBasedModal();
                    }
                }
            });
        }

        triggerScrollBasedModal() {
            if (this.canTrigger('scroll_based')) {
                this.system.log('Trigger de scroll activado');
                this.executeTrigger({
                    type: 'scroll_based',
                    conditions: { scrollPercent: this.system.sessionData.scrollDepth }
                });
            }
        }

        checkTimeBasedTriggers(inactiveTime) {
            const activeTrigger = this.activeTriggers.get('time_based');
            
            if (activeTrigger && this.canTrigger('time_based')) {
                this.system.log('Trigger de tiempo activado por inactividad:', inactiveTime);
                this.executeTrigger({
                    type: 'time_based',
                    conditions: { inactiveTime }
                });
            }
        }

        canTrigger(triggerType) {
            // Verificar si el trigger puede ejecutarse
            const recentTriggers = this.triggerHistory.filter(t => 
                t.type === triggerType && 
                Date.now() - t.timestamp < 300000 // 5 minutos
            );
            
            return recentTriggers.length === 0;
        }

        executeTrigger(triggerConfig) {
            this.system.log('Ejecutando trigger:', triggerConfig);
            
            // Registrar en historial
            this.triggerHistory.push({
                type: triggerConfig.type,
                timestamp: Date.now(),
                conditions: triggerConfig.conditions
            });

            // Obtener configuración del modal
            this.getModalConfiguration(triggerConfig)
                .then(modalConfig => {
                    if (modalConfig) {
                        this.system.showModal(modalConfig.type, modalConfig);
                    }
                })
                .catch(error => {
                    this.system.log('Error obteniendo configuración de modal:', error);
                });

            // Limpiar trigger activo
            this.activeTriggers.delete(triggerConfig.type);
        }

        getModalConfiguration(triggerConfig) {
            const data = {
                modalitie: 'abandonment',
                action: this.system.config.apiEndpoints.getModalConfig,
                abandonment_id: this.system.abandonmentId,
                trigger_type: triggerConfig.type,
                trigger_conditions: triggerConfig.conditions
            };

            return this.system.makeApiCall(this.system.config.apiEndpoints.base, data);
        }

        destroy() {
            // Limpiar triggers activos
            this.activeTriggers.forEach(trigger => {
                if (trigger.timeoutId) {
                    clearTimeout(trigger.timeoutId);
                }
            });
            this.activeTriggers.clear();
            
            // Limpiar eventos
            $(document).off('.exitIntent');
            $(window).off('.scrollTrigger');
        }
    }

    /**
     * Gestor de modales
     */
    class ModalManager {
        constructor(system) {
            this.system = system;
            this.currentModal = null;
            this.modalTemplates = new Map();
        }

        init() {
            this.loadModalTemplates();
            this.setupModalEvents();
        }

        loadModalTemplates() {
            // Cargar templates de modales desde el DOM
            const templates = [
                'abandoned-cart-simple-modal',
                'abandoned-cart-discount-modal',
                'abandoned-cart-urgency-modal',
                'abandoned-cart-recommendations-modal',
                'abandoned-cart-recovery-modal'
            ];

            templates.forEach(templateId => {
                const $template = $('#' + templateId);
                if ($template.length) {
                    this.modalTemplates.set(templateId, $template);
                }
            });

            this.system.log('Templates de modal cargados:', this.modalTemplates.size);
        }

        setupModalEvents() {
            // Eventos de cierre de modal
            $(document).on('click', '.modal-close, .modal-overlay', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeCurrentModal('closed');
                }
            });

            // Eventos de acciones de modal
            $(document).on('click', '[data-action]', (e) => {
                e.preventDefault();
                const action = $(e.target).data('action');
                this.handleModalAction(action, e.target);
            });

            // Tecla ESC para cerrar
            $(document).on('keydown', (e) => {
                if (e.keyCode === 27 && this.currentModal) {
                    this.closeCurrentModal('escaped');
                }
            });
        }

        showModal(modalType, config = {}) {
            return new Promise((resolve, reject) => {
                try {
                    // Cerrar modal actual si existe
                    if (this.currentModal) {
                        this.closeCurrentModal('replaced');
                    }

                    // Determinar template de modal
                    const templateId = this.getModalTemplateId(modalType);
                    const $template = this.modalTemplates.get(templateId);

                    if (!$template) {
                        throw new Error(`Template no encontrado para tipo: ${modalType}`);
                    }

                    // Clonar y personalizar modal
                    const $modal = $template.clone();
                    this.personalizeModal($modal, config);

                    // Agregar al DOM y mostrar
                    $('body').append($modal);
                    this.currentModal = {
                        $element: $modal,
                        type: modalType,
                        config: config,
                        startTime: Date.now()
                    };

                    // Animar entrada
                    setTimeout(() => {
                        $modal.addClass('show');
                    }, 50);

                    // Registrar interacción
                    this.registerModalInteraction('shown');

                    this.system.log('Modal mostrado:', modalType);
                    resolve($modal);

                } catch (error) {
                    this.system.log('Error mostrando modal:', error);
                    reject(error);
                }
            });
        }

        getModalTemplateId(modalType) {
            const mappings = {
                'simple_reminder': 'abandoned-cart-simple-modal',
                'discount_offer': 'abandoned-cart-discount-modal',
                'urgency_alert': 'abandoned-cart-urgency-modal',
                'related_products': 'abandoned-cart-recommendations-modal',
                'session_recovery': 'abandoned-cart-recovery-modal'
            };

            return mappings[modalType] || 'abandoned-cart-simple-modal';
        }

        personalizeModal($modal, config) {
            // Personalizar contenido según configuración
            if (config.discount) {
                this.applyDiscountConfiguration($modal, config);
            }

            if (config.urgency) {
                this.applyUrgencyConfiguration($modal, config);
            }

            if (config.personalization) {
                this.applyPersonalization($modal, config.personalization);
            }

            // Cargar datos dinámicos
            this.loadDynamicContent($modal, config);
        }

        applyDiscountConfiguration($modal, config) {
            const discount = config.discount;
            
            $modal.find('#discount-percentage').text(discount);
            $modal.find('#discount-code').val(config.discountCode || `SAVE${discount}NOW`);
            
            if (config.originalPrice && config.discountedPrice) {
                $modal.find('#original-price').text(`€${config.originalPrice}`);
                $modal.find('#discounted-price').text(`€${config.discountedPrice}`);
                $modal.find('#savings-amount').text(`€${config.savings}`);
            }

            // Configurar countdown timer
            this.startCountdownTimer($modal, config.validityTime || 15);
        }

        applyUrgencyConfiguration($modal, config) {
            if (config.lowStockProducts) {
                this.displayLowStockProducts($modal, config.lowStockProducts);
            }

            if (config.viewersCount) {
                $modal.find('#viewers-count').text(config.viewersCount);
            }

            if (config.stockLevel) {
                $modal.find('.stock-level').css('width', `${config.stockLevel}%`);
            }
        }

        applyPersonalization($modal, personalization) {
            const { user_segment, cart_value, products_count, device_type } = personalization;

            // Personalizar mensajes según segmento
            if (user_segment === 'high_value') {
                $modal.find('h3').prepend('<i class="fas fa-crown"></i> ');
            }

            // Actualizar información del carrito
            $modal.find('#modal-products-count').text(products_count);
            $modal.find('#modal-cart-total').text(cart_value.toFixed(2));

            // Adaptaciones para móvil
            if (device_type === 'mobile') {
                $modal.addClass('mobile-optimized');
            }
        }

        loadDynamicContent($modal, config) {
            // Cargar productos del carrito
            const cart = this.system.cartTracker.getCurrentCart();
            if (cart && cart.products) {
                this.displayCartProducts($modal, cart.products);
            }

            // Cargar recomendaciones si es necesario
            if (config.type === 'related_products') {
                this.loadProductRecommendations($modal);
            }
        }

        displayCartProducts($modal, products) {
            const $container = $modal.find('#cart-products-preview');
            if (!$container.length) return;

            const template = $('#product-item-template').html();
            if (!template) return;

            $container.empty();
            products.slice(0, 3).forEach(product => {
                const html = this.renderTemplate(template, product);
                $container.append(html);
            });

            if (products.length > 3) {
                $container.append(`<div class="more-products">+${products.length - 3} productos más</div>`);
            }
        }

        startCountdownTimer($modal, minutes) {
            const $timer = $modal.find('#discount-timer');
            if (!$timer.length) return;

            let totalSeconds = minutes * 60;

            const updateTimer = () => {
                const mins = Math.floor(totalSeconds / 60);
                const secs = totalSeconds % 60;
                
                $timer.find('.minutes').text(mins.toString().padStart(2, '0'));
                $timer.find('.seconds').text(secs.toString().padStart(2, '0'));
                
                if (totalSeconds <= 0) {
                    this.closeCurrentModal('expired');
                    return;
                }
                
                totalSeconds--;
            };

            updateTimer();
            const interval = setInterval(updateTimer, 1000);

            // Guardar referencia para limpieza
            $modal.data('countdown-interval', interval);
        }

        handleModalAction(action, element) {
            const $element = $(element);
            const timeToInteraction = this.currentModal ? Date.now() - this.currentModal.startTime : 0;

            this.system.log('Acción de modal:', action);

            switch (action) {
                case 'complete_purchase':
                case 'apply_discount':
                case 'buy_now':
                    this.handlePurchaseAction(action, timeToInteraction);
                    break;

                case 'continue_shopping':
                case 'decline_offer':
                case 'keep_browsing':
                    this.closeCurrentModal('declined', timeToInteraction);
                    break;

                case 'copy_code':
                    this.copyDiscountCode($element);
                    break;

                case 'view_combo':
                case 'view_offers':
                    this.handleViewAction(action, timeToInteraction);
                    break;

                default:
                    this.system.log('Acción no reconocida:', action);
            }
        }

        handlePurchaseAction(action, timeToInteraction) {
            // Registrar conversión
            this.registerModalInteraction('converted', {
                conversion_value: this.system.cartTracker.getCurrentCart()?.total || 0,
                time_to_interaction: timeToInteraction
            });

            // Marcar carrito como recuperado
            this.system.makeApiCall(this.system.config.apiEndpoints.base, {
                modalitie: 'abandonment',
                action: this.system.config.apiEndpoints.markRecovered,
                abandonment_id: this.system.abandonmentId,
                recovery_method: 'modal_interaction'
            });

            // Redirigir al checkout
            this.redirectToCheckout();
            
            this.closeCurrentModal('converted');
        }

        copyDiscountCode($element) {
            const code = $('#discount-code').val();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code).then(() => {
                    this.showCopyFeedback($element);
                });
            } else {
                // Fallback para navegadores antiguos
                $('#discount-code').select();
                document.execCommand('copy');
                this.showCopyFeedback($element);
            }
        }

        showCopyFeedback($element) {
            const originalText = $element.html();
            $element.html('<i class="fas fa-check"></i>').addClass('copied');
            
            setTimeout(() => {
                $element.html(originalText).removeClass('copied');
            }, 2000);
        }

        redirectToCheckout() {
            const checkoutUrl = window.checkout_url || '/pedido';
            window.location.href = checkoutUrl;
        }

        closeCurrentModal(reason = 'unknown', timeToInteraction = null) {
            if (!this.currentModal) return;

            const $modal = this.currentModal.$element;
            
            // Limpiar intervalos
            const countdownInterval = $modal.data('countdown-interval');
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }

            // Registrar interacción si no es conversión
            if (reason !== 'converted') {
                this.registerModalInteraction(reason, { time_to_interaction: timeToInteraction });
            }

            // Animar salida
            $modal.removeClass('show');
            
            setTimeout(() => {
                $modal.remove();
                this.currentModal = null;
            }, 300);

            this.system.log('Modal cerrado:', reason);
        }

        registerModalInteraction(interactionType, data = {}) {
            if (!this.currentModal || !this.system.abandonmentId) return;

            const interactionData = {
                modalitie: 'abandonment',
                action: this.system.config.apiEndpoints.registerModalInteraction,
                abandonment_id: this.system.abandonmentId,
                modal_type: this.currentModal.type,
                interaction_type: interactionType,
                trigger: 'javascript',
                variant: this.currentModal.config.variant || 'default',
                discount: this.currentModal.config.discount || null,
                data: data
            };

            this.system.makeApiCall(this.system.config.apiEndpoints.base, interactionData)
                .catch(error => {
                    this.system.log('Error registrando interacción:', error);
                });
        }

        renderTemplate(template, data) {
            let html = template;
            
            // Reemplazar variables simples
            Object.keys(data).forEach(key => {
                const regex = new RegExp(`{{${key}}}`, 'g');
                html = html.replace(regex, data[key] || '');
            });

            // Procesar condicionales básicos
            html = html.replace(/{{#if\s+(\w+)}}(.*?){{\/if}}/gs, (match, condition, content) => {
                return data[condition] ? content : '';
            });

            return html;
        }

        destroy() {
            this.closeCurrentModal('destroyed');
            $(document).off('.modalManager');
        }
    }

    /**
     * Tracker de comportamiento
     */
    class BehaviorTracker {
        constructor(system) {
            this.system = system;
            this.trackingData = {
                pageStartTime: Date.now(),
                interactions: [],
                scrollEvents: [],
                clickHeatmap: []
            };
        }

        init() {
            this.trackPageView();
            this.detectPageType();
        }

        trackPageView() {
            this.system.sessionData.pageViews++;
            this.trackingData.pageStartTime = Date.now();
            
            // Detectar tipo de página
            const pageType = this.detectPageType();
            
            if (pageType === 'product') {
                this.system.sessionData.productViews++;
            } else if (pageType === 'category') {
                this.system.sessionData.categoryViews++;
            }
        }

        detectPageType() {
            const path = window.location.pathname;
            const body = document.body;
            
            if (body.classList.contains('product') || path.includes('/product/')) {
                return 'product';
            } else if (body.classList.contains('category') || path.includes('/category/')) {
                return 'category';
            } else if (body.classList.contains('cart') || path.includes('/cart')) {
                return 'cart';
            } else if (body.classList.contains('order') || path.includes('/order')) {
                return 'checkout';
            }
            
            return 'other';
        }

        trackClick(event) {
            const clickData = {
                timestamp: Date.now(),
                x: event.clientX,
                y: event.clientY,
                element: event.target.tagName,
                className: event.target.className,
                id: event.target.id
            };
            
            this.trackingData.clickHeatmap.push(clickData);
            this.trackingData.interactions.push({
                type: 'click',
                ...clickData
            });
        }

        trackScroll() {
            const scrollTop = $(window).scrollTop();
            const documentHeight = $(document).height() - $(window).height();
            const scrollPercent = Math.round((scrollTop / documentHeight) * 100);
            
            if (scrollPercent > this.system.sessionData.scrollDepth) {
                this.system.sessionData.scrollDepth = scrollPercent;
            }
            
            this.trackingData.scrollEvents.push({
                timestamp: Date.now(),
                scrollTop: scrollTop,
                scrollPercent: scrollPercent
            });
        }

        trackSwipe(event) {
            // Tracking básico de swipes para móvil
            this.system.sessionData.mobileInteractions.swipes++;
        }

        destroy() {
            // Limpiar si es necesario
        }
    }

    /**
     * Tracker de carrito
     */
    class CartTracker {
        constructor(system) {
            this.system = system;
            this.lastCartState = null;
            this.cartInteractions = [];
        }

        init() {
            this.updateCartState();
            this.setupCartMonitoring();
        }

        setupCartMonitoring() {
            // Monitorear cambios en el carrito cada 30 segundos
            setInterval(() => {
                this.checkCartChanges();
            }, 30000);
        }

        updateCartState() {
            const cart = this.getCurrentCart();
            if (cart && (!this.lastCartState || this.hasCartChanged(cart))) {
                this.lastCartState = { ...cart };
                this.system.log('Estado del carrito actualizado:', cart);
                
                // Si hay productos y no hay abandono registrado, registrar
                if (cart.products && cart.products.length > 0 && !this.system.abandonmentId) {
                    this.system.registerAbandonment('cart');
                }
            }
        }

        getCurrentCart() {
            // Integración con el sistema de carrito de PrestaShop
            if (window.prestashop && window.prestashop.cart) {
                return {
                    id: window.prestashop.cart.id_cart,
                    products: window.prestashop.cart.products || [],
                    total: window.prestashop.cart.totals?.total?.amount || 0,
                    products_count: window.prestashop.cart.products_count || 0
                };
            }
            
            // Fallback: buscar en el DOM
            return this.extractCartFromDOM();
        }

        extractCartFromDOM() {
            const cart = {
                id: null,
                products: [],
                total: 0,
                products_count: 0
            };

            // Buscar productos en el carrito desde el DOM
            $('.cart-item, .product-line-grid').each((index, element) => {
                const $element = $(element);
                const product = {
                    id: $element.data('id-product'),
                    name: $element.find('.product-name').text().trim(),
                    price: this.extractPrice($element.find('.price')),
                    quantity: this.extractQuantity($element.find('input[name*="qty"]')),
                    image: $element.find('img').attr('src')
                };
                
                if (product.id) {
                    cart.products.push(product);
                }
            });

            // Buscar total
            const totalText = $('.cart-total, .total-value').last().text();
            cart.total = this.extractPrice(totalText);
            cart.products_count = cart.products.length;

            return cart;
        }

        extractPrice(element) {
            const text = typeof element === 'string' ? element : $(element).text();
            const match = text.match(/[\d.,]+/);
            return match ? parseFloat(match[0].replace(',', '.')) : 0;
        }

        extractQuantity(element) {
            return parseInt($(element).val()) || 1;
        }

        hasCartChanged(newCart) {
            if (!this.lastCartState) return true;
            
            return JSON.stringify(newCart) !== JSON.stringify(this.lastCartState);
        }

        checkCartChanges() {
            this.updateCartState();
        }

        trackAddToCart(event) {
            this.system.sessionData.cartInteractions++;
            this.cartInteractions.push({
                type: 'add_to_cart',
                timestamp: Date.now(),
                productId: $(event.target).data('id-product')
            });
            
            // Actualizar estado después de un breve delay
            setTimeout(() => {
                this.updateCartState();
            }, 1000);
        }

        trackRemoveFromCart(event) {
            this.system.sessionData.cartInteractions++;
            this.cartInteractions.push({
                type: 'remove_from_cart',
                timestamp: Date.now(),
                productId: $(event.target).data('id-product')
            });
            
            setTimeout(() => {
                this.updateCartState();
            }, 1000);
        }

        trackQuantityChange(event) {
            this.system.sessionData.cartInteractions++;
            this.cartInteractions.push({
                type: 'quantity_change',
                timestamp: Date.now(),
                newQuantity: $(event.target).val()
            });
        }

        trackCheckoutProgress(event) {
            const step = $(event.target).data('step') || 'unknown';
            this.cartInteractions.push({
                type: 'checkout_step',
                timestamp: Date.now(),
                step: step
            });
            
            // Registrar progreso en checkout
            if (step && this.system.abandonmentId) {
                this.system.registerAbandonment(step);
            }
        }

        destroy() {
            // Limpiar intervalos si es necesario
        }
    }

    // Inicialización automática cuando el DOM esté listo
    $(document).ready(function() {
        // Solo inicializar si no está ya inicializado
        if (!window.abandonedCartSystem) {
            window.abandonedCartSystem = new AbandonedCartSystem();
            
            // Exponer para debugging
            if (CONFIG.debug) {
                window.debugAbandonmentSystem = window.abandonedCartSystem;
            }
        }
    });

    // Limpiar al salir de la página
    $(window).on('beforeunload', function() {
        if (window.abandonedCartSystem) {
            window.abandonedCartSystem.destroy();
        }
    });

})(jQuery);