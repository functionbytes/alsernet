<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_password_resets', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at');

        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_password_resets');
    }
};
