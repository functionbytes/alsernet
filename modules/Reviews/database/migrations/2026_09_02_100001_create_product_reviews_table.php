<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro maestro de las opiniones de producto.
 *
 * La tienda sigue guardando las suyas —es lo que pinta la ficha—, pero la
 * decisión de publicar se toma aquí. `ps_comment_id` es la única atadura entre
 * las dos: por ahí viajan los cambios en ambos sentidos.
 *
 * Vive en la conexión 'helpdesk', donde está el resto de lo que el panel
 * gestiona de la tienda.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('product_reviews', function (Blueprint $table) {
            $table->id();

            // Identidad en PrestaShop
            $table->unsignedInteger('ps_comment_id')->unique();
            $table->unsignedInteger('ps_product_id')->index();
            $table->unsignedInteger('ps_customer_id')->nullable()->index();
            $table->unsignedInteger('ps_order_id')->nullable();
            $table->unsignedInteger('ps_lang_id');
            $table->string('lang_iso', 5);

            // Contenido tal y como lo escribió el cliente
            $table->unsignedTinyInteger('stars');            // 0-10, la escala de la tienda
            $table->string('author')->nullable();
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->text('answer')->nullable();

            // Contexto para poder juzgarla sin abrir otra pantalla
            $table->string('product_name')->nullable();
            $table->string('product_reference', 64)->nullable();
            $table->string('customer_email')->nullable();
            $table->string('order_reference', 32)->nullable();

            // Moderación
            $table->string('status', 16)->default('pending');  // pending|approved|rejected
            $table->string('rejection_reason')->nullable();
            $table->unsignedBigInteger('moderated_by')->nullable();
            $table->timestamp('moderated_at')->nullable();

            // Estado en la tienda, para detectar desfases entre los dos paneles
            $table->boolean('ps_active')->default(false);
            $table->timestamp('ps_date')->nullable();
            $table->timestamp('ps_date_upd')->nullable();

            $table->timestamp('translated_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('ps_active');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('product_reviews');
    }
};
