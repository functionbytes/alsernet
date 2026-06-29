<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chat_email_templates') && Schema::hasColumn('chat_email_templates', 'subject')) {
            return;
        }

        Schema::dropIfExists('chat_email_templates');

        Schema::create('chat_email_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('name');
            $table->string('event_type')->nullable()->index();
            $table->string('subject');
            $table->longText('body');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['account_id', 'active']);
            $table->foreign('account_id')->references('id')->on('chat_accounts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_email_templates');
    }
};
