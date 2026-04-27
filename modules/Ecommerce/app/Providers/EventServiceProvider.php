<?php

namespace Modules\Ecommerce\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Ecommerce\Events\CustomerEmailVerified;
use Modules\Ecommerce\Events\CustomerRegistered;
use Modules\Ecommerce\Events\OrderCancelled;
use Modules\Ecommerce\Events\OrderCompleted;
use Modules\Ecommerce\Events\OrderCreated;
use Modules\Ecommerce\Events\OrderPaymentConfirmed;
use Modules\Ecommerce\Events\OrderPlaced;
use Modules\Ecommerce\Events\ProductFileUpdated;
use Modules\Ecommerce\Events\ProductVariationCreated;
use Modules\Ecommerce\Events\ProductViewed;
use Modules\Ecommerce\Events\ShippingStatusChanged;
use Modules\Ecommerce\Listeners\AbandonedCartScheduler;
use Modules\Ecommerce\Listeners\AddLanguageForVariantsListener;
use Modules\Ecommerce\Listeners\ClearShippingRuleCache;
use Modules\Ecommerce\Listeners\GenerateInvoiceListener;
use Modules\Ecommerce\Listeners\GenerateLicenseCodeAfterOrderCompleted;
use Modules\Ecommerce\Listeners\OrderCancelledNotification;
use Modules\Ecommerce\Listeners\OrderCreatedNotification;
use Modules\Ecommerce\Listeners\OrderPaymentConfirmedNotification;
use Modules\Ecommerce\Listeners\RegisterAffiliateReferral;
use Modules\Ecommerce\Listeners\SaveProductFaqListener;
use Modules\Ecommerce\Listeners\SendMailsAfterCustomerEmailVerified;
use Modules\Ecommerce\Listeners\SendMailsAfterCustomerRegistered;
use Modules\Ecommerce\Listeners\SendProductFileUpdatedNotification;
use Modules\Ecommerce\Listeners\SendProductReviewsMailAfterOrderCompleted;
use Modules\Ecommerce\Listeners\SendShippingStatusChangedNotification;
use Modules\Ecommerce\Listeners\SendWebhookWhenOrderCancelled;
use Modules\Ecommerce\Listeners\SendWebhookWhenOrderPaid;
use Modules\Ecommerce\Listeners\SendWebhookWhenOrderPlaced;
use Modules\Ecommerce\Listeners\SendWebhookWhenShipmentUpdated;
use Modules\Ecommerce\Listeners\UpdateInvoiceAndShippingWhenOrderCancelled;
use Modules\Ecommerce\Listeners\UpdateInvoiceWhenOrderCompleted;
use Modules\Ecommerce\Listeners\UpdateInvoiceWhenPaymentConfirmed;
use Modules\Ecommerce\Listeners\UpdateProductStockStatus;
use Modules\Ecommerce\Listeners\UpdateProductVariationInfo;
use Modules\Ecommerce\Listeners\UpdateProductView;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CustomerRegistered::class => [
            SendMailsAfterCustomerRegistered::class,
        ],
        CustomerEmailVerified::class => [
            SendMailsAfterCustomerEmailVerified::class,
        ],
        OrderPlaced::class => [
            UpdateProductStockStatus::class,
            GenerateInvoiceListener::class,
            AbandonedCartScheduler::class,
        ],
        OrderCreated::class => [
            OrderCreatedNotification::class,
            SendWebhookWhenOrderPlaced::class,
            RegisterAffiliateReferral::class,
        ],
        OrderCompleted::class => [
            UpdateInvoiceWhenOrderCompleted::class,
            GenerateLicenseCodeAfterOrderCompleted::class,
            SendProductReviewsMailAfterOrderCompleted::class,
        ],
        OrderPaymentConfirmed::class => [
            OrderPaymentConfirmedNotification::class,
            UpdateInvoiceWhenPaymentConfirmed::class,
            SendWebhookWhenOrderPaid::class,
        ],
        OrderCancelled::class => [
            OrderCancelledNotification::class,
            UpdateInvoiceAndShippingWhenOrderCancelled::class,
            ClearShippingRuleCache::class,
            SendWebhookWhenOrderCancelled::class,
        ],
        ShippingStatusChanged::class => [
            SendShippingStatusChangedNotification::class,
            SendWebhookWhenShipmentUpdated::class,
        ],
        ProductViewed::class => [
            UpdateProductView::class,
        ],
        ProductVariationCreated::class => [
            UpdateProductVariationInfo::class,
            AddLanguageForVariantsListener::class,
        ],
        ProductFileUpdated::class => [
            SendProductFileUpdatedNotification::class,
            SaveProductFaqListener::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
