<?php

namespace App\Jobs;

use App\Library\Traits\Trackable;

class ExportCampaignLog extends Base {

    use Trackable;
    public $timeout = 3600;
    protected $campaign;
    protected $logtype;

    public function __construct($campaign, $logtype)
    {
        $this->campaign = $campaign;
        $this->logtype = $logtype;

        $this->afterDispatched(function ($thisJob, $monitor) {
            $monitor->setJsonData([
                'percentage' => 0
            ]);
        });
    }
    public function handle()
    {
        $this->campaign->generateTrackingLogCsv($this->logtype, function ($percentage, $path) {
            $this->monitor->updateJsonData([
                'percentage' => $percentage,
                'path' => $path,
            ]);
        });
    }
}
