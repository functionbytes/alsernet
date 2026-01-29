<?php

namespace Modules\Mailing\Library\HtmlHandler;

use Modules\Mailing\Library\StringHelper;
use League\Pipeline\StageInterface;

class AppendHtml implements StageInterface
{
    public $newHtml;

    public function __construct($newHtml)
    {
        $this->newHtml = trim($newHtml);
    }

    public function __invoke($html)
    {
        if (empty($this->newHtml)) {
            return $html;
        }

        return StringHelper::appendHtml($html, $this->newHtml);
    }
}
