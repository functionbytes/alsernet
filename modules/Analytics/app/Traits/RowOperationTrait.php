<?php

namespace Modules\Analytics\Traits;

trait RowOperationTrait
{
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected ?bool $keepEmptyRows = null;

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function keepEmptyRows(bool $keep = true): self
    {
        $this->keepEmptyRows = $keep;

        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function getKeepEmptyRows(): ?bool
    {
        return $this->keepEmptyRows;
    }
}
