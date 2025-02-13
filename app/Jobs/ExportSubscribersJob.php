<?php

namespace App\Jobs;

use App\Library\Traits\Trackable;
use Exception;
class ExportSubscribersJob extends Base
{
    use Trackable;
    public $timeout = 3600;
    protected $mailList;
    protected $segment;

    public function __construct($mailList, $segment = null)
    {
        $this->mailList = $mailList;
        $this->segment = $segment;

        $this->afterDispatched(function ($thisJob, $monitor) {
            $monitor->setJsonData([
                'percentage' => 0,
                'total' => 0,
                'processed' => 0,
                'failed' => 0,
                'message' => 'La tarea de exportación está en cola...',
                'filepath' => $this->mailList->getExportFilePath(),
            ]);
        });
    }

    public function handle()
    {

        $this->mailList->export(function ($processed, $total, $failed, $message) {

            $percentage = ($total && $processed) ? (int)($processed * 100 / $total) : 0;

            if ($total === 0) {
                $percentage = 100;
            }

            $this->monitor->updateJsonData([
                'percentage' => $percentage,
                'total' => $total,
                'processed' => $processed,
                'failed' => $failed,
                'message' => $message,
            ]);

        }, $this->segment);

    }
}
