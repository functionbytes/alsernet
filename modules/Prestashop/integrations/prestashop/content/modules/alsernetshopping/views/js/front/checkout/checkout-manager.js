if (typeof window.CheckoutManager === 'undefined') {

    class CheckoutManager {
        // Error types constants
        static get ERROR_TYPES() {
            return window.CHECKOUT_ERROR_TYPES || {
                NO_ADDRESSES_REGISTERED: 'no_addresses_registered',
                MISSING_INVOICE_ADDRESS: 'missing_invoice_address',
                MISSING_VAT: 'missing_vat',
                BLOCKED_PRODUCTS: 'blocked',
                PRODUCT_AVAILABILITY: 'product_availability',
                MISSING_DELIVERY_ADDRESS: 'missing_delivery_address'
            };
        }

        constructor() {
            this.config = {
                debounceDelay: 250,
                maxRetries: 3,
                retryDelay: 500,
                timeout: 10000,
                cacheTimeout: 30000,
                optimisticUpdates: true,
                validationThrottle: 1000
            };

            this.state = {
                activeRequests: new Map(),
                updateInProgress: new Set(),
                debounceTimers: new Map(),
                cache: new Map(),
                retryAttempts: new Map(),
                currentStep: 'login',
                validationCache: new Map(),
                optimisticState: new Map(),
                initialized: false,
                validationStatus: { lastResult: null, inFlight: null }, // 'success' | 'error' | null
                suppressValidationModal: false, // Flag to prevent showing any validation modal temporarily
                navigation: {
                    navigating: false,
                    pendingTransition: null,
                    previousStep: null
                },
                productCountValidation: {
                    changeDetected: false,
                    redirecting: false,
                    lastKnownCount: null
                }
            };

            this.steps = ['login', 'address', 'delivery', 'payment'];
            this.endpoints = this.getEndpoints();
            this.modalContext = null;

            // Cart debounce configuration
            this.cartConfig = {
                debounceMs: 600, // tiempo mínimo sin clics para disparar el pipeline
                postCartTimers: new Map(),
                pendingUpdates: new Map() // Acumula cambios de cantidad antes de enviar
            };

            // Global authentication state - updated from validations response
            this.isUserAuthenticated = null; // null = unknown, true = authenticated, false = not authenticated

            // GTM tracking flags (in-memory, resets on page reload)
            this.beginCheckoutTracked = false; // true once trackBeginCheckout is executed

            this.init();
        }

        init() {
            this.bindEvents();
            this.setupGlobalErrorHandler();
            this.initModalContext();
            this.state.initialized = true;

            // Start product count validation
            this.startProductCountValidation();

            // Cleanup on page unload
            window.addEventListener('beforeunload', () => {
                this.stopProductCountValidation();
            });
        }

        async initializeCheckout() {
            if (!this.state.initialized) return;
            try {
                console.log('🚀 Starting initial checkout validation - showing loading');

                if (window.checkoutNavigator && typeof window.checkoutNavigator.loadCheckoutSummary === 'function') {
                    window.checkoutNavigator.loadCheckoutSummary();
                }

                // Show loading during initial prevalidation
                this.manageCheckoutVisibility(false);

                // Suprime modales durante la primera validación si quieres (opcional)
                this.state.suppressValidationModal = true;
                await this.validate({ force: true, autoNavigate: true }); // ejecuta validación inicial con auto-navegación

                console.log('✅ Initial validation completed - content should be visible');

                // Track begin_checkout if user is authenticated (only once per page load)
                if (this.isUserAuthenticated === true && !this.beginCheckoutTracked) {
                    console.log('🧾 User is authenticated - tracking begin_checkout (first time this page load)');
                    if (window.GTMCheckoutHelper && typeof window.GTMCheckoutHelper.trackBeginCheckout === 'function') {
                        try {
                            await window.GTMCheckoutHelper.trackBeginCheckout();
                            this.beginCheckoutTracked = true;
                            console.log('✅ begin_checkout tracked successfully');
                        } catch (error) {
                            console.warn('⚠️ Error tracking begin_checkout:', error);
                        }
                    } else {
                        console.warn('⚠️ GTMCheckoutHelper.trackBeginCheckout not available');
                    }
                } else if (this.beginCheckoutTracked) {
                    console.log('ℹ️ begin_checkout already tracked in this page load, skipping');
                }
            } catch (error) {
                console.warn('⚠️ Initial validation reported issues (expected on fresh checkout):', error?.message || error);
                // Show content even on validation error to avoid stuck loading
                this.manageCheckoutVisibility(true);
                // No re-lances: ya se manejó UI/estado en handleValidationErrors()
            } finally {
                this.state.suppressValidationModal = false;
            }
            return true;
        }

        /**
         * Get the current authentication status
         * @returns {boolean|null} - true if authenticated, false if not authenticated, null if unknown
         */
        isAuthenticated() {
            return this.isUserAuthenticated;
        }

        /**
         * Update authentication status
         * @param {boolean} status - Authentication status from validations response
         */
        updateAuthenticationStatus(status) {
            const previousStatus = this.isUserAuthenticated;
            this.isUserAuthenticated = status;

            console.log(`🔐 Authentication status updated: ${previousStatus} → ${status}`);

            // Trigger custom event for authentication status change
            if (previousStatus !== status) {
                $(document).trigger('checkout:authenticationChanged', {
                    previous: previousStatus,
                    current: status,
                    isAuthenticated: status
                });
            }
        }

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

        getEndpoints() {
            const segments = window.location.pathname.split('/');
            const iso = (segments[1] && segments[1].length === 2) ? segments[1] : 'es';
            const prefix = (iso.toLowerCase() !== 'es') ? `/${iso}` : '';
            const baseUrl = `${prefix}/modules/alsernetshopping/routes`;

            return {
                baseUrl,
                iso,
                checkout: {
                    // Main actions
                    load: `${baseUrl}?modalitie=checkout&action=load&iso=${iso}`,
                    validations: `${baseUrl}?modalitie=checkout&action=validations&iso=${iso}`,

                    // Steps
                    steplogin: `${baseUrl}?modalitie=checkout&action=steplogin&iso=${iso}`,
                    stepaddress: `${baseUrl}?modalitie=checkout&action=stepaddress&iso=${iso}`,
                    stepdelivery: `${baseUrl}?modalitie=checkout&action=stepdelivery&iso=${iso}`,
                    steppayment: `${baseUrl}?modalitie=checkout&action=steppayment&iso=${iso}`,
                    stepsummary: `${baseUrl}?modalitie=checkout&action=stepsummary&iso=${iso}`, // ✅ NUEVO

                    // Auth (migrated from modalitie auth)
                    auth: `${baseUrl}?modalitie=checkout&action=auth&iso=${iso}`,
                    authlogin: `${baseUrl}?modalitie=checkout&action=authlogin&iso=${iso}`,
                    authregister: `${baseUrl}?modalitie=checkout&action=authregister&iso=${iso}`,
                    authvalidateemail: `${baseUrl}?modalitie=checkout&action=validateemail&iso=${iso}`,
                    // Addresses (consolidated from modalitie address)
                    addaddress: `${baseUrl}?modalitie=checkout&action=addaddress&iso=${iso}`,
                    editaddress: `${baseUrl}?modalitie=checkout&action=editaddress&iso=${iso}`,
                    deleteaddress: `${baseUrl}?modalitie=checkout&action=deleteaddress&iso=${iso}`,
                    setaddress: `${baseUrl}?modalitie=checkout&action=setaddress&iso=${iso}`,
                    getaddress: `${baseUrl}?modalitie=checkout&action=getaddress&iso=${iso}`,
                    setneeinvoice: `${baseUrl}?modalitie=checkout&action=setneeinvoice&iso=${iso}`,
                    getaddaddressfields: `${baseUrl}?modalitie=checkout&action=getaddaddressfields&iso=${iso}`,
                    getaddressfields: `${baseUrl}?modalitie=checkout&action=getaddressfields&iso=${iso}`,
                    getstates: `${baseUrl}?modalitie=checkout&action=getstates&iso=${iso}`,
                    validatepostcode: `${baseUrl}?modalitie=checkout&action=validatepostcode&iso=${iso}`,
                    getaddressdelivery: `${baseUrl}?modalitie=checkout&action=getaddressdelivery&iso=${iso}`,
                    getaddressinvoice: `${baseUrl}?modalitie=checkout&action=getaddressinvoice&iso=${iso}`,

                    // Delivery
                    setdelivery: `${baseUrl}?modalitie=checkout&action=setdelivery&iso=${iso}`,
                    getdelivery: `${baseUrl}?modalitie=checkout&action=getdelivery&iso=${iso}`,
                    selectdelivery: `${baseUrl}?modalitie=checkout&action=selectdelivery&iso=${iso}`,

                    // Payment
                    setpayment: `${baseUrl}?modalitie=checkout&action=setpayment&iso=${iso}`,
                    steppaymentss: `${baseUrl}?modalitie=checkout&action=steppaymentss&iso=${iso}`,

                    // Product ops (usar cart delete ya que checkout no tiene deleteproduct)
                    deleteproduct: `${baseUrl}?modalitie=cart&action=delete&iso=${iso}`
                }
            };
        }

        async makeRequest(url, options = {}) {
            const requestKey = this.generateRequestKey(url, options.data);

            // Check cache first
            if (options.useCache && this.isCacheValid(requestKey)) {
                return this.state.cache.get(requestKey).data;
            }

            if (this.state.activeRequests.has(requestKey)) {
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

                // Cache successful responses
                if (options.useCache) {
                    this.state.cache.set(requestKey, {
                        data,
                        timestamp: Date.now()
                    });
                }

                return data;

            } catch (error) {
                clearTimeout(timeoutId);
                this.state.activeRequests.delete(requestKey);

                if (error.name === 'AbortError') {
                    throw new Error('Request cancelled');
                }

                // Auto-retry logic
                if (options.autoRetry !== false) {
                    return this.handleRetry(url, options, requestKey, error);
                }

                throw error;
            }
        }

        async handleRetry(url, options, requestKey, originalError) {
            const retryCount = this.state.retryAttempts.get(requestKey) || 0;

            if (retryCount < this.config.maxRetries) {
                this.state.retryAttempts.set(requestKey, retryCount + 1);

                const delay = this.config.retryDelay * Math.pow(2, retryCount); // Exponential backoff
                await this.sleep(delay);

                console.warn(`Retrying request (attempt ${retryCount + 1}/${this.config.maxRetries}):`, url);
                return this.makeRequest(url, options);
            } else {
                this.state.retryAttempts.delete(requestKey);
                throw originalError;
            }
        }

        generateRequestKey(url, data = {}) {
            return `${url}-${JSON.stringify(data)}`;
        }

        isCacheValid(requestKey) {
            const cached = this.state.cache.get(requestKey);
            return cached && (Date.now() - cached.timestamp) < this.config.cacheTimeout;
        }

        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        /**
         * Loads the entire checkout process
         * @returns {Promise<Object>} - The server response
         */
        async loadCheckout() {
            try {
                // Evitar validar de nuevo si no venimos de error
                const requests = [
                    this.makeRequest(this.endpoints.checkout.load, { useCache: true }),
                    (this.state.validationStatus.lastResult === 'error'
                        ? this.throttledValidation()
                        : Promise.resolve())
                ];

                const [checkoutData] = await Promise.allSettled(requests);

                if (checkoutData.status === 'fulfilled' && checkoutData.value.status === 'success') {
                    this.updateCheckoutDisplay(checkoutData.value);
                    return checkoutData.value;
                } else {
                    throw new Error('Failed to load checkout');
                }

            } catch (error) {
                console.error('Error loading checkout:', error);
                throw error;
            }
        }


        /**
         * Manages visibility between checkout loading and content containers
         * @param {boolean} showContent - true to show content, false to show loading
         * @param {Object} options - Additional options
         */
        manageCheckoutVisibility(showContent = true, options = {}) {
            console.log(`🎭 CheckoutManager managing visibility: ${showContent ? 'CONTENT' : 'LOADING'}`);

            if (showContent) {
                // Show main checkout content, hide loading
                $('.checkout-container').removeClass('d-none');
                $('.checkout-container-process').addClass('d-none');
                $('.checkout-empty-container').addClass('d-none');

                console.log('✅ Checkout content visible, loading hidden');
            } else {
                // Show loading, hide content
                $('.checkout-container').addClass('d-none');
                $('.checkout-container-process').removeClass('d-none');
                $('.checkout-empty-container').addClass('d-none');

                console.log('⏳ Loading visible, checkout content hidden');
            }

            // Handle empty cart state
            if (options.isEmpty) {
                $('.checkout-container').addClass('d-none');
                $('.checkout-container-process').addClass('d-none');
                $('.checkout-empty-container').removeClass('d-none');
                console.log('🛒 Empty cart state displayed');
            }
        }

        updateCheckoutDisplay(response) {
            // Show checkout content using centralized visibility management
            this.manageCheckoutVisibility(true);

            // Update sections with provided data
            if (response.products) {
                $('.container-inventaries').html(response.products);
            }
            if (response.summary) {
                $('.container-summary').html(response.summary);
            }
            if (response.shipping) {
                $('.container-shipping').html(response.shipping);

                // Preserve and restore hooks content
                this.preserveHooksContent();

                // Reinitialize MondialRelay after shipping section update
                if (window.deliveryStepHandler?.reinitializeMondialRelay) {
                    window.deliveryStepHandler.reinitializeMondialRelay();
                }
            }
        }

        // Fallback method for updating cart totals
        fallbackCartUpdate() {
            try {
                console.log('🔄 Executing fallback cart update');

                // Update cart dropdown if available
                if (window.cart && typeof window.cart.loadCart === 'function') {
                    console.log('📦 Updating cart dropdown via window.cart.loadCart');
                    window.cart.loadCart();
                }

                // Update cart header totals if available
                if (window.settings && typeof window.settings.updateCartHeader === 'function') {
                    console.log('📦 Updating cart header via window.backups.updateCartHeader');
                    window.settings.updateCartHeader();
                }

                // Force refresh of cart information
                if (window.checkoutNavigator && typeof window.checkoutNavigator.refreshCartInfo === 'function') {
                    console.log('📦 Refreshing cart info via checkoutNavigator');
                    window.checkoutNavigator.refreshCartInfo();
                }

                // Update any visible cart summary elements
                this.updateVisibleCartElements();

                console.log('✅ Fallback cart update completed');

            } catch (error) {
                console.error('Fallback cart update failed:', error);
            }
        }

        // Update visible cart elements on the page
        updateVisibleCartElements() {
            try {
                console.log('🔄 Updating visible cart elements');

                // Update cart product count in summary if visible
                const $cartSummary = $('.cart-summary, .checkout-summary');
                if ($cartSummary.length) {
                    console.log('📦 Found cart summary elements to refresh');

                    // Trigger a manual update of visible product count
                    const visibleProducts = $('.product-item:visible').length;
                    console.log('📊 Visible inventaries count:', visibleProducts);

                    // Update any counter elements
                    $('.cart-inventaries-count, .cart-counter').each(function() {
                        const $counter = $(this);
                        console.log('📊 Updating counter element:', $counter[0]);
                    });
                }

                // Update any price elements that might be stale
                if (window.checkoutNavigator && typeof window.checkoutNavigator.refreshPriceElements === 'function') {
                    window.checkoutNavigator.refreshPriceElements();
                }

                console.log('✅ Visible cart elements updated');

            } catch (error) {
                console.error('❌ Error updating visible cart elements:', error);
            }
        }

        // Update cart totals in various places (header, mini-cart, etc.)
        updateCartTotals(response) {
            try {
                // Update cart counter in header if available
                if (response.cart_quantity !== undefined) {
                    $('.cart-inventaries-count').text(response.cart_quantity);
                }

                // Update cart total if available
                if (response.cart_total !== undefined) {
                    $('.cart-total-amount, .cart-summary-total').text(response.cart_total);
                }
            } catch (error) {
                console.error('Error updating cart totals:', error);
            }
        }

        // Enhanced error handling with Strategy Pattern
        handleValidationErrors(errorData) {
            console.log('🎯 HandleValidationErrors called with:', errorData);
            console.log('📋 Error type:', errorData?.type);
            console.log('📋 Error hasError:', errorData?.hasError);
            console.log('📋 Available properties:', Object.keys(errorData || {}));

            // ⚠️ MARK: Backend validation has processed errors
            this.state.validationStatus.lastResult = 'error';
            console.log('🔧 Marked backend validation as processed to prevent frontend duplicates');

            // Special case for no_addresses_registered - use dynamic modal from server
            if (errorData?.type === CheckoutManager.ERROR_TYPES.NO_ADDRESSES_REGISTERED) {
                console.log('🎯 Handling no_addresses_registered with dynamic modal');
                console.log('📋 modal_html length:', errorData.modal_html ? errorData.modal_html.length : 'undefined');
                console.log('📋 modal_html preview:', errorData.modal_html ? errorData.modal_html.substring(0, 200) + '...' : 'none');

                if (errorData.modal_html && errorData.modal_html.trim() !== '') {
                    console.log('✅ Dynamic modal HTML received from server');

                    // Clean any existing modals first
                    console.log('🧹 Cleaning existing modals');
                    $('.modal').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                    $('#no-addresses-modal').remove(); // Remove any existing modal

                    // Add the modal to DOM
                    console.log('📦 Inserting modal HTML into DOM');
                    $('body').append(errorData.modal_html);

                    // Verify insertion
                    const $insertedModal = $('#no-addresses-modal');
                    console.log('🔍 Modal inserted successfully:', $insertedModal.length > 0);

                    // Show the modal
                    setTimeout(() => {
                        const $modal = $('#no-addresses-modal');
                        if ($modal.length) {
                            console.log('🎯 Showing modal...');
                            $modal.modal('show');
                            console.log('✅ No addresses modal displayed');
                        } else {
                            console.error('❌ Dynamic modal #no-addresses-modal not found after insertion');
                            console.log('📋 All modals in DOM:', $('.modal').map(function() { return '#' + this.id; }).get());
                        }
                    }, 100);
                } else {
                    console.error('❌ No modal HTML received from server for no_addresses_registered');
                    console.log('📋 modal_html content:', errorData.modal_html);
                }
                return; // Exit early for this case
            }

            // Force show modals for validation errors, ignore suppression flags
            console.log('🔧 Forcing modal display for validation errors');

            if (!this.modalContext && typeof window.ModalStrategyContext !== 'undefined') {
                console.log('🏗️ Creating new ModalStrategyContext');
                this.modalContext = new ModalStrategyContext();
            } else if (!window.ModalStrategyContext) {
                console.error('❌ ModalStrategyContext not available in window');
            }

            if (this.modalContext && this.modalContext.supportsErrorType(errorData.type)) {
                console.log('✅ Using ModalStrategyContext for error type:', errorData.type);
                this.modalContext.handleModal(errorData);
            } else {
                console.warn('⚠️ ModalStrategyContext unavailable or unsupported error type:', errorData.type);
                console.log('📋 modalContext exists:', !!this.modalContext);
                console.log('📋 modalContext.supportsErrorType exists:', !!this.modalContext?.supportsErrorType);
                console.log('🔄 Using legacy error handling');
                this.handleLegacyError(errorData);
            }
        }

        handleLegacyError(errorData) {
            console.log('🔧 Legacy error handler called with:', errorData);
            console.log('📋 Has modal_html:', !!errorData?.modal_html);
            console.log('📋 Error type:', errorData?.type);

            // Caso especial: no_addresses_registered - usar HTML dinámico del servidor o ModalStrategies
            if (errorData?.type === 'no_addresses_registered') {
                console.log('🎯 Handling no_addresses_registered - checking for server HTML or ModalStrategies');

                // Prioridad 1: Usar ModalStrategies si está disponible
                if (window.addressStepHandler?.modalStrategies) {
                    console.log('✅ Using ModalStrategies for no_addresses_registered');
                    window.addressStepHandler.modalStrategies.showNoAddressesModal();
                    return;
                }

                // Prioridad 2: Usar HTML dinámico del servidor
                if (errorData.modal_html) {
                    console.log('✅ Using server-provided modal HTML for no_addresses_registered');
                    $('body').append(errorData.modal_html);
                    $('#no-addresses-modal').modal('show');
                    return;
                }

                // Prioridad 3: Buscar modal estático
                const $modal = $('#no-addresses-modal');
                if ($modal.length) {
                    console.log('✅ Found static no-addresses modal, showing it');
                    $modal.modal('show');
                } else {
                    console.error('❌ No modal available for no_addresses_registered');
                    console.log('📋 Available modals:', $('.modal').map(function() { return '#' + this.id; }).get());

                    // Fallback final: crear modal dinámico básico
                    const modalHtml = `
                        <div id="dynamic-no-addresses-modal" class="modal fade" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title">No hay direcciones registradas</h4>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Para continuar con tu compra necesitas registrar al menos una dirección.</p>
                                        <button type="button" class="btn btn-primary add-new-address" data-type="delivery">
                                            Agregar Dirección
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $('body').append(modalHtml);
                    $('#dynamic-no-addresses-modal').modal('show');
                }
                return;
            }

            if (errorData?.modal_html) {
                console.log('✅ Showing legacy modal with HTML');
                $('body').append(errorData.modal_html);
                $('.modal').last().modal('show');
            } else {
                console.warn('⚠️ No modal_html found in errorData, trying fallback toast');

                // Fallback: show toast notification
                const message = errorData?.message || 'Ha ocurrido un error de validación';
                const title = errorData?.title || 'Error de Validación';

                this.showToast('error', message, title);

                // Also log the full error for debugging
                console.error('❌ Full error data for debugging:', errorData);
            }
        }

        initModalContext() {
            if (typeof window.ModalStrategyContext !== 'undefined') {
                this.modalContext = new ModalStrategyContext();
            } else {
                console.warn('ModalStrategyContext not available. Check that modal-strategies.js loads first.');
            }
        }

        setupGlobalErrorHandler() {
            window.addEventListener('error', this.handleGlobalError.bind(this));
            window.addEventListener('unhandledrejection', this.handleUnhandledRejection.bind(this));
        }

        handleGlobalError(event) {
            console.error('Global error caught:', event.error);
            // Implement global error handling if needed
        }

        handleUnhandledRejection(event) {
            console.error('Unhandled promise rejection:', event.reason);
            // Implement unhandled rejection handling if needed
        }

        // Core event binding
        bindEvents() {
            console.log('🔗 Binding CheckoutManager core events...');

            function getLineKey($item) {
                const pid  = $item.data('id-product') || $item.attr('data-id-product') || 'p0';
                const pa   = $item.data('id-product-attribute') || $item.attr('data-id-product-attribute') || 'a0';
                const cust = $item.data('id-customization') || $item.attr('data-id-customization') || 'c0';
                return `${pid}-${pa}-${cust}`;
            }

            if (window.checkoutManager && typeof window.checkoutManager.showToast !== 'function') {
                window.checkoutManager.showToast = this.showToast.bind(window.checkoutManager);
            }

            // === Eventos dentro del resumen de checkout (pagina order/checkout) ===
            // Limpia solo los que te interesan
            $(document)
                .off('click.cart-summary', '.page-order .right-sidebar-checkout .cart-summary .summary .cart-inventaries-lists .product-item .quantity-body .le-quantity .plus')
                .off('click.cart-summary', '.page-order .right-sidebar-checkout .cart-summary .summary .cart-inventaries-lists .product-item .quantity-body .le-quantity .minus')
                .off('click.cart-summary', '.page-order .right-sidebar-checkout .cart-summary .summary .cart-inventaries-lists .product-item .delete-to-product');

            // PLUS
            $(document).on(
                'click.cart-summary',
                '.page-order .right-sidebar-checkout .cart-summary .summary .cart-inventaries-lists .product-item .quantity-body .le-quantity .plus',
                async (e) => {
                    e.preventDefault();
                    const $btn   = $(e.currentTarget).prop('disabled', true).addClass('loading');
                    const $item  = $btn.closest('.product-item');
                    const $input = $item.find('.cart-product-quantity');
                    const key    = getLineKey($item);

                    try {
                        // Actualizar cantidad visual inmediatamente
                        const currentQty = parseInt($input.val(), 10) || 1;
                        const newQty = currentQty + 1;
                        $input.val(newQty);

                        // Acumular cambio para enviar al final
                        this.accumulateQuantityChange(key, $item, 1);

                        // Programa el pipeline: si sigue clicando, se reinicia el timer
                        this.schedulePostCartPipeline(key);
                    } catch (err) {
                        console.error('Error al incrementar cantidad:', err);
                    } finally {
                        $btn.prop('disabled', false).removeClass('loading');
                    }
                }
            );

            $(document).on(
                'click.cart-summary',
                '.page-order .right-sidebar-checkout .cart-summary .summary .cart-inventaries-lists .product-item .quantity-body .le-quantity .minus',
                async (e) => {
                    e.preventDefault();
                    const $btn   = $(e.currentTarget).prop('disabled', true).addClass('loading');
                    const $item  = $btn.closest('.product-item');
                    const $input = $item.find('.cart-product-quantity');
                    const key    = getLineKey($item);
                    const currentQty = parseInt($input.val(), 10) || 0;

                    try {
                        if (currentQty <= 1) {
                            // Si vas a eliminar, dispara el flujo completo al confirmar la eliminación
                            window.cart.showDeleteModal($btn);
                            // TIP: Dentro de tu confirm modal, tras borrar, llama a runUpdateThenValidate()
                        } else {
                            // Actualizar cantidad visual inmediatamente
                            const currentQty = parseInt($input.val(), 10) || 1;
                            const newQty = Math.max(1, currentQty - 1);
                            $input.val(newQty);

                            // Acumular cambio para enviar al final
                            this.accumulateQuantityChange(key, $item, -1);

                            this.schedulePostCartPipeline(key);
                        }
                    } catch (err) {
                        console.error('Error al decrementar cantidad:', err);
                    } finally {
                        $btn.prop('disabled', false).removeClass('loading');
                    }
                }
            );

            $(document).on('mouseup.cart-summary touchend.cart-summary',
                '.page-order .right-sidebar-checkout .cart-summary .summary .cart-inventaries-lists .product-item .quantity-body .le-quantity .plus, \
                 .page-order .right-sidebar-checkout .cart-summary .summary .cart-inventaries-lists .product-item .quantity-body .le-quantity .minus',
                async (e) => {
                    const $item = $(e.currentTarget).closest('.product-item');
                    const key   = getLineKey($item);

                    // Ejecuta inmediatamente al soltar el botón
                    await this.flushPostCartPipeline(key);
                }
            );


            // DELETE en el resumen (no dropdown) - FIXED: Added more selectors
            $(document)
                .off('click.cart-summary', '.cart-inventaries-lists .delete-to-cart') // Clear any previous handlers
                .off('click.cart', '.cart-inventaries-lists .delete-to-cart') // Remove backups.js handler
                .on('click.cart-summary', '.page-order .right-sidebar-checkout .cart-summary .summary .cart-inventaries-lists .product-item .delete-to-product, .cart-inventaries-lists .delete-to-cart', async (e) => {
                    e.preventDefault();
                    e.stopImmediatePropagation(); // Prevent other handlers
                    const $btn = $(e.currentTarget);

                    console.log('🛒 Delete button clicked from cart-inventaries-lists');

                    // Use checkout-specific showDeleteModal method
                    this.showCheckoutDeleteModal($btn[0]);
                });

            // === Eventos del dropdown del carrito (HEADER) ===
            // OJO: solo desengancha lo del dropdown, no todo el namespace
            $(document)
                .off('click.cart-dropdown', '.cart-dropdown .delete-to-product')
                .on('click.cart-dropdown', '.cart-dropdown .delete-to-product', this.handleCartProductDelete.bind(this));

            // DELETE desde modal de confirmación (solo delete-modal, error-modal se maneja en modal-strategies.js)
            $(document)
                .off('click.delete-modal', '.delete-modal .delete-to-product')
                .on('click.delete-modal', '.delete-modal .delete-to-product', this.handleCartProductDelete.bind(this));

            // DELETE desde modal de confirmación del checkout
            $(document)
                .off('click.checkout-delete-modal', '#delete-checkout-modal .delete-to-product')
                .off('click.cart', '.page-order #delete-checkout-modal .delete-to-product') // Remove backups.js handler
                .on('click.checkout-delete-modal', '#delete-checkout-modal .delete-to-product', this.handleCheckoutProductDelete.bind(this));

            // Coupon management - Apply and Remove voucher
            // $(document)
            //     .off('click.checkout-coupon', '#coupon-apply')
            //     .on('click.checkout-coupon', '#coupon-apply', (e) => {
            //         e.preventDefault();
            //         this.applyCoupon(e.currentTarget);
            //     });

            $(document)
                .off('click.checkout-coupon', '.remove-voucher')
                .on('click.checkout-coupon', '.remove-voucher', (e) => {
                    e.preventDefault();
                    this.deleteCoupon(e.currentTarget);
                });

            // Backdrops Bootstrap
            $(document).off('hidden.bs.modal.checkout-manager').on('hidden.bs.modal.checkout-manager', '.modal', function () {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');
            });

            // Forms
            $(document).off('submit.checkout-manager', '.checkout-form')
                .on('submit.checkout-manager', '.checkout-form', this.handleFormSubmission.bind(this));

            // Inputs (debounce)
            $(document).off('input.checkout-manager change.checkout-manager', '.checkout-input')
                .on('input.checkout-manager change.checkout-manager', '.checkout-input',
                    this.debounce(this.handleInputChange.bind(this), this.config.debounceDelay)
                );

            // Fallback de productos bloqueados - ONLY if CheckoutBlockManager is not available
            if (!window.checkoutBlockManager) {
                console.log('⚠️ CheckoutBlockManager not found - using fallback in CheckoutManager');
                $(document).off('click.checkout-block', '.delete-blocked-product')
                    .on('click.checkout-block', '.delete-blocked-product', this.handleBlockedProductDelete.bind(this));
            } else {
                console.log('✅ CheckoutBlockManager found - skipping fallback handler');
            }

            this.setupCartObserver();

            // MondialRelay carrier selection handler is now in DeliveryStepHandler

            console.log('✅ CheckoutManager core events bound');
        }

        handleFormSubmission(event) {
            event.preventDefault();
            const $form = $(event.currentTarget);
            const formId = $form.attr('id');

            console.log('📋 CheckoutManager.handleFormSubmission called');
            console.log('📋 Form ID:', formId);
            console.log('📋 Form action:', $form.attr('action'));
            console.log('📋 Form classes:', $form.attr('class'));
            console.log('📋 Event type:', event.type);

            // Handle different form types
            if (formId === 'js-delivery' || $form.hasClass('step-checkout-delivery')) {
                console.log('🚛 Handling delivery form submission');
                this.handleDeliveryFormSubmission($form);
            } else if (formId === 'step-checkout-address' || $form.hasClass('step-checkout-address')) {
                console.log('🏠 Handling address form submission');
                this.handleAddressFormSubmission($form);
            } else {
                console.log('📋 Handling generic form submission');
                this.handleGenericFormSubmission($form);
            }
        }

        async handleDeliveryFormSubmission($form) {
            console.log('🚛 Processing delivery form submission');
            console.log('📋 Form element:', $form[0]);
            console.log('📋 Form ID:', $form.attr('id'));
            console.log('📋 Form classes:', $form.attr('class'));

            // Pre-submit validation: Check if delivery option is selected
            const selectedOption = $('input[name^="delivery_option"]:checked');
            console.log('📦 Selected delivery option:', selectedOption.length ? selectedOption.val() : 'NONE');

            if (!selectedOption.length) {
                console.warn('⚠️ No delivery option selected, showing modal');
                this.showMissingDeliveryOptionModal();
                return;
            }

            // Special validation for pickup carriers
            const selectedCarrierId = parseInt(selectedOption.val(), 10);
            console.log('📦 Selected carrier ID:', selectedCarrierId);

            // Guard carriers (ID 78: Guard NY, ID 39: Guard Civil)
            if (selectedCarrierId === 78 || selectedCarrierId === 39) {
                const carrierName = selectedCarrierId === 78 ? 'Guard NY' : 'Guard Civil';
                console.log(`🏪 ${carrierName} carrier selected, checking store selection...`);

                // Check if Guard store is selected within the appropriate carrier wrapper
                const selectedStore = $('#kb_pickup_selected_store').val();
                const deliveryConfirmation = $('#delivery_confirmation').val();

                console.log('📦 Selected store ID:', selectedStore);
                console.log('📦 Delivery confirmation:', deliveryConfirmation);

                if (!selectedStore || selectedStore === '' || deliveryConfirmation !== 'yes') {
                    console.warn(`⚠️ ${carrierName} selected but no store chosen, showing store selection modal`);
                    this.showPickupLocationModal(selectedCarrierId, carrierName);
                    return;
                }

                console.log(`✅ ${carrierName} store validation passed`);
            }

            // Correos Express carrier (ID 66)
            else if (selectedCarrierId === 66) {
                console.log('📮 Correos Express carrier selected, checking office selection...');

                // Check if Correos Express office is selected
                // Correos Express shows selection in #cexSelected container
                const $selectedOffice = $('#cexSelected');
                const isOfficeSelected = $selectedOffice.length && !$selectedOffice.hasClass('d-none') && $selectedOffice.html().trim() !== '';

                console.log('📦 Office selected container exists:', $selectedOffice.length > 0);
                console.log('📦 Office selected container visible:', !$selectedOffice.hasClass('d-none'));
                console.log('📦 Office selected container has content:', $selectedOffice.html().trim() !== '');
                console.log('📦 Overall office selection status:', isOfficeSelected);

                if (!isOfficeSelected) {
                    console.warn('⚠️ Correos Express selected but no office chosen, showing office selection modal');
                    this.showPickupLocationModal(selectedCarrierId, 'Correos Express');
                    return;
                }

                console.log('✅ Correos Express office validation passed');
            }

            // Mondial Relay and InPost carriers - validation now handled by DeliveryStepHandler
            else if ([98, 100, 107, 108, 109, 110, 111].includes(selectedCarrierId)) {
                // Delegate to delivery step handler for MondialRelay validation
                if (window.deliveryStepHandler && typeof window.deliveryStepHandler.validateMondialRelayCarrier === 'function') {
                    const validationResult = window.deliveryStepHandler.validateMondialRelayCarrier(selectedCarrierId);
                    if (!validationResult.isValid) {
                        this.showPickupLocationModal(selectedCarrierId, validationResult.carrierName);
                        return;
                    }
                    console.log('✅ Mondial Relay validation passed');
                } else {
                    console.log('⚠️ DeliveryStepHandler not available for MondialRelay validation');
                }
            }

            console.log('✅ Delivery option validation passed, proceeding with submission');

            try {
                // Use the stepdelivery endpoint
                const url = this.endpoints.checkout.stepdelivery;
                const formData = $form.serialize();

                console.log('📡 Delivery AJAX Request:');
                console.log('📋 URL:', url);
                console.log('📋 Data:', formData);

                const response = await this.makeRequest(url, {
                    method: 'POST',
                    data: formData,
                    autoRetry: true
                });

                console.log('📦 Delivery response:', response);

                if (response.status === 'success') {
                    console.log('✅ Delivery form submitted successfully');
                    // Navigate to payment step
                    if (window.checkoutNavigator?.loadCheckoutStep) {
                        console.log('🚀 Loading payment step');
                        await window.checkoutNavigator.loadCheckoutStep('payment', true, true);
                    } else {
                        console.error('❌ No navigation method available');
                    }
                } else {
                    console.error('❌ Delivery form submission failed:', response);
                    this.showToast('error', response.message || 'Error al configurar el método de envío');
                }

            } catch (error) {
                console.error('❌ Delivery form submission error:', error);
                this.showToast('error', 'Error al procesar el envío. Inténtalo de nuevo.');
            }
        }


        showMissingDeliveryOptionModal() {
            console.log('🚫 Showing missing delivery option modal (CheckoutManager)');

            // Use existing modal from template
            const $modal = $('#missing-delivery-option-modal');

            if ($modal.length) {
                $modal.modal('show');
                console.log('✅ Missing delivery modal shown successfully');
            } else {
                console.error('❌ Missing delivery option modal not found in template');
                console.warn('⚠️ Make sure #missing-delivery-option-modal exists in delivery.tpl template');
            }
        }

        showPickupLocationModal(carrierId, carrierName) {
            console.log(`🏪 Showing ${carrierName} location selection modal (CheckoutManager) for carrier ${carrierId}`);

            // Get carrier info for dynamic content
            let serviceName, locationText, buttonText, wrapperSelector;

            // Define carrier-specific info using JavaScript (since template is already loaded)
            switch(carrierId) {
                case 78:
                    serviceName = 'Guardia Civil';
                    locationText = 'una sede específica de recogida';
                    buttonText = 'Seleccionar sede';
                    wrapperSelector = '#kb-pts-carrier-wrapper';
                    break;
                case 39:
                    serviceName = 'Guard Civil';
                    locationText = 'una sede específica de recogida';
                    buttonText = 'Seleccionar sede';
                    wrapperSelector = '#kbgcs-pts-carrier-wrapper';
                    break;
                case 66:
                    serviceName = 'Correos Express';
                    locationText = 'una oficina de recogida';
                    buttonText = 'Seleccionar oficina';
                    wrapperSelector = '.correosexpress-address-wrapper';
                    break;
                case 98:
                    serviceName = 'Mondial Relay Express';
                    locationText = 'un punto de relevo';
                    buttonText = 'Seleccionar punto de relevo';
                    wrapperSelector = '#mondialrelay_widget';
                    break;
                case 100:
                    serviceName = 'Mondial Relay';
                    locationText = 'un punto de relevo';
                    buttonText = 'Seleccionar punto de relevo';
                    wrapperSelector = '#mondialrelay_widget';
                    break;
                case 107:
                    serviceName = 'InPost Punto Pack';
                    locationText = 'un punto de recogida';
                    buttonText = 'Seleccionar punto InPost';
                    wrapperSelector = '#mondialrelay_widget';
                    break;
                case 108:
                    serviceName = 'InPost Locker';
                    locationText = 'un locker automático';
                    buttonText = 'Seleccionar locker InPost';
                    wrapperSelector = '#mondialrelay_widget';
                    break;
                case 109:
                    serviceName = 'InPost Premium';
                    locationText = 'un punto de recogida premium';
                    buttonText = 'Seleccionar punto InPost Premium';
                    wrapperSelector = '#mondialrelay_widget';
                    break;
                case 110:
                    serviceName = 'InPost Express';
                    locationText = 'un punto de recogida express';
                    buttonText = 'Seleccionar punto InPost Express';
                    wrapperSelector = '#mondialrelay_widget';
                    break;
                case 111:
                    serviceName = 'InPost Plus';
                    locationText = 'un punto de recogida plus';
                    buttonText = 'Seleccionar punto InPost Plus';
                    wrapperSelector = '#mondialrelay_widget';
                    break;
                default:
                    serviceName = carrierName;
                    locationText = 'un punto de recogida';
                    buttonText = 'Seleccionar punto';
                    wrapperSelector = '.delivery-options';
            }

            // Use existing modal from template and update its content
            const $modal = $('#pickup-location-modal');

            if ($modal.length) {
                // Update modal content with carrier-specific information
                $modal.find('#modal-service-name').text(serviceName);
                $modal.find('#modal-location-text').text(locationText);
                $modal.find('#modal-button-text').text(buttonText);
                $modal.find('button[data-dismiss="modal"]')
                    .attr('data-carrier-id', carrierId)
                    .attr('data-wrapper-selector', wrapperSelector);

                // Show the modal
                $modal.modal('show');
                console.log(`✅ ${carrierName} location selection modal shown successfully`);

                // Add event handler for scroll to pickup selection area
                $modal.find('button[data-dismiss="modal"]').off('click.pickupScroll').on('click.pickupScroll', (e) => {
                    const $button = $(e.target).closest('button');
                    const currentCarrierId = parseInt($button.attr('data-carrier-id'), 10) || carrierId;
                    const currentWrapperSelector = $button.attr('data-wrapper-selector') || wrapperSelector;
                    this.scrollToPickupSelection(currentCarrierId, carrierName, currentWrapperSelector);
                });
            } else {
                console.error('❌ Pickup location modal not found in template');
                console.warn('⚠️ Make sure #pickup-location-modal exists in delivery.tpl template');
            }
        }

        scrollToPickupSelection(carrierId, carrierName, wrapperSelector = '') {
            console.log(`📍 Scrolling to ${carrierName} selection area`);

            // Find the appropriate carrier wrapper
            const $wrapper = $(wrapperSelector);

            if ($wrapper.length && $wrapper.is(':visible')) {
                console.log(`✅ Found ${carrierName} wrapper, scrolling to it`);

                $('html, body').animate({
                    scrollTop: $wrapper.offset().top - 100
                }, 500);

                // Try to focus on appropriate input based on carrier
                setTimeout(() => {
                    let $inputToFocus;

                    switch(carrierId) {
                        case 78: // Guard NY
                        case 39: // Guard Civil
                            $inputToFocus = $('#address_search');
                            break;
                        case 66: // Correos Express
                            $inputToFocus = $('#postalcode');
                            break;
                        case 98:  // Mondial Relay Express
                        case 100: // Mondial Relay
                        case 107: // InPost Punto Pack
                        case 108: // InPost Locker
                        case 109: // InPost Premium
                        case 110: // InPost Express
                        case 111: // InPost Plus
                            // All these carriers use Mondial Relay widget with search input
                            $inputToFocus = $('#mondialrelay_widget .MRW-Search .Arg2'); // ZIP code input
                            break;
                        default:
                            $inputToFocus = $wrapper.find('input[type="text"]').first();
                    }

                    if ($inputToFocus && $inputToFocus.length) {
                        $inputToFocus.focus();
                        console.log(`✅ Focused on input for ${carrierName}`);
                    }
                }, 600);
            } else {
                console.warn(`⚠️ ${carrierName} wrapper not found or not visible`);

                // Fallback: scroll to delivery options
                const $deliveryOptions = $('.delivery-options');
                if ($deliveryOptions.length) {
                    $('html, body').animate({
                        scrollTop: $deliveryOptions.offset().top - 100
                    }, 500);
                }
            }
        }

        async handleAddressFormSubmission($form) {
            console.log('🏠 Processing address form submission (guarded)');

            // 🚨 PRIMERA LÍNEA DE DEFENSA - Verificar si AddressStepHandler está bloqueando
            if (window.addressStepHandler?.isShowingModal === true) {
                console.error('🛑 CheckoutManager BLOCKED - AddressStepHandler showing modal');
                return false; // Hard stop
            }

            // 1) Respeta el validator si existe
            if (typeof $form.valid === 'function' && !$form.valid()) {
                console.log('⛔ Form invalid by jQuery Validate (Address)');
                return false; // invalidHandler ya mostró modal
            }

            // 2) Freno duro (por si el validator no corrió o el DOM cambió)
            const isVirtual   = ($form.data('is-virtual') === 1 || $form.data('is-virtual') === '1');
            const needInvoice = $('#need_invoice').is(':checked');

            const deliverySelected =
                $form.find('input[name="id_address_delivery"]:checked').val() ||
                $form.find('input[name="id_address_delivery"]').val();

            if (!isVirtual && !deliverySelected) {
                console.error('⛔ CHECKOUT MANAGER: Missing delivery address - HARD STOP');
                if (window.addressStepHandler?.showMissingDeliveryModal) {
                    window.addressStepHandler.showMissingDeliveryModal();
                }
                return false; // Hard stop
            }

            const invoiceSelected =
                $form.find('input[name="id_address_invoice"]:checked').val() ||
                $form.find('input[name="id_address_invoice"]').val();

            if (needInvoice && !invoiceSelected) {
                console.error('⛔ CHECKOUT MANAGER: Missing invoice address - HARD STOP');
                if (window.addressStepHandler?.showMissingInvoiceModal) {
                    window.addressStepHandler.showMissingInvoiceModal();
                }
                return false; // Hard stop
            }

            // 3) Fuerza el flag need_invoice en el payload
            const formData = $form.serializeArray();
            formData.push({ name: 'need_invoice', value: needInvoice ? '1' : '0' });

            try {
                // Usa SIEMPRE el endpoint del step (no el action genérico)
                const response = await this.makeRequest(this.endpoints.checkout.stepaddress, {
                    method: 'POST',
                    data: formData,
                    autoRetry: true
                });

                // 4) Valida post-éxito y navega SOLO si pasa
                await this.validateAfterSuccess(response, async () => {
                    if (response.status === 'success') {
                        if (window.checkoutNavigator?.loadCheckoutStep) {
                            await window.checkoutNavigator.loadCheckoutStep('delivery', true, true);
                        } else if (window.checkoutNavigator?.navigateToStepDirect) {
                            window.checkoutNavigator.navigateToStepDirect('delivery', true);
                        }
                    } else {
                        this.handleFormError(response);
                    }
                });

            } catch (err) {
                console.error('❌ Address submit error:', err);
                this.showToast('error', 'No se pudo guardar direcciones');
            }
        }

        handleGenericFormSubmission($form) {
            console.log('📋 Processing generic form submission');

            const formData = new FormData($form[0]);
            const data = Object.fromEntries(formData.entries());

            this.makeRequest($form.attr('action'), {
                method: 'POST',
                data: data,
                autoRetry: true
            }).then(response => {
                if (response.status === 'success') {
                    this.handleFormSuccess(response);
                } else {
                    this.handleFormError(response);
                }
            }).catch(error => {
                console.error('Form submission error:', error);
            });
        }

        async handleFormSuccess(response) {
            console.log('Form submitted successfully:', response);

            await this.validateAfterSuccess(response, () => {
                if (response.nextStep && window.checkoutNavigator) {
                    window.checkoutNavigator.navigateToStep(response.nextStep);
                }
            });
        }

        handleFormError(response) {
            console.error('Form submission failed:', response);
            // Implement error handling
        }

        /**
         * Universal post-success validation for next buttons
         * Call this method from any success handler to validate checkout after form success
         * @param {Object} response - Original AJAX response
         * @param {Function} callback - Optional callback to execute if validation passes
         */
        async validateAfterSuccess(response = {}, callback = null) {
            // Store original suppression state to restore later
            const originalSuppressModal = this.state.suppressValidationModal;

            try {
                console.log('🔄 Performing universal post-success validation...');
                console.log('📋 Original response:', response);

                // Temporarily disable modal suppression for post-success validation
                this.state.suppressValidationModal = false;
                console.log('🔧 Modal suppression disabled for post-success validation');

                const validationResult = await this.validate({ force: true, autoNavigate: false });
                console.log('📋 Validation result:', validationResult);

                // Restore original suppression state
                this.state.suppressValidationModal = originalSuppressModal;

                if (validationResult?.errors?.hasError === true) {
                    console.warn('⚠️ Validation errors found after success:', validationResult.errors);
                    console.log('🎯 Calling handleValidationErrors with:', validationResult.errors);

                    this.handleValidationErrors(validationResult.errors);

                    console.log('❌ Blocking navigation due to validation errors');
                    return false; // Validation failed
                }

                console.log('✅ Post-success validation passed, executing callback');

                // Execute callback if provided and validation passed
                if (callback && typeof callback === 'function') {
                    callback(response);
                } else {
                    console.log('ℹ️ No callback provided or callback is not a function');
                }

                return true; // Validation passed

            } catch (validationError) {
                console.error('❌ Post-success validation error:', validationError);
                console.error('❌ Error stack:', validationError.stack);

                // Restore original suppression state in case of error
                this.state.suppressValidationModal = originalSuppressModal;

                // Execute callback even if validation fails (fallback behavior)
                if (callback && typeof callback === 'function') {
                    console.log('⚠️ Executing callback despite validation error (fallback)');
                    callback(response);
                }

                return false;
            }
        }

        displayFormErrors(errors) {
            for (const field in errors) {
                const errorMessage = errors[field];
                const $field = $(`[name="${field}"]`);
                if ($field.length) {
                    $field.addClass('is-invalid');
                    $field.after(`<div class="invalid-feedback">${errorMessage}</div>`);
                }
            }
        }

        handleInputChange(event) {
            const $input = $(event.currentTarget);
            $input.removeClass('is-invalid');
            $input.next('.invalid-feedback').remove();

            // Optional: trigger validation
            if (this.config.autoValidation) {
                this.validateField($input);
            }
        }

        validateField($input) {
            // Implement field-level validation
            const value = $input.val();
            const rules = this.getValidationRules($input.attr('name'));

            if (rules) {
                const isValid = this.validateValue(value, rules);
                $input.toggleClass('is-invalid', !isValid);
                $input.toggleClass('is-valid', isValid);
            }
        }

        getValidationRules(fieldName) {
            // Return validation rules for specific fields
            const rules = {
                email: { required: true, email: true },
                password: { required: true, minLength: 6 },
                // Add more rules as needed
            };

            return rules[fieldName];
        }

        validateValue(value, rules) {
            if (rules.required && !value.trim()) return false;
            if (rules.email && !this.isValidEmail(value)) return false;
            if (rules.minLength && value.length < rules.minLength) return false;
            return true;
        }

        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        debounce(func, delay) {
            let timeoutId;
            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => func.apply(this, args), delay);
            };
        }

        /**
         * Handle checkout modal product deletion
         * @param {Event} event - Click event from checkout delete modal
         */
        async handleCheckoutProductDelete(event) {
            console.log('🗑️ handleCheckoutProductDelete called from checkout modal');

            event.preventDefault();

            const $btn = $(event.currentTarget);
            const $modal = $btn.closest('#delete-checkout-modal');

            try {
                // Get product data from button attributes
                const productData = {
                    id_product: parseInt($btn.data('id-product'), 10),
                    id_product_attribute: parseInt($btn.data('id-product-attribute'), 10) || 0,
                    id_customization: parseInt($btn.data('id-customization'), 10) || 0,
                    id_cart: parseInt($btn.data('id-cart'), 10),
                };

                console.log('📦 Deleting product from checkout:', productData);

                if (!productData.id_product) {
                    console.error('Missing product ID for deletion', productData);
                    this.showErrorMessage('Missing product data');
                    return;
                }

                // Close modal first
                $modal.modal('hide');

                // Show loading state while deleting product
                console.log('⏳ Showing loading during product deletion');

                // Call API to delete product
                const response = await this.makeRequest(this.endpoints.checkout.deleteproduct, {
                    method: 'POST',
                    data: productData,
                    autoRetry: true,
                });

                if (response?.status === 'success') {
                    console.log('✅ Product deleted successfully from checkout');

                    // GTM tracking for product removal
                    this.trackProductRemoval(productData);

                    // Clear cart cache (same as backups.js)
                    if (window.cart && typeof window.cart.clearCache === 'function') {
                        window.cart.clearCache();
                    }

                    // Update cart dropdown/component (same as backups.js) - NO OPEN in checkout
                    if (window.cart && typeof window.cart.loadCart === 'function') {
                        console.log('🔄 Updating cart dropdown component');
                        await window.cart.loadCart(false); // fromProduct = false to NOT open dropdown

                        // FORCE CLOSE: Ensure cart dropdown never opens in checkout
                        setTimeout(() => {
                            $('.cart-dropdown').removeClass('opened');
                            console.log('🔧 FORCE CLOSED cart dropdown in checkout after modal delete');
                        }, 100);
                    }

                    // Update cart summary after deletion
                    await this.updateCartSummary();

                    // TEMPORARY DISABLED: Cart empty check to prevent page refresh
                    // Check if cart is empty after deletion and redirect to cart page
                    const isEmpty = await this.isCartEmpty();
                    if (isEmpty.isEmpty) {
                        console.log('🔄 Cart is empty after deletion, redirecting to cart page');
                        window.location.href = isEmpty.url;
                        return;
                    }
                    console.log('⚠️ CART EMPTY CHECK DISABLED: Skipping cart empty verification to prevent page refresh');

                    // TEMPORARY DISABLED: Navigation after delete to prevent page refresh
                    // Check current step and navigate accordingly
                    const currentStep = window.currentStep || '';
                    console.log('📍 Current step:', currentStep);
                    console.log('⚠️ NAVIGATION DISABLED: Skipping step navigation to prevent page refresh after product delete');

                    // if (currentStep === 'delivery' || currentStep === 'payment') {
                    //     console.log('🔄 In delivery/payment step, navigating to delivery to reload');
                    //     await window.checkoutNavigator.navigateToStepDirect('delivery', true);
                    // } else if (currentStep === 'address' || currentStep === 'login') {
                    //     console.log('⏭️ In address/login step, skipping navigation');
                    //     // No hacer nada - no ejecutar navigateToStepDirect
                    // } else {
                    //     console.log('🔄 Unknown step, defaulting to delivery navigation');
                    //     await window.checkoutNavigator.navigateToStepDirect('delivery', true);
                    // }


                } else {
                    throw new Error(response?.message || 'Failed to delete product');
                }
            } catch (error) {
                console.error('❌ Error deleting product from checkout:', error);
                this.showErrorMessage('Error al eliminar el producto. Inténtalo de nuevo.');
            }
        }

        /**
         * Apply coupon/voucher to cart (checkout version)
         * @param {Element} button - The apply button element that was clicked
         */
        // async applyCoupon(button) {
        //     const $btn = $(button);
        //     const coupon = $('#coupon').val().trim();
        //     const confirmation = $('#confirmation').val().trim();
        //     const $errorLabel = $('label[for="coupon"].error');

        //     $errorLabel.hide().text('');

        //     if (!coupon) {
        //         $errorLabel.text('Please enter a code.').show();
        //         return;
        //     }

        //     $btn.prop('disabled', true).text('Applying...');

        //     try {
        //         // Use cart endpoints for coupon application (same as cart-manager)
        //         const applyCouponUrl = window.cart?.endpoints?.cart?.coupon ||
        //             `${this.endpoints.baseUrl}?modalitie=cart&action=coupon&iso=${this.endpoints.iso}`;

        //         const response = await this.makeRequest(applyCouponUrl, {
        //             method: 'POST',
        //             data: { coupon, confirmation }
        //         });

        //         if (response.status === 'success') {
        //             // Hide promotion code form
        //             $('#promotion-code').addClass('d-none');
        //             // Clear inputs
        //             $('#coupon, #confirmation').val('');

        //             // Clear cache
        //             this.clearCachesAfterCartChange();
        //             if (window.cart && typeof window.cart.clearCache === 'function') {
        //                 window.cart.clearCache();
        //             }

        //             // Update cart dropdown
        //             if (window.cart && typeof window.cart.loadCart === 'function') {
        //                 await window.cart.loadCart(false); // Don't open dropdown
        //             }

        //             // Update checkout summary to show applied coupon
        //             await this.updateCartSummary();

        //             // Navigate to delivery step if needed (same logic as product changes)
        //             const currentStep = window.currentStep || '';
        //             if (currentStep === 'delivery' || currentStep === 'payment') {
        //                 console.log('🎯 Coupon applied - navigating to delivery to reload options');
        //                 if (window.checkoutNavigator?.navigateToStepDirect) {
        //                     await window.checkoutNavigator.navigateToStepDirect('delivery', true);
        //                 }
        //             } else if (currentStep === 'address') {
        //                 console.log('📍 Coupon applied in address step - no navigation needed');
        //             } else {
        //                 console.log('🎯 Coupon applied - navigating to delivery to reload');
        //                 if (window.checkoutNavigator?.navigateToStepDirect) {
        //                     await window.checkoutNavigator.navigateToStepDirect('delivery', true);
        //                 }
        //             }

        //             // Show success message
        //             this.showToast('success', response.message || 'Cupón aplicado correctamente');
        //         } else {
        //             $errorLabel.text(response.message || 'Invalid coupon').show();
        //         }
        //     } catch (error) {
        //         console.error('❌ Error applying coupon:', error);
        //         $errorLabel.text('Connection error. Please try again.').show();
        //     } finally {
        //         $btn.prop('disabled', false).text('Apply');
        //     }
        // }

        /**
         * Delete coupon/voucher from cart (checkout version)
         * @param {Element} button - The button element that was clicked
         */
        async deleteCoupon(button) {
            const $btn = $(button);
            const ruleId = $btn.data('rule');
            const code = $btn.data('code');

            console.log('🔍 deleteCoupon debugging (checkout):', {
                button: button,
                $btn: $btn,
                ruleId: ruleId,
                code: code,
                dataRule: $btn.attr('data-rule'),
                dataCode: $btn.attr('data-code'),
                allData: $btn.data(),
                buttonHTML: $btn[0].outerHTML
            });

            if (!ruleId || !code) {
                console.error('❌ Missing coupon data:', { ruleId, code });
                this.showErrorMessage('Coupon data missing');
                return;
            }

            try {
                // Use cart endpoints for coupon deletion (same as cart-manager)
                const deleteCouponUrl = window.cart?.endpoints?.cart?.deleteCoupon ||
                    `${this.endpoints.baseUrl}?modalitie=cart&action=deletecoupon&iso=${this.endpoints.iso}`;

                const response = await this.makeRequest(deleteCouponUrl, {
                    data: { rule: ruleId, code }
                });

                if (response.status === 'success') {
                    // Clear cache
                    this.clearCachesAfterCartChange();
                    if (window.cart && typeof window.cart.clearCache === 'function') {
                        window.cart.clearCache();
                    }

                    // Update cart dropdown
                    if (window.cart && typeof window.cart.loadCart === 'function') {
                        await window.cart.loadCart(false); // Don't open dropdown
                    }

                    // Update checkout summary
                    await this.updateCartSummary();

                    // Navigate to delivery step if needed (same logic as product changes)
                    const currentStep = window.currentStep || '';
                    if (currentStep === 'delivery' || currentStep === 'payment') {
                        console.log('🎯 Coupon deleted - navigating to delivery to reload options');
                        if (window.checkoutNavigator?.navigateToStepDirect) {
                            await window.checkoutNavigator.navigateToStepDirect('delivery', true);
                        }
                    } else if (currentStep === 'address') {
                        console.log('📍 Coupon deleted in address step - no navigation needed');
                    } else {
                        console.log('🎯 Coupon deleted - navigating to delivery to reload');
                        if (window.checkoutNavigator?.navigateToStepDirect) {
                            await window.checkoutNavigator.navigateToStepDirect('delivery', true);
                        }
                    }

                    // Show success message
                    this.showToast('success', response.message || 'Cupón eliminado correctamente');
                } else {
                    this.showErrorMessage(response.message || 'Error al eliminar el cupón');
                }
            } catch (error) {
                console.error('❌ Error removing coupon:', error);
                this.showErrorMessage('Error al eliminar el cupón. Inténtalo de nuevo.');
            }
        }

        /**
         * Check if cart is empty by calling backend endpoint
         * @returns {Promise<boolean>} - True if cart has no inventaries left
         */
        async isCartEmpty() {
            try {
                console.log('🔍 Checking if cart is empty via backend...');

                // Use cart endpoints from window.cart if available
                if (window.cart && window.cart.endpoints) {
                    const cartInitUrl = window.cart.endpoints.cart.count;
                    console.log('🌐 Calling cart init endpoint:', cartInitUrl);

                    const response = await fetch(cartInitUrl);
                    const cartData = await response.json();

                    console.log('📦 Backend cart response:', cartData);

                    if (cartData && cartData.status === 'success') {
                        const productCount = cartData.products ? cartData.products.length : 0;
                        const totalProducts = cartData.count || 0;
                        const url = cartData.cart_link;

                        console.log('🔍 Backend product count:', productCount);
                        console.log('🔍 Backend total count:', totalProducts);

                        const isEmpty =  totalProducts === 0;

                        if (isEmpty) {
                            console.log('✅ Cart is empty according to backend');
                        } else {
                            console.log('❌ Cart is NOT empty - has inventaries');
                        }

                        return {
                            url: url || null,
                            isEmpty
                        };

                    } else {
                        console.log('⚠️ Invalid backend response, fallback to DOM check');
                    }
                }

                // Fallback: Check DOM elements if backend call fails
                const $visibleProducts = $('.checkout-inventaries .product-item:visible');
                const visibleProductCount = $visibleProducts.length;
                console.log('🔍 Fallback DOM check - visible inventaries:', visibleProductCount);

                const isEmpty = visibleProductCount === 0;
                console.log(isEmpty ? '✅ Cart is empty (DOM fallback)' : '❌ Cart is NOT empty (DOM fallback)');

                return {
                    url: null,
                    isEmpty
                };

            } catch (error) {
                console.error('❌ Error checking if cart is empty:', error);

                // Final fallback: DOM check
                const $visibleProducts = $('.checkout-inventaries .product-item:visible');
                const isEmpty = $visibleProducts.length === 0;
                console.log('🆘 Error fallback - DOM check result:', isEmpty);

                return {
                    url: null,
                    isEmpty
                };
            }
        }

        /**
         * Check if product count has changed and handle redirect/reload
         * Validates current product count against backend every 5 seconds
         * @param {number} currentCount - Current product count
         * @returns {Promise<boolean>} - True if count changed and action was taken
         */
        async validateProductCountChange() {
            try {
                // Only run on page-order
                if (!$('body').hasClass('page-order')) {
                    console.log('⏭️ Skipping validation - not on page-order');
                    return false;
                }

                // IMPORTANT: Skip if user is not authenticated
                if ($('body').hasClass('checkout-blocked') || this.isUserAuthenticated === false) {
                    console.log('⏭️ Skipping product count validation - user not authenticated');
                    return false;
                }

                // Skip if already detected a change or currently redirecting
                if (this.state.productCountValidation.changeDetected ||
                    this.state.productCountValidation.redirecting) {
                    console.log('⏭️ Skipping validation - change already detected or redirecting');
                    return false;
                }

                // Use cart endpoints from window.cart if available
                if (window.cart && window.cart.endpoints) {
                    const cartInitUrl = window.cart.endpoints.cart.count;
                    const response = await fetch(cartInitUrl);
                    const cartData = await response.json();

                    if (cartData && cartData.status === 'success') {
                        const backendCount = cartData.count || 0;
                        const lastKnownCount = this.state.productCountValidation.lastKnownCount;

                        console.log('📊 Count comparison:', {
                            lastKnown: lastKnownCount,
                            backend: backendCount,
                            changed: lastKnownCount !== null && lastKnownCount !== backendCount
                        });

                        // Initialize lastKnownCount if it's the first time
                        if (lastKnownCount === null) {
                            this.state.productCountValidation.lastKnownCount = backendCount;
                            console.log('🔍 Initialized last known count:', backendCount);
                            return false;
                        }

                        // Check if count changed from last known value
                        if (lastKnownCount !== backendCount) {
                            console.log('🔄 Product count changed! From', lastKnownCount, 'to', backendCount);

                            // Set flags to prevent multiple executions
                            this.state.productCountValidation.changeDetected = true;
                            this.state.productCountValidation.redirecting = true;

                            // Update last known count
                            this.state.productCountValidation.lastKnownCount = backendCount;

                            // Si está en address step, no navegar automáticamente - solo actualizar
                            if (window.currentStep === 'address') {
                                console.log('📍 Already on address step during count validation - skipping navigation to avoid disruption');

                                // Solo actualizar el resumen sin navegar
                                await this.updateCartSummary();

                                // Reset flags
                                this.state.productCountValidation.redirecting = false;
                                this.state.productCountValidation.changeDetected = false;

                                return true;
                            }

                            // Navigate to delivery step (solo si no está en address)
                            console.log('🎯 Direct navigation to step: delivery (no validations)');
                            await window.checkoutNavigator.navigateToStepDirect('delivery', true);

                            // Reload summary
                            await this.updateCartSummary();

                            // Reset flags after action completes to allow future detections
                            this.state.productCountValidation.redirecting = false;
                            this.state.productCountValidation.changeDetected = false;

                            return true;
                        }
                    }
                }

                return false;
            } catch (error) {
                console.error('❌ Error validating product count change:', error);
                // Reset flags on error
                this.state.productCountValidation.redirecting = false;
                this.state.productCountValidation.changeDetected = false;
                return false;
            }
        }

        /**
         * Start periodic validation of product count changes
         * Runs every 5 seconds to check if product count has changed
         */
        startProductCountValidation() {
            // Clear any existing interval
            if (this.productCountInterval) {
                clearInterval(this.productCountInterval);
            }

            this.productCountInterval = setInterval(async () => {
                try {
                    await this.validateProductCountChange();
                } catch (error) {
                    console.error('❌ Error in periodic product count validation:', error);
                }
            }, 5000); // Every 5 seconds

            console.log('⏰ Started product count validation (every 5 seconds)');
        }

        /**
         * Stop periodic validation of product count changes
         */
        stopProductCountValidation() {
            if (this.productCountInterval) {
                clearInterval(this.productCountInterval);
                this.productCountInterval = null;
                console.log('⏹️ Stopped product count validation');
            }
        }

        /**
         * Validate if cart exists, if not redirect to cart_link
         * Similar to validateProductCountChange but checks for cart existence
         * @returns {Promise<boolean>} - True if cart exists, false if redirected
         */
        async validateCartExistence() {
            try {
                // Only run on page-order
                if (!$('body').hasClass('page-order')) {
                    console.log('⏭️ Skipping cart validation - not on page-order');
                    return true;
                }

                // Use cart endpoints from window.cart if available
                if (window.cart && window.cart.endpoints) {
                    const cartInitUrl = window.cart.endpoints.cart.count;
                    console.log('🌐 Validating cart existence via:', cartInitUrl);

                    const response = await fetch(cartInitUrl);
                    const cartData = await response.json();

                    console.log('📦 Cart validation response:', cartData);

                    if (cartData && cartData.status === 'success') {
                        const totalProducts = cartData.count || 0;
                        const cartLink = cartData.cart_link;

                        console.log('🔍 Cart validation - Total inventaries:', totalProducts);
                        console.log('🔍 Cart link available:', cartLink);

                        const isEmpty = totalProducts === 0;

                        if (isEmpty && cartLink) {
                            console.log('🛒 Cart is empty, redirecting to cart_link:', cartLink);
                            window.location.href = cartLink;
                            return false;
                        } else if (!isEmpty) {
                            console.log('✅ Cart has inventaries, validation passed');
                            return true;
                        } else {
                            console.log('⚠️ Cart is empty but no cart_link available');
                            return false;
                        }
                    } else {
                        console.log('❌ Invalid cart validation response');
                        return false;
                    }
                } else {
                    console.log('❌ Cart endpoints not available');
                    return false;
                }
            } catch (error) {
                console.error('❌ Error validating cart existence:', error);
                return false;
            }
        }

        /**
         * Get current product count from DOM or backend
         * @returns {number} - Current product count
         */
        getCurrentProductCount() {
            try {
                // Try to get from cart counter first
                const $counter = $('.cart-counter, .cart-inventaries-count');
                if ($counter.length && $counter.text()) {
                    const count = parseInt($counter.text().trim()) || 0;
                    console.log('📊 Current count from counter:', count);
                    return count;
                }

                // Fallback: count visible inventaries in DOM
                const $visibleProducts = $('.checkout-inventaries .product-item:visible');
                const domCount = $visibleProducts.length;
                console.log('📊 Current count from DOM:', domCount);
                return domCount;
            } catch (error) {
                console.error('❌ Error getting current product count:', error);
                return 0;
            }
        }

        /**
         * Reset product count validation flags
         * Call this when you want to re-enable validation after a step change
         */
        resetProductCountValidation() {
            this.state.productCountValidation.changeDetected = false;
            this.state.productCountValidation.redirecting = false;
            this.state.productCountValidation.lastKnownCount = null;
            console.log('🔄 Product count validation flags and count reset');
        }

        /**
         * Handle cart dropdown product deletion on order pages
         * Updates summary and validates from address step
         * @param {Event} event - Click event
         */
        async handleCartProductDelete(event) {
            console.trace('🔥 handleCartProductDelete ejecutado');

            event.preventDefault();

            const $btn = $(event.currentTarget);
            const $item = $btn.closest('.product-item');

            console.log('🛒 Cart product delete clicked!');
            console.log('🛒 Event target:', event.currentTarget);
            console.log('🛒 Button jQuery object:', $btn);
            console.log('🛒 Button HTML:', $btn[0]?.outerHTML);
            console.log('🛒 Page classes:', $('body').attr('class'));
            console.log('🛒 Product item HTML:', $item[0]?.outerHTML);

            try {
                // Intentar leer desde .product-item primero, si no existe, leer del botón directamente
                const $dataSource = $item.length ? $item : $btn;

                console.log('🛒 Data source element:', $dataSource[0]?.outerHTML);

                const productData = {
                    id_product: $dataSource.data('id-product') ?? $dataSource.attr('data-id-product'),
                    id_product_attribute: $dataSource.data('id-product-attribute') ?? $dataSource.attr('data-id-product-attribute') ?? 0,
                    id_customization: $dataSource.data('id-customization') ?? $dataSource.attr('data-id-customization') ?? 0,
                    id_cart: $dataSource.data('id-cart') ?? $dataSource.attr('data-id-cart'),
                };

                // Normaliza a enteros cuando aplique
                productData.id_product = parseInt(productData.id_product, 10);
                productData.id_product_attribute = parseInt(productData.id_product_attribute, 10) || 0;
                productData.id_customization = parseInt(productData.id_customization, 10) || 0;
                productData.id_cart = parseInt(productData.id_cart, 10);

                if (!productData.id_product) {
                    console.error('Missing product ID for deletion', productData);
                    this.showErrorMessage('Missing product data');
                    return;
                }

                console.log('📦 Deleting product from cart:', productData);

                // Show loading state for checkout containers
                console.log('⏳ Showing loading during cart dropdown deletion');

                // Estado de carga en el botón
                $btn.prop('disabled', true).addClass('loading');

                // Llamada a API
                const response = await this.makeRequest(this.endpoints.checkout.deleteproduct, {
                    method: 'POST',
                    data: productData,
                    autoRetry: true,
                });

                if (response?.status === 'success') {
                    console.log('✅ Product deleted successfully from cart dropdown');

                    // Close dropdown
                    $('.dropdown-toggle').removeClass('show');

                    // Update cart dropdown component - NO OPEN since we're in checkout
                    if (window.cart && typeof window.cart.clearCache === 'function') {
                        window.cart.clearCache();
                    }
                    if (window.cart && typeof window.cart.loadCart === 'function') {
                        console.log('🔄 Updating cart dropdown after dropdown delete');
                        await window.cart.loadCart(false); // fromProduct = false to NOT open dropdown

                        // FORCE CLOSE: Ensure cart dropdown never opens in checkout
                        setTimeout(() => {
                            $('.cart-dropdown').removeClass('opened');
                            console.log('🔧 FORCE CLOSED cart dropdown in checkout');
                        }, 100);
                    }

                    // Actualizar resumen del checkout directamente
                    await this.updateCartSummary();

                    // Ejecutar validaciones sin navegación automática
                    // await this.executeValidations(true, false);

                } else {
                    throw new Error(response?.message || 'Failed to delete product');
                }
            } catch (error) {
                console.error('❌ Error deleting product from cart dropdown:', error);
                this.showErrorMessage('Error al eliminar el producto. Inténtalo de nuevo.');
            } finally {
                $btn.prop('disabled', false).removeClass('loading');
            }
        }

        async revalidateFromAddresses() {
            try {
                console.log('🔍 Re-validating checkout from address step due to cart changes');

                // Force fresh validation
                this.state.validationStatus.lastResult = null;

                // Wait a moment for server state to update
                await this.sleep(300);

                // Navigate to address step and validate
                if (window.checkoutNavigator && typeof window.checkoutNavigator.navigateToStepDirect === 'function') {
                    // Navigate to address without validation first
                    await window.checkoutNavigator.navigateToStepDirect('address', true);

                    // Then perform comprehensive validation
                    setTimeout(async () => {
                        try {
                            this.clearCachesAfterCartChange(); // ✅ asegura validación sin cache
                            await this.validate({ force: true, autoNavigate: false });
                            console.log('✅ Checkout re-validation from address completed successfully');
                        } catch (validationError) {
                            console.log('⚠️ Validation errors found after cart modification:', validationError);
                            // Errors will be handled by handleValidationErrors automatically
                        }
                    }, 10000);

                } else {
                    console.warn('CheckoutNavigator not available for address navigation');
                    // Fallback: just validate without navigation
                    this.clearCachesAfterCartChange();
                    await this.validate({ force: true, autoNavigate: false });
                }

            } catch (error) {
                console.error('❌ Error during checkout re-validation:', error);
            }
        }

        /**
         * Update cart summary after product deletion
         */
        async updateCartSummaryAfterDeletion() {
            try {
                console.log('🔄 Updating cart summary after product deletion');

                // ⬇️ Limpia caches para evitar datos viejos
                this.clearCachesAfterCartChange();

                // Update checkout summary
                if (window.checkoutNavigator && typeof window.checkoutNavigator.loadCheckoutSummary === 'function') {
                    window.checkoutNavigator.loadCheckoutSummary();
                }
                // Update cart dropdown and header totals
                this.fallbackCartUpdate();

                console.log('✅ Cart summary updated after product deletion');

            } catch (error) {
                console.error('❌ Error updating cart summary after deletion:', error);
            }
        }

        // Método delegado a checkout-block.js
        handleBlockedProductDelete(event) {
            // Si el gestor de bloques está disponible, delegar el evento
            if (window.checkoutBlockManager) {
                window.checkoutBlockManager.handleBlockedProductDelete(event);
            } else {
                console.warn('CheckoutBlockManager no está disponible. Verifica que checkout-block.js esté cargado.');
            }
        }


        showCheckoutDeleteModal(element) {
            const data = this.getProductData(element);
            console.log('🔍 showCheckoutDeleteModal called with data:', data);

            const $modal = $('#delete-checkout-modal');
            const $modalButton = $modal.find('.delete-to-product');

            console.log('🔍 Checkout delete modal found:', $modal.length > 0);

            if ($modal.length === 0) {
                console.error('❌ Checkout delete modal #delete-checkout-modal not found');
                return;
            }

            // ENSURE backups.js handler is removed before showing modal
            $modalButton.off('click.cart');
            console.log('🔧 Removed backups.js handler from modal button');

            // Clear existing data attributes
            $modalButton.removeAttr('data-id-cart data-id-product data-id-product-attribute')
                .removeData('id-cart id-product id-product-attribute');

            // Set new data attributes
            $modalButton.attr({
                'data-id-cart': data.id_cart,
                'data-id-product': data.id_product,
                'data-id-product-attribute': data.id_product_attribute
            });

            console.log('🎯 Showing checkout delete modal');
            $modal.modal('show');
        }

        getProductData(element) {
            const $element = $(element);
            const $source = $element.data('id-product') ? $element : $element.closest('.item-product, .product-item');

            return {
                id_product: $source.data('id-product'),
                id_product_attribute: $source.data('id-product-attribute') || 0,
                id_cart: $source.data('id-cart') || null,
                id_customization: $source.data('id-customization') || 0
            };
        }

        showErrorMessage(message) {
            if (typeof window.settings?.showToast === 'function') {
                window.settings.showToast('error', message);
            } else {
                console.error('Toast not available:', message);
                alert(message); // Fallback
            }
        }

        updateCheckoutDisplay(response) {
            $('.checkout-container').removeClass('d-none');
            $('.checkout-empty-container').addClass('d-none');

            // Update sections with provided data
            if (response.products) {
                $('.container-inventaries').html(response.products);
            }
            if (response.summary) {
                $('.container-summary').html(response.summary);
            }
            if (response.shipping) {
                $('.container-shipping').html(response.shipping);

                // Preserve and restore hooks content
                this.preserveHooksContent();

                // Reinitialize MondialRelay after shipping section update
                if (window.deliveryStepHandler?.reinitializeMondialRelay) {
                    window.deliveryStepHandler.reinitializeMondialRelay();
                }
            }
        }

        clearCachesAfterCartChange() {
            try {
                this.state.cache.clear();            // cache de makeRequest()
                this.state.validationCache.clear();  // cache de validaciones
            } catch (e) {
                console.warn('No se pudo limpiar cache tras cambio de carrito', e);
            }
        }

        accumulateQuantityChange(key, $item, delta) {
            // Obtener datos del producto
            const productData = {
                id_product: $item.data('id-product') || $item.attr('data-id-product'),
                id_product_attribute: $item.data('id-product-attribute') || $item.attr('data-id-product-attribute') || 0,
                id_cart: $item.data('id-cart') || $item.attr('data-id-cart'),
                id_customization: $item.data('id-customization') || $item.attr('data-id-customization') || 0,
                element: $item
            };

            // Acumular cambios por producto
            if (!this.cartConfig.pendingUpdates.has(key)) {
                this.cartConfig.pendingUpdates.set(key, {
                    ...productData,
                    totalDelta: 0
                });
            }

            const pending = this.cartConfig.pendingUpdates.get(key);
            pending.totalDelta += delta;

            console.log(`📊 Accumulated ${delta > 0 ? '+' : ''}${delta} for product ${key}, total delta: ${pending.totalDelta}`);
        }

        async processPendingQuantityUpdates() {
            if (this.cartConfig.pendingUpdates.size === 0) {
                console.log('📭 No pending quantity updates to process');
                return;
            }

            console.log('📤 Processing accumulated quantity updates...');

            const updates = Array.from(this.cartConfig.pendingUpdates.values());
            this.cartConfig.pendingUpdates.clear();

            // Procesar cada actualización acumulada
            for (const update of updates) {
                if (update.totalDelta !== 0) {
                    console.log(`🔄 Sending ${Math.abs(update.totalDelta)} ${update.totalDelta > 0 ? 'increments' : 'decrements'} for product ${update.id_product}`);

                    try {
                        // Enviar deltas individuales (como espera PrestaShop)
                        const delta = update.totalDelta;
                        const op = delta > 0 ? 'up' : 'down';
                        const iterations = Math.abs(delta);

                        for (let i = 0; i < iterations; i++) {
                            await window.cart.makeRequest(window.cart.endpoints.cart.update, {
                                method: 'POST',
                                data: {
                                    id_product: update.id_product,
                                    id_product_attribute: update.id_product_attribute,
                                    id_cart: update.id_cart,
                                    quantity: 1,
                                    op: op
                                }
                            });
                        }

                        console.log(`✅ Successfully updated quantity for product ${update.id_product}`);
                    } catch (error) {
                        console.error('❌ Error updating accumulated quantity:', error);
                        // Revertir cantidad visual en caso de error
                        const $input = update.element.find('.cart-product-quantity');
                        const currentQty = parseInt($input.val(), 10);
                        $input.val(currentQty - update.totalDelta);
                    }
                }
            }
        }

        accumulateQuantityChange(key, $item, delta) {
            // Obtener datos del producto
            const productData = {
                id_product: $item.data('id-product') || $item.attr('data-id-product'),
                id_product_attribute: $item.data('id-product-attribute') || $item.attr('data-id-product-attribute') || 0,
                id_cart: $item.data('id-cart') || $item.attr('data-id-cart'),
                id_customization: $item.data('id-customization') || $item.attr('data-id-customization') || 0,
                element: $item
            };

            // Acumular cambios por producto
            if (!this.cartConfig.pendingUpdates.has(key)) {
                this.cartConfig.pendingUpdates.set(key, {
                    ...productData,
                    totalDelta: 0
                });
            }

            const pending = this.cartConfig.pendingUpdates.get(key);
            pending.totalDelta += delta;

            console.log(`📊 Accumulated ${delta > 0 ? '+' : ''}${delta} for product ${key}, total delta: ${pending.totalDelta}`);
        }

        async processPendingQuantityUpdates() {
            if (this.cartConfig.pendingUpdates.size === 0) {
                console.log('📭 No pending quantity updates to process');
                return;
            }

            console.log('📤 Processing accumulated quantity updates...');

            const updates = Array.from(this.cartConfig.pendingUpdates.values());
            this.cartConfig.pendingUpdates.clear();

            // Procesar cada actualización acumulada
            for (const update of updates) {
                if (update.totalDelta !== 0) {
                    console.log(`🔄 Sending ${Math.abs(update.totalDelta)} ${update.totalDelta > 0 ? 'increments' : 'decrements'} for product ${update.id_product}`);

                    try {
                        // Enviar deltas individuales (como espera PrestaShop)
                        const delta = update.totalDelta;
                        const op = delta > 0 ? 'up' : 'down';
                        const iterations = Math.abs(delta);

                        for (let i = 0; i < iterations; i++) {
                            await window.cart.makeRequest(window.cart.endpoints.cart.update, {
                                method: 'POST',
                                data: {
                                    id_product: update.id_product,
                                    id_product_attribute: update.id_product_attribute,
                                    id_cart: update.id_cart,
                                    quantity: 1,
                                    op: op
                                }
                            });
                        }

                        console.log(`✅ Successfully updated quantity for product ${update.id_product}`);
                    } catch (error) {
                        console.error('❌ Error updating accumulated quantity:', error);
                        // Revertir cantidad visual en caso de error
                        const $input = update.element.find('.cart-product-quantity');
                        const currentQty = parseInt($input.val(), 10);
                        $input.val(currentQty - update.totalDelta);
                    }
                }
            }
        }

        async processPendingQuantityUpdatesAbsolute() {
            if (this.cartConfig.pendingUpdates.size === 0) {
                console.log('📭 No pending quantity updates to process');
                return;
            }

            console.log('📤 Processing accumulated quantity updates (ABSOLUTE METHOD)...');

            const updates = Array.from(this.cartConfig.pendingUpdates.values());
            this.cartConfig.pendingUpdates.clear();

            // Procesar cada actualización con cantidad final absoluta
            for (const update of updates) {
                if (update.totalDelta !== 0) {
                    const finalQty = parseInt(update.element.find('.cart-product-quantity').val(), 10);
                    console.log(`🔄 ABSOLUTE: Setting product ${update.id_product} to quantity ${finalQty}`);

                    try {
                        // Enviar cantidad absoluta (requiere backend modificado)
                        await window.cart.makeRequest(window.cart.endpoints.cart.update, {
                            method: 'POST',
                            data: {
                                id_product: update.id_product,
                                id_product_attribute: update.id_product_attribute,
                                id_cart: update.id_cart,
                                quantity: finalQty,
                                op: 'set' // Operación para cantidad absoluta
                            }
                        });

                        console.log(`✅ ABSOLUTE: Successfully set quantity ${finalQty} for product ${update.id_product}`);
                    } catch (error) {
                        console.error('❌ ABSOLUTE: Error updating quantity:', error);
                        throw error;
                    }
                }
            }
        }

        async runUpdateThenValidate() {
            try {
                console.log('🔄 Running post-cart update pipeline...');

                // Show loading during the entire pipeline
                // DISABLED: No mostrar loading durante cambios de cantidad plus/minus
                // this.manageCheckoutVisibility(false);

                // Limpia caches para forzar datos frescos
                this.clearCachesAfterCartChange();

                // 1) Enviar todas las actualizaciones acumuladas de cantidad
                // OPCIÓN A: Cantidad absoluta (requiere backend modificado)
                // await this.processPendingQuantityUpdatesAbsolute();

                // OPCIÓN B: Deltas individuales (funciona con backend actual)
                await this.processPendingQuantityUpdates();

                // 2) Update cart dropdown component (same as backups.js) - NO OPEN
                if (window.cart && typeof window.cart.clearCache === 'function') {
                    window.cart.clearCache();
                }
                if (window.cart && typeof window.cart.loadCart === 'function') {
                    console.log('🔄 Updating cart dropdown after quantity change');
                    await window.cart.loadCart(false); // fromProduct = false to NOT open dropdown

                    // FORCE CLOSE: Ensure cart dropdown never opens in checkout
                    setTimeout(() => {
                        $('.cart-dropdown').removeClass('opened');
                        console.log('🔧 FORCE CLOSED cart dropdown in checkout after quantity change');
                    }, 100);
                }

                // 3) Actualizar resumen del checkout
                await this.updateCartSummary();

                // 4) Si no está autenticado y ya está en login, saltar validaciones y navegación
                if (!this.isUserAuthenticated && window.currentStep === 'login') {
                    console.log('🔐 User not authenticated and already on login step - skipping validations and navigation');
                    this.manageCheckoutVisibility(true);
                    return;
                }

                // 5) Si está en address y es cambio de cantidad, no navegar automáticamente
                if (window.currentStep === 'address') {
                    console.log('📍 Already on address step and quantity changed - skipping navigation to avoid disruption');
                    this.manageCheckoutVisibility(true);
                    return;
                }

                // 6) Ejecutar validaciones para detectar errores (desde cambio de cantidad)
                const validationResult = await this.executeValidations(true, false, { fromQuantityChange: true });

                // 7) Solo navegar a address SI hay errores
                if (validationResult?.errors?.hasError === true) {
                    console.log('🚨 Validation errors detected, navigating to address step');
                    if (window.checkoutNavigator?.navigateToStepDirect) {
                        await window.checkoutNavigator.navigateToStepDirect('address', true);
                    } else if (window.checkoutNavigator?.loadCheckoutStep) {
                        await window.checkoutNavigator.loadCheckoutStep('address', true, true);
                    }
                    // Navigation methods will handle showing content
                } else {
                    console.log('✅ No validation errors, staying on current step');
                    // Show content since no navigation is needed
                    this.manageCheckoutVisibility(true);
                }

                console.log('✅ Post-cart pipeline completed successfully');
            } catch (err) {
                console.error('❌ Post-cart pipeline error:', err);
                // Show content even on error to avoid stuck loading
                this.manageCheckoutVisibility(true);
            }
        }

        schedulePostCartPipeline(key) {
            if (this.cartConfig.postCartTimers.has(key)) {
                clearTimeout(this.cartConfig.postCartTimers.get(key));
            }
            const t = setTimeout(async () => {
                this.cartConfig.postCartTimers.delete(key);
                await this.runUpdateThenValidate();
            }, this.cartConfig.debounceMs);
            this.cartConfig.postCartTimers.set(key, t);
        }

        async flushPostCartPipeline(key) {
            if (this.cartConfig.postCartTimers.has(key)) {
                clearTimeout(this.cartConfig.postCartTimers.get(key));
                this.cartConfig.postCartTimers.delete(key);

                // Esperar un momento para que terminen los requests de updateQuantity
                console.log('⏳ Waiting for cart update requests to complete before validation...');
                await this.sleep(200);

                await this.runUpdateThenValidate();
            }
        }

        async executeValidations(force = false, autoNavigate = true, context = {}) {
            console.log('🔄 executeValidations called:', { force, autoNavigate, context });

            const cacheKey = 'validations';
            if (!force && this.isValidationCacheValid(cacheKey)) {
                console.log('🔄 Using cached validation result');
                return this.state.validationCache.get(cacheKey);
            }

            try {
                console.log('🔄 Making validation request to:', this.endpoints.checkout.validations);

                const response = await this.makeRequest(this.endpoints.checkout.validations, {
                    useCache: !force, // ✅ sin cache cuando force === true
                    autoRetry: true
                });

                console.log('🔄 Validation response received:', response);

                if (response?.status !== "success") {
                    console.warn("Invalid checkout response");
                    throw new Error("Validation failed");
                }

                // Update authentication status from response
                this.updateAuthenticationStatus(response.authentication);

                // If backend says user is not authenticated
                if (!this.isUserAuthenticated) {
                    $('body').addClass('checkout-blocked');
                    $('.accordion-item').addClass('disabled');

                    const currentStep = window.currentStep || 'unknown';
                    console.log(`🔐 Navigating to login step (from ${currentStep})`);
                    window.checkoutNavigator.navigateToStepDirect('login', true);

                } else {

                    $('body').removeClass('checkout-blocked');
                    $('.accordion-item').removeClass('disabled');
                    $('#checkout-login .login-overlay').remove();
                    $('#login-overlay-style').remove();

                    // Cache validation result
                    this.state.validationCache.set(cacheKey, { result: response, timestamp: Date.now() });

                    const errorData = response.errors;
                    const step = response.step;

                    console.log('🔍 executeValidations - Processing response:', {
                        hasErrorData: !!errorData,
                        errorHasError: errorData?.hasError,
                        errorType: errorData?.type,
                        step: step
                    });

                    if (errorData?.hasError === true) {
                        console.log('🚨 Validation errors detected - calling handleValidationErrors');
                        console.log('🚨 Error data:', errorData);
                        this.updateCheckoutDisplay(response);
                        this.handleValidationErrors(errorData);

                        // Only navigate automatically if autoNavigate is true (initial load) and not already on address
                        if (autoNavigate && window.checkoutNavigator && typeof window.checkoutNavigator.loadCheckoutStep === 'function') {
                            const currentStep = window.currentStep || '';
                            if (currentStep !== 'address') {
                                console.log('🚨 Auto-navigating to address step due to errors (current step:', currentStep, ')');
                                // loadCheckoutStep will handle loading state and show content when done
                                window.checkoutNavigator.loadCheckoutStep('address', true, true);
                            } else {
                                console.log('📍 Already on address step, showing modal without navigation');
                                // Show content since no navigation is needed
                                this.manageCheckoutVisibility(true);
                            }
                        } else {
                            // No navigation, show content with error modal
                            console.log('⚠️ No auto-navigation for errors, showing content');
                            this.manageCheckoutVisibility(true);
                        }
                        return response;
                    } else if (autoNavigate && step) {
                        // Check authentication status again before navigation
                        console.log(`🔍 Backend suggests navigation to step: ${step}`);
                        console.log(`🔍 autoNavigate: ${autoNavigate}, step: ${step}`);
                        console.log(`🔍 Context:`, context);
                        console.log(`🔍 Authentication status:`, response.authentication);

                        // IMPORTANT: Don't navigate if user is not authenticated (ALWAYS BLOCK)
                        if (response.authentication === false) {
                            console.log(`🔒 BLOCKING navigation to ${step} - user not authenticated`);
                            this.manageCheckoutVisibility(true);
                            return response;
                        }

                        // Only auto-navigate on initial load, not on manual validations
                        console.log(`🚀 Auto-navigating to step: ${step} - loading step from server`);
                        // The loadCheckoutStep will handle showing content when done
                        window.checkoutNavigator.loadCheckoutStep(step, true, true);
                    } else {
                        // No navigation needed, show content
                        console.log('✅ No auto-navigation needed, showing content');
                        this.manageCheckoutVisibility(true);
                    }

                    // Always return response regardless of navigation
                    return response;

                }

                // Update summary if provided
                if (response.summary) {
                    this.updateSummary(response.summary);
                }

                return response;
            } catch (error) {
                console.warn("Error executing validations:", error);
                throw error;
            }
        }

        isValidationCacheValid(cacheKey) {
            const cached = this.state.validationCache.get(cacheKey);
            return cached && (Date.now() - cached.timestamp) < this.config.validationThrottle;
        }

        async validate({ onlyOnError = false, force = false, autoNavigate = true } = {}) {
            // Reuse in-flight promise
            if (this.state.validationStatus.inFlight) return this.state.validationStatus.inFlight;

            // Skip if already validated OK and only want to validate if there was previous error
            if (!force && onlyOnError && this.state.validationStatus.lastResult === 'success') {
                return Promise.resolve({ skipped: true });
            }

            const p = this.executeValidations(force, autoNavigate)
                .then((res) => {
                    this.state.validationStatus.lastResult = 'success';
                    return res;
                })
                .catch((err) => {
                    this.state.validationStatus.lastResult = 'error';
                    throw err;
                })
                .finally(() => {
                    this.state.validationStatus.inFlight = null;
                });

            this.state.validationStatus.inFlight = p;
            return p;
        }

        updateSummary(summaryHtml) {
            $('.container-summary').html(summaryHtml);
        }


        // Update cart summary by fetching fresh data
        async updateCartSummary() {
            try {
                console.log('Updating cart summary after product deletion...');

                // Use the CheckoutNavigator function if available (preferred method)
                if (window.checkoutNavigator && typeof window.checkoutNavigator.loadCheckoutSummary === 'function') {
                    // CheckoutNavigator will handle visibility management
                    window.checkoutNavigator.loadCheckoutSummary();
                    return;
                }

                // Fallback: manually fetch summary
                const response = await this.makeRequest(this.endpoints.checkout.stepsummary, {
                    method: 'GET',
                    useCache: false
                });

                if (response.success !== false) {

                    // Update cart summary
                    if (response.summary) {
                        this.updateSummary(response.summary);
                        console.log('Cart summary updated successfully');
                    }

                    // Update product list if cart has inventaries
                    if (response.products) {
                        $('.container-inventaries').html(response.products);
                        console.log('Product list updated');
                    }

                    // Update shipping options if available
                    if (response.shipping) {
                        $('.container-shipping').html(response.shipping);
                        console.log('Shipping options updated');

                        // Preserve and restore hooks content
                        this.preserveHooksContent();

                        // Reinitialize MondialRelay after shipping section update
                        if (window.deliveryStepHandler?.reinitializeMondialRelay) {
                            window.deliveryStepHandler.reinitializeMondialRelay();
                        }
                    }

                    // Update cart totals in header/mini-cart if they exist
                    this.updateCartTotals(response);

                } else {
                    console.warn('Summary update returned unsuccessful response');
                }

            } catch (error) {
                console.error('Error updating cart summary:', error);
                // Don't show error notification to user as this is background operation
                // but try alternative method
                this.fallbackCartUpdate();
            }
        }

        /**
         * Setup observer para detectar cambios en el dropdown del carrito
         * Maneja contenido generado dinámicamente
         */
        setupCartObserver() {
            console.log('🔍 Setting up cart dropdown observer for dynamic content');

            // Método 1: Observer de mutaciones DOM
            if (window.MutationObserver) {
                const observer = new MutationObserver((mutations) => {
                    let shouldRebind = false;

                    mutations.forEach((mutation) => {
                        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                            // Check if any added nodes contain cart dropdown elements
                            Array.from(mutation.addedNodes).forEach(node => {
                                if (node.nodeType === Node.ELEMENT_NODE) {
                                    const $node = $(node);
                                    if ($node.hasClass('cart-dropdown') ||
                                        $node.find('.cart-dropdown').length > 0 ||
                                        $node.hasClass('delete-to-product') ||
                                        $node.find('.delete-to-product').length > 0) {
                                        shouldRebind = true;
                                    }
                                }
                            });
                        }
                    });

                    if (shouldRebind) {
                        console.log('🔄 Cart dropdown content detected, rebinding events');
                        this.bindCartEvents();
                    }
                });

                // Observe the document body for changes
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });

                // Store observer for cleanup
                this.cartObserver = observer;
            }

        }

        /**
         * Bind events específicamente para botones del dropdown del carrito
         * Se llama dinámicamente cuando se detecta nuevo contenido
         */
        bindCartEvents() {
            console.log('🔗 Binding cart dropdown events to dynamic content');

            // Siempre limpia primero
            $(document).off('click.cart-dynamic', '.cart-dropdown .delete-to-product');
            $(document).on('click.cart-dynamic', '.cart-dropdown .delete-to-product', this.handleCartProductDelete.bind(this));


            const deleteButtons = $('.cart-dropdown .delete-to-product, .cart-dropdown .btn-close').length;
            console.log(`🔗 Bound events to ${deleteButtons} cart dropdown delete buttons`);
        }


        handleChangeDeliveryInvoices(e) {
            e.preventDefault();

            this.clearBackdrop();

            console.log('🔄 Changing invoice address');

            const $btn = $(e.currentTarget);

            // Elige un destino de scroll: atributo data-scroll-target o alguno Por defecto
            const selectors = [
                $btn.data('scrollTarget'),
                '#stepcheckout-address',
                '#address-step',
                '#collapseaddress'
            ].filter(Boolean);

            let $target = $btn; // fallback: el propio botón
            for (const sel of selectors) {
                if ($(sel).length) { $target = $(sel); break; }
            }

            const doScroll = () => {
                const top = ($target.offset() && $target.offset().top) ? $target.offset().top - 100 : 0;
                $('html, body').stop(true).animate({ scrollTop: top }, 150);
            };

            // Cierra modales y luego hace scroll
            const $openModals = $('.modal.show');
            if ($openModals.length) {
                $openModals.one('hidden.bs.modal', doScroll).modal('hide');
            } else {
                doScroll();
            }

            // Navega al paso de direcciones
            if (window.checkoutNavigator && typeof window.checkoutNavigator.navigateToStepDirect === 'function') {
                window.checkoutNavigator.navigateToStepDirect('address', true);
            }
        }


        clearBackdrop(e) {
            $('.modal-backdrop').remove(); // Elimina cualquier backdrop existente
            $('body').removeClass('modal-open'); // Limpia la clase que bloquea el scroll
            $('body').css('padding-right', ''); // Restaura padding
        }


        handleChangeDeliveryInvoice(e) {
            e.preventDefault();
            this.clearBackdrop();
            console.log('🔄 Changing invoice address');

            const $btn = $(e.currentTarget);

            // Primero validar el checkbox need_invoice
            const $invoice = $('#need_invoice');
            if ($invoice.length && !$invoice.is(':checked')) {
                console.log('✅ Activando facturación');
                $invoice.prop('checked', true).trigger('change');
            }



            // Navega al paso de direcciones
            if (window.checkoutNavigator && typeof window.checkoutNavigator.navigateToStepSection === 'function') {
                window.checkoutNavigator.navigateToStepSection('address', true);
            }

            // Elige un destino de scroll (prioridad: invoice-addresses)
            const selectors = [
                '#invoice-addresses',
                $btn.data('scrollTarget'),
                '#stepcheckout-address',
                '#address-step',
                '#collapseaddress'
            ].filter(Boolean);

            let $target = $btn; // fallback
            for (const sel of selectors) {
                if ($(sel).length) { $target = $(sel); break; }
            }

            const doScroll = () => {
                const top = ($target.offset() && $target.offset().top) ? $target.offset().top - 100 : 0;
                $('html, body').stop(true).animate({ scrollTop: top }, 150);
            };

            // Cierra modales y luego hace scroll
            const $openModals = $('.modal.show');
            if ($openModals.length) {
                $openModals.one('hidden.bs.modal', doScroll).modal('hide');
            } else {
                doScroll();
            }

        }

        handleChangeDeliveryAddress(e) {

            e.preventDefault();
            console.log('🔄 Changing delivery address');
            this.clearBackdrop();

            const $btn = $(e.currentTarget);

            // Elige un destino de scroll: atributo data-scroll-target o alguno Por defecto
            const selectors = [
                $btn.data('scrollTarget'),
                '#stepcheckout-address',
                '#address-step',
                '#collapseaddress'
            ].filter(Boolean);

            let $target = $btn; // fallback: el propio botón
            for (const sel of selectors) {
                if ($(sel).length) { $target = $(sel); break; }
            }

            const doScroll = () => {
                const top = ($target.offset() && $target.offset().top) ? $target.offset().top - 100 : 0;
                $('html, body').stop(true).animate({ scrollTop: top }, 150);
            };

            // Cierra modales y luego hace scroll
            const $openModals = $('.modal.show');
            if ($openModals.length) {
                $openModals.one('hidden.bs.modal', doScroll).modal('hide');
            } else {
                doScroll();
            }

            // Navega al paso de direcciones
            if (window.checkoutNavigator && typeof window.checkoutNavigator.navigateToStepDirect === 'function') {
                window.checkoutNavigator.navigateToStepDirect('address', true);
            }
        }



        // Clean up when not needed
        destroy() {
            // Remove event listeners
            $(document).off('.checkout-manager');
            $(document).off('.cart-dynamic');

            // Stop mutation observer
            if (this.cartObserver) {
                this.cartObserver.disconnect();
            }

            // Cancel active requests
            this.state.activeRequests.forEach(controller => {
                controller.abort();
            });

            // Clear caches and timers
            this.state.cache.clear();
            this.state.debounceTimers.forEach(timer => clearTimeout(timer));
            this.state.debounceTimers.clear();

            console.log('CheckoutManager destroyed');
        }

        /**
         * Track product removal with GTM
         */
        async trackProductRemoval(productData) {
            try {
                console.log('🗑️ Tracking product removal with GTM:', productData);

                if (window.gtmExecuteFromAnywhere) {
                    await window.gtmExecuteFromAnywhere('remove_from_cart', {
                        options: {
                            item_id: productData.id_product || productData.product_id,
                            item_name: productData.name || 'Producto eliminado',
                            quantity: parseInt(productData.qty || 1)
                        }
                    });
                    console.log('✅ GTM remove_from_cart event triggered');
                }
            } catch (error) {
                console.error('❌ Error tracking product removal:', error);
            }
        }

        /**
         * Preserve and restore hooks content that gets lost during AJAX updates
         * This solves the issue where PrestaShop hooks content disappears after step navigation
         */
        preserveHooksContent() {
            try {
                console.log('🔄 Preserving hooks content...');

                // Special handling for hook-display-before-carrier with MondialRelay (delegated to delivery step handler)
                if (window.deliveryStepHandler?.preserveMondialRelayHookContent) {
                    window.deliveryStepHandler.preserveMondialRelayHookContent();
                }

                // Look for other common hook containers that might get lost
                const hookSelectors = [
                    '.hook-display-before-carriere',
                    '[id*="hook-display-before-carriere"]',
                    '[class*="hook-display-before-carriere"]',
                    '.delivery-options-before',
                    '.shipping-hooks-content'
                ];

                hookSelectors.forEach(selector => {
                    const $hookContent = $(selector);
                    if ($hookContent.length > 0 && $hookContent.html().trim()) {
                        console.log(`📌 Found hook content for selector: ${selector}`);

                        // Store the content
                        const storageKey = `hook_content_${selector.replace(/[^a-zA-Z0-9]/g, '_')}`;
                        sessionStorage.setItem(storageKey, $hookContent.html());
                        console.log(`💾 Stored hook content for: ${selector}`);
                    }
                });

                // Try to restore any missing hook content
                this.restoreHooksContent();

            } catch (error) {
                console.error('❌ Error preserving hooks content:', error);
            }
        }


        /**
         * Restore hooks content from sessionStorage if missing
         */
        restoreHooksContent() {
            try {
                // Special restoration for hook-display-before-carrier (delegated to delivery step handler)
                if (window.deliveryStepHandler?.restoreMondialRelayHook) {
                    window.deliveryStepHandler.restoreMondialRelayHook();
                }

                // General hook restoration
                const hookSelectors = [
                    '.hook-display-before-carriere',
                    '[id*="hook-display-before-carriere"]',
                    '[class*="hook-display-before-carriere"]',
                    '.delivery-options-before',
                    '.shipping-hooks-content'
                ];

                hookSelectors.forEach(selector => {
                    if (selector.includes('hook-display-before-carrier')) {
                        return; // Skip, handled by delivery step handler
                    }

                    const $hookContainer = $(selector);
                    const storageKey = `hook_content_${selector.replace(/[^a-zA-Z0-9]/g, '_')}`;
                    const storedContent = sessionStorage.getItem(storageKey);

                    if (storedContent && $hookContainer.length > 0 && !$hookContainer.html().trim()) {
                        console.log(`🔄 Restoring hook content for: ${selector}`);
                        $hookContainer.html(storedContent);
                    }
                });

            } catch (error) {
                console.error('❌ Error restoring hooks content:', error);
            }
        }


        /**
         * Reinitialize MondialRelay widget after content updates
         * This solves the issue where MondialRelay widget doesn't work after step navigation
         */
        reinitializeMondialRelay() {
            // Delegate to delivery step handler where this logic now belongs
            if (window.deliveryStepHandler && typeof window.deliveryStepHandler.reinitializeMondialRelay === 'function') {
                window.deliveryStepHandler.reinitializeMondialRelay();
            } else {
                console.log('🔄 Delivery step handler not available, skipping MondialRelay reinitialization');
            }
        }

        // MondialRelay functions moved to DeliveryStepHandler



    }

    window.CheckoutManager = CheckoutManager;

    // Global function to access authentication status
    window.isUserAuthenticated = function() {
        if (window.checkoutManager && typeof window.checkoutManager.isAuthenticated === 'function') {
            return window.checkoutManager.isAuthenticated();
        }
        return null; // Unknown if CheckoutManager not available
    };

    $(document).ready(function() {
        try {
            if ($('.page-checkout, .page-order').length) {
                window.checkoutManager = new CheckoutManager();

                // Initialize CheckoutNavigator if available
                if (typeof window.CheckoutNavigator !== 'undefined') {
                    window.checkoutNavigator = new window.CheckoutNavigator(window.checkoutManager);
                }

                window.checkoutManager.initializeCheckout()
                    .then(() => {
                        console.log('✅ CheckoutManager initialized');

                        // Load checkout summary after initialization only if no step navigation happened
                        // (if step navigation happened, content is already visible)
                        const currentStep = window.currentStep;
                        if (!currentStep || currentStep === 'login') {
                            console.log('🔄 Loading checkout summary after initialization');
                            if (window.checkoutNavigator && typeof window.checkoutNavigator.loadCheckoutSummary === 'function') {
                                window.checkoutNavigator.loadCheckoutSummary();
                            } else {
                                window.checkoutNavigator.loadCheckoutSummary();
                            }
                        } else {
                            console.log(`ℹ️ Skip summary load, already on step: ${currentStep}`);
                        }
                    })
                    .catch(error => {
                        console.error('❌ Error initializing CheckoutManager:', error);
                    });
            }
        } catch (error) {
            console.error('❌ Error initializing CheckoutManager:', error);
        }
    });

}
