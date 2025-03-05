<?php

namespace App\Jobs\Subscribers;

use App\Jobs\Base;
use App\Jobs\VerifyAndCreateSubscriber;
use App\Library\Traits\Trackable;
use Illuminate\Bus\Batchable;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ImportSubscribersLists extends Base
{
    use Batchable;
    use Trackable;
    public $timeout = 7200;

    protected $list;
    protected $file;

    public function __construct($list, $file)
    {
        $this->list = $list;
        $this->file = $file;
        $this->afterDispatched(function ($thisJob, $monitor) {
            $monitor->setJsonData([
                'message' => 'La importación está en cola para su procesamiento (trabajo enviado)...',
            ]);
        });

        // After the batch has finished
        // This is the place for the job itself to log
        $this->afterFinished(function ($thisJob, $monitor) {
            // For example
            // $monitor->updateJsonData([
            //     'message' => 'Import complete!',
            // ]);
        });
    }

    /**
     * Execute the job.
     *
     * @return void
     */
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
            'message' => 'La importación está en curso...',
        ]);

        $logger->info('La importación está en curso...');

        $this->list->parseCsvFile($this->file, function ($record) use ($logger) {
            $this->batch()->add(new VerifyAndCreateSubscriber($this->list, $record, $logger, $this->monitor));
        });
    }
}
