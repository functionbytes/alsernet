<?php

namespace App\Console\Commands\Mailing;

class SegmentsAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_segments';

    protected $signature = 'mailing:segments {action : Action to perform (list, create, show, update, delete)} {--id= : Record ID} {--mail_list_id= : Mail list ID} {--name= : Segment name} {--matching= : Matching condition}';

    protected $description = 'Manage mailing_segments table - Create, read, update, and delete segment records';

    protected array $skipColumns = ['id', 'uid', 'created_at', 'updated_at'];

    protected array $foreignKeys = ['mail_list_id'];
}
