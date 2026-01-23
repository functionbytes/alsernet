<?php

namespace Modules\Core\Console\Commands;

use Acelle\Model\Log;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log as LaravelLog;

class SystemCleanup extends Command
{
    protected $signature = 'system:cleanup';

    protected $description = 'System cleanup';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        /*
        // Delete old log
        Log::where('created_at', '<', new Carbon('1 year ago'))->delete();

        // Delete orphan subscription
        $query = Subscription::leftJoin('customers', 'subscriptions.customer_id', '=', 'customers.id')->whereNull('customers.id');
        if ($query->count()) {
            LaravelLog::warning('Orphan subscriptions');
            $query->delete();
        }
        */
        return 0;
    }
}
