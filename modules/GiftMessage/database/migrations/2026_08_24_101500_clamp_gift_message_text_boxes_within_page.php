<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Porteado desde el modulo GiftMessage del proyecto system: la migracion
 * original que crea las columnas *_w/*_h (2026_08_24_093000) ya se ejecuto en
 * este proyecto antes de que se le anadiera este ajuste, asi que se aplica
 * aqui como una migracion nueva en vez de reescribir la historica.
 *
 * Las x/y antiguas eran la esquina de una etiqueta que se ajustaba al texto;
 * con cajas de ancho fijo esas mismas coordenadas se salen de la pagina y el
 * texto se imprimiria cortado. Se meten dentro del margen.
 */
return new class extends Migration
{
    private const SLOTS = ['env_t1', 'env_t2', 'card_t1', 'card_t2'];

    public function up(): void
    {
        foreach (self::SLOTS as $slot) {
            DB::table('gift_message_configs')->whereRaw("{$slot}_x + {$slot}_w > 100")
                ->update([$slot.'_x' => DB::raw("GREATEST(0, 100 - {$slot}_w)")]);

            DB::table('gift_message_configs')->whereRaw("{$slot}_y + {$slot}_h > 100")
                ->update([$slot.'_y' => DB::raw("GREATEST(0, 100 - {$slot}_h)")]);
        }
    }

    public function down(): void
    {
        // No reversible: no se guarda el valor previo al clamp.
    }
};
