<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('erp_id')->unique()->comment('IDFAMILIA_CL Oracle');
            $table->foreignId('sport_id')->nullable()->constrained('supplier_sports')->onDelete('set null');
            $table->unsignedBigInteger('erp_sport_id')->nullable()->comment('IDDEPORTE_CL Oracle (desnormalizado)');
            $table->string('name', 255)->comment('descripcion');
            $table->string('short_name', 100)->nullable()->comment('desc_corta');
            $table->boolean('available')->default(true)->comment('estado');
            $table->timestamp('erp_created_at')->nullable()->comment('created del endpoint');
            $table->timestamp('erp_updated_at')->nullable()->comment('updated del endpoint');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index('sport_id');
            $table->index('erp_sport_id');
            $table->index('available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_categories');
    }
};
