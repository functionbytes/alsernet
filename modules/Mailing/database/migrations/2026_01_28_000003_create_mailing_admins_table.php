<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_admins', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->char('uid', 36);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('creator_id')->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedInteger('admin_group_id');
            $table->unsignedInteger('language_id')->nullable();
            $table->string('timezone');
            $table->string('status')->nullable();
            $table->string('color_scheme')->nullable();
            $table->string('text_direction')->default('ltr');
            $table->string('menu_layout')->default('none');
            $table->string('theme_mode')->default('light');
            $table->timestamps();

            $table->foreign('admin_group_id')->references('id')->on('mailing_admin_groups')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('mailing_contacts')->onDelete('set null');
            $table->foreign('creator_id')->references('id')->on('mailing_users')->onDelete('set null');
            $table->foreign('language_id')->references('id')->on('mailing_languages')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('mailing_users')->onDelete('cascade');

            $table->index('admin_group_id');
            $table->index('contact_id');
            $table->index('creator_id');
            $table->index('language_id');
            $table->index('user_id');

            // Foreign Keys
            $table->foreign('admin_group_id')
                ->references('id')
                ->on('mailing_admin_groups')
                ->onDelete('cascade');
            $table->foreign('contact_id')
                ->references('id')
                ->on('mailing_contacts')
                ->onDelete('set null');
            $table->foreign('creator_id')
                ->references('id')
                ->on('mailing_users')
                ->onDelete('set null');
            $table->foreign('language_id')
                ->references('id')
                ->on('mailing_languages')
                ->onDelete('set null');
            $table->foreign('user_id')
                ->references('id')
                ->on('mailing_users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_admins');
    }
};
