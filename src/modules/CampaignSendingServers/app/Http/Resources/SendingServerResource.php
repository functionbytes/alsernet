<?php

namespace Modules\CampaignSendingServers\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API representation de SendingServer. Excluye TODAS las credenciales
 * del output para no exponerlas vía API. El consumidor solo ve metadata.
 */
class SendingServerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->uid,
            'name' => $this->name,
            'type' => $this->type,
            'type_label' => $this->getTypeName(),
            'status' => $this->status,
            'host' => $this->host,
            'smtp_port' => $this->smtp_port,
            'smtp_protocol' => $this->smtp_protocol,
            'aws_region' => $this->aws_region,
            'domain' => $this->domain,
            'default_from_email' => $this->default_from_email,
            'quota' => [
                'value' => $this->quota_value,
                'base' => $this->quota_base,
                'unit' => $this->quota_unit,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
