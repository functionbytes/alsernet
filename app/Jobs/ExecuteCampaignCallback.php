<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteCampaignCallback implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $webhook;
    protected $log;
    public function __construct($webhook, $log)
    {
        $this->webhook = $webhook;
        $this->log = $log;
    }

    public function handle()
    {
        $this->webhook->execute($this->log);
    }

}
