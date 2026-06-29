<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_backups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('environment')->default('dev');
            $table->json('config_files')->nullable();
            $table->json('supervisor_status')->nullable();
            $table->unsignedBigInteger('backup_size')->default(0);
            $table->boolean('is_auto')->default(false);
            $table->timestamp('backed_up_at')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->unsignedBigInteger('restored_by')->nullable();
            $table->timestamps();

            $table->index(['environment', 'backed_up_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_backups');
    }
};
