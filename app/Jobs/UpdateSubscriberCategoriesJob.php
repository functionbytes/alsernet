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
    protected $auth;

    public function __construct(Subscriber $subscriber, $categories, $auth)
    {
        $this->subscriber = $subscriber;
        $this->categories = $categories;
        $this->auth = $auth;
    }

    public function handle(): void
    {
        try {
            $this->subscriber->updateCategoriesWithLog($this->categories, $this->auth);
            Log::info("Actualización de categorías para el suscriptor ID {$this->subscriber->id} completada.");
        } catch (\Exception $e) {
            Log::error("Error en UpdateSubscriberCategoriesJob: {$e->getMessage()}");
        }
    }
}
