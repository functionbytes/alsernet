<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_macros', function (Blueprint $table) {
            $table->string('language', 5)->nullable()->after('description')
                ->comment('null = todos los idiomas; codigo ISO 639-1 (es/en/fr/de/pt/it) = solo para ese idioma');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_macros', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
};
