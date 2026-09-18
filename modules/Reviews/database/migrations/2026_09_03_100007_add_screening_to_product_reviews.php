<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Resultado del cribado automático.
 *
 * Con 1.769 opiniones esperando revisión, leerlas todas a mano no es realista.
 * La máquina no decide: marca lo que le parece sospechoso y por qué, para que
 * quien modera empiece por ahí en vez de por la primera de la lista.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('product_reviews', function (Blueprint $table) {
            // clean | spam | offensive | personal_data | off_topic | unclear
            $table->string('screening', 24)->nullable()->after('rejection_reason');
            $table->text('screening_reason')->nullable()->after('screening');
            $table->unsignedTinyInteger('screening_confidence')->nullable()->after('screening_reason');
            $table->timestamp('screened_at')->nullable()->after('screening_confidence');

            $table->index('screening');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('product_reviews', function (Blueprint $table) {
            $table->dropIndex(['screening']);
            $table->dropColumn(['screening', 'screening_reason', 'screening_confidence', 'screened_at']);
        });
    }
};
