<?php

namespace Modules\Mailing\Providers;

use Modules\Mailing\Cashier\Services\BraintreePaymentGateway;
use Modules\Mailing\Cashier\Services\CoinpaymentsPaymentGateway;
use Modules\Mailing\Cashier\Services\OfflinePaymentGateway;
use Modules\Mailing\Cashier\Services\PaypalPaymentGateway;
use Modules\Mailing\Cashier\Services\PaystackPaymentGateway;
use Modules\Mailing\Cashier\Services\RazorpayPaymentGateway;
use Modules\Mailing\Cashier\Services\StripePaymentGateway;
use Modules\Mailing\Library\BillingManager;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class CheckoutServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot() {}

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(BillingManager::class, function ($app) {
            $returnUrl = route('subscription.index');
            $manager = new BillingManager($returnUrl);

            // Register payment gateways
            $manager->register('stripe', function () {
                return new StripePaymentGateway;
            });

            $manager->register('braintree', function () {
                return new BraintreePaymentGateway;
            });

            $manager->register('paypal', function () {
                return new PaypalPaymentGateway;
            });

            $manager->register('offline', function () {
                return new OfflinePaymentGateway;
            });

            $manager->register('coinpayments', function () {
                return new CoinpaymentsPaymentGateway;
            });

            $manager->register('paystack', function () {
                return new PaystackPaymentGateway;
            });

            $manager->register('razorpay', function () {
                return new RazorpayPaymentGateway;
            });

            return $manager;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [BillingManager::class];
    }
}
