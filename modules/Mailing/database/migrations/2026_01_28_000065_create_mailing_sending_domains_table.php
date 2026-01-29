<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_sending_domains', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('admin_id')->nullable();
            $table->unsignedInteger('customer_id')->nullable();
            $table->string('name');
            $table->text('dkim_private');
            $table->text('dkim_public');
            $table->string('signing_enabled')->default(1);
            $table->string('status')->nullable();
            $table->text('verification_token')->nullable();
            $table->unsignedInteger('sending_server_id')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('admin_id')->references('id')->on('mailing_admins')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('mailing_customers')->onDelete('set null');
            $table->foreign('sending_server_id')->references('id')->on('mailing_sending_servers')->onDelete('set null');

            $table->index('admin_id');
            $table->index('customer_id');
            $table->index('sending_server_id');

            // Foreign Keys
            $table->foreign('admin_id')
                ->references('id')
                ->on('mailing_admins')
                ->onDelete('set null');
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('set null');
            $table->foreign('sending_server_id')
                ->references('id')
                ->on('mailing_sending_servers')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_sending_domains');
    }
};
