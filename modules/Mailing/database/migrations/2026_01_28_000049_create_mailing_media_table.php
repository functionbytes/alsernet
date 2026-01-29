<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_media', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->string('uid')->nullable()->unique();
            $table->integer('customer_id')->nullable();
            $table->string('source')->nullable();
            $table->string('type')->nullable();
            $table->string('dir')->nullable();
            $table->string('ext')->nullable();
            $table->string('slug')->nullable();
            $table->string('thumes')->nullable();
            $table->string('status')->nullable();
            $table->string('author')->nullable();
            $table->text('caption')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_media');
    }
};
