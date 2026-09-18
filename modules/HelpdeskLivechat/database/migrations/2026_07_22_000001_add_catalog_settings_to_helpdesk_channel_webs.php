<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_channel_webs', function (Blueprint $table) {
            // URL del product feed (JSON) para el driver de catálogo por feed.
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'product_feed_url')) {
                $table->string('product_feed_url', 500)->nullable();
            }
            // Activa el bot que responde a la pregunta del visitante con productos.
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'enable_product_search')) {
                $table->boolean('enable_product_search')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_channel_webs', function (Blueprint $table) {
            $table->dropColumn(['product_feed_url', 'enable_product_search']);
        });
    }
};
