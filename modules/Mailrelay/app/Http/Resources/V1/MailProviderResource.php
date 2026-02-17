<?php

namespace Modules\Mailrelay\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\Mailrelay\Entities\MailProvider
 */
class MailProviderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'driver' => $this->driver,
            'driver_info' => $this->getDriverInfo(),

            // Status flags
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'priority' => $this->priority,
            'connection_ok' => $this->connection_ok,
            'is_working' => $this->isWorking(),

            // Configuration (hide sensitive credentials)
            'has_credentials' => ! empty($this->credentials),
            'limits' => $this->limits,
            'metadata' => $this->metadata,

            // Timestamps
            'last_tested_at' => $this->last_tested_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships (only when loaded)
            'campaigns_count' => $this->whenCounted('campaigns'),

            // UI helpers
            'status_badge' => $this->when($request->has('include_ui'), fn () => $this->getStatusBadge()),
            'description' => $this->when($request->has('include_ui'), fn () => $this->getDescription()),
        ];
    }
}
