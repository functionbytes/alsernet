<?php

namespace Modules\Health\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Logo extends Component
{
    public function render(): View
    {
        return view('health::components.logo');
    }
}
