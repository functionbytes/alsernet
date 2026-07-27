<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::create('engagement_segments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inbox_id')->index();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->json('conditions');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('engagement_segment_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('segment_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->timestamp('matched_at');
            $table->unique(['segment_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engagement_segment_customers');
        Schema::dropIfExists('engagement_segments');
    }
};
