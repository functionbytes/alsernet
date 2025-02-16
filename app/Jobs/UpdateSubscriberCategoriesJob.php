<?php

namespace App\Jobs;

use App\Models\Subscriber\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateSubscriberCategoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Subscriber $subscriber;
    protected  $categories;

    public function __construct(Subscriber $subscriber, $categories)
    {
        $this->subscriber = $subscriber;
        $this->categories = $categories;
    }

    public function handle(): void
    {
        try {
            $this->subscriber->updateCategoriesWithLog($this->categories, app('managers'));
            Log::info("Actualización de categorías para el suscriptor ID {$this->subscriber->id} completada.");
        } catch (\Exception $e) {
            Log::error("Error en UpdateSubscriberCategoriesJob: {$e->getMessage()}");
        }
    }
}
