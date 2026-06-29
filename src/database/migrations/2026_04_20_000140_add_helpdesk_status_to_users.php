<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'helpdesk_status')) {
                $t->string('helpdesk_status', 16)->default('offline');
            }
            if (! Schema::hasColumn('users', 'helpdesk_status_updated_at')) {
                $t->timestamp('helpdesk_status_updated_at')->nullable();
            }
        });
    }

    public function down(): void {}
};
