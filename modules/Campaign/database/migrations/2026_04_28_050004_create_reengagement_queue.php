<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_reengagement_queue', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('subscriber_id');
            $table->enum('status', ['pending', 'processed', 'skipped'])->default('pending');
            $table->timestamps();
            $table->unique('subscriber_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_reengagement_queue');
    }
};
