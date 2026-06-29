<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('session_id', 40)->nullable();
            $table->string('ip_hash', 64);
            $table->string('locale', 10)->nullable();
            $table->string('referrer')->nullable();
            $table->string('referrer_domain', 100)->nullable();
            $table->enum('device_type', ['desktop', 'mobile', 'tablet'])->default('desktop');
            $table->string('browser', 50)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->date('viewed_date');
            $table->timestamp('viewed_at');

            $table->index(['page_id', 'viewed_date']);
            $table->index(['page_id', 'device_type']);
            $table->index('viewed_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
