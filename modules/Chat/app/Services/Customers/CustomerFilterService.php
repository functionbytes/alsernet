<?php

namespace Modules\Chat\Services\Customers;

use Illuminate\Database\Eloquent\Builder;
use Modules\Chat\Models\Customers\Customer;

class CustomerFilterService
{
    /**
     * Apply filters to customer query.
     *
     * @param  array  $filters  Array of filter conditions
     */
    public function apply(int $accountId, array $filters): Builder
    {
        $query = Customer::where('account_id', $accountId);

        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter);
        }

        return $query;
    }

    /**
     * Apply a single filter condition.
     */
    protected function applyFilter(Builder $query, array $filter): void
    {
        $field = $filter['field'] ?? null;
        $operator = $filter['operator'] ?? 'equals';
        $value = $filter['value'] ?? null;

        if (! $field) {
            return;
        }

        // Handle custom attributes separately
        if (str_starts_with($field, 'custom_')) {
            $this->applyCustomAttributeFilter($query, $field, $operator, $value);

            return;
        }

        // Handle standard fields
        match ($operator) {
            'equals' => $query->where($field, $value),
            'not_equals' => $query->where($field, '!=', $value),
            'contains' => $query->where($field, 'LIKE', '%'.$this->escapeLikeWildcards($value).'%'),
            'not_contains' => $query->where($field, 'NOT LIKE', '%'.$this->escapeLikeWildcards($value).'%'),
            'starts_with' => $query->where($field, 'LIKE', $this->escapeLikeWildcards($value).'%'),
            'ends_with' => $query->where($field, 'LIKE', '%'.$this->escapeLikeWildcards($value)),
            'is_empty' => $query->whereNull($field)->orWhere($field, ''),
            'is_not_empty' => $query->whereNotNull($field)->where($field, '!=', ''),
            'greater_than' => $query->where($field, '>', $value),
            'less_than' => $query->where($field, '<', $value),
            'in' => $query->whereIn($field, (array) $value),
            'not_in' => $query->whereNotIn($field, (array) $value),
            default => null,
        };
    }

    /**
     * Apply filter for custom attributes stored in JSON.
     */
    protected function applyCustomAttributeFilter(Builder $query, string $field, string $operator, $value): void
    {
        // Extract the custom attribute key (remove 'custom_' prefix)
        $attributeKey = substr($field, 7);

        $jsonPath = "additional_attributes->{$attributeKey}";

        match ($operator) {
            'equals' => $query->whereJsonContains($jsonPath, $value),
            'not_equals' => $query->whereJsonDoesntContain($jsonPath, $value),
            'contains' => $query->where($jsonPath, 'LIKE', '%'.$this->escapeLikeWildcards($value).'%'),
            'is_empty' => $query->whereNull($jsonPath),
            'is_not_empty' => $query->whereNotNull($jsonPath),
            default => null,
        };
    }

    /**
     * Escape LIKE wildcards to prevent SQL injection.
     */
    protected function escapeLikeWildcards(string $value): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }

    /**
     * Get available filter fields.
     */
    public function getAvailableFields(): array
    {
        return [
            'standard' => [
                ['key' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['key' => 'email', 'label' => 'Email', 'type' => 'text'],
                ['key' => 'phone_number', 'label' => 'Phone', 'type' => 'text'],
                ['key' => 'city', 'label' => 'City', 'type' => 'text'],
                ['key' => 'country', 'label' => 'Country', 'type' => 'text'],
                ['key' => 'company_name', 'label' => 'Company', 'type' => 'text'],
                ['key' => 'created_at', 'label' => 'Created Date', 'type' => 'date'],
                ['key' => 'last_activity_at', 'label' => 'Last Activity', 'type' => 'date'],
            ],
            'custom' => [], // To be populated dynamically from CustomerAttribute
        ];
    }

    /**
     * Get available operators for a field type.
     */
    public function getOperatorsForType(string $type): array
    {
        return match ($type) {
            'text' => [
                ['key' => 'equals', 'label' => 'Equals'],
                ['key' => 'not_equals', 'label' => 'Not Equals'],
                ['key' => 'contains', 'label' => 'Contains'],
                ['key' => 'not_contains', 'label' => 'Does Not Contain'],
                ['key' => 'starts_with', 'label' => 'Starts With'],
                ['key' => 'ends_with', 'label' => 'Ends With'],
                ['key' => 'is_empty', 'label' => 'Is Empty'],
                ['key' => 'is_not_empty', 'label' => 'Is Not Empty'],
            ],
            'number' => [
                ['key' => 'equals', 'label' => 'Equals'],
                ['key' => 'not_equals', 'label' => 'Not Equals'],
                ['key' => 'greater_than', 'label' => 'Greater Than'],
                ['key' => 'less_than', 'label' => 'Less Than'],
                ['key' => 'is_empty', 'label' => 'Is Empty'],
                ['key' => 'is_not_empty', 'label' => 'Is Not Empty'],
            ],
            'date' => [
                ['key' => 'equals', 'label' => 'On'],
                ['key' => 'greater_than', 'label' => 'After'],
                ['key' => 'less_than', 'label' => 'Before'],
                ['key' => 'is_empty', 'label' => 'Is Empty'],
                ['key' => 'is_not_empty', 'label' => 'Is Not Empty'],
            ],
            'list' => [
                ['key' => 'equals', 'label' => 'Is'],
                ['key' => 'not_equals', 'label' => 'Is Not'],
                ['key' => 'in', 'label' => 'Is Any Of'],
                ['key' => 'not_in', 'label' => 'Is None Of'],
            ],
            default => [
                ['key' => 'equals', 'label' => 'Equals'],
                ['key' => 'not_equals', 'label' => 'Not Equals'],
            ],
        };
    }
}
