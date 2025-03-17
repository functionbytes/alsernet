<?php

namespace App\Jobs\Subscribers\Subscribers;

use App\Jobs\Base;
use App\Library\Traits\Trackable;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ImportSubscribersListsJob extends Base
{
    use Trackable;

    public $timeout = 7200;

    protected $list;
    protected $lists;
    protected $file;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($import, $file)
    {
        $this->import = $import;
        $this->file = $file;

        $this->afterDispatched(function ($thisJob, $monitor) {
            $monitor->setJsonData([
                'percentage' => 0,
                'total' => 0,
                'processed' => 0,
                'failed' => 0,
                'message' => 'La importación está en cola para su procesamiento...',
                'logfile' => null,
            ]);
        });

    }
    public function handle()
    {

        $formatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message%\n");
        $logfile = $this->file.".log";
        $stream = new StreamHandler($logfile, Logger::DEBUG);
        $stream->setFormatter($formatter);

        $pid = getmypid();
        $logger = new Logger($pid);
        $logger->pushHandler($stream);

        $this->monitor->updateJsonData([
            'logfile' => $logfile,
        ]);

        $logger->info('Initiated');

        $this->import->import(
            $this->file,
            function ($processed, $total, $failed, $message) use ($logger) {

                $percentage = ($total && $processed) ? (int)($processed * 100 / $total) : 0;

                $this->monitor->updateJsonData([
                    'percentage' => $percentage,
                    'total' => $total,
                    'processed' => $processed,
                    'failed' => $failed,
                    'message' => $message,
                ]);

                $logger->info($message);
                $logger->info(sprintf('Procesado: %s/%s, Saltado: %s', $processed, $total, $failed));
            },
            function ($invalidRecord, $error) use ($logger) {
                $logger->warning('Invalid record: [' . implode(",", array_values($invalidRecord)) . "] | Validation error: " . implode(";", $error));
            }
        );

        $logger->info('Finished');
    }
}
