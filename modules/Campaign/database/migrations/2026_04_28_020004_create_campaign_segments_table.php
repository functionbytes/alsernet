<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_segments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('mail_list_id')
                ->constrained('campaign_maillists')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('matching', 16)->default('and'); // and | or
            $table->timestamps();
        });

        Schema::create('campaign_segment_conditions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('segment_id')
                ->constrained('campaign_segments')
                ->cascadeOnDelete();
            $table->string('field')->index();   // email | first_name | tag | open_count | click_count | etc.
            $table->string('operator', 32);     // equals|not_equals|contains|gt|lt|...
            $table->text('value')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_segment_conditions');
        Schema::dropIfExists('campaign_segments');
    }
};
