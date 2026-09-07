<?php

namespace Modules\HelpdeskBirthday\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskBirthday\Models\BirthdayRedemption;
use Modules\HelpdeskBirthday\Services\Redemption\BirthdayRedemptionReader;

/**
 * Trae los canjes de la tienda y los guarda en local.
 *
 * Es idempotente: se puede correr las veces que haga falta sobre el mismo rango
 * sin duplicar nada, porque cada fila se identifica por (pedido, línea de
 * descuento). Eso es lo que permite tenerlo en el scheduler cada hora y a la
 * vez ofrecer un botón «actualizar» en el panel.
 *
 * LA ATRIBUCIÓN, QUE ES LA PARTE DELICADA
 * ---------------------------------------
 * Un canje se ata a un destinatario por dos caminos, en este orden:
 *
 *   1. Por el código del bono, cuando la tienda o Gestión lo conservan. Es
 *      atribución exacta: ese bono se emitió para esa persona.
 *   2. Por el email del pedido. Necesario porque PrestaShop borra la
 *      `cart_rule` al consumirla y en 363 de los 1.116 canjes históricos el
 *      código ya no consta en ninguna parte.
 *
 * Y se marca `attributed` sólo si además le habíamos enviado el correo: quien
 * compró con un código que le reenviaron cuenta como canje del bono, pero no
 * como conversión de la campaña. Esa diferencia es justo lo que hay que poder
 * leer en el panel.
 */
class BirthdayRedemptionSyncService
{
    /** Páginas del lector; el bridge no sirve más de 500 por petición. */
    private const PAGE = 500;

    public function __construct(
        private readonly BirthdayRedemptionReader $reader,
    ) {}

    public function isAvailable(): bool
    {
        return $this->reader->isAvailable();
    }

    /**
     * Sincroniza un rango de fechas.
     *
     * @return array{available: bool, read: int, saved: int, attributed: int, source: string}
     */
    public function sync(?CarbonImmutable $from = null, ?CarbonImmutable $to = null, ?string $nameLike = null): array
    {
        $result = ['available' => false, 'read' => 0, 'saved' => 0, 'attributed' => 0, 'source' => $this->reader->label()];

        if (! $this->reader->isAvailable()) {
            Log::warning('[HelpdeskBirthday] No hay de dónde leer los canjes.', ['source' => $this->reader->label()]);

            return $result;
        }

        $result['available'] = true;

        $fromDate = $from?->toDateString();
        $toDate = $to?->toDateString();
        $nameLike ??= (string) config('helpdeskbirthday.voucher_name_like', 'cumplea');

        $offset = 0;

        do {
            $rows = $this->reader->redemptions($fromDate, $toDate, $nameLike, self::PAGE, $offset);

            foreach ($rows as $row) {
                $saved = $this->store($row);

                $result['saved']++;

                if ($saved->attributed) {
                    $result['attributed']++;
                }
            }

            $result['read'] += count($rows);
            $offset += self::PAGE;

            // Se para cuando una página viene incompleta: significa que era la
            // última. Preguntar el total en cada vuelta sería otra consulta.
        } while (count($rows) === self::PAGE);

        Log::info('[HelpdeskBirthday] Canjes sincronizados', $result + ['from' => $fromDate, 'to' => $toDate]);

        return $result;
    }

    /**
     * Guarda un canje, casándolo con el destinatario que le corresponda.
     *
     * @param  array<string, mixed>  $row
     */
    private function store(array $row): BirthdayRedemption
    {
        $code = trim((string) ($row['code'] ?? ''));
        $email = mb_strtolower(trim((string) ($row['customer_email'] ?? '')));

        $recipient = $this->matchRecipient($code, $email, $row['order_date'] ?? null);

        return BirthdayRedemption::updateOrCreate(
            // La línea de descuento identifica el canje; ver la migración.
            ['ps_order_line_id' => (int) ($row['line_id'] ?? 0) ?: null],
            [
                'ps_order_id' => (int) $row['order_id'],
                'ps_cart_rule_id' => (int) ($row['cart_rule_id'] ?? 0) ?: null,
                'campaign_id' => $recipient?->campaign_id,
                'recipient_id' => $recipient?->id,
                'coupon_code' => $code !== '' ? mb_substr($code, 0, 64) : null,
                'code_source' => $row['code_source'] ?? null,
                'voucher_name' => $row['voucher_name'] ?? null,
                'ps_order_reference' => $row['order_reference'] ?? null,
                'ps_customer_id' => (int) ($row['customer_id'] ?? 0) ?: null,
                'customer_email' => $email !== '' ? $email : null,
                'order_state' => $row['order_state'] ?? null,
                'order_valid' => (bool) ($row['order_valid'] ?? false),
                'order_total' => (float) ($row['order_total'] ?? 0),
                'discount' => (float) ($row['discount'] ?? 0),
                'ordered_at' => $row['order_date'] ?? null,
                'erp_marked' => (bool) ($row['erp']['marked'] ?? false),
                'erp_bono' => $row['erp']['bono'] ?? null,
                'erp_operation' => $row['erp']['operation'] ?? null,
                'erp_response' => $row['erp']['response'] ?? null,
                'erp_sale_amount' => $row['erp']['sale_amount'] ?? null,
                'erp_marked_at' => $row['erp']['marked_at'] ?? null,
                // Atribuido = es de alguien a quien le mandamos el correo. Un
                // destinatario que quedó en 'skipped' o 'failed' no cuenta:
                // nunca recibió nada que canjear.
                'attributed' => $recipient !== null && $recipient->status === BirthdayRecipient::STATUS_SENT,
                'synced_at' => now(),
            ]
        );
    }

    /**
     * A quién pertenece este canje.
     *
     * Por código si lo hay —es exacto— y si no por email. El email se acota a
     * los 90 días anteriores al pedido: sin esa ventana, un cliente que cumple
     * años todos los años acabaría con el canje de este año atribuido a la
     * campaña de hace tres.
     */
    private function matchRecipient(string $code, string $email, mixed $orderedAt): ?BirthdayRecipient
    {
        if ($code !== '' && str_contains($code, '-')) {
            [$bono, $verification] = array_pad(explode('-', $code, 2), 2, '');

            $byCode = BirthdayRecipient::query()
                ->where('coupon_code', $bono)
                ->when($verification !== '', fn ($q) => $q->where('coupon_verification_code', $verification))
                ->first();

            if ($byCode) {
                return $byCode;
            }
        }

        if ($email === '') {
            return null;
        }

        $date = $orderedAt ? CarbonImmutable::parse($orderedAt) : CarbonImmutable::now();

        // Se compara contra el día de la CAMPAÑA y no contra el created_at del
        // destinatario: created_at es cuándo se escribió la fila, que en un
        // backfill es la misma hora para todos y en una campaña preparada de
        // víspera va un día por delante del bono. Lo que acota de verdad es
        // cuándo se emitió el bono, y eso es la campaña.
        return BirthdayRecipient::query()
            ->join('helpdesk_birthday_campaigns as c', 'c.id', '=', 'helpdesk_birthday_recipients.campaign_id')
            ->where('helpdesk_birthday_recipients.email', $email)
            ->whereDate('c.campaign_date', '<=', $date->toDateString())
            ->whereDate('c.campaign_date', '>=', $date->subDays(90)->toDateString())
            // El más cercano al pedido: si hubo dos campañas en la ventana, el
            // bono que se gastó es el más reciente de los dos.
            ->orderByDesc('c.campaign_date')
            ->select('helpdesk_birthday_recipients.*')
            ->first();
    }
}
