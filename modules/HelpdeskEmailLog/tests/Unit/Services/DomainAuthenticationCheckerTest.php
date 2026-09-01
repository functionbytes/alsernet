<?php

namespace Modules\HelpdeskEmailLog\Tests\Unit\Services;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskEmailLog\Services\DomainAuthenticationChecker;
use Tests\TestCase;

/**
 * lookupTxt() está aislado en su propio método protected precisamente para
 * poder sobreescribirlo con registros TXT enlatados — sin depender de DNS
 * real, lo que haría el test lento y no determinista.
 */
class DomainAuthenticationCheckerTest extends TestCase
{
    private function makeChecker(array $records): DomainAuthenticationChecker
    {
        Cache::flush();

        return new class($records) extends DomainAuthenticationChecker
        {
            public function __construct(private readonly array $canned) {}

            protected function lookupTxt(string $host): array
            {
                return $this->canned[$host] ?? [];
            }
        };
    }

    public function test_detects_valid_spf_record(): void
    {
        $checker = $this->makeChecker([
            'example.test' => ['v=spf1 include:_spf.example.com -all'],
        ]);

        $result = $checker->check('example.test');

        $this->assertSame('pass', $result['spf']['status']);
    }

    public function test_reports_spf_missing_when_no_matching_record(): void
    {
        $checker = $this->makeChecker(['example.test' => ['some other txt record']]);

        $result = $checker->check('example.test');

        $this->assertSame('missing', $result['spf']['status']);
    }

    public function test_detects_dmarc_with_enforcement_policy(): void
    {
        $checker = $this->makeChecker([
            '_dmarc.example.test' => ['v=DMARC1; p=quarantine; rua=mailto:dmarc@example.test'],
        ]);

        $result = $checker->check('example.test');

        $this->assertSame('pass', $result['dmarc']['status']);
        $this->assertSame('quarantine', $result['dmarc']['policy']);
    }

    public function test_dmarc_policy_none_is_reported_as_warning_not_pass(): void
    {
        $checker = $this->makeChecker([
            '_dmarc.example.test' => ['v=DMARC1; p=none'],
        ]);

        $result = $checker->check('example.test');

        $this->assertSame('warning', $result['dmarc']['status']);
        $this->assertSame('none', $result['dmarc']['policy']);
    }

    public function test_dkim_passes_with_a_configured_selector(): void
    {
        $checker = $this->makeChecker([
            'myselector._domainkey.example.test' => ['v=DKIM1; k=rsa; p=MIGfMA0...'],
        ]);

        $result = $checker->check('example.test', ['myselector']);

        $this->assertSame('pass', $result['dkim']['status']);
        $this->assertSame('myselector', $result['dkim']['selector']);
    }

    public function test_dkim_is_unknown_not_fail_when_no_selector_responds(): void
    {
        $checker = $this->makeChecker([]);

        $result = $checker->check('example.test', ['myselector']);

        $this->assertSame('unknown', $result['dkim']['status']);
        $this->assertNotSame('fail', $result['dkim']['status']);
    }

    public function test_results_are_cached_between_calls(): void
    {
        Cache::flush();

        $checker = new class extends DomainAuthenticationChecker
        {
            public int $calls = 0;

            protected function lookupTxt(string $host): array
            {
                $this->calls++;

                return [];
            }
        };

        $checker->check('cached.test');
        $callsAfterFirstCheck = $checker->calls;
        $checker->check('cached.test');

        // Sin selectores DKIM configurados, checkDkim() prueba varios
        // selectores comunes además de spf+dmarc — el número exacto de
        // lookups de la primera llamada no es lo relevante aquí (es un
        // detalle de DomainAuthenticationChecker::checkDkim(), no de este
        // test); lo que prueba el caché es que la SEGUNDA llamada a check()
        // no añade ningún lookup más.
        $this->assertGreaterThan(0, $callsAfterFirstCheck);
        $this->assertSame($callsAfterFirstCheck, $checker->calls);
    }
}
