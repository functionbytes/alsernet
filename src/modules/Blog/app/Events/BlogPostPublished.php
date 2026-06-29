<?php

namespace Modules\Blog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Blog\Models\BlogPost;

class BlogPostPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly BlogPost $post
    ) {}
}
