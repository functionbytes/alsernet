<?php

namespace App\Jobs\Subscribers\Subscribers;

use App\Jobs\Base;
use App\Library\Traits\Trackable;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Exception;

class ImportSubscribersJob extends Base
{
    use Trackable;

    public $timeout = 7200;

    protected $imports;
    protected $file;
    protected $map;

    public function __construct($imports, $file)
    {
        $this->imports = $imports;
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
        $logfile = storage_path("logs/imports/") . basename($this->file) . ".log";
        $stream = new StreamHandler($logfile, Logger::DEBUG);
        $stream->setFormatter($formatter);

        $pid = getmypid();
        $logger = new Logger($pid);
        $logger->pushHandler($stream);

        $this->monitor->updateJsonData([
            'logfile' => $logfile,
        ]);

        $logger->info('Importación iniciada.');

        try {
            $this->imports->import(
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
                    $logger->info(sprintf('Procesado: %s/%s, Fallido: %s', $processed, $total, $failed));
                },
                function ($invalidRecord, $error) use ($logger) {
                    $logger->warning('Registro inválido: [' . implode(",", array_values($invalidRecord)) . "] | Error: " . implode(";", $error));
                }
            );

            $logger->info('Importación finalizada con éxito.');

        } catch (Exception $e) {
            $logger->error('Error en la importación: ' . $e->getMessage());
            $this->monitor->updateJsonData(['message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
