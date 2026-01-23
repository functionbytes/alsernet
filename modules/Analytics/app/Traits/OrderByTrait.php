<?php

namespace Modules\Analytics\Traits;

trait OrderByTrait
{
    protected array $orderBys = [];

    public function orderByMetricDesc(string $metric): self
    {
        $this->orderBys[] = [
            'metric' => ['metric_name' => $metric],
            'desc' => true,
        ];

        return $this;
    }

    public function orderByMetricAsc(string $metric): self
    {
        $this->orderBys[] = [
            'metric' => ['metric_name' => $metric],
            'desc' => false,
        ];

        return $this;
    }

    public function orderByDimensionDesc(string $dimension): self
    {
        $this->orderBys[] = [
            'dimension' => ['dimension_name' => $dimension],
            'desc' => true,
        ];

        return $this;
    }

    public function orderByDimensionAsc(string $dimension): self
    {
        $this->orderBys[] = [
            'dimension' => ['dimension_name' => $dimension],
            'desc' => false,
        ];

        return $this;
    }

    public function getOrderBys(): array
    {
        return $this->orderBys;
    }
}
