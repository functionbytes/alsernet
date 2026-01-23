<?php

namespace Modules\Analytics\Traits;

trait FilterTrait
{
    protected ?array $dimensionFilter = null;
    protected ?array $metricFilter = null;

    public function whereMetric(string $metric, string $operator, string|int $value): self
    {
        $this->metricFilter = [
            'metric_filter' => [
                'filter' => [
                    'field_name' => $metric,
                    'string_filter' => [
                        'match_type' => $this->getMatchType($operator),
                        'value' => (string) $value,
                    ],
                ],
            ],
        ];

        return $this;
    }

    public function whereDimension(string $dimension, string $operator, string|int $value): self
    {
        $this->dimensionFilter = [
            'dimension_filter' => [
                'filter' => [
                    'field_name' => $dimension,
                    'string_filter' => [
                        'match_type' => $this->getMatchType($operator),
                        'value' => (string) $value,
                    ],
                ],
            ],
        ];

        return $this;
    }

    protected function getMatchType(string $operator): string
    {
        return match ($operator) {
            '=' => 'EXACT',
            'contains' => 'CONTAINS',
            'begins_with' => 'BEGINS_WITH',
            'ends_with' => 'ENDS_WITH',
            default => 'EXACT',
        };
    }

    public function getDimensionFilter(): ?array
    {
        return $this->dimensionFilter;
    }

    public function getMetricFilter(): ?array
    {
        return $this->metricFilter;
    }
}
