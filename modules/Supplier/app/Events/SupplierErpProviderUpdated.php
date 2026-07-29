<?php

namespace Modules\Supplier\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Supplier\Models\Supplier\Supplier;

class SupplierErpProviderUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Supplier $provider,
        public array $changedFields,
        public int $userId,
        public string $ipAddress,
    ) {}

    public function shouldSyncToErp(): bool
    {
        if (empty($this->provider->erp_id)) {
            return false;
        }

        $syncableFields = ['label', 'email'];

        return count(array_intersect($this->changedFields, $syncableFields)) > 0;
    }

    public function getDescription(): string
    {
        $fields = implode(', ', $this->changedFields);

        return "Proveedor #{$this->provider->id} modificado (campos: $fields)";
    }
}
