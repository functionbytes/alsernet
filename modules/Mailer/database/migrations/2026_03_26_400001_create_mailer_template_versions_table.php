<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailer_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailer_template_id')->constrained('mailer_templates')->cascadeOnDelete();
            $table->foreignId('lang_id')->constrained('langs')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject')->nullable();
            $table->longText('content')->nullable();
            $table->text('change_note')->nullable();
            $table->timestamps();

            $table->index(['mailer_template_id', 'lang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailer_template_versions');
    }
};
