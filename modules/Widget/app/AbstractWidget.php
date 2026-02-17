<?php

namespace Modules\Widget;

use Illuminate\Support\Arr;

abstract class AbstractWidget
{
    /**
     * Widget configuration
     *
     * @var array
     */
    protected $config = [];

    /**
     * Widget frontend template
     *
     * @var string
     */
    protected $frontendTemplate = 'frontend';

    /**
     * Widget backend template
     *
     * @var string
     */
    protected $backendTemplate = 'backend';

    /**
     * Widget template data
     *
     * @var array
     */
    protected $data = [];

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Get the widget name
     */
    abstract public static function group(): string;

    /**
     * Get the widget title
     */
    public function title(): string
    {
        return class_basename(static::class);
    }

    /**
     * Get widget configuration
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function getConfig(?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->config;
        }

        return Arr::get($this->config, $key, $default);
    }

    /**
     * Set widget configuration
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setConfig($key, $value = null)
    {
        if (is_array($key)) {
            $this->config = array_merge($this->config, $key);
        } else {
            Arr::set($this->config, $key, $value);
        }

        return $this;
    }

    /**
     * Set data for widget template
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setData($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Get data for widget template
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Render the widget frontend
     */
    public function run(): string
    {
        $data = $this->data();

        if (is_array($data)) {
            $this->setData($data);
        }

        return $this->render($this->frontendTemplate);
    }

    /**
     * Render the widget backend settings
     */
    public function backend(): string
    {
        return $this->render($this->backendTemplate);
    }

    /**
     * Get data for the widget
     */
    protected function data(): ?array
    {
        return null;
    }

    /**
     * Render widget template
     */
    protected function render(string $template): string
    {
        $viewPath = $this->getViewPath($template);

        if (view()->exists($viewPath)) {
            return view($viewPath, $this->data)->render();
        }

        return '';
    }

    /**
     * Get the view path for the widget
     */
    protected function getViewPath(string $template): string
    {
        $class = str_replace('\\', '/', get_class($this));
        $path = strtolower(str_replace('Modules/', '', $class));

        return str_replace('/app/', '::widgets.', $path).'.'.$template;
    }

    /**
     * Check if widget is core widget
     */
    public function isCoreWidget(): bool
    {
        return false;
    }

    /**
     * Get widget settings form
     */
    public function settingForm(): array
    {
        return [];
    }
}
