<?php

namespace Spatie\QueryBuilder\Includes;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 */
interface IncludeInterface
{
    /**
     * @param  Builder<TModelClass>  $query
     * @return mixed
     */
    public function __invoke(Builder $query, string $include);
}
