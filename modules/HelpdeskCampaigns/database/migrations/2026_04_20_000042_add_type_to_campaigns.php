<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_campaigns') && ! $schema->hasColumn('helpdesk_campaigns', 'type')) {
            $schema->table('helpdesk_campaigns', function (Blueprint $t) {
                $t->string('type', 32)->default('announcement')->after('name')->index();
            });
        }
    }

    public function down(): void {}
};
