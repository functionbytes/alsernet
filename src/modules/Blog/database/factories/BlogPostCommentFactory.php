<?php

namespace Modules\Blog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Blog\Enums\CommentStatus;
use Modules\Blog\Models\BlogPostComment;

class BlogPostCommentFactory extends Factory
{
    protected $model = BlogPostComment::class;

    public function definition(): array
    {
        return [
            'author_name' => $this->faker->name(),
            'author_email' => $this->faker->safeEmail(),
            'content' => $this->faker->paragraph(),
            'status' => CommentStatus::Approved,
            'parent_id' => null,
            'ip_address' => $this->faker->ipv4(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => CommentStatus::Pending]);
    }

    public function spam(): static
    {
        return $this->state(['status' => CommentStatus::Spam]);
    }
}
