<?php

namespace App\Jobs\Subscribers\Subscribers;

use App\Jobs\Base;
use App\Jobs\VerifyAndCreateSubscriber;
use App\Library\Traits\Trackable;
use Illuminate\Bus\Batchable;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ImportSubscribers extends Base
{
    use Batchable;
    use Trackable;
    public $timeout = 7200;

    protected $import;
    protected $file;

    public function __construct($import, $file)
    {
        $this->import= $import;
        $this->file = $file;
        $this->afterDispatched(function ($thisJob, $monitor) {
            $monitor->setJsonData([
                'message' => 'La importación está en cola para su procesamiento (trabajo enviado)...',
            ]);
        });

        $this->afterFinished(function ($thisJob, $monitor) {
            // $monitor->updateJsonData([
            //     'message' => 'Import complete!',
            // ]);
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
            'message' => 'La importación está en curso...',
        ]);

        $logger->info('La importación está en curso...');

        $this->import->parseCsvFile($this->file, function ($record) use ($logger) {
            $this->batch()->add(new VerifyAndCreateSubscriber($this->import, $record, $logger, $this->monitor));
        });
    }
}
