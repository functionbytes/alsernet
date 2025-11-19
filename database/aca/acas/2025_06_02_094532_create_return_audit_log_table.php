<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnAuditLogTable extends Migration
{
    public function up()
    {
        Schema::create('return_audit_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('event'); // created, updated, deleted, status_changed, etc.
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('user_id');
            $table->index('event');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_audit_log');
    }
}

