<?php

namespace Modules\Reviews\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Reviews\Models\ReviewSource;

/**
 * Las tres tiendas de las que ya venían reseñas importadas a mano.
 *
 * Se registran desactivadas y sin credenciales: hay que completarlas desde el
 * panel antes de que el sistema empiece a leerlas solo.
 */
class ReviewSourcesSeeder extends Seeder
{
    private const TIENDAS = [
        'Álvarez Capitán Haya',
        'Álvarez Diego de León',
        'Álvarez Coruña',
    ];

    public function run(): void
    {
        foreach (self::TIENDAS as $nombre) {
            ReviewSource::firstOrCreate(
                ['platform' => ReviewSource::PLATFORM_GOOGLE, 'external_id' => null, 'name' => $nombre],
                ['active' => false, 'auto_approve' => false]
            );
        }
    }
}
