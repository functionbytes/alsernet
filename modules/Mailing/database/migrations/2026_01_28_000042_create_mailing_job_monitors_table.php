<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_job_monitors', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->char('uid', 36)->unique();
            $table->string('subject_name');
            $table->bigInteger('subject_id');
            $table->string('batch_id')->nullable();
            $table->bigInteger('job_id')->nullable();
            $table->string('job_type')->nullable();
            $table->mediumText('error')->nullable();
            $table->mediumText('data')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_job_monitors');
    }
};
