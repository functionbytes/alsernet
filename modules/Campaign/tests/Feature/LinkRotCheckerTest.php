<?php

namespace Modules\Campaign\Tests\Feature;

use Modules\Campaign\Services\LinkRotChecker;
use Tests\TestCase;

class LinkRotCheckerTest extends TestCase
{
    public function test_extracts_urls_from_html(): void
    {
        $checker = new LinkRotChecker;
        $results = $checker->check('<a href="https://httpbin.org/status/200">OK</a><a href="https://httpbin.org/status/404">Broken</a>', timeout: 15);

        $this->assertCount(2, $results);
    }

    public function test_detects_broken_links(): void
    {
        $checker = new LinkRotChecker;
        $hasBroken = $checker->hasBrokenLinks('<a href="https://httpbin.org/status/404">Broken</a>');

        $this->assertTrue($hasBroken);
    }

    public function test_no_broken_links_for_200(): void
    {
        $checker = new LinkRotChecker;
        $hasBroken = $checker->hasBrokenLinks('<a href="https://httpbin.org/status/200">OK</a>');

        $this->assertFalse($hasBroken);
    }
}
