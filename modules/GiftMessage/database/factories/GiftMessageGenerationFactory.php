<?php

namespace Modules\GiftMessage\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GiftMessage\Models\GiftMessageGeneration;

class GiftMessageGenerationFactory extends Factory
{
    protected $model = GiftMessageGeneration::class;

    public function definition(): array
    {
        $rowsCount = $this->faker->numberBetween(1, 40);

        return [
            'type' => $this->faker->randomElement(['envelope', 'card']),
            'rows_count' => $rowsCount,
            'order_numbers' => collect(range(1, $rowsCount))
                ->map(fn () => (string) $this->faker->numberBetween(10000, 99999))
                ->all(),
            'file_path' => 'giftmessage/generated/'.$this->faker->uuid().'.pdf',
            'file_name' => 'mensaje-regalo-'.$this->faker->uuid().'.pdf',
            'generated_by' => null,
        ];
    }

    public function envelope(): static
    {
        return $this->state(['type' => 'envelope']);
    }

    public function card(): static
    {
        return $this->state(['type' => 'card']);
    }

    public function generatedBy(): static
    {
        return $this->state(['generated_by' => User::factory()]);
    }
}
