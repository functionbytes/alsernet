<?php

namespace Modules\Engagement\Tests\Unit;

use Modules\Engagement\Services\SessionLinkService;
use PHPUnit\Framework\TestCase;

class SessionLinkServiceTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(SessionLinkService::class));
    }
}
