<?php

namespace App\Jobs\Erp;

use App\Models\Subscriber\Subscriber;
use App\Services\ErpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SynchronizationSubscription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3; // Máximo de intentos
    public $retryAfter = 60; // Reintentar después de 60 segundos

    protected $subscriberId;

    public function __construct($subscriberId)
    {
        $this->subscriberId = $subscriberId;
    }

    public function handle()
    {
        try {

                $subscriber = Subscriber::find($this->subscriberId);

                if (!$subscriber) {
                    Log::error("SynchronizationSubscription: No se encontró el suscriptor con ID {$this->subscriberId}");
                    return;
                }

                $erpService = app(ErpService::class);
                $ids = $subscriber->categories()->pluck('categories.id');

                $sports = $ids->map(function ($id) use ($erpService) {
                    return $erpService->getIdiomaGestion($id);
                })->filter()->implode(',');

                $responseData = $erpService->retrieveErpClient($subscriber->email)->getData();

                if (!$responseData || (isset($responseData->status) && $responseData->status === 'error')) {

                    $response = $erpService->saveErpClient(
                        null,
                        $subscriber->firstname,
                        $subscriber->lastname,
                        null,
                        $subscriber->email,
                        null,
                        null,
                        $erpService->getIdiomaGestion($subscriber->lang_id),
                        null,
                        null,
                        null,
                        null,
                        null,
                        $erpService->getPaisGestion($subscriber->lang_id),
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        str_replace(" ", "T", $subscriber->birth_date ?? ''),
                        $subscriber->commercial ?? 0,
                        $subscriber->parties ?? 0,
                        $sports ?? null,
                        null,
                        0
                    );

                    Log::info('SynchronizationSubscription: Alta de cliente en gestión mediante suscripción => ' . json_encode($response));

                } else{
                    $responseData = $erpService->saveLopd($subscriber->email,now()->format('Y-m-d\TH:i:s'),0,0)->getData();
                    Log::info('SynchronizationSubscription: savelopd respuesta => ' . json_encode($responseData));
                }

            } catch (Throwable $e) {
                Log::error("SynchronizationSubscription: Error al procesar la suscripción => " . $e->getMessage());
                $this->fail($e);
            }
    }

}
