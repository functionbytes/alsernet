<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create and authenticate a manager user
     */
    protected function actingAsManager(?User $user = null): self
    {
        $user ??= User::factory()->create();

        return $this->actingAs($user);
    }
}
