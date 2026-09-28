<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Helpdesk\Models\ConversationFarewell;
use Modules\Helpdesk\Models\ConversationGreeting;
use Modules\Helpdesk\Models\OffHoursResponse;

/**
 * Bienvenida, despedida y fuera de horario en catalán (todos los canales).
 *
 * DeepL no traduce catalán, así que estos textos se redactan a mano con
 * language='ca' en vez de depender de la traducción automática. Idempotente:
 * no pisa un texto que ya se haya editado desde Ajustes.
 *
 * php artisan db:seed --class="Modules\Helpdesk\Database\Seeders\CatalanAutoRepliesSeeder"
 */
class CatalanAutoRepliesSeeder extends Seeder
{
    public function run(): void
    {
        $messages = [
            ConversationGreeting::class => "Gràcies per contactar amb Álvarez. T'atendrem el més aviat possible.\r\n"
                ."Per ajudar-te de forma més ràpida i eficaç, si us plau, envia'ns un missatge indicant:\r\n"
                ."- Nom i cognoms\r\n"
                ."- Correu electrònic\r\n"
                ."- Motiu de la teva consulta\r\n"
                ."Revisarem el que ens indiquis i et respondrem com més aviat millor.\r\n"
                .'Moltes gràcies per la teva confiança.',
            ConversationFarewell::class => "Esperem haver-te estat d'ajuda. Si necessites qualsevol cosa, no dubtis a contactar de nou amb nosaltres.",
            OffHoursResponse::class => "Gràcies per contactar. El nostre horari d'atenció és de dilluns a divendres de 10:00 a 17:00 h. Si us plau, torna'ns a enviar un missatge dins d'aquest horari.",
        ];

        foreach ($messages as $model => $message) {
            $model::firstOrCreate(
                ['channel' => null, 'language' => 'ca'],
                ['message' => $message, 'is_active' => true],
            );
        }
    }
}
