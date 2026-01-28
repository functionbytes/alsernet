<?php

namespace App\Console\Commands\Mailing;

class LayoutsAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_layouts';

    protected $signature = 'mailing:layouts {action : Action to perform (list, create, show, update, delete)} {--id= : Record ID} {--alias= : Layout alias} {--group_name= : Group name} {--type= : Layout type} {--subject= : Subject}';

    protected $description = 'Manage mailing_layouts table - Create, read, update, and delete layout records';

    protected array $skipColumns = ['id', 'uid', 'created_at', 'updated_at', 'content'];
}
