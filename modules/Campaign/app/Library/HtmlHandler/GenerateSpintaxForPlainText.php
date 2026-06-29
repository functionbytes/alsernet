<?php

namespace Modules\Campaign\Library\HtmlHandler;

use bjoernffm\Spintax\Parser;
use League\Pipeline\StageInterface;

class GenerateSpintaxForPlainText implements StageInterface
{
    public function __invoke($text)
    {
        return Parser::replicate($text, []);
    }

    private function containsSpintaxPattern($text)
    {
        // REGEXP to check if a text contains Spintax {}
        $containsSpintaxRegexp = '/{.+|.+}/';

        return preg_match($containsSpintaxRegexp, $text) == true;
    }
}
