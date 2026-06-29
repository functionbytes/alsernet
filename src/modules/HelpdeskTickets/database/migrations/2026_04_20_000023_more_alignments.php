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

        // helpdesk_ticket_groups — align with helpdesk_groups feature set
        if ($schema->hasTable('helpdesk_ticket_groups')) {
            $schema->table('helpdesk_ticket_groups', function (Blueprint $t) use ($schema) {
                if (! $schema->hasColumn('helpdesk_ticket_groups', 'is_active')) {
                    $t->boolean('is_active')->default(true)->index();
                }
                if (! $schema->hasColumn('helpdesk_ticket_groups', 'email')) {
                    $t->string('email')->nullable();
                }
                if (! $schema->hasColumn('helpdesk_ticket_groups', 'position')) {
                    $t->integer('position')->default(0)->index();
                }
                if (! $schema->hasColumn('helpdesk_ticket_groups', 'assignment_mode')) {
                    $t->string('assignment_mode', 32)->default('round_robin');
                }
                if (! $schema->hasColumn('helpdesk_ticket_groups', 'deleted_at')) {
                    $t->softDeletes();
                }
            });
        }

        // helpdesk_ticket_canned_replies — add is_active
        if ($schema->hasTable('helpdesk_ticket_canned_replies') && ! $schema->hasColumn('helpdesk_ticket_canned_replies', 'is_active')) {
            $schema->table('helpdesk_ticket_canned_replies', fn (Blueprint $t) => $t->boolean('is_active')->default(true)->index());
        }

        // helpdesk_ticket_category_ticket_group pivot — add timestamps
        if ($schema->hasTable('helpdesk_ticket_category_ticket_group') && ! $schema->hasColumn('helpdesk_ticket_category_ticket_group', 'created_at')) {
            $schema->table('helpdesk_ticket_category_ticket_group', function (Blueprint $t) {
                $t->timestamps();
            });
        }
    }

    public function down(): void {}
};
