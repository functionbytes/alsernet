<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_customers', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('admin_id')->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedInteger('language_id')->nullable();
            $table->string('timezone');
            $table->string('status')->nullable();
            $table->string('color_scheme')->nullable();
            $table->longText('quota')->nullable();
            $table->string('text_direction')->default('ltr');
            $table->longText('payment_method')->nullable();
            $table->longText('auto_billing_data')->nullable();
            $table->string('menu_layout')->default('none');
            $table->string('theme_mode')->default('light');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('admin_id')->references('id')->on('mailing_admins')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('mailing_contacts')->onDelete('set null');
            $table->foreign('language_id')->references('id')->on('mailing_languages')->onDelete('set null');

            $table->index('admin_id');
            $table->index('contact_id');
            $table->index('language_id');

            // Foreign Keys
            $table->foreign('admin_id')
                ->references('id')
                ->on('mailing_admins')
                ->onDelete('set null');
            $table->foreign('contact_id')
                ->references('id')
                ->on('mailing_contacts')
                ->onDelete('set null');
            $table->foreign('language_id')
                ->references('id')
                ->on('mailing_languages')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_customers');
    }
};
