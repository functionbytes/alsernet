<?php

namespace Modules\HelpdeskBirthday\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;
use Modules\HelpdeskBirthday\Exceptions\BirthdayAudienceException;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskEmailActivity\Models\EmailSuppression;

/**
 * Construye la lista de cumpleañeros del día.
 *
 * La fecha de nacimiento solo existe en Oracle (CLIENTE_CENT.FNACIMIENTO) y
 * este proyecto no llega a Oracle (ver docker/laravel.env), así que se consulta
 * por HTTP al manager, igual que hace HelpdeskErp\Services\ErpContextService.
 */
class BirthdayAudienceService
{
    /** El manager limita 'limit' a 100 por página. */
    private const PAGE_SIZE = 100;

    /** Tope de páginas, por si el manager pagina mal y nos deja en bucle. */
    private const MAX_PAGES = 500;

    public function __construct(
        private readonly BirthdayDayResolver $days,
        private readonly BirthdayLanguageResolver $languages,
        private readonly BirthdayErpAudienceService $erp,
    ) {}

    /**
     * @return array<int, array{erp_customer_id: ?string, email: string, name: ?string, birth_date: ?string, status: string, skip_reason: ?string}>
     */
    public function fetchForDate(CarbonImmutable $date, array $exclusions = []): array
    {
        $days = $this->days->daysFor($date);

        // Dos orígenes posibles para la misma pregunta. Gestión es el bueno:
        // su endpoint de clientes filtra por día y mes de nacimiento (el año del
        // parámetro se ignora), que es justo lo que hace falta. El manager se
        // mantiene como alternativa porque es lo que había, pero su filtro
        // `birthday` no existe en todas las versiones desplegadas — y cuando no
        // existe lo ignora en silencio y devuelve clientes cualesquiera.
        $rows = $this->source() === 'erp'
            ? $this->erp->fetchForDate($date, $exclusions)
            : $this->fetchFromManager($days, $exclusions);

        $this->assertFilterWasApplied($rows, $days);

        return $this->normalize($rows, $exclusions);
    }

    private function source(): string
    {
        $configured = (string) Setting::get(
            'helpdeskbirthday.audience_source',
            config('helpdeskbirthday.audience_source', 'erp'),
        );

        return $configured === 'manager' ? 'manager' : 'erp';
    }

    /**
     * Pagina el listado del manager acumulando filas, con la guarda de tamaño
     * aplicada sobre la marcha (no esperamos a traernos la base entera para
     * darnos cuenta de que algo va mal).
     *
     * @param  array<int, string>  $days
     * @return array<int, array<string, mixed>>
     */
    private function fetchFromManager(array $days, array $exclusions): array
    {
        $baseUrl = rtrim((string) config('helpdeskErp.manager_url', ''), '/');

        if ($baseUrl === '') {
            throw BirthdayAudienceException::managerNotConfigured();
        }

        $max = (int) config('helpdeskbirthday.max_recipients', 2000);
        $query = [
            'birthday' => implode(',', $days),
            'commercial_optin' => $this->flag($exclusions, 'commercial_optin'),
            'lopd_accepted' => $this->flag($exclusions, 'lopd_accepted'),
            'has_email' => $this->flag($exclusions, 'has_email'),
            'limit' => self::PAGE_SIZE,
        ];

        $rows = [];
        $offset = 0;

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $response = $this->http()->get(
                $baseUrl.'/api/erp/customer',
                $query + ['offset' => $offset]
            );

            if (! $response->successful()) {
                throw BirthdayAudienceException::requestFailed($response->status(), $response->body());
            }

            $payload = $response->json();
            $batch = $payload['data'] ?? [];

            if ($batch === []) {
                break;
            }

            $rows = array_merge($rows, $batch);

            if (count($rows) > $max) {
                throw BirthdayAudienceException::tooManyRecipients(count($rows), $max);
            }

            if (! ($payload['pagination']['hasMore'] ?? false)) {
                break;
            }

            $offset += self::PAGE_SIZE;
        }

        return $rows;
    }

    /**
     * Comprobación de que el manager entendió el filtro. Si la versión
     * desplegada allí es anterior a este cambio, ignora `birthday` en silencio y
     * devuelve clientes cualesquiera — cosa que aquí se ve al instante porque su
     * fecha de nacimiento no cae en el día pedido.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $days
     */
    private function assertFilterWasApplied(array $rows, array $days): void
    {
        $sampled = 0;
        $mismatched = 0;

        foreach ($rows as $row) {
            $birthDate = $row['birth_date'] ?? null;

            // Un manager antiguo tampoco devuelve birth_date; sin el dato no se
            // puede verificar, y la guarda de tamaño es la única red que queda.
            if (! is_string($birthDate) || strlen($birthDate) < 10) {
                continue;
            }

            $sampled++;

            if (! in_array(substr($birthDate, 5, 5), $days, true)) {
                $mismatched++;
            }
        }

        if ($sampled > 0 && $mismatched > 0) {
            throw BirthdayAudienceException::filterNotApplied($mismatched, $sampled);
        }
    }

    /**
     * Deduplica por email, descarta direcciones inválidas y marca las suprimidas.
     *
     * Los descartes no se tiran: se guardan como `skipped` con su motivo, para
     * que el panel pueda explicar por qué la campaña salió con menos gente de la
     * que devolvió el ERP.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalize(array $rows, array $exclusions): array
    {
        $recipients = [];
        $seen = [];

        foreach ($rows as $row) {
            $email = mb_strtolower(trim((string) ($row['email'] ?? '')));
            $name = $this->composeName($row);

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if (isset($seen[$email])) {
                continue;
            }
            $seen[$email] = true;

            $recipients[] = [
                'erp_customer_id' => isset($row['id']) ? (string) $row['id'] : null,
                'email' => $email,
                'name' => $name,
                // Idioma en el que hay que escribirle, resuelto desde el id de
                // idioma del ERP (ver BirthdayLanguageResolver).
                'lang' => $this->languages->isoFor($row['language'] ?? null),
                'birth_date' => $row['birth_date'] ?? null,
                'status' => BirthdayRecipient::STATUS_PENDING,
                'skip_reason' => null,
            ];
        }

        if ($exclusions['check_suppressions'] ?? true) {
            $recipients = $this->markSuppressed($recipients);
        }

        return $recipients;
    }

    /**
     * Cruce en bloque contra email_suppressions.
     *
     * El listener EnforceEmailSuppression ya bloquea el envío por su cuenta,
     * pero marcarlos aquí evita encolar miles de jobs destinados a morir y deja
     * el recuento correcto en el panel desde el primer momento.
     *
     * @param  array<int, array<string, mixed>>  $recipients
     * @return array<int, array<string, mixed>>
     */
    private function markSuppressed(array $recipients): array
    {
        if ($recipients === []) {
            return $recipients;
        }

        $emails = array_column($recipients, 'email');
        $suppressed = [];

        // En bloques, para no armar un IN gigante con toda la audiencia.
        foreach (array_chunk($emails, 500) as $chunk) {
            $found = EmailSuppression::query()
                ->whereIn('email', $chunk)
                ->whereIn('module', ['', 'HelpdeskBirthday'])
                ->pluck('email')
                ->all();

            foreach ($found as $email) {
                $suppressed[mb_strtolower($email)] = true;
            }
        }

        if ($suppressed === []) {
            return $recipients;
        }

        foreach ($recipients as $i => $recipient) {
            if (isset($suppressed[$recipient['email']])) {
                $recipients[$i]['status'] = BirthdayRecipient::STATUS_SKIPPED;
                $recipients[$i]['skip_reason'] = BirthdayRecipient::SKIP_SUPPRESSED;
            }
        }

        Log::info('[HelpdeskBirthday] Destinatarios omitidos por supresión', [
            'count' => count($suppressed),
        ]);

        return $recipients;
    }

    private function composeName(array $row): ?string
    {
        $name = trim((string) ($row['label'] ?? ''));

        return $name !== '' ? $name : null;
    }

    private function flag(array $exclusions, string $key): int
    {
        $default = (bool) config("helpdeskbirthday.exclusions.{$key}", false);

        return ($exclusions[$key] ?? $default) ? 1 : 0;
    }

    /**
     * Mismo cliente HTTP que ErpContextService: timeout de la config de
     * HelpdeskErp y bearer del bridge cuando está configurado.
     */
    private function http(): PendingRequest
    {
        $request = Http::createPendingRequest()
            ->timeout((int) config('helpdeskErp.http_timeout', 15))
            ->acceptJson();

        $token = (string) config('helpdeskErp.bridge_token', '');

        return $token !== '' ? $request->withToken($token) : $request;
    }
}
