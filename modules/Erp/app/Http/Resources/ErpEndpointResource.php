<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErpEndpointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'url' => $this->url,
            'full_url' => $this->full_url,
            'method' => $this->method,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'timeout' => $this->timeout,
            'retry_attempts' => $this->retry_attempts,
            'content_type' => $this->content_type,
            'rate_limit' => $this->rate_limit,
            'headers' => $this->headers,
            'query_params' => $this->query_params,
            'credential' => new ErpCredentialResource($this->whenLoaded('credential')),
            'credentials_count' => $this->whenCounted('credentials'),
            'logs_count' => $this->whenCounted('logs'),
            'statistics' => $this->when($request->routeIs('*.show'), [
                'success_rate' => $this->success_rate,
                'average_execution_time' => $this->average_execution_time,
                'last_execution' => $this->last_execution,
                'total_calls' => $this->logs_count ?? $this->logs()->count(),
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
