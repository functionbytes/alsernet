<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('return_states', 'id_return_state');
            $table->string('color')->nullable();
            $table->boolean('send_email')->default(false);
            $table->boolean('is_pickup')->default(false);
            $table->boolean('is_received')->default(false);
            $table->boolean('is_refunded')->default(false);
            $table->boolean('shown_to_customer')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_status');
    }
};
