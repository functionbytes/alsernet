<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_message_configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('envelope_image')->nullable();
            $table->string('card_image')->nullable();
            $table->decimal('env_t1_x', 6, 2)->default(10.00);
            $table->decimal('env_t1_y', 6, 2)->default(10.00);
            $table->decimal('env_t2_x', 6, 2)->default(10.00);
            $table->decimal('env_t2_y', 6, 2)->default(20.00);
            $table->decimal('card_t1_x', 6, 2)->default(10.00);
            $table->decimal('card_t1_y', 6, 2)->default(10.00);
            $table->decimal('card_t2_x', 6, 2)->default(10.00);
            $table->decimal('card_t2_y', 6, 2)->default(20.00);
            $table->string('env_t1_font', 64)->default('helvetica');
            $table->unsignedInteger('env_t1_size')->default(14);
            $table->string('env_t2_font', 64)->default('helvetica');
            $table->unsignedInteger('env_t2_size')->default(12);
            $table->string('card_t1_font', 64)->default('helvetica');
            $table->unsignedInteger('card_t1_size')->default(14);
            $table->string('card_t2_font', 64)->default('helvetica');
            $table->unsignedInteger('card_t2_size')->default(12);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_message_configs');
    }
};
