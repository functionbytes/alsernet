<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Alterar la tabla para agregar 'manual' a los valores enum de source
        DB::statement("ALTER TABLE request_documents MODIFY source ENUM('email', 'api', 'whatsapp', 'manual', 'wp') NULL");
    }

    public function down(): void
    {
        // Revertir al enum original
        DB::statement("ALTER TABLE request_documents MODIFY source ENUM('email', 'api', 'whatsapp') NULL");
    }
};
