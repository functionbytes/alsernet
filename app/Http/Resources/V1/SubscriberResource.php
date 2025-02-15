<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriberResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'categories' => $this->categories,
            'suscribe' => $this->suscribe,
            'lang' => $this->lang_id,
            'check_at' => $this->check_at,
            'updated_at' => $this->updated_at,
        ];
    }
}


