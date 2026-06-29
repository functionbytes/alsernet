<?php

namespace Modules\Reviews\Tests\Unit\Enums;

use Modules\Reviews\Enums\ConnectionStatus;
use PHPUnit\Framework\TestCase;

class ConnectionStatusTest extends TestCase
{
    public function test_label_returns_human_readable_text(): void
    {
        $this->assertSame('Pending', ConnectionStatus::PENDING->label());
        $this->assertSame('Active', ConnectionStatus::ACTIVE->label());
        $this->assertSame('Expired', ConnectionStatus::EXPIRED->label());
        $this->assertSame('Revoked', ConnectionStatus::REVOKED->label());
        $this->assertSame('Error', ConnectionStatus::ERROR->label());
    }

    public function test_color_returns_bootstrap_class(): void
    {
        $this->assertSame('warning', ConnectionStatus::PENDING->color());
        $this->assertSame('success', ConnectionStatus::ACTIVE->color());
        $this->assertSame('warning', ConnectionStatus::EXPIRED->color());
        $this->assertSame('secondary', ConnectionStatus::REVOKED->color());
        $this->assertSame('danger', ConnectionStatus::ERROR->color());
    }

    public function test_all_enum_cases_exist(): void
    {
        $cases = ConnectionStatus::cases();

        $this->assertCount(5, $cases);
        $this->assertContains(ConnectionStatus::PENDING, $cases);
        $this->assertContains(ConnectionStatus::ACTIVE, $cases);
        $this->assertContains(ConnectionStatus::EXPIRED, $cases);
        $this->assertContains(ConnectionStatus::REVOKED, $cases);
        $this->assertContains(ConnectionStatus::ERROR, $cases);
    }

    public function test_enum_values_are_lowercase_strings(): void
    {
        $this->assertSame('pending', ConnectionStatus::PENDING->value);
        $this->assertSame('active', ConnectionStatus::ACTIVE->value);
        $this->assertSame('expired', ConnectionStatus::EXPIRED->value);
        $this->assertSame('revoked', ConnectionStatus::REVOKED->value);
        $this->assertSame('error', ConnectionStatus::ERROR->value);
    }
}
