<?php

namespace App\Console\Commands\Mailing;

class LanguagesAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_languages';

    protected $signature = 'mailing:languages {action : Action to perform (list, create, show, update, delete)} {--id= : Record ID} {--name= : Language name} {--code= : Language code} {--region_code= : Region code} {--status= : Status} {--is_default= : Is default (0 or 1)}';

    protected $description = 'Manage mailing_languages table - Create, read, update, and delete language records';

    protected array $skipColumns = ['id', 'uid', 'created_at', 'updated_at'];
}
