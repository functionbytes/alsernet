<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_sending_servers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name');
            $table->string('type', 64)->index();
            $table->string('status', 32)->default('active')->index();

            // SMTP / Sendmail / API
            $table->string('host')->nullable();
            $table->text('smtp_username')->nullable();
            $table->text('smtp_password')->nullable(); // encrypted
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_protocol', 16)->nullable();
            $table->string('sendmail_path')->nullable();

            // AWS SES
            $table->text('aws_access_key_id')->nullable();   // encrypted
            $table->text('aws_secret_access_key')->nullable(); // encrypted
            $table->string('aws_region', 32)->nullable();

            // Otras APIs
            $table->string('domain')->nullable();
            $table->text('api_key')->nullable();        // encrypted
            $table->text('api_secret_key')->nullable(); // encrypted

            // Cuotas
            $table->integer('quota_value')->nullable();
            $table->integer('quota_base')->nullable();
            $table->string('quota_unit', 32)->nullable();

            // Handlers
            $table->unsignedBigInteger('bounce_handler_id')->nullable()->index();
            $table->unsignedBigInteger('feedback_loop_handler_id')->nullable()->index();

            // Defaults
            $table->string('default_from_email')->nullable();
            $table->string('username')->nullable();

            // Opciones libres del proveedor
            $table->json('options')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_sending_servers');
    }
};
