<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Acelle\Model\Sender;

class VerifySender extends Command
{

    protected $signature = 'sender:verify';

    protected $description = 'Verify Sender';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $senders = Sender::pending()->get();
        foreach ($senders as $sender) {
            $sender->updateVerificationStatus();
        }
        return 0;
    }

}
