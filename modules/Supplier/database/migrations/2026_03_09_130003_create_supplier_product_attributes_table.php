<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_product_attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('erp_id')->unique()->comment('IDARTICULO Oracle');
            $table->foreignId('product_id')->constrained('supplier_products')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('supplier_categories')->onDelete('set null');
            $table->unsignedBigInteger('erp_category_id')->nullable()->comment('IDFAMILIA_CL Oracle (desnormalizado)');
            $table->unsignedBigInteger('erp_group_id')->nullable()->comment('IDGRUPO_CL Oracle');

            // Datos de ARTICULO
            $table->string('code', 100)->nullable()->comment('articulo.codigo');
            $table->string('reference', 100)->nullable()->comment('articulo.referencia');
            $table->string('name', 500)->nullable()->comment('articulo.descripcion');
            $table->boolean('available')->default(true)->comment('articulo.estado');
            $table->boolean('web_published')->default(false)->comment('articulo.estado_publicado_web');

            // Timestamps del ERP
            $table->timestamp('erp_created_at')->nullable()->comment('created del endpoint');
            $table->timestamp('erp_updated_at')->nullable()->comment('updated del endpoint');

            // Sync
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id');
            $table->index('category_id');
            $table->index('erp_category_id');
            $table->index('erp_group_id');
            $table->index('available');
            $table->index('web_published');
            $table->index('last_sync_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_product_attributes');
    }
};
