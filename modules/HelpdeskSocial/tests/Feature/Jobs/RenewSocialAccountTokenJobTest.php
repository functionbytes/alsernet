<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskSocial\Jobs\RenewSocialAccountTokenJob;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Services\Channels\MetaApiClient;
use Modules\HelpdeskSocial\Tests\TestCase;
use RuntimeException;

class RenewSocialAccountTokenJobTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'helpdesksocial.integrations.meta.app_id' => 'app_1',
            'helpdesksocial.integrations.meta.app_secret' => 'secret_1',
        ]);
    }

    private function makeAccount(): SocialAccount
    {
        return SocialAccount::factory()->create([
            'platform' => 'facebook',
            'external_id' => 'page_1',
            'is_active' => true,
            'user_access_token' => 'user_token_old',
            'page_access_token' => 'page_token_old',
        ]);
    }

    public function test_renews_tokens_exchanging_the_user_access_token(): void
    {
        Http::fake([
            '*/oauth/access_token*' => Http::response(['access_token' => 'user_token_new'], 200),
            '*/page_1*' => Http::response(['access_token' => 'page_token_new'], 200),
        ]);

        $account = $this->makeAccount();

        (new RenewSocialAccountTokenJob($account->id))->handle(new MetaApiClient);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'oauth/access_token')
            && $request['fb_exchange_token'] === 'user_token_old');

        $account->refresh();
        $this->assertSame('user_token_new', $account->user_access_token);
        $this->assertSame('page_token_new', $account->page_access_token);
    }

    public function test_throws_when_exchange_fails_so_retries_apply(): void
    {
        Http::fake(['*' => Http::response(['error' => ['code' => 190]], 400)]);

        $account = $this->makeAccount();

        $this->expectException(RuntimeException::class);

        (new RenewSocialAccountTokenJob($account->id))->handle(new MetaApiClient);
    }

    public function test_failed_hook_records_failure_on_the_account(): void
    {
        $account = $this->makeAccount();

        (new RenewSocialAccountTokenJob($account->id))->failed(new RuntimeException('boom'));

        $this->assertSame(1, $account->fresh()->consecutive_failures);
    }
}
