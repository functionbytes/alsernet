<?php

namespace Modules\Media\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Media\Models\MediaFavorite;
use Modules\Media\Models\MediaFile;

class MediaFavoriteFactory extends Factory
{
    protected $model = MediaFavorite::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'favoritable_id' => MediaFile::factory(),
            'favoritable_type' => MediaFile::class,
        ];
    }
}
