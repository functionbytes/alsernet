<?php

namespace Modules\Supplier\Tests\Unit;

use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Presenters\AiContentPresenter;
use Tests\TestCase;

class AiContentPresenterTest extends TestCase
{
    public function test_format_content_type_returns_name_when_generated_name_present(): void
    {
        $presenter = new AiContentPresenter;
        $content = new AiContent(['generated_name' => 'Foo']);
        $this->assertStringContainsString('Nombre', $presenter->formatContentType($content));
    }

    public function test_format_content_type_returns_completo_when_empty(): void
    {
        $presenter = new AiContentPresenter;
        $content = new AiContent;
        $this->assertSame('Completo', $presenter->formatContentType($content));
    }

    public function test_format_quality_uses_correct_badge_by_score(): void
    {
        $presenter = new AiContentPresenter;
        $this->assertStringContainsString('bg-success', $presenter->formatQuality(85));
        $this->assertStringContainsString('bg-warning', $presenter->formatQuality(60));
        $this->assertStringContainsString('bg-danger', $presenter->formatQuality(30));
    }
}
