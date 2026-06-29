<?php

namespace Tests\Feature;

use Tests\TestCase;

class PingTest extends TestCase
{
    public function test_app_boots(): void
    {
        $this->assertTrue(true);
    }

    public function test_basic_get(): void
    {
        $response = $this->get('/login');
        $this->assertNotEmpty($response->getContent());
    }
}
