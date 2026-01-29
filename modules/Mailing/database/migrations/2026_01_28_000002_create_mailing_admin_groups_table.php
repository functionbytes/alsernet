<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_admin_groups', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('name');
            $table->text('options')->nullable();
            $table->text('permissions')->nullable();
            $table->unsignedInteger('creator_id')->nullable();
            $table->timestamps();

            $table->foreign('creator_id')->references('id')->on('mailing_users')->onDelete('set null');
            $table->index('creator_id');

            // Foreign Keys
            $table->foreign('creator_id')
                ->references('id')
                ->on('mailing_users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_admin_groups');
    }
};
