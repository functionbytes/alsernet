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
        Schema::create('proxies', function (Blueprint $table) {
            $table->id();
            $table->string('id_secure')->unique();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();

            // Proxy Details
            $table->string('name');
            $table->string('host');
            $table->integer('port')->default(8080);
            $table->string('username')->nullable();
            $table->string('password')->nullable(); // Should be encrypted
            $table->enum('type', ['http', 'https', 'socks4', 'socks5'])->default('http');

            // Status
            $table->tinyInteger('status')->default(1); // 0=disabled, 1=active

            $table->timestamps();

            // Indexes
            $table->index(['account_id', 'status']);
            $table->index('id_secure');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proxies');
    }
};
