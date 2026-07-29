<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_label_generations', function (Blueprint $table) {
            $table->string('status')->default('completed')->after('type');
            $table->string('source_excel_path')->nullable()->after('sample_row');
            $table->text('error_message')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('price_label_generations', function (Blueprint $table) {
            $table->dropColumn(['status', 'source_excel_path', 'error_message']);
        });
    }
};
