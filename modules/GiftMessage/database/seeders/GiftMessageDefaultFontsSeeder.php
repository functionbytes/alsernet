<?php

namespace Modules\GiftMessage\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Modules\GiftMessage\Models\GiftMessageFont;

/**
 * Instala la fuente CJK que trae el módulo (Noto Sans SC, licencia SIL OFL 1.1 —
 * ver modules/GiftMessage/resources/fonts/NotoSansSC-LICENSE.txt) para que los
 * mensajes con caracteres chinos/japoneses/coreanos se impriman en vez de salir
 * como cuadros en blanco.
 *
 * Ninguna fuente del sistema los cubre: ni las Base-14 de DomPDF (Helvetica/
 * Times/Courier, Latin-only) ni "DejaVu Sans"/"DejaVu Serif" (el fallback
 * Unicode del módulo, para emojis y acentos raros) — DejaVu no incluye
 * caracteres CJK, es una limitación conocida de esa familia. Sin esta fuente
 * disponible, GiftMessagePdfService::resolveFont() detecta el problema y lo
 * anota como aviso, pero no tiene con qué imprimir el texto igual.
 *
 * Idempotente: no duplica la fila si ya se corrió antes.
 */
class GiftMessageDefaultFontsSeeder extends Seeder
{
    private const DISK = 'public';

    private const FOLDER = 'giftmessage/fonts';

    // TrueType (glyf), no la OTF/CFF original de Noto: DomPDF embebe mal las
    // fuentes CJK en formato CFF (los caracteres salen como "?"), pero maneja
    // bien TrueType. Google Fonts sirve esta misma fuente re-convertida a TTF.
    private const SOURCE = __DIR__.'/../../resources/fonts/NotoSansSC-Regular.ttf';

    private const FAMILY = 'noto_sans_sc';

    public function run(): void
    {
        $exists = GiftMessageFont::query()
            ->where('family', self::FAMILY)
            ->where('weight', 'normal')
            ->where('style', 'normal')
            ->exists();

        if ($exists) {
            return;
        }

        if (! is_file(self::SOURCE)) {
            $this->command?->warn('GiftMessage: no se encontró el archivo de la fuente Noto Sans SC, se omite el seeder.');

            return;
        }

        $path = self::FOLDER.'/noto_sans_sc_regular.ttf';

        // put() devuelve false en un fallo de escritura (p.ej. sin permiso sobre
        // el directorio) en vez de lanzar excepción — sin comprobarlo, la fila en
        // BD se crea igual apuntando a un archivo que nunca llegó a disco.
        if (! Storage::disk(self::DISK)->put($path, file_get_contents(self::SOURCE))) {
            $this->command?->error('GiftMessage: no se pudo escribir la fuente Noto Sans SC en el disco público.');

            return;
        }

        GiftMessageFont::query()->create([
            'name' => 'Noto Sans SC (CJK)',
            'family' => self::FAMILY,
            'weight' => 'normal',
            'style' => 'normal',
            'file_path' => $path,
            'created_by' => null,
        ]);
    }
}
