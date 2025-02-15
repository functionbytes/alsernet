<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Subscriber\SubscriberListUser;
use App\Models\Subscriber\SubscriberListCategorie;

class AddSuscriberListJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $subscriberId;
    protected $listIds;

    public function __construct(int $subscriberId, $mailingLists)
    {
        $this->subscriberId = $subscriberId;
        $this->listIds = $mailingLists;


    }

    public function handle()
    {
        try {

            if (!empty($this->listIds)) {

                $batchInsert = [];

                foreach ($this->listIds as $listId) {
                    $exists = SubscriberListUser::where('subscriber_id', $this->subscriberId)->where('list_id', $listId)->exists();
                    if (!$exists) {
                        $batchInsert[] = [
                            'subscriber_id' => $this->subscriberId,
                            'list_id' => $listId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (!empty($batchInsert)) {
                    SubscriberListUser::insert($batchInsert);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error al añadir Suscriptor ID {$this->subscriberId} a las listas: " . $e->getMessage());
        }
    }
}

