<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class SocketWorker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $port;

    /**
     * Create a new job instance.
     */
    public function __construct($port)
    {
        $this->port = $port;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Artisan::call('websockets:serve', [
            '--port' => $this->port,
        ]);
    }
}
