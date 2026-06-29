<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mails_email_templates', function (Blueprint $table) {
            $table->foreignId('layout_id')->nullable()->after('subject')->constrained('mailrelay_layouts')->nullOnDelete();
            $table->boolean('use_layout')->default(true)->after('layout_id');
        });

        // Create pivot table for template-variable relationship
        Schema::create('email_template_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_template_id')->constrained('mails_email_templates')->cascadeOnDelete();
            $table->foreignId('mailrelay_variable_id')->constrained('mailrelay_variables')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['email_template_id', 'mailrelay_variable_id'], 'template_variable_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_template_variables');

        Schema::table('mails_email_templates', function (Blueprint $table) {
            $table->dropForeign(['layout_id']);
            $table->dropColumn(['layout_id', 'use_layout']);
        });
    }
};
