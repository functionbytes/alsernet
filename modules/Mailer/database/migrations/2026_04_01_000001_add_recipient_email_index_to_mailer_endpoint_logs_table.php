<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mailer_endpoint_logs', function (Blueprint $table) {
            $table->index('recipient_email');
        });
    }

    public function down(): void
    {
        Schema::table('mailer_endpoint_logs', function (Blueprint $table) {
            $table->dropIndex(['recipient_email']);
        });
    }
};
