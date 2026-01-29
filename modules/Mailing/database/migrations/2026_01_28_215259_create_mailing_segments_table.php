<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_segments', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36);
            $table->foreignId('mail_list_id');
            $table->char('name', 191);
            $table->char('matching', 191);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign Keys
            $table->foreign('mail_list_id')
                ->references('id')
                ->on('mailing_mail_lists')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_segments');
    }
};
