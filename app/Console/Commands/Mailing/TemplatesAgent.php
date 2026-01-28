<?php

namespace App\Console\Commands\Mailing;

class TemplatesAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_templates';

    protected $signature = 'mailing:templates {action : Action to perform (list, create, show, update, delete)} {--id= : Record ID} {--name= : Template name} {--type= : Template type} {--builder= : Builder mode (0 or 1)} {--is_default= : Is default (0 or 1)} {--is_private= : Is private (0 or 1)} {--customer_id= : Customer ID} {--theme= : Theme name}';

    protected $description = 'Manage mailing_templates table - Create, read, update, and delete email template records';

    protected array $skipColumns = ['id', 'uid', 'created_at', 'updated_at', 'content'];

    protected array $foreignKeys = ['customer_id'];
}
