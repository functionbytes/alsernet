<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->unsignedBigInteger('customer_id')->nullable()->index(); // Recursos globales: nullable
            $table->string('type', 32)->default('regular'); // regular | plain-text
            $table->string('name')->nullable()->index(); // Nombre interno
            $table->string('title')->nullable();         // Alias usado por código heredado
            $table->string('subject')->nullable();
            $table->text('plain')->nullable();
            $table->string('preheader')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('status', 32)->default('new')->index(); // new|queuing|queued|sending|done|paused|error|scheduled
            $table->boolean('sign_dkim')->default(false);
            $table->boolean('track_open')->default(true);
            $table->boolean('track_click')->default(true);
            $table->boolean('track_fbl')->default(false);
            $table->boolean('resend')->default(false);
            $table->boolean('skip_failed_message')->default(true);
            $table->boolean('use_default_sending_server_from_email')->default(false);
            $table->timestamp('run_at')->nullable()->index();
            $table->timestamp('delivery_at')->nullable();
            $table->string('template_source')->nullable();
            $table->text('last_error')->nullable();
            $table->string('image')->nullable();
            $table->unsignedBigInteger('default_maillist_id')->nullable()->index();
            $table->unsignedBigInteger('tracking_domain_id')->nullable()->index();
            $table->integer('running_pid')->nullable();
            $table->unsignedBigInteger('template_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
