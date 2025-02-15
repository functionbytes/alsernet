<?php

namespace App\Jobs;

use App\Models\Subscriber\Subscriber;
use App\Models\Subscriber\SubscriberCategorie;
use App\Models\Subscriber\SubscriberList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Subscriber\SubscriberListUser;
use App\Models\Subscriber\SubscriberListCategorie;

class RemoveSuscriberListJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected  $subscriberId;
    protected  $listIds;

    public function __construct(int $subscriberId, $mailingLists)
    {
        $this->subscriberId = $subscriberId;
        $this->listIds = $mailingLists;
    }

    public function handle()
    {
        try {

            $subscriber = Subscriber::id($this->subscriberId);

            if (empty($subscriber->categories) || $subscriber->parties) {
                SubscriberListUser::where('subscriber_id', $this->subscriberId)->delete();
                SubscriberCategorie::where('subscriber_id', $this->subscriberId)->delete();
                Log::info("Suscriptor ID {$this->subscriberId} eliminado de TODAS las listas de mailing.");
            } else {

                foreach ($this->listIds as $listId) {
                    SubscriberListUser::where('subscriber_id', $this->subscriberId)->where('list_id', $listId)->delete();
                    Log::info("Suscriptor ID {$this->subscriberId} eliminado de lista ID {$listId}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Error al eliminar Suscriptor ID {$this->subscriberId} de las listas: " . $e->getMessage());
        }
    }

}
