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
        Schema::create('supplier_sources', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique()->comment('UUID unique identifier');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('cascade');
            $table->enum('source_type', ['website', 'ftp', 'file', 'api'])->comment('Type of data source');
            $table->string('label', 255)->comment('Descriptive name for the source');
            $table->text('description')->nullable()->comment('Notes about the source');
            $table->enum('trust_level', ['high', 'medium', 'low'])->default('medium')->comment('Trust level of the source');
            $table->text('usage_notes')->nullable()->comment('Restrictions (e.g., "inspiration only")');
            $table->integer('priority')->default(1)->comment('Priority order (1 = highest priority)');
            $table->boolean('is_active')->default(true)->comment('Active/inactive source');
            $table->timestamp('last_accessed_at')->nullable()->comment('Last time the source was accessed');
            $table->timestamps();

            $table->index(['supplier_id', 'source_type']);
            $table->index(['supplier_id', 'is_active']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_sources');
    }
};
