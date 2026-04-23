<?php

declare(strict_types=1);

namespace Modules\Captcha\Events;

use Illuminate\Foundation\Events\Dispatchable;

class CaptchaRendered
{
    use Dispatchable;

    public function __construct(public string $rendered = '') {}
}
