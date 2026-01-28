<?php

namespace App\Console\Commands\Mailing;

class SubscribersAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_subscribers';

    protected $signature = 'mailing:subscribers {action : Action to perform (list, create, show, update, delete)} {--id= : Record ID} {--mail_list_id= : Mail list ID} {--email= : Email address} {--status= : Status} {--subscription_type= : Subscription type} {--tags= : Tags} {--verification_status= : Verification status}';

    protected $description = 'Manage mailing_subscribers table - Create, read, update, and delete subscriber records';

    protected array $skipColumns = ['id', 'uid', 'created_at', 'updated_at'];

    protected array $foreignKeys = ['mail_list_id'];
}
