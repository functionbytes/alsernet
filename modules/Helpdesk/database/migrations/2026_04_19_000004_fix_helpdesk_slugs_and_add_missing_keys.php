<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        // Make slug nullable where seeders don't set it
        Schema::connection($this->connection)->table('helpdesk_categories', function (Blueprint $table) {
            $table->string('slug')->nullable()->change();
        });

        Schema::connection($this->connection)->table('helpdesk_conversation_statuses', function (Blueprint $table) {
            $table->string('slug')->nullable()->change();
        });

        // Add key to helpdesk_groups
        Schema::connection($this->connection)->table('helpdesk_groups', function (Blueprint $table) {
            $table->string('key')->nullable()->unique()->after('name');
            $table->string('description')->nullable()->after('key');
            $table->boolean('is_active')->default(true)->after('description');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_categories', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });

        Schema::connection($this->connection)->table('helpdesk_conversation_statuses', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });

        Schema::connection($this->connection)->table('helpdesk_groups', function (Blueprint $table) {
            $table->dropColumn(['key', 'description', 'is_active']);
        });
    }
};
