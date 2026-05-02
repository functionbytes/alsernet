<?php

namespace Modules\Remarketing\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Segment;

class SegmentService
{
    /**
     * Build an Eloquent query from a segment's JSON AST conditions.
     */
    public function getMembers(Segment $segment): Builder
    {
        $query = Customer::query()->where('store_id', $segment->store_id);

        $this->applyNode($query, $segment->conditions ?? []);

        return $query;
    }

    /**
     * Return the count of matching customers for a segment.
     */
    public function getMemberCount(Segment $segment): int
    {
        return $this->getMembers($segment)->count();
    }

    /**
     * Recalculate a segment's member_count and update last_calculated_at.
     */
    public function recalculate(Segment $segment): int
    {
        $count = $this->getMemberCount($segment);

        $segment->update([
            'member_count' => $count,
            'last_calculated_at' => now(),
        ]);

        return $count;
    }

    /**
     * Recursively apply an AST node to a query builder.
     */
    private function applyNode(Builder $query, array $node): void
    {
        if (! isset($node['operator'])) {
            return;
        }

        // Top-level OR/AND group
        if (isset($node['conditions']) && is_array($node['conditions'])) {
            $operator = strtoupper($node['operator']);
            $method = $operator === 'OR' ? 'orWhere' : 'where';

            $query->{$method}(function (Builder $sub) use ($node): void {
                foreach ($node['conditions'] as $condition) {
                    $this->applyCondition($sub, $condition);
                }
            });

            return;
        }

        // Leaf condition
        $this->applyCondition($query, $node);
    }

    /**
     * Apply a single condition (leaf) to a query.
     */
    private function applyCondition(Builder $query, array $condition): void
    {
        $type = $condition['type'] ?? null;

        match ($type) {
            'property' => $this->applyPropertyCondition($query, $condition),
            'event' => $this->applyEventCondition($query, $condition),
            'rfm' => $this->applyRfmCondition($query, $condition),
            'AND', 'OR' => $this->applyNode($query, $condition),
            default => $this->applyNode($query, $condition),
        };
    }

    /**
     * Apply a customer property (column) condition.
     */
    private function applyPropertyCondition(Builder $query, array $condition): void
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;

        if (! $field) {
            return;
        }

        // Handle JSON array fields (tags)
        if ($field === 'tags' && $operator === 'contains') {
            $query->whereJsonContains('tags', $value);

            return;
        }

        $sqlOperator = $this->toSqlOperator($operator);

        match ($operator) {
            'contains' => $query->where($field, 'like', '%'.$value.'%'),
            'in' => $query->whereIn($field, (array) $value),
            'not_in' => $query->whereNotIn($field, (array) $value),
            default => $query->where($field, $sqlOperator, $value),
        };
    }

    /**
     * Apply an event-based condition using a correlated EXISTS subquery.
     */
    private function applyEventCondition(Builder $query, array $condition): void
    {
        $eventType = $condition['event'] ?? null;
        $operator = $condition['operator'] ?? 'at_least';
        $count = (int) ($condition['count'] ?? 1);
        $windowDays = $condition['window_days'] ?? null;

        if (! $eventType) {
            return;
        }

        $sinceDate = $windowDays ? now()->subDays((int) $windowDays) : null;

        match ($operator) {
            'at_least' => $query->whereExists(function (\Illuminate\Database\Query\Builder $sub) use ($eventType, $count, $sinceDate): void {
                $sub->selectRaw('1')
                    ->from('remarketing_events')
                    ->whereColumn('remarketing_events.customer_id', 'remarketing_customers.id')
                    ->where('remarketing_events.type', $eventType)
                    ->when($sinceDate, fn ($q) => $q->where('remarketing_events.occurred_at', '>=', $sinceDate))
                    ->havingRaw('COUNT(*) >= ?', [$count])
                    ->groupBy('remarketing_events.customer_id');
            }),
            'at_most' => $query->whereExists(function (\Illuminate\Database\Query\Builder $sub) use ($eventType, $count, $sinceDate): void {
                $sub->selectRaw('1')
                    ->from('remarketing_events')
                    ->whereColumn('remarketing_events.customer_id', 'remarketing_customers.id')
                    ->where('remarketing_events.type', $eventType)
                    ->when($sinceDate, fn ($q) => $q->where('remarketing_events.occurred_at', '>=', $sinceDate))
                    ->havingRaw('COUNT(*) <= ?', [$count])
                    ->groupBy('remarketing_events.customer_id');
            }),
            default => null,
        };
    }

    /**
     * Apply an RFM score condition (direct column comparison).
     */
    private function applyRfmCondition(Builder $query, array $condition): void
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'gte';
        $value = $condition['value'] ?? null;

        if (! $field || $value === null) {
            return;
        }

        $query->where($field, $this->toSqlOperator($operator), $value);
    }

    /**
     * Convert a DSL operator string to a SQL comparison operator.
     */
    private function toSqlOperator(string $operator): string
    {
        return match ($operator) {
            'equals' => '=',
            'not_equals' => '!=',
            'gt' => '>',
            'gte' => '>=',
            'lt' => '<',
            'lte' => '<=',
            default => '=',
        };
    }
}
