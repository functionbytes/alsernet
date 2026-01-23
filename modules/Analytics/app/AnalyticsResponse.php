<?php

namespace Modules\Analytics;

use Illuminate\Support\Collection;

class AnalyticsResponse
{
    protected Collection $table;
    protected array $rows = [];
    protected array $totals = [];

    public function __construct(array $responseData = [])
    {
        $this->table = collect();
        $this->processResponse($responseData);
    }

    protected function processResponse(array $responseData): void
    {
        if (empty($responseData)) {
            return;
        }

        // Procesar dimensiones y métricas
        $rows = $responseData['rows'] ?? [];

        foreach ($rows as $row) {
            $rowData = [];

            // Agregar dimensiones
            if (isset($row['dimensions'])) {
                foreach ($row['dimensions'] as $index => $dimension) {
                    $rowData[$index] = $dimension;
                }
            }

            // Agregar métricas
            if (isset($row['metricValues'])) {
                foreach ($row['metricValues'] as $index => $metric) {
                    $rowData['metric_' . $index] = $metric['value'] ?? 0;
                }
            }

            $this->rows[] = $rowData;
        }

        $this->table = collect($this->rows);

        // Procesar totales si existen
        if (isset($responseData['totals'])) {
            foreach ($responseData['totals'] as $total) {
                if (isset($total['metricValues'])) {
                    foreach ($total['metricValues'] as $index => $metric) {
                        $this->totals['metric_' . $index] = $metric['value'] ?? 0;
                    }
                }
            }
        }
    }

    public function getTable(): Collection
    {
        return $this->table;
    }

    public function getTotals(): array
    {
        return $this->totals;
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    public function toArray(): array
    {
        return $this->table->toArray();
    }

    public function toJson(): string
    {
        return $this->table->toJson();
    }
}
