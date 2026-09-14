<?php

namespace Modules\Mailer\Library;

use League\Pipeline\StageInterface;

class RemoveTitleTag implements StageInterface
{
    public function __invoke($html)
    {
        return strip_tags_only($html, 'title');
    }
}
