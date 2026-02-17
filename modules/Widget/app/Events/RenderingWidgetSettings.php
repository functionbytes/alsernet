<?php

namespace Modules\Widget\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Widget\AbstractWidget;

class RenderingWidgetSettings
{
    use Dispatchable, SerializesModels;

    /**
     * The widget instance
     *
     * @var AbstractWidget
     */
    public $widget;

    /**
     * Additional data
     *
     * @var array
     */
    public $data;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(AbstractWidget $widget, array $data = [])
    {
        $this->widget = $widget;
        $this->data = $data;
    }

    /**
     * Get the widget instance
     */
    public function getWidget(): AbstractWidget
    {
        return $this->widget;
    }

    /**
     * Get additional data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set additional data
     *
     * @return $this
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Merge additional data
     *
     * @return $this
     */
    public function mergeData(array $data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }
}
