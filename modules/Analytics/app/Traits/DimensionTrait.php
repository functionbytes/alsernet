<?php

namespace Modules\Analytics\Traits;

trait DimensionTrait
{
    protected array $dimensions = [];

    /**
     * Add dimensions to the query
     *
     * @param string|array $dimensions
     * @return $this
     */
    public function dimensions(string|array $dimensions): self
    {
        $dimensions = is_string($dimensions) ? [$dimensions] : $dimensions;

        foreach ($dimensions as $dimension) {
            $this->dimensions[] = ['name' => $dimension];
        }

        return $this;
    }

    public function getDimensions(): array
    {
        return $this->dimensions;
    }
}
