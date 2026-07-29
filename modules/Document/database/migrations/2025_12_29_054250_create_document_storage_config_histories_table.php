<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_storage_config_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_configuration_id')->constrained('document_configurations')->cascadeOnDelete();
            $table->json('old_values');
            $table->json('new_values');
            $table->string('change_type');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index('document_configuration_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_storage_config_histories');
    }
};
