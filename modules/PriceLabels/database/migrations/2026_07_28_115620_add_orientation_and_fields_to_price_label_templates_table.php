<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_label_templates', function (Blueprint $table) {
            $table->string('orientation')->default('both')->after('is_active');
            $table->unsignedInteger('vertical_rows')->default(2)->after('positions_horizontal');
            $table->unsignedInteger('vertical_columns')->default(2)->after('vertical_rows');
            $table->unsignedInteger('horizontal_rows')->default(2)->after('vertical_columns');
            $table->unsignedInteger('horizontal_columns')->default(4)->after('horizontal_rows');
            $table->json('field_definitions')->nullable()->after('horizontal_columns');
        });
    }

    public function down(): void
    {
        Schema::table('price_label_templates', function (Blueprint $table) {
            $table->dropColumn([
                'orientation',
                'vertical_rows',
                'vertical_columns',
                'horizontal_rows',
                'horizontal_columns',
                'field_definitions',
            ]);
        });
    }
};
