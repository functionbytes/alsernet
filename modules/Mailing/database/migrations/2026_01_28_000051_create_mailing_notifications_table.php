<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_notifications', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->text('type');
            $table->text('title');
            $table->text('message');
            $table->text('level');
            $table->unsignedInteger('admin_id')->nullable();
            $table->unsignedInteger('customer_id')->nullable();
            $table->boolean('visibility')->default(1);
            $table->mediumText('debug')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('admin_id')->references('id')->on('mailing_admins')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('mailing_customers')->onDelete('set null');

            $table->index('admin_id');
            $table->index('customer_id');

            // Foreign Keys
            $table->foreign('admin_id')
                ->references('id')
                ->on('mailing_admins')
                ->onDelete('set null');
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_notifications');
    }
};
