<?php

namespace Modules\HelpdeskTranslate\Tests\Unit;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskTranslate\Http\Concerns\EnforcesTranslationQuota;
use Tests\TestCase;

class TranslationQuotaTest extends TestCase
{
    private function enforcer(): object
    {
        return new class
        {
            use EnforcesTranslationQuota;

            public function run(Request $request, int $chars): void
            {
                $this->enforceDailyCharacterQuota($request, $chars);
            }
        };
    }

    private function requestForUser(int $userId): Request
    {
        $request = Request::create('/translate', 'POST');
        $request->setUserResolver(fn () => (object) ['id' => $userId]);

        return $request;
    }

    public function test_it_allows_requests_under_the_daily_limit(): void
    {
        config()->set('helpdesktranslate.daily_char_limit', 1000);

        $enforcer = $this->enforcer();
        $request = $this->requestForUser(1);

        $enforcer->run($request, 400);
        $enforcer->run($request, 400);

        $this->assertSame(800, (int) Cache::get('helpdesktranslate:chars:1:'.now()->format('Ymd')));
    }

    public function test_it_blocks_when_the_daily_limit_is_exceeded(): void
    {
        config()->set('helpdesktranslate.daily_char_limit', 1000);

        $enforcer = $this->enforcer();
        $request = $this->requestForUser(2);

        $enforcer->run($request, 900);

        try {
            $enforcer->run($request, 200);
            $this->fail('Expected the quota to be exceeded.');
        } catch (HttpResponseException $e) {
            $this->assertSame(429, $e->getResponse()->getStatusCode());
        }

        // El intento rechazado no debe consumir cupo.
        $this->assertSame(900, (int) Cache::get('helpdesktranslate:chars:2:'.now()->format('Ymd')));
    }

    public function test_the_quota_is_isolated_per_user(): void
    {
        config()->set('helpdesktranslate.daily_char_limit', 1000);

        $enforcer = $this->enforcer();

        $enforcer->run($this->requestForUser(10), 900);
        $enforcer->run($this->requestForUser(11), 900);

        $this->assertSame(900, (int) Cache::get('helpdesktranslate:chars:10:'.now()->format('Ymd')));
        $this->assertSame(900, (int) Cache::get('helpdesktranslate:chars:11:'.now()->format('Ymd')));
    }

    public function test_a_zero_limit_disables_the_cap(): void
    {
        config()->set('helpdesktranslate.daily_char_limit', 0);

        $enforcer = $this->enforcer();

        $enforcer->run($this->requestForUser(3), 5_000_000);

        $this->assertNull(Cache::get('helpdesktranslate:chars:3:'.now()->format('Ymd')));
        $this->addToAssertionCount(1);
    }
}
