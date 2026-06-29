<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\Request;

class FootprinterService
{
    public function capture(): array
    {
        return [
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'referrer' => Request::header('referer'),
            'landing_page' => Request::fullUrl(),
        ];
    }
}
