<?php

namespace Modules\Mailing\Library\HtmlHandler;

use Modules\Mailing\Library\StringHelper;
use League\Pipeline\StageInterface;

class InjectTrackingPixel implements StageInterface
{
    public $campaign;

    public $msgId;

    public function __construct($campaign, $msgId)
    {
        $this->campaign = $campaign;
        $this->msgId = $msgId;
    }

    public function __invoke($html)
    {
        $pixel = $this->campaign->makeTrackingPixel($this->msgId);

        return StringHelper::appendHtml($html, $pixel);
    }
}
