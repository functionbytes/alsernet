<?php

namespace App\Jobs;

use App\Model\MailList;
use Exception;
use App\Library\Traits\Trackable;
use Illuminate\Bus\Batchable;

class VerifyMailListJob extends Base
{
    use Batchable;
    use Trackable;

    public $timeout = 14400;
    protected $mailList;
    protected $server;
    protected $subscription;

    public function __construct($mailList, $server, $subscription)
    {
        $this->mailList = $mailList;
        $this->server = $server;
        $this->subscription = $subscription;

        $this->afterDispatched(function ($thisJob, $monitor) {
            $monitor->setJsonData([
                'percentage' => 0,
                'total' => 0,
                'processed' => 0,
                'failed' => 0,
                'message' => 'Verification process is being queued...',
            ]);
        });
    }

    public function handle()
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        $this->monitor->updateJsonData([
            'message' => 'Verification is in progress...',
        ]);

        // Get subscribers that are not verified
        $query = $this->mailList->subscribers()->unverified();

        if (!$query->exists()) {
            throw new Exception('There is no unverified contact in your list');
        }

        $this->monitor->updateJsonData([
            'message' => "Verification is in progress ({$query->count()})...",
        ]);

        // Query batches of 1000 records each, dispatch the verification job
        // Add job to batch
        cursorIterate($query, 'subscribers.id', $size = 1000, function ($subscribers, $page) {
            foreach ($subscribers as $subscriber) {
                $this->batch()->add(new VerifySubscriber($subscriber, $this->server, $this->subscription));
            }
        });
    }
}
