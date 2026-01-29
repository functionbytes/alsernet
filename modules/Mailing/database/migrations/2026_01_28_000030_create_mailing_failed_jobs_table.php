<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_failed_jobs', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at');
            $table->string('uuid')->unique();

        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_failed_jobs');
    }
};
