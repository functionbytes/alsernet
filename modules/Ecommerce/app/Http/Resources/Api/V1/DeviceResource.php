<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;

class DeviceResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'deviceId' => $this->device_id,
            'platform' => $this->platform,
            'appVersion' => $this->app_version,
            'locale' => $this->locale,
            'lastUsedAt' => $this->iso($this->last_used_at),
            'isActive' => (bool) $this->is_active,
            'createdAt' => $this->iso($this->created_at),
        ];
    }
}
