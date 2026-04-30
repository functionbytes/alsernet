<?php

namespace Modules\Chat\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToAccount
{
    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->where($this->getTable().'.account_id', $accountId);
    }
}
