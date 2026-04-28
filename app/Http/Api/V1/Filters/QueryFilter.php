<?php

namespace App\Http\Api\V1\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class QueryFilter
{
    protected array $allowedFilters = [];

    protected array $allowedSorts = [];

    protected array $allowedIncludes = [];

    protected int $maxPerPage = 100;

    protected int $defaultPerPage = 15;

    public function __construct(protected Request $request) {}

    public function apply(Builder $query): Builder
    {
        $this->applyFilters($query);
        $this->applySorts($query);
        $this->applyIncludes($query);

        return $query;
    }

    public function perPage(): int
    {
        $requested = (int) $this->request->input('per_page', $this->defaultPerPage);

        return min($requested, $this->maxPerPage);
    }

    protected function applyFilters(Builder $query): void
    {
        $filters = $this->request->input('filter', []);

        if (! is_array($filters)) {
            return;
        }

        foreach ($filters as $field => $value) {
            if (! in_array($field, $this->allowedFilters, true) || $value === null || $value === '') {
                continue;
            }

            $method = 'filter'.ucfirst(str()->camel($field));

            if (method_exists($this, $method)) {
                $this->{$method}($query, $value);
            } else {
                $query->where($field, 'like', '%'.$value.'%');
            }
        }
    }

    protected function applySorts(Builder $query): void
    {
        $sort = $this->request->input('sort');

        if (! $sort) {
            return;
        }

        foreach (explode(',', $sort) as $field) {
            $direction = 'asc';

            if (str_starts_with($field, '-')) {
                $direction = 'desc';
                $field = substr($field, 1);
            }

            $column = str()->snake($field);

            if (in_array($field, $this->allowedSorts, true)) {
                $query->orderBy($column, $direction);
            }
        }
    }

    protected function applyIncludes(Builder $query): void
    {
        $include = $this->request->input('include');

        if (! $include) {
            return;
        }

        $requested = array_map('trim', explode(',', $include));
        $allowed = array_intersect($requested, $this->allowedIncludes);

        if ($allowed) {
            $query->with($allowed);
        }
    }
}
