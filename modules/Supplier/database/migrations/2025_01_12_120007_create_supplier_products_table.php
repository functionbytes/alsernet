<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('erp_id')->unique()->comment('IDARTIPROV Oracle');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('supplier_categories')->onDelete('set null');
            $table->unsignedBigInteger('erp_category_id')->nullable()->comment('IDFAMILIA_CL Oracle (desnormalizado)');

            // Datos de ARTIPROV
            $table->string('code', 100)->nullable()->comment('artiprov.codigo');
            $table->string('name', 500)->nullable()->comment('artiprov.descripcion');
            $table->boolean('available')->default(true)->comment('artiprov.estado');
            $table->boolean('is_default')->default(false)->comment('artiprov.pordefecto');
            $table->boolean('web_published')->default(false)->comment('modelo.estado_publicado_web');

            // Referencia al modelo Oracle (desnormalizado)
            $table->unsignedBigInteger('erp_model_id')->nullable()->comment('IDMODELO Oracle');

            // Sync
            $table->json('metadata')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('supplier_id');
            $table->index('category_id');
            $table->index('erp_category_id');
            $table->index('erp_model_id');
            $table->index('available');
            $table->index('is_default');
            $table->index('web_published');
            $table->index('last_sync_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
    }
};
