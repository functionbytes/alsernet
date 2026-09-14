<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locales', function (Blueprint $table) {
            $table->string('language_code', 10)->nullable()->after('code')->comment('Full ISO code, e.g. es_CO, en_US');
            $table->boolean('rtl')->default(false)->after('native_name')->comment('Right-to-left text direction');
        });
    }

    public function down(): void
    {
        Schema::table('locales', function (Blueprint $table) {
            $table->dropColumn(['language_code', 'rtl']);
        });
    }
};
