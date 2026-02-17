<?php

namespace Modules\Widget;

class WidgetId
{
    /**
     * The widget ID
     *
     * @var string
     */
    protected $id;

    /**
     * Constructor
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * Get the widget ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->id;
    }

    /**
     * Create a new widget ID from class name
     *
     * @return static
     */
    public static function fromClassName(string $className): self
    {
        return new static(md5($className));
    }

    /**
     * Create a new widget ID
     *
     * @return static
     */
    public static function make(string $id): self
    {
        return new static($id);
    }
}
