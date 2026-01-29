<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_pages', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('layout_id');
            $table->unsignedInteger('mail_list_id');
            $table->longText('content');
            $table->string('subject');
            $table->boolean('use_outside_url')->default(0);
            $table->string('outside_url')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('layout_id')->references('id')->on('mailing_layouts')->onDelete('cascade');
            $table->foreign('mail_list_id')->references('id')->on('mailing_mail_lists')->onDelete('cascade');

            $table->index('layout_id');
            $table->index('mail_list_id');

            // Foreign Keys
            $table->foreign('layout_id')
                ->references('id')
                ->on('mailing_layouts')
                ->onDelete('cascade');
            $table->foreign('mail_list_id')
                ->references('id')
                ->on('mailing_mail_lists')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_pages');
    }
};
