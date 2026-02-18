<?php

namespace Modules\Optimize\Supports;

use Modules\Core\Models\Setting;

class Optimizer
{
    protected bool $isEnabled;

    public function __construct()
    {
        $this->isEnabled = ! request()->is('setting*')
            && Setting::get('optimize.enabled', '0') === '1'
            && ! app()->runningInConsole();
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function enable(): self
    {
        $this->isEnabled = true;

        return $this;
    }

    public function disable(): self
    {
        $this->isEnabled = false;

        return $this;
    }
}
