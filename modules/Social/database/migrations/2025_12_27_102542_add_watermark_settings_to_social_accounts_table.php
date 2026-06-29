<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->boolean('enable_watermark')->default(false)->after('status');
            $table->string('watermark_image')->nullable()->after('enable_watermark');
            $table->string('watermark_position')->default('bottom-right')->after('watermark_image');
            // top-left, top-right, bottom-left, bottom-right, center
            $table->integer('watermark_opacity')->default(80)->after('watermark_position'); // 0-100
            $table->integer('watermark_size')->default(20)->after('watermark_opacity'); // Percentage of image
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'enable_watermark',
                'watermark_image',
                'watermark_position',
                'watermark_opacity',
                'watermark_size',
            ]);
        });
    }
};
