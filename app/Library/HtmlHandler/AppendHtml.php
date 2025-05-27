<?php

namespace App\Library\HtmlHandler;

use League\Pipeline\StageInterface;
use App\Library\StringHelper;

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
