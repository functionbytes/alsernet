/**
 * Wishlist Manager - Manages wishlist operations and updates
 * Similar structure to cart-manager.js
 */
class WishlistManager {
    constructor() {
        this.endpoints = {
            wishlist: {
                add: "/modules/alsernetcustomer/controllers/routes.php?modalitie=wishlist&action=add",
                remove: "/modules/alsernetcustomer/controllers/routes.php?modalitie=wishlist&action=remove",
                cart: "/modules/alsernetcustomer/controllers/routes.php?modalitie=wishlist&action=cart",
                count: "/modules/alsernetcustomer/controllers/routes.php?modalitie=wishlist&action=count"
            }
        };
        this.cache = new Map();
        this.init();
    }

    init() {
        console.log('🔧 WishlistManager initialized');
        this.bindEvents();
    }

    bindEvents() {
        // Bind authentication modal
        $(document).on('click', '.not-auth-wishlist', (e) => {
            this.showAuthModal();
        });

        $(document).on('click', '#authWishlistClose', (e) => {
            $('#modal-not-auth-wishlist').modal('hide');
        });

        // Bind add to wishlist events
        $(document).on('click', '.add-to-wishlist', (e) => {
            e.preventDefault();
            this.addToWishlistButton($(e.currentTarget));
        });

        // Bind remove from wishlist events (dashboard)
        $(document).on('click', '.add-delete-to-wishlist', (e) => {
            e.preventDefault();
            this.removeFromWishlist($(e.currentTarget));
        });

        // Bind remove from wishlist events (product pages)
        $(document).on('click', '.delete-to-wishlist', (e) => {
            e.preventDefault();
            this.removeFromWishlistButton($(e.currentTarget));
        });

        // Bind add to cart from wishlist events
        $(document).on('click', '.add-to-cart-wishlist', (e) => {
            e.preventDefault();
            this.addToCartFromWishlist($(e.currentTarget));
        });

        // Bind Prestashop events
        if (typeof prestashop !== 'undefined') {
            prestashop.on('updateProductList', () => {
                this.rebindWishlistActions();
            });

            prestashop.on('updatedProduct', () => {
                this.rebindWishlistActions();
            });
        }

        // Initial bind
        this.rebindWishlistActions();
    }

    showAuthModal() {
        $('#modal-not-auth-wishlist').modal('show');

        var redirectData = {
            redirect_url: window.location.href
        };

        this.setCookie('wishlist_redirect', encodeURIComponent(JSON.stringify(redirectData)), 1);
    }

    setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + days*24*60*60*1000);
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + value + expires + "; path=/";
    }

    rebindWishlistActions() {
        // This method ensures events are properly bound after Prestashop updates
        console.log('🔄 Rebinding wishlist actions');
    }

    getLanguage() {
        const pathArray = window.location.pathname.split('/');
        return (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : 'es';
    }

    async makeRequest(url, data = {}) {
        try {
            const response = await $.ajax({
                url: url + "&iso=" + this.getLanguage(),
                method: 'POST',
                data: { ...data, iso: this.getLanguage() },
                cache: false
            });
            return response;
        } catch (error) {
            console.error('Wishlist request error:', error);
            throw error;
        }
    }

    async addToWishlist($element) {
        const productData = this.extractProductData($element);

        try {
            const response = await this.makeRequest(this.endpoints.wishlist.add, productData);

            if (response.status === 'success') {
                this.showSuccess(response.message);
                this.updateWishlistCounter();
                $element.addClass('in-wishlist');
            } else {
                this.showError(response.message || 'Error al agregar a wishlist');
            }
        } catch (error) {
            this.showError('Error al agregar producto a wishlist');
        }
    }

    async addToWishlistButton($element) {
        const productData = this.extractProductData($element);

        try {
            const response = await this.makeRequest(this.endpoints.wishlist.add, productData);

            if (response.status === 'success') {
                // Update button state
                if (!$element.hasClass("wishlist-button-stick")) {
                    $element.find('span').text(response.data.title);
                }

                if ($element.hasClass('add-to-wishlist')) {
                    $element.removeClass('add-to-wishlist');
                    $element.addClass('delete-to-wishlist');
                }

                $element.find('i').removeClass().addClass(response.data.icon);

                this.updateWishlistCounter();
                this.showSuccess(response.data.title);
            } else {
                this.showError(response.message || 'Error al agregar a wishlist');
            }
        } catch (error) {
            this.showError('Error al agregar producto a wishlist');
        }
    }

    async removeFromWishlistButton($element) {
        console.log('🗑️ removeFromWishlistButton called');
        const productData = this.extractProductData($element);
        console.log('📦 Product data:', productData);

        try {
            const response = await this.makeRequest(this.endpoints.wishlist.remove, productData);
            console.log('📡 Response received:', response);

            if (response.status === 'success') {
                console.log('✅ Success - updating button state');

                // Update button state
                if (!$element.hasClass("wishlist-button-stick")) {
                    $element.find('span').text(response.data.title);
                }

                if ($element.hasClass('delete-to-wishlist')) {
                    $element.addClass('add-to-wishlist');
                    $element.removeClass('delete-to-wishlist');
                }

                $element.find('i').removeClass().addClass(response.data.icon);

                console.log('🔢 Calling updateWishlistCounter');
                this.updateWishlistCounter();
            } else {
                console.log('❌ Error response:', response);
                this.showError(response.message || 'Error al eliminar de wishlist');
            }
        } catch (error) {
            console.log('💥 Exception:', error);
            this.showError('Error al eliminar producto de wishlist');
        }
    }

    async removeFromWishlist($element) {
        const productData = this.extractProductData($element);
        const $wishlistItem = $element.closest('.wishlist-item');

        try {
            const response = await this.makeRequest(this.endpoints.wishlist.remove, productData);

            if (response.status === 'success') {
                this.showSuccess(response.message);
                this.updateWishlistCounter();

                // Remove item from DOM
                $wishlistItem.fadeOut(300, function () {
                    $(this).remove();
                    if ($('.wishlist-item').length === 0) {
                        $('.wishlist-container').addClass('d-none');
                        $('.wishlist-empty-container').removeClass('d-none');
                    }
                });

                $element.removeClass('in-wishlist');
            } else {
                this.showError(response.message || 'Error al eliminar de wishlist');
            }
        } catch (error) {
            this.showError('Error al eliminar producto de wishlist');
        }
    }

    async addToCartFromWishlist($element) {
        const productData = this.extractProductData($element);

        try {
            const response = await this.makeRequest(this.endpoints.wishlist.cart, productData);

            if (response.status === 'success') {
                this.showSuccess(response.message);
                window.reloadCarts();

                // Update cart counter
                /*if (window.updateCartCounter && typeof window.updateCartCounter === 'function') {
                    window.updateCartCounter();
                } else if (window.reloadCarts && typeof window.reloadCarts === 'function') {
                    window.reloadCarts();
                }*/
            } else {
                this.showWarning(response.message || 'Advertencia al agregar al carrito');
            }
        } catch (error) {
            this.showError('Error al agregar producto al carrito');
        }
    }

    extractProductData($element) {
        return {
            id_product: $element.data('id-product'),
            id_wishlist_product: $element.data('id-product'),
            id_wishlist: $element.data('id-wishlist'),
            id_product_attribute: $element.data('id-product-attribute') || 0
        };
    }

    async updateWishlistCounter() {
        console.log('🔢 updateWishlistCounter method called');
        try {
            console.log('📡 Making request to:', this.endpoints.wishlist.count);
            const response = await this.makeRequest(this.endpoints.wishlist.count);
            console.log('📡 Counter response received:', response);

            if (response && response.count !== undefined) {
                console.log('🎯 Updating counter elements with:', response.count);
                $('.wishlist-count, .wishlist-counter').text(response.count);
                console.log('✅ Wishlist counter updated to:', response.count);

                // Debug: check how many elements were found
                const elements = $('.wishlist-count, .wishlist-counter');
                console.log('🔍 Found', elements.length, 'counter elements');
                elements.each(function(i) {
                    console.log('🔍 Element', i, ':', this.className, 'text:', $(this).text());
                });
            } else {
                console.log('⚠️ No count in response or response is undefined');
            }
        } catch (error) {
            console.error('❌ Error updating wishlist counter:', error);
        }
    }

    showSuccess(message) {
        if (window.toastr) {
            toastr.success(null, message, {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-bottom-right'
            });
        }
    }

    showError(message) {
        if (window.toastr) {
            toastr.error(null, message, {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-bottom-right'
            });
        }
    }

    showWarning(message) {
        if (window.toastr) {
            toastr.warning(null, message, {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-bottom-right'
            });
        }
    }

    clearCache() {
        this.cache.clear();
        console.log('🧹 Wishlist cache cleared');
    }
}

// Initialize wishlist manager
window.wishlist = new WishlistManager();

// Global function to update wishlist counter
window.updateWishlistCounter = function() {
    console.log('🔢 updateWishlistCounter called');
    if (window.wishlist) {
        window.wishlist.updateWishlistCounter();
    }
};

// Global function to reload wishlist
window.reloadWishlist = function() {
    console.log('🔄 reloadWishlist called');
    if (window.wishlist) {
        window.wishlist.updateWishlistCounter();
    }
};

console.log('🔧 Wishlist Manager loaded successfully');