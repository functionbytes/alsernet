<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('document_type');
            $table->string('document_type_label')->nullable();
            $table->json('required_documents')->nullable();
            $table->string('storage_disk')->nullable();
            $table->string('storage_path')->nullable();
            $table->json('storage_config')->nullable();
            $table->boolean('is_storage_enabled')->default(false);
            $table->timestamps();

            // Fields for DocumentsConfiguration
            $table->boolean('enable_initial_request')->default(true);
            $table->text('initial_request_message')->nullable();
            $table->boolean('enable_reminder')->default(true);
            $table->integer('reminder_days')->default(7);
            $table->text('reminder_message')->nullable();
            $table->boolean('enable_missing_docs')->default(true);
            $table->text('missing_docs_message')->nullable();

            $table->unique('document_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_configurations');
    }
};
