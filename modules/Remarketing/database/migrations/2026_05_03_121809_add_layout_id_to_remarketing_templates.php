<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remarketing_templates', function (Blueprint $table): void {
            $table->foreignId('layout_id')
                ->nullable()
                ->after('mailer_template_id')
                ->constrained('mailer_layouts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('remarketing_templates', function (Blueprint $table): void {
            $table->dropForeign(['layout_id']);
            $table->dropColumn('layout_id');
        });
    }
};
