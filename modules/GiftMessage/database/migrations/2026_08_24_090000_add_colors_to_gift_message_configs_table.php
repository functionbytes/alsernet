<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SLOTS = ['env_t1', 'env_t2', 'card_t1', 'card_t2'];

    public function up(): void
    {
        Schema::table('gift_message_configs', function (Blueprint $table) {
            foreach (self::SLOTS as $slot) {
                $table->string($slot.'_color', 7)->default('#000000')->after($slot.'_size');
                $table->unsignedTinyInteger($slot.'_opacity')->default(100)->after($slot.'_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gift_message_configs', function (Blueprint $table) {
            foreach (self::SLOTS as $slot) {
                $table->dropColumn([$slot.'_color', $slot.'_opacity']);
            }
        });
    }
};
