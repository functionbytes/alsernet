<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_sports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('erp_id')->unique()->comment('IDDEPORTE_CL Oracle');
            $table->string('name', 255)->comment('descripcion');
            $table->string('short_name', 100)->nullable()->comment('desc_corta');
            $table->boolean('available')->default(true)->comment('estado');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index('available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_sports');
    }
};
