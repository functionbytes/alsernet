<?php

namespace Modules\Subscriber\Traits;

use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Trait HasSubscriptionManagement
 *
 * Gestiona todo lo relacionado con subscripciones, planes, billing,
 * invoices, payment methods, y lógica de SAAS.
 */
trait HasSubscriptionManagement
{
    /**
     * Get user's subscriptions
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany('App\Models\Subscription');
    }

    /**
     * Get general subscriptions
     */
    public function generalSubscriptions()
    {
        return $this->subscriptions()->general();
    }

    /**
     * Get new general subscription
     */
    public function getNewGeneralSubscription()
    {
        // ONLY one new subscription allowed
        $subscriptions = $this->generalSubscriptions()->new();

        if ($subscriptions->count() > 1) {
            throw new Exception('There are 2 subscriptions of [new] status. Please clean up the DB');
        }

        return $subscriptions->first();
    }

    /**
     * Get last cancelled or ended general subscription
     */
    public function getLastCancelledOrEndedGeneralSubscription()
    {
        return $this->generalSubscriptions()
            ->cancelledOrEdned()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get current active subscription (alias)
     */
    public function getCurrentActiveSubscription()
    {
        return $this->getCurrentActiveGeneralSubscription();
    }

    /**
     * Get current active general subscription
     */
    public function getCurrentActiveGeneralSubscription()
    {
        if (! config('app.saas')) {
            throw new Exception('Operation not allowed in NON-SAAS mode');
        }

        $subscriptions = $this->generalSubscriptions()->active();

        if ($subscriptions->count() > 1) {
            throw new Exception('There are 2 subscriptions of [active] status. Please clean up the DB');
        }

        return $subscriptions->first();
    }

    /**
     * Get new or active general subscription
     */
    public function getNewOrActiveGeneralSubscription()
    {
        $subscriptions = $this->generalSubscriptions()->newOrActive();

        if ($subscriptions->count() > 1) {
            throw new Exception('There are 2 subscriptions of [active, new] status. Please clean up the DB');
        }

        return $subscriptions->first();
    }

    /**
     * Check if custom tracking domain is required
     */
    public function isCustomTrackingDomainRequired(): bool
    {
        if (! config('app.saas')) {
            return false;
        }

        $plan = $this->getNewOrActiveGeneralSubscription()->planGeneral;

        if (! $plan) {
            return false;
        }

        return $plan->isCustomTrackingDomainRequired();
    }

    /**
     * Assign general plan to customer
     */
    public function assignGeneralPlan($plan)
    {
        if ($this->getNewOrActiveGeneralSubscription()) {
            throw new \Exception(trans('messages.customer.already_new_active_sub'));
        }

        $subscription = \DB::transaction(function () use ($plan) {
            $subscription = \App\Models\Subscription::createNewSubscription($this, $plan);

            if ($plan->hasTrial()) {
                $invoice = $subscription->createNewSubscriptionInvoiceWithTrial();
            } else {
                $invoice = $subscription->createNewSubscriptionInvoiceWithoutTrial();
            }

            $subscription->setDefaultEmailCredits();

            \App\Facades\SubscriptionFacade::log($subscription, \App\Models\SubscriptionLog::TYPE_SELECT_PLAN, $invoice->uid, [
                'plan' => $subscription->getPlanName(),
                'customer' => $subscription->getCustomerName(),
                'amount' => $invoice->total(),
            ]);

            return $subscription;
        });

        return $subscription;
    }

    /**
     * Check if customer has subscription notice
     */
    public function hasSubscriptionNotice(): bool
    {
        if (! config('app.saas')) {
            return false;
        }

        $subscription = $this->getNewOrActiveGeneralSubscription();

        if (is_null($subscription)) {
            return false;
        }

        if ($subscription->getUnpaidInvoice()) {
            return true;
        }

        return false;
    }

    /**
     * Get user's invoices
     */
    public function invoices(): HasMany
    {
        return $this->hasMany('App\Models\Invoice');
    }

    /**
     * Get user's products
     */
    public function products(): HasMany
    {
        return $this->hasMany('App\Models\Product');
    }

    /**
     * Get billing addresses
     */
    public function billingAddresses(): HasMany
    {
        return $this->hasMany('App\Models\BillingAddress');
    }

    /**
     * Create new billing address
     */
    public function newBillingAddress()
    {
        $address = new \App\Models\BillingAddress;
        $address->customer_id = $this->id;

        return $address;
    }

    /**
     * Get default billing address
     */
    public function getDefaultBillingAddress()
    {
        return $this->billingAddresses()->first();
    }

    /**
     * Update billing information from invoice
     */
    public function updateBillingInformationFromInvoice($invoice): void
    {
        $billingAddress = $this->getDefaultBillingAddress();

        if (! $billingAddress) {
            $billingAddress = $this->newBillingAddress();
        }

        $billingAddress->fill([
            'first_name' => $invoice->billing_first_name,
            'last_name' => $invoice->billing_last_name,
            'address' => $invoice->billing_address,
            'email' => $invoice->billing_email,
            'phone' => $invoice->billing_phone,
            'country_id' => $invoice->billing_country_id,
        ]);

        $billingAddress->save();
    }

    /**
     * Get preferred payment gateway
     */
    public function getPreferredPaymentGateway()
    {
        if (! $this['payment_method']) {
            return null;
        }

        $meta = json_decode($this['payment_method'], true);

        if (! array_key_exists('method', $meta)) {
            throw new Exception("The 'method' key is required for 'preferred payment' data");
        }

        $type = $meta['method'];

        if ($type && \App\Billing::isGatewayRegistered($type)) {
            return \App\Billing::getGateway($type);
        } else {
            return null;
        }
    }

    /**
     * Update payment method
     */
    public function updatePaymentMethod($data = []): void
    {
        $this['payment_method'] = json_encode($data);
        $this->save();
    }

    /**
     * Remove payment method
     */
    public function removePaymentMethod(): void
    {
        $this->payment_method = null;
        $this->save();
    }

    /**
     * Get auto billing data
     */
    public function getAutoBillingData()
    {
        if ($this->auto_billing_data == null) {
            return null;
        }

        return \App\Models\AutoBillingData::fromJson($this->auto_billing_data);
    }

    /**
     * Set auto billing data
     */
    public function setAutoBillingData(\App\Models\AutoBillingData $autoBillingData): void
    {
        $this->auto_billing_data = $autoBillingData->toJson();
        $this->save();
    }

    /**
     * Check if preferred payment gateway can auto charge
     */
    public function preferredPaymentGatewayCanAutoCharge(): bool
    {
        if ($this->getPreferredPaymentGateway() == null) {
            return false;
        }

        if (! $this->getPreferredPaymentGateway()->supportsAutoBilling()) {
            return false;
        }

        if ($this->getAutoBillingData() == null || $this->getAutoBillingData()->getGateway() == null) {
            return false;
        }

        if ($this->getPreferredPaymentGateway()->getType() != $this->getAutoBillingData()->getGateway()->getType()) {
            return false;
        }

        return true;
    }

    /**
     * Create new product
     */
    public function newProduct()
    {
        $product = new \App\Models\Product;
        $product->customer_id = $this->id;

        return $product;
    }

    /**
     * Customers count by time (static)
     */
    public static function customersCountByTime($begin, $end, $admin = null): int
    {
        $query = \Modules\Customer\Models\Customer::select('customers.*');

        if (isset($admin) && ! $admin->can('readAll', new \Modules\Customer\Models\Customer)) {
            $query = $query->where('customers.admin_id', '=', $admin->id);
        }

        $query = $query->where('customers.created_at', '>=', $begin)
            ->where('customers.created_at', '<=', $end);

        return $query->count();
    }

    /**
     * Get select2 options for customers
     */
    public static function select2($request): string
    {
        $data = ['items' => [], 'more' => true];

        $query = self::select('customers.*')
            ->leftJoin('users', 'users.customer_id', '=', 'customers.id');

        if (isset($request->q)) {
            $keyword = $request->q;
            $query = $query->where(function ($q) use ($keyword) {
                $q->orwhere('users.first_name', 'like', '%'.$keyword.'%')
                    ->orWhere('users.last_name', 'like', '%'.$keyword.'%')
                    ->orWhere('users.email', 'like', '%'.$keyword.'%');
            });
        }

        if (! $request->user()->admin->can('readAll', new \Modules\Customer\Models\Customer)) {
            $query = $query->where('customers.admin_id', '=', $request->user()->admin->id);
        }

        foreach ($query->limit(20)->get() as $customer) {
            $data['items'][] = ['id' => $customer->uid, 'text' => $customer->displayNameEmailOption()];
        }

        return json_encode($data);
    }

    /**
     * Create account and user
     */
    public function createAccountAndUser($request)
    {
        $user = new \App\Models\User;

        DB::transaction(function () use ($request, &$user) {
            $this->fill($request->all());
            $this->status = self::STATUS_ACTIVE;
            $this->save();

            $user = new \App\Models\User;
            $user->fill($request->all());
            $user->password = bcrypt($request->password);
            $user->customer()->associate($this);
            $user->save();
        });

        return $user;
    }

    /**
     * Delete account (keep user if has admin)
     */
    public function deleteAccount(): void
    {
        DB::transaction(function () {
            $user = $this->user;
            $this->delete();
            if (! $user->admin()->exists()) {
                $user->deleteAndCleanup();
            }
        });
    }

    /**
     * Create new customer instance
     */
    public static function newCustomer()
    {
        $customer = new self;
        $customer->menu_layout = \Modules\Core\Models\Setting::get('layout.menu_bar');

        return $customer;
    }
}
