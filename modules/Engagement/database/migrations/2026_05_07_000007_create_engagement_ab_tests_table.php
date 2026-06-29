<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engagement_ab_tests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inbox_id')->index();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft'); // draft, running, paused, completed
            $table->unsignedInteger('sample_size')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('winner_variant_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('engagement_ab_test_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ab_test_id')->index();
            $table->string('name', 100);
            $table->json('config'); // trigger rule config
            $table->unsignedInteger('weight')->default(50);
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engagement_ab_test_variants');
        Schema::dropIfExists('engagement_ab_tests');
    }
};
