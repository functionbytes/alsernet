<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_email_verification_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('server_id')
                ->references('id')
                ->on('mailing_email_verification_servers')
                ->onDelete('cascade');

            $table->string('email')->index();
            $table->enum('result', ['valid', 'invalid', 'risky', 'unknown']);
            $table->string('reason')->nullable();
            $table->json('details')->nullable();

            // Validation details
            $table->boolean('is_disposable')->default(false);
            $table->boolean('is_role_based')->default(false);
            $table->boolean('is_catch_all')->default(false);
            $table->boolean('smtp_valid')->default(false);
            $table->integer('score')->nullable(); // 0-100 quality score

            // Cache management
            $table->timestamp('verified_at');
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['server_id', 'email']);
            $table->index('result');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_email_verification_results');
    }
};
