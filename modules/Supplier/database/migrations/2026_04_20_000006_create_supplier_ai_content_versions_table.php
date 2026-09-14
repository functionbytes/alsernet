<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_ai_content_versions')) {
            return;
        }

        Schema::create('supplier_ai_content_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('supplier_ai_contents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('version_number');
            $table->longText('long_description')->nullable();
            $table->string('generated_name')->nullable();
            $table->text('short_description')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('change_reason', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['content_id', 'version_number']);
            $table->index(['content_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_ai_content_versions');
    }
};
