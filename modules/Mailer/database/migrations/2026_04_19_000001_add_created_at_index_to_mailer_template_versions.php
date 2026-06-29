<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mailer_template_versions', function (Blueprint $table) {
            $table->index(['mailer_template_id', 'created_at'], 'idx_versions_template_created');
        });
    }

    public function down(): void
    {
        Schema::table('mailer_template_versions', function (Blueprint $table) {
            $table->dropIndex('idx_versions_template_created');
        });
    }
};
