<?php

namespace Modules\Ecommerce\Http\Resources\Api\V1;

use App\Http\Api\V1\BaseResource;
use Illuminate\Http\Request;

class LegalPageResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'content' => $this->content,
            'metaTitle' => $this->meta_title,
            'metaDescription' => $this->meta_description,
            'updatedAt' => $this->iso($this->updated_at),
        ];
    }
}
