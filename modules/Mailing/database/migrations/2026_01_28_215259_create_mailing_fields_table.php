<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_fields', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36);
            $table->foreignId('mail_list_id');
            $table->char('label', 191);
            $table->char('type', 191);
            $table->char('tag', 191);
            $table->char('default_value', 191)->nullable();
            $table->integer('visible')->default(1);
            $table->integer('required')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('is_email')->default(0);

            // Foreign Keys
            $table->foreign('mail_list_id')
                ->references('id')
                ->on('mailing_mail_lists')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_fields');
    }
};
