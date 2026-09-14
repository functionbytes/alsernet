<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fichas de las que el sistema recoge opiniones.
 *
 * Hoy son las tres tiendas físicas en Google (Capitán Haya, Coruña y Diego de
 * León), de donde ya venían 1.747 reseñas importadas a mano. Registrarlas aquí
 * permite leerlas a diario en vez de a golpe de importación, y saber siempre de
 * qué establecimiento habla cada una.
 *
 * Las credenciales van cifradas: el modelo las declara 'encrypted', así que en
 * la base no queda ni el client_secret ni el refresh_token en claro.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('review_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // "Álvarez Capitán Haya"
            $table->string('platform', 32)->default('google');
            $table->string('external_id')->nullable();       // locations/12345678901234567890
            $table->string('account_id')->nullable();        // accounts/1234567890
            $table->text('credentials')->nullable();         // cifradas
            $table->boolean('active')->default(true);
            $table->boolean('auto_approve')->default(false); // publicar sin pasar por moderación

            $table->timestamp('last_fetch_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('fetched_total')->default(0);

            $table->timestamps();

            $table->unique(['platform', 'external_id']);
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('review_sources');
    }
};
