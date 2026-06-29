<?php

namespace App\Http\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    protected function iso(?Carbon $date): ?string
    {
        return $date?->toIso8601String();
    }

    protected function mediaUrl(?string $path, ?string $conversion = null): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }

    protected function whenIncluded(string $relation, callable $cb): mixed
    {
        return $this->whenLoaded($relation, $cb);
    }
}
