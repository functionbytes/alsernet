<?php

namespace App\Console\Commands\Mailing;

class CampaignsAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_campaigns';

    protected $signature = 'mailing:campaigns {action : Action to perform (list, create, show, update, delete)} {--id= : Record ID} {--name= : Campaign name} {--type= : Campaign type} {--subject= : Subject line} {--from_email= : From email} {--from_name= : From name} {--status= : Status} {--customer_id= : Customer ID} {--template_id= : Template ID}';

    protected $description = 'Manage mailing_campaigns table - Create, read, update, and delete campaign records';

    protected array $skipColumns = ['id', 'uid', 'created_at', 'updated_at', 'plain', 'template_source', 'last_error', 'image'];

    protected array $foreignKeys = ['customer_id', 'default_mail_list_id', 'tracking_domain_id', 'template_id'];
}
