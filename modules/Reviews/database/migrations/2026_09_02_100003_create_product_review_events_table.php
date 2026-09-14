<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quién cambió qué y desde dónde.
 *
 * Con dos paneles moderando —este y el back-office de PrestaShop— hace falta
 * saber de dónde vino cada decisión: sin esto, un cambio hecho en la tienda que
 * pisa uno hecho aquí es indistinguible de un fallo de sincronización.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('product_review_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('product_reviews')->cascadeOnDelete();
            $table->string('event', 32);                 // received|approved|rejected|published|edited|conflict
            $table->string('source', 16);                // panel|prestashop|system
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['review_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('product_review_events');
    }
};
