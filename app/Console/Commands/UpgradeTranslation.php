<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Acelle\Library\UpgradeManager;
use Acelle\Model\Language;

class UpgradeTranslation extends Command
{

    protected $signature = 'translation:upgrade';

    protected $description = 'Update translation files to make those up-to-date with the default EN language';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        \Acelle\Helpers\pcopy(resource_path('lang/en'), resource_path('lang/default'));
        Language::dump();
    }

}
