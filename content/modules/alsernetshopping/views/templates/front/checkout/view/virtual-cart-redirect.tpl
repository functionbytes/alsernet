{*
* Virtual Cart Redirect Template
* Handles redirection for virtual carts that don't need delivery
*}

{if $is_virtual_cart}
    <div class="virtual-cart-redirect">
        <div class="checkout-step-loading">
            <div class="loading-message">
                <h3>{l s='Processing your virtual order...' mod='alsernetshopping'}</h3>
                <p>{l s='Since your cart contains only digital products, we are redirecting you directly to payment.' mod='alsernetshopping'}</p>
                <div class="loading-spinner">
                    <i class="material-icons rotating">refresh</i>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        // Use jQuery for better compatibility
        $(document).ready(function() {
            console.log('Virtual cart detected - redirecting to payment');
            setTimeout(function() {
                if (typeof window.checkoutNavigator !== 'undefined' && window.checkoutNavigator.loadCheckoutStep) {
                    window.checkoutNavigator.loadCheckoutStep('payment', true, true);
                }
            }, 500);
        });
    </script>

    <style>
        .virtual-cart-redirect {
            text-align: center;
            padding: 60px 20px;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loading-message h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .loading-message p {
            margin-bottom: 30px;
            color: #666;
            font-size: 16px;
        }

        .loading-spinner {
            font-size: 24px;
            color: #007cba;
        }

        .rotating {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
{/if}