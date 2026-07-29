<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->unsignedBigInteger('erp_id')->nullable()->unique()->comment('IDPROVEEDOR Oracle');

            $table->string('code', 50)->nullable()->unique();
            $table->string('label', 255)->comment('Nombre del proveedor (label en ERP)');
            $table->text('description')->nullable();
            $table->string('cif', 50)->nullable()->comment('CIF/NIF');

            $table->string('email', 255)->nullable();

            $table->boolean('available')->default(true);
            $table->unsignedSmallInteger('priority')->default(10);

            $table->json('metadata')->nullable();
            $table->timestamp('erp_created_at')->nullable();
            $table->timestamp('erp_updated_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('erp_id');
            $table->index('available');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
