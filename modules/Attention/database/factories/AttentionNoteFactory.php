<?php

namespace Modules\Attention\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionNote;

class AttentionNoteFactory extends Factory
{
    protected $model = AttentionNote::class;

    public function definition(): array
    {
        return [
            'attention_id' => Attention::factory(),
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'content' => $this->faker->paragraph(2),
        ];
    }
}
