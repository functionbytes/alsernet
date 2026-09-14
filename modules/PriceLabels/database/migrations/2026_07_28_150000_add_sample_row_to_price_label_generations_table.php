<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_label_generations', function (Blueprint $table) {
            $table->json('sample_row')->nullable()->after('rows_count');
        });
    }

    public function down(): void
    {
        Schema::table('price_label_generations', function (Blueprint $table) {
            $table->dropColumn('sample_row');
        });
    }
};
