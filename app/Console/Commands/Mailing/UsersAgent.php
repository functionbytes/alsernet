<?php

namespace App\Console\Commands\Mailing;

class UsersAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_users';

    protected $signature = 'mailing:users {action : Action to perform (list, create, show, update, delete)} {--id= : Record ID} {--email= : Email address} {--password= : Password} {--first_name= : First name} {--last_name= : Last name} {--status= : Status} {--activated= : Activated (0 or 1)} {--customer_id= : Customer ID} {--creator_id= : Creator ID}';

    protected $description = 'Manage mailing_users table - Create, read, update, and delete user records';

    protected array $skipColumns = ['uid', 'api_token', 'remember_token', 'one_time_api_token', 'created_at', 'updated_at', 'google_id', 'google_token', 'google_refresh_token', 'facebook_id', 'facebook_token', 'facebook_refresh_token', 'onscreen_intros'];

    protected array $foreignKeys = ['creator_id', 'customer_id'];
}
