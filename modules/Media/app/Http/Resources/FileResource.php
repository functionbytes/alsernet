<?php

namespace Modules\Media\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'name' => $this->name,
            'mimeType' => $this->mime_type,
            'type' => $this->type,
            'size' => $this->size,
            'humanSize' => $this->human_size,
            'url' => $this->url,
            'alt' => $this->alt,
            'folderId' => $this->folder_id,
            'disk' => $this->disk,
            'visibility' => $this->visibility,
            'isFavorite' => $this->isFavoritedBy(auth()->id() ?? 0),
            'metadata' => $this->metadata,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
