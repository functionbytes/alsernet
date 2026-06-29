<?php

namespace Modules\HelpdeskSocial\Tests;

use Modules\HelpdeskSocial\Database\Seeders\HelpdeskSocialPermissionsSeeder;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Boot the real production permission seeder so the suite exercises the
        // exact permission names shipped to production (dot notation), instead of
        // inventing ad-hoc names that mask permission drift.
        $this->seed(HelpdeskSocialPermissionsSeeder::class);
    }
}
