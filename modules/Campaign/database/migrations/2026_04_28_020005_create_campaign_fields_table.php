<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_fields', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('mail_list_id')
                ->nullable()
                ->constrained('campaign_maillists')
                ->cascadeOnDelete();
            $table->string('tag')->index(); // FIRST_NAME, LAST_NAME, etc.
            $table->string('label');
            $table->string('type', 32)->default('text'); // text|email|number|select|date|...
            $table->string('default_value')->nullable();
            $table->boolean('required')->default(false);
            $table->boolean('visible')->default(true);
            $table->integer('order')->default(0);
            $table->json('options')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_field_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('field_id')
                ->constrained('campaign_fields')
                ->cascadeOnDelete();
            $table->string('value');
            $table->string('label');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_field_options');
        Schema::dropIfExists('campaign_fields');
    }
};
