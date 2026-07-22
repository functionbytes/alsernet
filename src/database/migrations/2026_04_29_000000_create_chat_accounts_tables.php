<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea `chat_accounts` y `chat_accounts_user`.
 *
 * Estas tablas existían solo en la BD de desarrollo (ninguna migración las
 * creaba), pero varias migraciones posteriores dependen de ellas:
 *  - 2026_04_30_010510_add_account_id_to_users_for_chat (consulta e inserta
 *    en ambas),
 *  - 2026_04_30_011139_create_chat_email_templates_table (FK a chat_accounts),
 *  - módulo Social (FKs a chat_accounts si el módulo se habilita).
 *
 * Sin ellas, `php artisan migrate` sobre BD limpia falla (errno 150 en las
 * FKs). El esquema replica el de la BD de desarrollo. Guardado con
 * Schema::hasTable() para que sea no-op en entornos donde ya existen
 * (dev, e2e vía deployment/e2e/fixtures/30-panel-bootstrap.sql).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_accounts')) {
            Schema::create('chat_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('default_locale')->default('en');
                $table->json('supported_locales')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('chat_accounts_user')) {
            Schema::create('chat_accounts_user', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('account_id');
                $table->timestamps();

                $table->unique(['user_id', 'account_id']);
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('account_id')->references('id')->on('chat_accounts')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_accounts_user');
        Schema::dropIfExists('chat_accounts');
    }
};
