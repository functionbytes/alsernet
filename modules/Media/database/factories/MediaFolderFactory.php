<?php

namespace Modules\Media\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Media\Models\MediaFolder;

class MediaFolderFactory extends Factory
{
    protected $model = MediaFolder::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'uid' => (string) Str::ulid(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'parent_id' => null,
            'user_id' => User::factory(),
            'color' => '#3498db',
            'disk' => 'media',
        ];
    }
}
