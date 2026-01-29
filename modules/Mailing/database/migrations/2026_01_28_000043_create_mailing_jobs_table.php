<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->string('queue');
            $table->longText('payload');
            $table->unsignedInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');

        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_jobs');
    }
};
