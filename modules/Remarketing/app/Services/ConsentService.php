<?php

namespace Modules\Remarketing\Services;

use Illuminate\Support\Facades\DB;
use Modules\Remarketing\Jobs\SendDoubleOptinMailJob;
use Modules\Remarketing\Models\ConsentEvent;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Suppression;

class ConsentService
{
    /**
     * EU member states (ISO 3166-1 alpha-2) + Brazil for GDPR/LGPD geofencing.
     */
    private const GEOFENCED_COUNTRIES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
        'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
        'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'IS', 'LI', 'NO',
        'BR',
    ];

    /**
     * Grant marketing consent to a customer. If in a geofenced country,
     * sends double opt-in email instead of immediately marking as subscribed.
     */
    public function grantConsent(
        Customer $customer,
        string $source,
        ?string $ip = null,
        ?string $ua = null,
        ?string $formUrl = null
    ): void {
        DB::transaction(function () use ($customer, $source, $ip, $ua, $formUrl): void {
            ConsentEvent::query()->create([
                'store_id' => $customer->store_id,
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'event_type' => 'granted',
                'source' => $source,
                'ip' => $ip,
                'user_agent' => $ua,
                'form_url' => $formUrl,
                'occurred_at' => now(),
            ]);

            if ($this->isGeofenced($customer->country)) {
                $this->sendDoubleOptin($customer);

                return;
            }

            $customer->update([
                'consent_marketing' => true,
                'status' => 'subscribed',
            ]);

            ConsentEvent::query()->create([
                'store_id' => $customer->store_id,
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'event_type' => 'confirmed',
                'source' => 'api',
                'occurred_at' => now(),
            ]);
        });
    }

    /**
     * Generate a double opt-in token and dispatch the confirmation email.
     */
    public function sendDoubleOptin(Customer $customer): void
    {
        $token = hash_hmac('sha256', $customer->id.'|'.$customer->email.'|'.time(), config('app.key'));

        $customer->update([
            'double_optin_token' => $token,
            'double_optin_sent_at' => now(),
            'status' => 'pending',
        ]);

        // Dispatch mail via job (placeholder — SendDoubleOptinMailJob created by jobs agent)
        if (class_exists(SendDoubleOptinMailJob::class)) {
            SendDoubleOptinMailJob::dispatch($customer);
        }
    }

    /**
     * Confirm a double opt-in token. Returns the confirmed customer or null on invalid/expired token.
     */
    public function confirmDoubleOptin(string $token): ?Customer
    {
        $customer = Customer::query()
            ->where('double_optin_token', $token)
            ->where('double_optin_sent_at', '>=', now()->subDays(7))
            ->first();

        if (! $customer) {
            return null;
        }

        DB::transaction(function () use ($customer): void {
            $customer->update([
                'consent_marketing' => true,
                'status' => 'subscribed',
                'consent_confirmed_at' => now(),
                'double_optin_token' => null,
            ]);

            ConsentEvent::query()->create([
                'store_id' => $customer->store_id,
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'event_type' => 'confirmed',
                'source' => 'double_optin_confirm',
                'occurred_at' => now(),
            ]);
        });

        return $customer->fresh();
    }

    /**
     * Withdraw consent for a customer and add to suppression list.
     */
    public function withdraw(
        Customer $customer,
        string $source = 'admin',
        ?string $ip = null
    ): void {
        DB::transaction(function () use ($customer, $source, $ip): void {
            $customer->update([
                'consent_marketing' => false,
                'status' => 'unsubscribed',
                'unsubscribed_at' => now(),
            ]);

            Suppression::query()->firstOrCreate(
                ['store_id' => $customer->store_id, 'email' => $customer->email],
                ['reason' => 'unsubscribe']
            );

            ConsentEvent::query()->create([
                'store_id' => $customer->store_id,
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'event_type' => 'withdrawn',
                'source' => $source,
                'ip' => $ip,
                'occurred_at' => now(),
            ]);
        });
    }

    /**
     * Determine if a country requires mandatory double opt-in (GDPR/LGPD).
     */
    public function isGeofenced(?string $country): bool
    {
        if (empty($country)) {
            return false;
        }

        return in_array(strtoupper($country), self::GEOFENCED_COUNTRIES, true);
    }

    /**
     * Check whether an email is on the suppression list for a given store.
     */
    public function isSuppressed(Store $store, string $email): bool
    {
        return Suppression::query()
            ->where('store_id', $store->id)
            ->where('email', strtolower(trim($email)))
            ->exists();
    }
}
