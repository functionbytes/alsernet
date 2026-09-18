<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationTag;
use Modules\Helpdesk\Models\Customer;

/**
 * Datos de ejemplo para el informe "Clientes en riesgo"
 * (/panel/helpdesk/reports/at-risk).
 *
 * El informe no inventa nada: cuenta las veces que la etiqueta
 * 'sentiment-negative' se ha aplicado a conversaciones de un cliente en los
 * últimos 90 días y lo cruza con su puntuación de salud. Sin esa etiqueta y sin
 * filas en el pivote, la pantalla sale vacía por mucha conversación que haya
 * (que es como estaba: 0 filas en helpdesk_conversation_tag_pivot).
 *
 * La puntuación de salud sale de CustomerInsightsService: 50 de base, +30 con
 * CSAT medio >= 4, +20 por cada 5 conversaciones cerradas, -30 si hay etiqueta
 * negativa en los últimos 30 días. Los cinco clientes de abajo están calculados
 * para dar un abanico (20, 40, 50, 20, 100) y para que dos empaten a 3
 * negativas y se vea el desempate por salud ascendente.
 *
 * La etiqueta se crea con los mismos valores que usa
 * AnalyzeSentimentOnIncoming::attachSentimentTag(), para que el listener la
 * reutilice en vez de crear una segunda.
 *
 * Idempotente: clientes por email y conversaciones por external_id.
 *
 *   php artisan db:seed --class="Modules\Helpdesk\Database\Seeders\HelpdeskAtRiskReportDemoSeeder"
 */
class HelpdeskAtRiskReportDemoSeeder extends Seeder
{
    private const EMAIL_DOMAIN = '@ejemplo.test';

    public function run(): void
    {
        $tag = $this->sentimentTag();
        $statuses = $this->statuses();

        $conversations = 0;

        foreach ($this->customers() as $definition) {
            $customer = $this->customer($definition);

            $this->negativeConversations($customer, $definition, $tag, $statuses);
            $this->closedConversations($customer, $definition, $statuses);
            $this->csatRating($customer, $definition);

            $conversations += $definition['negativas'] + $definition['cerradasExtra'];
        }

        // El endpoint cachea el ranking 5 minutos; sin esto el informe seguiría
        // enseñando el resultado vacío de antes.
        Cache::forget('helpdesk:reports:at-risk');

        $this->command?->info(
            'Sembrados '.count($this->customers())." clientes en riesgo con {$conversations} conversaciones."
        );
    }

    /**
     * Cinco perfiles pensados para que el ranking y el desempate se vean.
     *
     * negativas        conversaciones etiquetadas como negativas
     * negativasHaceDias  antigüedad de la etiqueta; por encima de 30 no penaliza salud
     * negativasCerradas  cuántas de esas negativas están además cerradas
     * cerradasExtra    conversaciones cerradas sin etiqueta, solo para la salud
     * csat             nota CSAT contestada, o null
     *
     * @return array<int, array<string, mixed>>
     */
    private function customers(): array
    {
        return [
            [
                'name' => 'Marta Ruiz Peña',
                'email' => 'marta.ruiz'.self::EMAIL_DOMAIN,
                'key' => 'marta-ruiz',
                'negativas' => 6,
                'negativasHaceDias' => [2, 5, 9, 14, 19, 26],
                'negativasCerradas' => 0,
                'cerradasExtra' => 0,
                'csat' => null,
                'motivo' => 'Seis quejas en menos de un mes y ninguna cerrada.',
            ],
            [
                'name' => 'Iván Torrijos',
                'email' => 'ivan.torrijos'.self::EMAIL_DOMAIN,
                'key' => 'ivan-torrijos',
                'negativas' => 5,
                'negativasHaceDias' => [1, 6, 12, 18, 25],
                'negativasCerradas' => 5,
                'cerradasExtra' => 0,
                'csat' => null,
                'motivo' => 'Se le cierran los casos pero vuelve enfadado.',
            ],
            [
                'name' => 'Lucía Benavent',
                'email' => 'lucia.benavent'.self::EMAIL_DOMAIN,
                'key' => 'lucia-benavent',
                'negativas' => 4,
                'negativasHaceDias' => [3, 11, 17, 28],
                'negativasCerradas' => 0,
                'cerradasExtra' => 0,
                'csat' => 5,
                'motivo' => 'Puntúa bien la atención pero el producto le falla.',
            ],
            [
                'name' => 'Óscar Maldonado',
                'email' => 'oscar.maldonado'.self::EMAIL_DOMAIN,
                'key' => 'oscar-maldonado',
                'negativas' => 3,
                'negativasHaceDias' => [4, 10, 21],
                'negativasCerradas' => 0,
                'cerradasExtra' => 0,
                'csat' => null,
                'motivo' => 'Empata a tres con Nuria, pero con la salud por los suelos.',
            ],
            [
                'name' => 'Nuria Alcaraz',
                'email' => 'nuria.alcaraz'.self::EMAIL_DOMAIN,
                'key' => 'nuria-alcaraz',
                'negativas' => 3,
                // Por encima de 30 días: cuentan para el informe (ventana de 90)
                // pero ya no restan salud. Es el caso "se quejó, se arregló".
                'negativasHaceDias' => [44, 61, 79],
                'negativasCerradas' => 3,
                'cerradasExtra' => 2,
                'csat' => 5,
                'motivo' => 'Mismo recuento que Óscar y salud perfecta: el desempate.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function customer(array $definition): Customer
    {
        $customer = Customer::withTrashed()->firstOrNew(['email' => $definition['email']]);

        $customer->fill([
            'name' => $definition['name'],
            'language' => 'es',
            'country' => 'ES',
            'internal_notes' => 'Cliente de ejemplo del informe de riesgo. '.$definition['motivo'],
        ]);
        $customer->deleted_at = null;
        $customer->save();

        return $customer;
    }

    /**
     * Las conversaciones que llevan la etiqueta negativa.
     *
     * @param  array<string, mixed>  $definition
     * @param  array<string, int|null>  $statuses
     */
    private function negativeConversations(Customer $customer, array $definition, ConversationTag $tag, array $statuses): void
    {
        foreach (range(1, $definition['negativas']) as $i) {
            $daysAgo = $definition['negativasHaceDias'][$i - 1];
            $createdAt = now()->subDays($daysAgo);
            $isClosed = $i <= $definition['negativasCerradas'];

            $conversation = $this->conversation(
                key: "{$definition['key']}-neg-{$i}",
                customer: $customer,
                subject: $this->negativeSubjects()[($i - 1) % count($this->negativeSubjects())],
                createdAt: $createdAt,
                statusId: $isClosed ? $statuses['resuelto'] : $statuses['nuevo'],
                closedAt: $isClosed ? $createdAt->copy()->addHours(30) : null,
                priority: $i === 1 ? 'urgent' : 'high',
            );

            $this->attachTag($conversation, $tag, $createdAt);
        }
    }

    /**
     * Conversaciones cerradas sin etiqueta: solo suben la puntuación de salud
     * (+20 por cada cinco cerradas).
     *
     * @param  array<string, mixed>  $definition
     * @param  array<string, int|null>  $statuses
     */
    private function closedConversations(Customer $customer, array $definition, array $statuses): void
    {
        // range(1, 0) devuelve [1, 0], no un array vacío: hay que cortar antes.
        if ($definition['cerradasExtra'] < 1) {
            return;
        }

        foreach (range(1, $definition['cerradasExtra']) as $i) {
            $createdAt = now()->subDays(90 + $i * 7);

            $this->conversation(
                key: "{$definition['key']}-ok-{$i}",
                customer: $customer,
                subject: 'Consulta resuelta sin incidencias',
                createdAt: $createdAt,
                statusId: $statuses['cerrado'],
                closedAt: $createdAt->copy()->addDay(),
                priority: 'normal',
            );
        }
    }

    private function conversation(
        string $key,
        Customer $customer,
        string $subject,
        Carbon $createdAt,
        ?int $statusId,
        ?Carbon $closedAt,
        string $priority,
    ): Conversation {
        $conversation = Conversation::withTrashed()
            ->firstOrNew(['external_id' => 'demo-at-risk-'.$key]);

        $conversation->forceFill([
            'customer_id' => $customer->id,
            'channel' => 'email',
            'subject' => $subject,
            'status_id' => $statusId,
            'priority' => $priority,
            'is_archived' => false,
            'closed_at' => $closedAt,
            'last_message_at' => $createdAt,
            'last_customer_message_at' => $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $closedAt ?? $createdAt,
            'deleted_at' => null,
        ])->save();

        return $conversation;
    }

    /**
     * El pivote lleva su propio created_at y es lo que fecha el informe: la
     * ventana de 90 días y la penalización de salud de 30 días se miden ahí,
     * no en la conversación.
     */
    private function attachTag(Conversation $conversation, ConversationTag $tag, Carbon $taggedAt): void
    {
        DB::connection('helpdesk')->table('helpdesk_conversation_tag_pivot')->updateOrInsert(
            ['conversation_id' => $conversation->id, 'tag_id' => $tag->id],
            ['created_at' => $taggedAt, 'updated_at' => $taggedAt],
        );
    }

    /**
     * La encuesta cuelga de una conversación concreta (conversation_id es NOT
     * NULL sin default), así que se ancla a la última del cliente.
     *
     * @param  array<string, mixed>  $definition
     */
    private function csatRating(Customer $customer, array $definition): void
    {
        if ($definition['csat'] === null) {
            return;
        }

        $conversationId = Conversation::where('customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->value('id');

        if (! $conversationId) {
            return;
        }

        DB::connection('helpdesk')->table('helpdesk_csat_ratings')->updateOrInsert(
            ['survey_token' => 'demo-at-risk-'.$definition['key']],
            [
                'conversation_id' => $conversationId,
                'customer_id' => $customer->id,
                'rating' => $definition['csat'],
                'comment' => 'La atención bien, el problema de fondo sigue.',
                'sent_at' => now()->subDays(20),
                'answered_at' => now()->subDays(19),
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(19),
            ],
        );
    }

    /**
     * Mismos valores que AnalyzeSentimentOnIncoming, para no acabar con dos
     * etiquetas 'sentiment-negative' distintas.
     */
    private function sentimentTag(): ConversationTag
    {
        return ConversationTag::firstOrCreate(
            ['slug' => 'sentiment-negative'],
            [
                'name' => 'Sentimiento negativo',
                'color' => '#dc3545',
                'is_active' => true,
            ],
        );
    }

    /**
     * @return array<string, int|null>
     */
    private function statuses(): array
    {
        $byName = fn (string $name) => ConversationStatus::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->value('id');

        $nuevo = $byName('Nuevo') ?? ConversationStatus::orderBy('id')->value('id');

        return [
            'nuevo' => $nuevo,
            'resuelto' => $byName('Resuelto') ?? $byName('Cerrado') ?? $nuevo,
            'cerrado' => $byName('Cerrado') ?? $byName('Resuelto') ?? $nuevo,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function negativeSubjects(): array
    {
        return [
            'Llevo tres semanas esperando una respuesta',
            'El pedido ha vuelto a llegar roto',
            'Nadie me devuelve la llamada',
            'Me habéis cobrado dos veces el mismo mes',
            'Es la cuarta vez que abro el mismo caso',
            'Quiero cancelar y que me devolváis el dinero',
        ];
    }
}
