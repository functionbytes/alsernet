<?php

namespace Modules\Media\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Media\Models\MediaComment;
use Modules\Media\Models\MediaFile;

class MediaCommentFactory extends Factory
{
    protected $model = MediaComment::class;

    public function definition(): array
    {
        return [
            'commentable_id' => MediaFile::factory(),
            'commentable_type' => MediaFile::class,
            'user_id' => User::factory(),
            'parent_id' => null,
            'content' => $this->faker->paragraph(),
            'mentions' => [],
        ];
    }

    public function reply(MediaComment $parent): static
    {
        return $this->state(fn () => [
            'commentable_id' => $parent->commentable_id,
            'commentable_type' => $parent->commentable_type,
            'parent_id' => $parent->id,
        ]);
    }
}
