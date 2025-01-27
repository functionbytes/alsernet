<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\mailmailablesend;
use Illuminate\Support\Facades\Mail;

class MailSend implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 60;

    public $email;

    public $templateCode;

    public $ticketData;

    /**
     * Create a new job instance.
     */
    public function __construct($email,$templateCode,$ticketData)
    {
        $this->email = $email;
        $this->templateCode = $templateCode;
        $this->ticketData = $ticketData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->email)->send(new mailmailablesend($this->templateCode, $this->ticketData));
    }
}
