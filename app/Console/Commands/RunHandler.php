<?php

/**
 * RunHandler class.
 *
 * CLI interface for trigger email handling by cronjob (bounce, feedback)
 *
 * LICENSE: This product includes software developed at
 * the Acelle Co., Ltd. (http://acellemail.com/).
 *
 * @category   Console App
 *
 * @author     N. Pham <n.pham@acellemail.com>
 * @author     L. Pham <l.pham@acellemail.com>
 * @copyright  Acelle Co., Ltd
 * @license    Acelle Co., Ltd
 *
 * @version    1.0
 *
 * @link       http://acellemail.com
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Acelle\Model\BounceHandler;
use Acelle\Model\FeedbackLoopHandler;
use Acelle\Library\Log;
use Acelle\Library\Lockable;

class RunHandler extends Command
{

    protected $signature = 'handler:run';

    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $timeoutCallback = function () {
        };

        $lock = new Lockable(storage_path('locks/bounce-feedback-handler'));
        $lock->getExclusiveLock(function () {
            $this->execRunHandler();
        }, $timeout = 5, $timeoutCallback);

        Log::info('Handlers finished!');

        return 0;
    }

    private function execRunHandler()
    {
        Log::info('Try to start handling process...');

        $handlers = BounceHandler::get();
        Log::info(sizeof($handlers).' bounce handlers found');
        $count = 1;
        foreach ($handlers as $handler) {
            Log::info('Starting handler '.$handler->name." ($count/".sizeof($handlers).')');
            $handler->start();
            Log::info('Finish processing handler '.$handler->name);
            $count += 1;
        }

        $handlers = FeedbackLoopHandler::get();
        Log::info(sizeof($handlers).' feedback loop handlers found');
        $count = 1;
        foreach ($handlers as $handler) {
            Log::info('Starting handler '.$handler->name." ($count/".sizeof($handlers).')');
            $handler->start();
            Log::info('Finish processing handler '.$handler->name);
            $count += 1;
        }
    }
}
