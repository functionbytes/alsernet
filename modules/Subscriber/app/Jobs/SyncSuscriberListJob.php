<?php

namespace Modules\Subscriber\Jobs\Subscribers;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Subscriber\Models\Subscriber;

class SyncSuscriberListJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $subscriberId;

    protected $addedCategories;

    protected $removedCategories;

    protected $categoriesIds;

    public function __construct($subscriberId, $addedCategories, $removedCategories, $categoriesIds)
    {
        $this->subscriberId = $subscriberId;
        $this->addedCategories = $addedCategories;
        $this->removedCategories = $removedCategories;
        $this->categoriesIds = $categoriesIds;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $subscriber = Subscriber::find($this->subscriberId);

        if (! $subscriber) {
            return;
        }

        $subscriber->subscribeToCategorie($this->addedCategories);
        $subscriber->unsubscribeFromCategorie($this->removedCategories);
        $subscriber->categories()->sync($this->categoriesIds);
    }
}
