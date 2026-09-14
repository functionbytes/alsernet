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
        Schema::create('supplier_credentials', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique()->comment('ULID unique identifier');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreignId('source_id')->nullable()->constrained('supplier_sources')->nullOnDelete();
            $table->enum('credential_type', ['ftp', 'api', 'oauth', 'basic_auth', 'proxy']);
            $table->string('name', 100)->comment('Identifier name');
            $table->text('credentials')->comment('Encrypted JSON credentials');
            $table->timestamp('expires_at')->nullable()->comment('Expiration date');
            $table->timestamp('last_used_at')->nullable()->comment('Last time used');
            $table->boolean('is_valid')->default(true)->comment('If credentials are valid');
            $table->text('validation_error')->nullable()->comment('Last validation error');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('credential_type');
            $table->index(['supplier_id', 'source_id']);
            $table->index('is_valid');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_credentials');
    }
};
