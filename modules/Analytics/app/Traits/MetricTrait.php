<?php

namespace Modules\Analytics\Traits;

trait MetricTrait
{
    protected array $metrics = [];

    /**
     * Add metrics to the query
     *
     * @param string|array $metrics
     * @return $this
     */
    public function metrics(string|array $metrics): self
    {
        $metrics = is_string($metrics) ? [$metrics] : $metrics;

        foreach ($metrics as $metric) {
            $this->metrics[] = ['name' => $metric];
        }

        return $this;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }
}
