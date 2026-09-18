<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErpEndpointLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'endpoint_id' => $this->endpoint_id,
            'user_id' => $this->user_id,
            'method' => $this->method,
            'url' => $this->url,
            'request_headers' => $this->request_headers,
            'request_payload' => $this->request_payload,
            'response_headers' => $this->response_headers,
            'response_payload' => $this->response_payload,
            'status_code' => $this->status_code,
            'execution_time' => $this->execution_time,
            'success' => $this->success,
            'error_message' => $this->error_message,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
