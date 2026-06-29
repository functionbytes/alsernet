<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_sending_server_bounce_handlers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name');
            $table->string('type', 16)->default('imap'); // imap|pop3
            $table->string('host')->nullable();
            $table->integer('port')->nullable();
            $table->string('protocol', 32)->nullable(); // ssl|tls|none
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // encrypted
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_sending_server_bounce_handlers');
    }
};
