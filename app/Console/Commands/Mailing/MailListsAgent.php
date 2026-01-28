<?php

namespace App\Console\Commands\Mailing;

class MailListsAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_mail_lists';

    protected $signature = 'mailing:mail-lists {action : Action to perform (list, create, show, update, delete)} {--id= : Record ID} {--name= : Mail list name} {--from_email= : From email address} {--from_name= : From name} {--status= : Status} {--customer_id= : Customer ID}';

    protected $description = 'Manage mailing_mail_lists table - Create, read, update, and delete mail list records';

    protected array $skipColumns = ['id', 'uid', 'created_at', 'updated_at'];

    protected array $foreignKeys = ['customer_id', 'contact_id'];
}
