<?php

namespace Modules\Chat\Jobs\Webhooks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Models\Customers\Customer;

class FetchCustomerProfileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public array $backoff = [30, 60];

    /**
     * Create a new job instance.
     *
     * @param  Customer  $customer  The customer to update with profile data
     * @param  string  $facebookUserId  The Facebook user ID to fetch profile from
     * @param  string  $accessToken  The page access token for Graph API
     */
    public function __construct(
        public Customer $customer,
        public string $facebookUserId,
        public string $accessToken
    ) {}

    /**
     * Execute the job.
     *
     * Fetches Facebook user profile asynchronously and updates customer record.
     * This runs AFTER the message has been processed, so it doesn't block webhook response.
     */
    public function handle(): void
    {
        try {
            $response = Http::timeout(10)->get('https://graph.facebook.com/v24.0/'.$this->facebookUserId, [
                'access_token' => $this->accessToken,
                'fields' => 'first_name,last_name,email,profile_pic',
            ]);

            if ($response->failed()) {
                Log::warning('Facebook profile fetch failed (async)', [
                    'customer_id' => $this->customer->id,
                    'facebook_user_id' => $this->facebookUserId,
                    'status' => $response->status(),
                ]);

                return;
            }

            $profile = $response->json();

            // Build full name from profile
            $fullName = $profile['first_name'] ?? 'Facebook User';
            if (isset($profile['last_name'])) {
                $fullName .= ' '.$profile['last_name'];
            }

            // Update customer with real profile data
            $this->customer->update([
                'name' => $fullName,
                'email' => $profile['email'] ?? $this->customer->email,
                'avatar_url' => $profile['profile_pic'] ?? $this->customer->avatar_url,
            ]);

            Log::info('Customer profile updated from Facebook (async)', [
                'customer_id' => $this->customer->id,
                'facebook_user_id' => $this->facebookUserId,
                'name' => $fullName,
            ]);

        } catch (\Exception $e) {
            Log::error('Exception fetching Facebook profile (async)', [
                'customer_id' => $this->customer->id,
                'facebook_user_id' => $this->facebookUserId,
                'error' => $e->getMessage(),
            ]);

            // Don't fail the job - profile fetch is nice-to-have
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(static::class.' failed', [
            'customer_id' => $this->customer->id,
            'facebook_user_id' => $this->facebookUserId,
            'error' => $exception->getMessage(),
        ]);
    }
}
