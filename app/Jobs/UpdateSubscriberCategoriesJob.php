<?php

namespace App\Jobs;

use App\Models\Subscriber\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateSubscriberCategoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $subscriber;
    protected $categories;
    protected $auth;
    protected $hasLangChanged;
    protected $currentLangId;
    protected $previousLangId;

    /**
     * Create a new job instance.
     */
    public function __construct(Subscriber $subscriber, array $categories, $auth, bool $hasLangChanged, int $currentLangId, int $previousLangId)
    {
        $this->subscriber = $subscriber;
        $this->categories = $categories;
        $this->auth = $auth;
        $this->hasLangChanged = $hasLangChanged;
        $this->currentLangId = $currentLangId;
        $this->previousLangId = $previousLangId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $this->subscriber->updateCategoriesWithLog(
            $this->categories,
            $this->auth,
            $this->hasLangChanged,
            $this->currentLangId,
            $this->previousLangId
        );
    }
}
