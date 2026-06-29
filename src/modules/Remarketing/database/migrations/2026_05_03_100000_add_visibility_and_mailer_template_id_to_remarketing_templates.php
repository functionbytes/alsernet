<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remarketing_templates', function (Blueprint $table) {
            $table->enum('visibility', ['store', 'user', 'global'])
                ->default('store')
                ->after('store_id')
                ->comment('store=solo esta tienda, user=todas las tiendas del usuario, global=todos los usuarios');
            $table->foreignId('mailer_template_id')
                ->nullable()
                ->after('visibility')
                ->constrained('mailer_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('remarketing_templates', function (Blueprint $table) {
            $table->dropForeign(['mailer_template_id']);
            $table->dropColumn(['visibility', 'mailer_template_id']);
        });
    }
};
