<?php

namespace Modules\Returns\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Returns\Models\ReturnRequest;
use Throwable;

/**
 * Event fired when return PDF generation fails
 */
class ReturnPDFGenerationFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ReturnRequest $return,
        public Throwable $exception,
        public string $generatedBy = 'system',
        public ?string $ipAddress = null,
        public ?string $userAgent = null
    ) {
        $this->ipAddress = $this->ipAddress ?? request()->ip();
        $this->userAgent = $this->userAgent ?? request()->userAgent();
    }

    /**
     * Obtener datos del evento para logs
     */
    public function getEventData(): array
    {
        return [
            'event' => 'return_pdf_generation_failed',
            'return_id' => $this->return->id_return_request,
            'error' => $this->exception->getMessage(),
            'error_code' => $this->exception->getCode(),
            'generated_by' => $this->generatedBy,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'timestamp' => now()->toISOString(),
        ];
    }
}
