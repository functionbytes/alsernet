<?php

namespace App\Console\Commands\Mailing;

class ContactsAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_contacts';

    protected $signature = 'mailing:contacts {action : Action to perform (list, create, show, update, delete)} {--id= : Record ID} {--first_name= : First name} {--last_name= : Last name} {--email= : Email address} {--company= : Company name} {--address_1= : Address line 1} {--city= : City} {--country_id= : Country ID} {--phone= : Phone}';

    protected $description = 'Manage mailing_contacts table - Create, read, update, and delete contact records';

    protected array $skipColumns = ['id', 'uid', 'created_at', 'updated_at'];

    protected array $foreignKeys = ['country_id'];
}
