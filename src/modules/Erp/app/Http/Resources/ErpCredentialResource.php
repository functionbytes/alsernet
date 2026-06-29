<?php

namespace Modules\Erp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErpCredentialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'auth_type' => $this->auth_type,
            'username' => $this->when($this->auth_type === 'basic', $this->username),
            'api_key_header' => $this->when($this->auth_type === 'api_key', $this->api_key_header),
            'custom_headers' => $this->when($this->auth_type === 'custom', $this->custom_headers),
            'is_active' => $this->is_active,
            'is_expired' => $this->isExpired(),
            'expires_at' => $this->expires_at,
            'last_used_at' => $this->last_used_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
