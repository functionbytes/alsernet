<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_plans', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('admin_id')->nullable();
            $table->unsignedInteger('currency_id');
            $table->string('name');
            $table->decimal('price', 16, 2);
            $table->string('frequency_amount');
            $table->string('frequency_unit');
            $table->text('options');
            $table->string('status');
            $table->text('description')->nullable();
            $table->string('visible')->default(0);
            $table->integer('trial_amount')->nullable();
            $table->string('trial_unit')->nullable();
            $table->boolean('own_tracking_domain_required')->default(0);
            $table->string('type');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('admin_id')->references('id')->on('mailing_admins')->onDelete('set null');
            $table->foreign('currency_id')->references('id')->on('mailing_currencies')->onDelete('cascade');

            $table->index('admin_id');
            $table->index('currency_id');

            // Foreign Keys
            $table->foreign('admin_id')
                ->references('id')
                ->on('mailing_admins')
                ->onDelete('set null');
            $table->foreign('currency_id')
                ->references('id')
                ->on('mailing_currencies')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_plans');
    }
};
