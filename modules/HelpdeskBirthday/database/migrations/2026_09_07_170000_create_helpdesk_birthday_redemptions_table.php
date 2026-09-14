<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La foto del canje: qué bono se gastó, en qué pedido y si Gestión lo descontó.
 *
 * Existe para que el panel no consulte PrestaShop en cada carga. Un JOIN de
 * cinco tablas sobre 181.000 pedidos por pantalla no escala, la tienda puede no
 * responder, y —lo que más importa— el histórico se borra solo: PrestaShop
 * elimina la `cart_rule` al consumirla, así que de los 1.116 cheques de
 * cumpleaños canjeados desde 2023 solo 4 conservan su código en la tienda. Lo
 * que no se guarde aquí se pierde.
 *
 * `campaign_id` es nullable a propósito: el histórico anterior a este módulo
 * son canjes reales, sin campaña que los reclame, y son la única línea base
 * contra la que comparar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('helpdesk')->create('helpdesk_birthday_redemptions', function (Blueprint $table): void {
            $table->id();

            // Nullable: el histórico de la tienda no tiene campaña detrás.
            $table->unsignedBigInteger('campaign_id')->nullable()->index();
            $table->unsignedBigInteger('recipient_id')->nullable()->index();

            // El código tal como lo tecleó el cliente, «{idbono}-{cv}». Puede
            // faltar: 363 de los canjes históricos ya no lo conservan en ningún
            // sitio y solo se identifican por el pedido y el email.
            $table->string('coupon_code', 64)->nullable()->index();
            // De dónde salió: 'cart_rule' (la regla sigue viva), 'erp' (lo
            // guardó marcarbono) o null. Dice cuánto fiarse de la atribución.
            $table->string('code_source', 16)->nullable();
            $table->string('voucher_name')->nullable();

            $table->unsignedBigInteger('ps_order_id')->index();
            $table->string('ps_order_reference', 32)->nullable();
            $table->unsignedBigInteger('ps_customer_id')->nullable();
            $table->string('customer_email')->nullable()->index();

            $table->string('order_state')->nullable();
            $table->boolean('order_valid')->default(false);
            $table->decimal('order_total', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->timestamp('ordered_at')->nullable()->index();

            // Validación en Gestión. `erp_marked` es que respondiera OK, no que
            // exista el registro: marcarbono guarda también los rechazos.
            $table->boolean('erp_marked')->default(false)->index();
            $table->string('erp_bono', 32)->nullable();
            $table->unsignedTinyInteger('erp_operation')->nullable();
            $table->text('erp_response')->nullable();
            $table->decimal('erp_sale_amount', 12, 2)->nullable();
            $table->timestamp('erp_marked_at')->nullable();

            // El pedido es de alguien a quien le mandamos el correo.
            $table->boolean('attributed')->default(false)->index();

            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unsignedBigInteger('ps_cart_rule_id')->nullable();

            // La identidad de un canje es la LÍNEA de descuento del pedido
            // (`order_cart_rule.id_order_cart_rule`), no el pedido ni la regla:
            //  - el pedido no basta, porque puede llevar dos bonos;
            //  - la regla tampoco, porque PrestaShop la borra al consumirla y
            //    entonces no hay id con el que distinguir dos líneas del mismo
            //    pedido — 11 canjes se machacaban entre sí por esto.
            // La línea, en cambio, existe siempre y no cambia.
            $table->unsignedBigInteger('ps_order_line_id')->nullable();
            $table->unique('ps_order_line_id', 'hbr_order_line_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_birthday_redemptions');
    }
};
