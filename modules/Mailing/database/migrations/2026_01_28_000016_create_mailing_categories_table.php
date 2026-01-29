<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->char('uid', 36)->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_categories');
    }
};
