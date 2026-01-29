<?php

namespace Modules\Campaign\Console\Commands;

use Modules\Mailing\Models\Sender;
use Illuminate\Console\Command;

class VerifySender extends Command
{
    protected $signature = 'sender:verify';

    protected $description = 'Verify pending senders';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $senders = Sender::pending()->get();
        foreach ($senders as $sender) {
            $sender->updateVerificationStatus();
        }

        return 0;
    }
}
