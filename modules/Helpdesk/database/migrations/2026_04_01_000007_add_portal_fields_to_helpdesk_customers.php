<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('helpdesk_customers')) {
            return;
        }

        Schema::table('helpdesk_customers', function (Blueprint $table) {
            if (! Schema::hasColumn('helpdesk_customers', 'portal_token')) {
                $table->string('portal_token', 64)->nullable()->unique()->after('internal_notes');
            }

            if (! Schema::hasColumn('helpdesk_customers', 'portal_token_expires_at')) {
                $table->timestamp('portal_token_expires_at')->nullable()->after('portal_token');
            }

            if (! Schema::hasColumn('helpdesk_customers', 'portal_password')) {
                $table->string('portal_password')->nullable()->after('portal_token_expires_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('helpdesk_customers')) {
            return;
        }

        Schema::table('helpdesk_customers', function (Blueprint $table) {
            $columns = array_filter(
                ['portal_token', 'portal_token_expires_at', 'portal_password'],
                fn (string $col) => Schema::hasColumn('helpdesk_customers', $col)
            );

            if ($columns) {
                $table->dropColumn(array_values($columns));
            }
        });
    }
};
