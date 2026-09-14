<?php

namespace Modules\HelpdeskErp\Jobs;

use App\Helpers\PiiMasker;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskErp\Services\ErpContextService;

class RefreshErpContextJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    // Debe quedar por debajo del retry_after (90s) de la conexión de cola
    // redis: si el timeout iguala o supera retry_after, el worker puede
    // considerar el job "perdido" y otro worker lo recoge mientras el
    // primero sigue procesándolo — doble ejecución.
    public int $timeout = 60;

    public int $backoff = 10;

    public int $uniqueFor = 30;

    public function __construct(
        private readonly string $email,
        private readonly ?string $phone = null,
    ) {
        $this->onQueue('helpdesk-erp');
    }

    public function uniqueId(): string
    {
        return md5($this->email !== '' ? $this->email : 'phone:'.$this->phone);
    }

    public function handle(ErpContextService $service): void
    {
        $service->forgetCache($this->email, $this->phone);
        $service->getCustomerContext($this->email, $this->phone);
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('RefreshErpContextJob failed', [
            'email' => PiiMasker::email($this->email),
            'error' => $e->getMessage(),
        ]);
    }
}
