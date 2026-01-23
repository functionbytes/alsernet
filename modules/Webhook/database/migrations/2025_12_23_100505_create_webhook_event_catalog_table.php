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
        Schema::create('webhook_event_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('order.created, document.received');
            $table->string('version', 10)->default('v1');
            $table->text('description')->nullable();
            $table->json('json_schema')->nullable()->comment('JSON Schema for validation');
            $table->json('example_payload')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['key', 'version'], 'idx_catalog_key_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_event_catalog');
    }
};
