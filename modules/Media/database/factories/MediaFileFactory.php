<?php

namespace Modules\Media\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Media\Models\MediaFile;

class MediaFileFactory extends Factory
{
    protected $model = MediaFile::class;

    public function definition(): array
    {
        return [
            'uid' => (string) Str::ulid(),
            'name' => $this->faker->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'type' => 'image',
            'size' => $this->faker->numberBetween(1000, 10_000_000),
            'url' => '0/'.Str::random(20).'.jpg',
            'folder_id' => null,
            'user_id' => User::factory(),
            'disk' => 'media',
            'file_hash' => hash('sha256', Str::random(40)),
            'metadata' => ['width' => 800, 'height' => 600],
            'visibility' => 'private',
        ];
    }
}
