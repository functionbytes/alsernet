<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_automation_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('remarketing_automations')->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->enum('type', ['wait', 'send_email']);
            $table->json('config');
            $table->timestamps();

            $table->index(['automation_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_automation_steps');
    }
};
