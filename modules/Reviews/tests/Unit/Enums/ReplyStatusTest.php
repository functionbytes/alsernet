<?php

namespace Modules\Reviews\Tests\Unit\Enums;

use Modules\Reviews\Enums\ReplyStatus;
use PHPUnit\Framework\TestCase;

class ReplyStatusTest extends TestCase
{
    public function test_label_returns_human_readable_text(): void
    {
        $this->assertSame('Draft', ReplyStatus::DRAFT->label());
        $this->assertSame('Approved', ReplyStatus::APPROVED->label());
        $this->assertSame('Published', ReplyStatus::PUBLISHED->label());
        $this->assertSame('Failed', ReplyStatus::FAILED->label());
    }

    public function test_color_returns_bootstrap_class(): void
    {
        $this->assertSame('secondary', ReplyStatus::DRAFT->color());
        $this->assertSame('warning', ReplyStatus::APPROVED->color());
        $this->assertSame('success', ReplyStatus::PUBLISHED->color());
        $this->assertSame('danger', ReplyStatus::FAILED->color());
    }

    public function test_all_enum_cases_exist(): void
    {
        $cases = ReplyStatus::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(ReplyStatus::DRAFT, $cases);
        $this->assertContains(ReplyStatus::APPROVED, $cases);
        $this->assertContains(ReplyStatus::PUBLISHED, $cases);
        $this->assertContains(ReplyStatus::FAILED, $cases);
    }

    public function test_enum_values_are_lowercase_strings(): void
    {
        $this->assertSame('draft', ReplyStatus::DRAFT->value);
        $this->assertSame('approved', ReplyStatus::APPROVED->value);
        $this->assertSame('published', ReplyStatus::PUBLISHED->value);
        $this->assertSame('failed', ReplyStatus::FAILED->value);
    }
}
