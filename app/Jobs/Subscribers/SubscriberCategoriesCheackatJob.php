<?php

namespace App\Jobs\Subscribers;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Subscriber\Subscriber;
use Illuminate\Bus\Queueable;

class SubscriberCategoriesCheackatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $subscriber;
    protected $categories;

    public function __construct(Subscriber $subscriber,  $categories)
    {
        $this->subscriber = $subscriber;
        $this->categories = $categories;
    }
    public function handle()
    {
        $this->subscriber->suscriberCategoriesCheackatWithLog(
            $this->categories,
        );
    }
}
