<?php

namespace App\Jobs;

use App\Models\Subscriber\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AddSuscriberListJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Subscriber $subscriber;
    protected array $listIds;

    public function __construct(Subscriber $subscriber, array $mailingLists)
    {
        $this->subscriber = $subscriber;
        $this->listIds = $mailingLists;
    }

    public function handle(): void
    {
        try {
            $this->subscriber->addToLists($this->listIds);
        } catch (\Exception $e) {
            Log::error("Error al añadir Suscriptor ID {$this->subscriber->id} a las listas: {$e->getMessage()}");
        }
    }
}
