<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remarketing_automation_steps', function (Blueprint $table) {
            $table->foreignId('mailer_template_id')
                ->nullable()
                ->after('config')
                ->constrained('mailer_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('remarketing_automation_steps', function (Blueprint $table) {
            $table->dropForeign(['mailer_template_id']);
            $table->dropColumn('mailer_template_id');
        });
    }
};
