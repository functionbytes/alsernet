<?php

namespace App\Jobs;

use App\Models\Blacklist;
use App\Library\Traits\Trackable;
use Exception;

class ImportBlacklistJob extends Base
{
    use Trackable;

    public $timeout = 7200;
    protected $filepath;
    protected $customer;
    public function __construct($filepath, $customer = null)
    {
        $this->filepath = $filepath;
        $this->customer = $customer;

        $this->afterDispatched(function ($thisJob, $monitor) {
            $monitor->setJsonData([
                'percentage' => 0,
                'total' => 0,
                'processed' => 0,
                'failed' => 0,
                'message' => 'Import is being queued for processing...',
            ]);
        });
    }

    public function handle()
    {
        $this->monitor->updateJsonData([
            'message' => 'Import is in progress...',
        ]);

        Blacklist::import($this->filepath, $this->customer, function ($processed, $total, $failed, $message) {
            $percentage = ($total && $processed) ? (int)($processed * 100 / $total) : 0;

            $this->monitor->updateJsonData([
                'percentage' => $percentage,
                'total' => $total,
                'processed' => $processed,
                'failed' => $failed,
                'message' => $message,
            ]);
        });
    }
}
