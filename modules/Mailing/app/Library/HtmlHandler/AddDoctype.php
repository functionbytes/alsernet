<?php

namespace Modules\Mailer\Library;

use League\Pipeline\StageInterface;

class AddDoctype implements StageInterface
{
    public function __invoke($html)
    {
        $closure = function () {};

        // Call StringHelper::updateHtml in order to have DOCTYPE available
        return StringHelper::updateHtml($html, $closure);
    }

}
