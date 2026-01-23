<?php

namespace Modules\Subscriber\Jobs\Subscribers;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Subscriber\Models\Subscriber;

class SubscriberCategoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $subscriber;

    protected $categories;

    protected $type;

    public function __construct(Subscriber $subscriber, $categories, $type)
    {
        $this->subscriber = $subscriber;
        $this->categories = $categories;
        $this->type = $type;
    }

    public function handle()
    {
        $this->subscriber->suscriberCategoriesWithLog(
            $this->categories,
            $this->type,
        );
    }
}
