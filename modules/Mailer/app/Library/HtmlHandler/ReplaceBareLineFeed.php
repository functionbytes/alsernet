<?php

namespace Modules\Mailer\Library;


use League\Pipeline\StageInterface;

class ReplaceBareLineFeed implements StageInterface
{
    public function __invoke($html)
    {
        $html = StringHelper::replaceBareLineFeed($html);

        // By the way, add a CR-LF to the end of the message as required by RFC 2822
        $html = trim($html)."\r\n";

        return $html;
    }
}
