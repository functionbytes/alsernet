<?php

namespace Modules\Engagement\Tests\Unit;

use Modules\Engagement\Services\VariantAssigner;
use PHPUnit\Framework\TestCase;

class VariantAssignerTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(VariantAssigner::class));
    }
}
