<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level')->index();
            $table->string('channel')->nullable()->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->json('extra')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address')->nullable();
            $table->string('url')->nullable();
            $table->string('method')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['level', 'created_at']);
            $table->index(['channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_logs');
    }
};
