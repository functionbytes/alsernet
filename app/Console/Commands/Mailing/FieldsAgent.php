<?php

namespace App\Console\Commands\Mailing;

class FieldsAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_fields';

    protected $signature = 'mailing:fields {action : Action to perform (list, create, show, update, delete)} {--id= : Record ID} {--mail_list_id= : Mail list ID} {--label= : Field label} {--type= : Field type} {--tag= : Field tag} {--visible= : Visible (0 or 1)} {--required= : Required (0 or 1)} {--is_email= : Is email (0 or 1)}';

    protected $description = 'Manage mailing_fields table - Create, read, update, and delete field records';

    protected array $skipColumns = ['id', 'uid', 'created_at', 'updated_at'];

    protected array $foreignKeys = ['mail_list_id'];
}
