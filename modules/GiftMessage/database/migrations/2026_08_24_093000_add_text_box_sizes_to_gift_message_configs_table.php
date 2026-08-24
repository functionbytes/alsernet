<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ancho y alto de la caja de cada texto, en porcentaje de la pagina, igual
     * que las coordenadas x/y que ya existian.
     *
     * @var array<string, array{w: float, h: float}>
     */
    private const BOXES = [
        'env_t1' => ['w' => 80.00, 'h' => 25.00],
        'env_t2' => ['w' => 30.00, 'h' => 10.00],
        'card_t1' => ['w' => 80.00, 'h' => 40.00],
        'card_t2' => ['w' => 30.00, 'h' => 10.00],
    ];

    public function up(): void
    {
        Schema::table('gift_message_configs', function (Blueprint $table) {
            foreach (self::BOXES as $slot => $size) {
                $table->decimal($slot.'_w', 6, 2)->default($size['w'])->after($slot.'_y');
                $table->decimal($slot.'_h', 6, 2)->default($size['h'])->after($slot.'_w');
            }
        });

        // Hasta ahora el T1 se centraba siempre a lo ancho de la pagina y su x
        // guardada no se usaba. Al pasar a cajas la x si cuenta, asi que se
        // centra la caja para conservar el resultado que ya veia el usuario.
        foreach (['env_t1', 'card_t1'] as $slot) {
            DB::table('gift_message_configs')->update([
                $slot.'_x' => round((100 - self::BOXES[$slot]['w']) / 2, 2),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('gift_message_configs', function (Blueprint $table) {
            foreach (array_keys(self::BOXES) as $slot) {
                $table->dropColumn([$slot.'_w', $slot.'_h']);
            }
        });
    }
};
