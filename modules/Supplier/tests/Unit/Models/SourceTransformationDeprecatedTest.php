<?php

namespace Modules\Supplier\Tests\Unit\Models;

use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Source\SourceTransformation;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SourceTransformationDeprecatedTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function deprecatedTypeProvider(): array
    {
        return [
            'formula' => ['formula'],
            'lookup' => ['lookup'],
            'custom_function' => ['custom_function'],
        ];
    }

    #[DataProvider('deprecatedTypeProvider')]
    public function test_deprecated_transformation_type_passes_value_through(string $type): void
    {
        Log::spy();

        $transformation = new SourceTransformation([
            'transformation_type' => $type,
            'transformation_config' => [],
        ]);

        $this->assertSame('valor', $transformation->apply('valor'));

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Deprecated transformation type skipped', \Mockery::on(
                fn (array $context): bool => $context['type'] === $type
            ));
    }

    public function test_non_deprecated_transformation_type_still_works(): void
    {
        $transformation = new SourceTransformation([
            'transformation_type' => 'mapping',
            'transformation_config' => [
                'mapping' => ['a' => 'A'],
                'default' => 'unknown',
            ],
        ]);

        $this->assertSame('A', $transformation->apply('a'));
        $this->assertSame('unknown', $transformation->apply('z'));
    }
}
