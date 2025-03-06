<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use function Acelle\Helpers\updateTranslationFile;

class MergeTranslationFiles extends Command
{

    protected $signature = 'translation:merge {current} {update}';


    protected $description = 'Merge translation phrases from $new to $current (overwrite). The utility is helpful when we have a new translation file and want to apply it to a current file in the repos.
        IMPORTANT: do not merge any files under lang/en/ folder (which is considered the main language) or it may add redundant keys to the main file which will in turn propogate to the other files of other languages';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $current = $this->argument('current');
        $update = $this->argument('update');

        $maindir = realpath(resource_path('lang/en'));

        if (strpos(realpath($current), $maindir) === 0) {
            throw new \Exception('Cannot update a translation file of the main language (EN)');
        }

        updateTranslationFile($current, $update, $overwrite = true, $deleteTargetKeys = false, $sort = true);
    }

}
