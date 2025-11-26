(function() {
    'use strict';

    console.log('🔍 GTM Search Config loading...');

    // =========================================================================
    // UTILIDADES
    // =========================================================================

    /**
     * Obtiene ISO de la URL
     */
    function getISOFromPath() {
        const segments = (window.location.pathname || '').split('/');
        const iso = (segments[1] && segments[1].length === 2) ? segments[1].toLowerCase() : 'es';
        const prefix = (iso !== 'es') ? `/${iso}` : '';
        return { iso, prefix };
    }

    /**
     * Obtiene endpoints GTM
     */
    function getEndpoints() {
        const { iso, prefix } = getISOFromPath();
        const baseUrl = `${prefix}/modules/alsernetshopping/routes`;
        const gtmBase = `${baseUrl}?modalitie=gtp&action=init&iso=${iso}`;

        return {
            baseUrl,
            iso,
            gtm: {
                base: gtmBase,
                view_search_results: `${gtmBase}&type=view_search_results`
            }
        };
    }

    /**
     * Obtiene el parámetro de búsqueda de la URL
     */
    function getSearchTermFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('s') || urlParams.get('search_query') || urlParams.get('q') || '';
    }

    /**
     * Detecta si estamos en una página de resultados de búsqueda
     */
    function isSearchResultsPage() {
        const bodyId = document.body.id || '';
        return bodyId === 'module-ambjolisearch-jolisearch';
    }

    /**
     * Obtiene los productos desde inputs ocultos del template
     * Los productos ya vienen formateados desde el backend con getProductAnalytics()
     */
    function getSearchProducts() {
        try {
            const productsInput = document.getElementById('gtm-search-products');

            if (!productsInput) {
                console.warn('⚠️ Input #gtm-search-products not found');
                return [];
            }

            const productsJson = productsInput.value;
            console.log('🔍 Raw products JSON from input:', productsJson);

            if (!productsJson || productsJson === '[]') {
                console.warn('⚠️ Products input is empty');
                return [];
            }

            const products = JSON.parse(productsJson);
            console.log('✅ Products parsed from input:', products.length, 'items');

            if (products.length > 0) {
                console.log('🔍 First product sample:', products[0]);
            }

            return Array.isArray(products) ? products : [];

        } catch (error) {
            console.error('❌ Error parsing products from input:', error);
            return [];
        }
    }

    /**
     * Obtiene el search term desde el input oculto
     */
    function getSearchTermFromInput() {
        const searchInput = document.getElementById('gtm-search-term');
        return searchInput ? searchInput.value : '';
    }

    // =========================================================================
    // HELPER PRINCIPAL
    // =========================================================================
    window.GTMSearchHelper = {

        /**
         * Extrae contexto del usuario desde gtmData (igual que cart-gtm-config.js)
         */
        resolveContext(gtmData) {
            const cartData     = gtmData?.cartData ?? {};
            const customerData = gtmData?.customerData ?? {};
            return {
                user_id:   customerData.user_id ?? '',
                user_type: customerData.user_type ?? '',
                country:   customerData.country ?? '',
                page_type: 'search',
                currency:  cartData.currency || window.GTM_CART_LAST_CURRENCY || 'EUR'
            };
        },

        /**
         * Sincroniza datos del backend (igual que syncFreshCartData)
         */
        async syncFreshSearchData(searchTerm) {
            try {
                const endpoints = getEndpoints();
                const url = new URL(endpoints.gtm.view_search_results, window.location.origin);
                url.searchParams.set('search_term', searchTerm);
                url.searchParams.set('includeCartData', '1');
                url.searchParams.set('includeCustomerData', '1');
                url.searchParams.set('timestamp', Date.now().toString());
                url.searchParams.set('forceRefresh', '1');

                console.log('🔄 Sync GTM Search Data (GET):', url.toString());

                const response = await fetch(url.toString(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    },
                    credentials: 'same-origin',
                    cache: 'no-store'
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

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

                const result = JSON.parse(text);

                if (result.status !== 'success') {
                    throw new Error(result.message || 'Search backend sync failed');
                }

                console.log('✅ GTM search data synchronized');
                return result;

            } catch (error) {
                console.error('❌ Error syncing GTM search data:', error);
                return false;
            }
        },

        /**
         * Track view_search_results event (mismo patrón que trackViewItemList)
         */
        async trackViewSearchResults(searchTerm, items = null) {
            if (!searchTerm) {
                console.warn('⚠️ GTM Search: No search term provided');
                return;
            }

            console.log('🔍 GTM Search: Tracking view_search_results', { searchTerm, itemsCount: items?.length });

            try {
                // 1. Sincronizar datos del backend
                const res = await this.syncFreshSearchData(searchTerm);
                const gtmData = res?.gtmData || {};
                const ctx = this.resolveContext(gtmData);

                // 2. Si no se proporcionan items, obtenerlos
                if (!items || items.length === 0) {
                    items = getSearchProducts();
                    console.log('🔍 Items obtained:', items.length);
                }

                // 3. Preparar payload completo con datos de contexto
                const payload = {
                    search_term: searchTerm,
                    item_list_name: searchTerm,
                    user_id: ctx.user_id,
                    user_type: ctx.user_type,
                    country: ctx.country,
                    page_type: ctx.page_type,
                    ecommerce: {
                        currency: ctx.currency,
                        items: items
                    }
                };

                // 4. Enviar a GTM (usando gtmExecuteWithBackendData directamente)
                if (window.gtmExecuteWithBackendData) {
                    console.log('🔄 Using gtmExecuteWithBackendData for search tracking');
                    const result = await window.gtmExecuteWithBackendData('view_search_results', payload);
                    console.log('✅ Search tracking completed:', result);
                    return result;
                } else if (typeof dataLayer !== 'undefined') {
                    console.log('🔄 Using dataLayer for search tracking');
                    dataLayer.push({
                        event: 'view_search_results',
                        ...payload
                    });
                    return { success: true, fallback: 'dataLayer' };
                } else {
                    console.warn('⚠️ No GTM API available for search tracking');
                    return { success: false, error: 'GTM API not available' };
                }

            } catch (error) {
                console.error('❌ Error tracking search results:', error);

                // Fallback final a dataLayer directo
                if (typeof dataLayer !== 'undefined') {
                    console.log('🔄 Fallback: pushing to dataLayer directly');
                    dataLayer.push({
                        event: 'view_search_results',
                        search_term: searchTerm,
                        item_list_name: `Resultados de búsqueda: ${searchTerm}`,
                        ecommerce: {
                            items: items || []
                        }
                    });
                    return { success: true, fallback: 'dataLayer' };
                }

                throw error;
            }
        },

        /**
         * Auto-track cuando se carga la página de resultados
         */
        async autoTrackSearchResults() {
            if (!isSearchResultsPage()) {
                console.log('ℹ️ Not a search results page, skipping search tracking');
                return;
            }

            const searchTerm = getSearchTermFromInput() || getSearchTermFromURL();

            if (!searchTerm) {
                console.warn('⚠️ Search term not found');
                return;
            }

            console.log('🔍 Search results page detected, search term:', searchTerm);

            // Esperar a que el DOM esté completamente cargado
            await new Promise(resolve => {
                if (document.readyState === 'complete') {
                    resolve();
                } else {
                    window.addEventListener('load', resolve);
                }
            });

            // Pequeño delay para asegurarnos de que los productos estén renderizados
            await new Promise(resolve => setTimeout(resolve, 300));

            // Obtener productos
            const items = getSearchProducts();
            console.log('🔍 Products obtained:', items.length);

            // Track el evento
            await this.trackViewSearchResults(searchTerm, items);
        },

        /**
         * Inicialización
         */
        init() {
            console.log('🔍 GTM Search Helper initialized');

            // Auto-track si estamos en página de resultados
            this.autoTrackSearchResults().catch(err => {
                console.error('❌ Error in auto-track search results:', err);
            });
        }
    };

    // =========================================================================
    // AUTO-INIT
    // =========================================================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.GTMSearchHelper.init();
        });
    } else {
        // DOM ya está listo
        window.GTMSearchHelper.init();
    }

    console.log('✅ GTM Search Config loaded');
})();