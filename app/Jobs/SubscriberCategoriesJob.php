<?php

namespace App\Jobs;

use App\Models\Subscriber\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubscriberCategoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $subscriber;
    protected $categories;

    /**
     * Create a new job instance.
     */
    public function __construct(Subscriber $subscriber, array $categories)
    {
        $this->subscriber = $subscriber;
        $this->categories = $categories;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $this->subscriber->suscriberCategoriesWithLog(
            $this->categories,
        );
    }
}
