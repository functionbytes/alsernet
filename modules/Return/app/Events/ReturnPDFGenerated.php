<?php

namespace Modules\Returns\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Returns\Models\ReturnRequest;

/**
 * Event fired when a return PDF is successfully generated
 */
class ReturnPDFGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ReturnRequest $return,
        public string $pdfPath,
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
            'event' => 'return_pdf_generated',
            'return_id' => $this->return->id_return_request,
            'pdf_path' => $this->pdfPath,
            'generated_by' => $this->generatedBy,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'timestamp' => now()->toISOString(),
        ];
    }
}
