<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_campaign_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('template_type');
            $table->longText('template_content');
            $table->json('variables')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('template_type');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_campaign_templates');
    }
};
