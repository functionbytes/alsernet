<?php

namespace Modules\HelpdeskBirthday\Services;

use Carbon\CarbonImmutable;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;
use Modules\HelpdeskBirthday\Exceptions\BirthdayAudienceException;
use Throwable;
use XMLReader;

/**
 * Cumpleañeros del día leídos DIRECTAMENTE de Gestión.
 *
 * `GET /api-gestion/cliente/?fnacimiento=AAAA-MM-DD` devuelve los clientes que
 * cumplen años ese día y mes: **el año del parámetro se ignora**. Verificado
 * contra el ERP real — `1980-09-04` y `1975-09-04` devuelven exactamente los
 * mismos 727 clientes, y `09-04` a secas responde HTTP 500, así que hay que
 * mandar siempre una fecha completa aunque el año no signifique nada.
 *
 * No usa ErpService a propósito: su parseXmlResponse() vuelca la respuesta
 * entera a `Log::info`, y aquí eso son ~880 KB con el nombre, el correo y los
 * datos LOPD de cientos de personas en cada consulta.
 *
 * Tampoco carga el XML en memoria de golpe: se recorre con XMLReader y solo se
 * conservan los campos que la campaña necesita.
 */
class BirthdayErpAudienceService
{
    /** Año bisiesto arbitrario: el ERP ignora el año, pero exige uno válido —
     *  y con 2000 el 29 de febrero también es una fecha real. */
    private const ANY_YEAR = 2000;

    public function __construct(
        private readonly BirthdayDayResolver $days,
    ) {}

    public function isConfigured(): bool
    {
        return $this->baseUrl() !== '';
    }

    /**
     * Filas con la misma forma que devuelve el manager, para que
     * BirthdayAudienceService::normalize() no distinga la procedencia.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchForDate(CarbonImmutable $date, array $exclusions = []): array
    {
        $base = $this->baseUrl();

        if ($base === '') {
            throw BirthdayAudienceException::managerNotConfigured();
        }

        $max = (int) config('helpdeskbirthday.max_recipients', 2000);
        $rows = [];

        // Normalmente un solo día; dos cuando la política de bisiestos traspasa
        // los 29-feb a otro día (ver BirthdayDayResolver).
        foreach ($this->days->daysFor($date) as $monthDay) {
            foreach ($this->request($base, $monthDay) as $row) {
                if (! $this->passesExclusions($row, $exclusions)) {
                    continue;
                }

                $rows[] = $row;

                // La guarda de tamaño se aplica sobre la marcha: si el ERP
                // devolviera media base de clientes, se corta aquí y no después
                // de habérsela traído entera.
                if (count($rows) > $max) {
                    throw BirthdayAudienceException::tooManyRecipients(count($rows), $max);
                }
            }
        }

        Log::info('[HelpdeskBirthday] Cumpleañeros leídos de Gestión', [
            'date' => $date->toDateString(),
            'count' => count($rows),
        ]);

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function request(string $base, string $monthDay): array
    {
        [$month, $day] = explode('-', $monthDay);
        $fnacimiento = sprintf('%d-%s-%s', self::ANY_YEAR, $month, $day);

        try {
            $response = $this->client($base)->get('/api-gestion/cliente/', [
                'query' => ['fnacimiento' => $fnacimiento],
            ]);
        } catch (Throwable $e) {
            throw BirthdayAudienceException::requestFailed(0, $e->getMessage());
        }

        if ($response->getStatusCode() !== 200) {
            throw BirthdayAudienceException::requestFailed(
                $response->getStatusCode(),
                mb_substr((string) $response->getBody(), 0, 500),
            );
        }

        return $this->parse((string) $response->getBody());
    }

    // protected: los tests la sustituyen por un cliente con respuestas
    // enlatadas, sin tocar el ERP real.
    protected function client(string $base): Client
    {
        return new Client([
            'base_uri' => $base,
            // La consulta de un día tarda ~16 s contra el ERP real; el timeout
            // por defecto de ErpService (30 s) se queda corto en días con mucha
            // gente.
            'timeout' => (int) config('helpdeskbirthday.erp_timeout', 90),
            'connect_timeout' => 10,
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/xml',
                'User-Agent' => 'Laravel/HelpdeskBirthday',
                'Connection' => 'close',
            ],
        ]);
    }

    /**
     * Recorre el XML quedándose solo con los <resource> de primer nivel: cada
     * cliente trae dentro otros <resource> anidados (sus catálogos), que no
     * interesan y multiplicarían por cuatro el recuento.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parse(string $xml): array
    {
        $reader = new XMLReader;

        if (! $reader->XML($xml, 'UTF-8', LIBXML_NONET | LIBXML_NOERROR)) {
            return [];
        }

        $rows = [];
        $depth = null;

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'resource') {
                continue;
            }

            // El primer <resource> que aparece marca la profundidad de "cliente";
            // los que estén más abajo son datos anidados suyos.
            $depth ??= $reader->depth;

            if ($reader->depth !== $depth) {
                continue;
            }

            $node = simplexml_load_string($reader->readOuterXml(), 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR);

            if ($node === false) {
                continue;
            }

            $rows[] = $this->toRow($node);
        }

        $reader->close();

        return $rows;
    }

    /**
     * Traduce un cliente de Gestión al formato que ya consume la campaña.
     *
     * `birth_date` va a null a propósito: el endpoint NO devuelve la fecha de
     * nacimiento (solo la acepta como filtro), así que aquí no hay dato que
     * poner. Rellenarlo con el día consultado sería inventarse el valor que
     * precisamente sirve para verificar que el filtro se aplicó.
     *
     * @return array<string, mixed>
     */
    private function toRow(\SimpleXMLElement $node): array
    {
        $get = static fn (string $field): string => trim((string) ($node->{$field} ?? ''));

        return [
            'id' => $get('idcliente'),
            'email' => $get('email'),
            'first_name' => $get('nombre'),
            'last_name' => $get('apellidos'),
            'language' => $get('ididioma') !== '' ? $get('ididioma') : null,
            'birth_date' => null,
            // Campos de Gestión que deciden si se le puede escribir.
            'lopd_accepted_at' => $get('faceptacion_lopd'),
            'no_commercial_info' => $get('no_informacion_comercial_lopd') === '1',
            'unsubscribed_at' => $get('fbaja'),
            'status' => $get('estado'),
        ];
    }

    /**
     * Exclusiones que el manager aplicaba como filtros de consulta y que aquí
     * hay que resolver en casa: el endpoint de Gestión solo sabe filtrar por
     * fecha de nacimiento.
     *
     * @param  array<string, mixed>  $row
     */
    private function passesExclusions(array $row, array $exclusions): bool
    {
        // Una baja en Gestión es una baja: no se le escribe pase lo que pase.
        if (($row['unsubscribed_at'] ?? '') !== '') {
            return false;
        }

        if (($exclusions['has_email'] ?? true) && ($row['email'] ?? '') === '') {
            return false;
        }

        if (($exclusions['lopd_accepted'] ?? true) && ($row['lopd_accepted_at'] ?? '') === '') {
            return false;
        }

        if (($exclusions['commercial_optin'] ?? true) && ($row['no_commercial_info'] ?? false)) {
            return false;
        }

        return true;
    }

    private function baseUrl(): string
    {
        $url = (string) Setting::get('erp_api_url', (string) env('ERP_URL', ''));

        // La URL guardada incluye /api-gestion, pero las rutas de este servicio
        // ya lo llevan delante (igual que en ErpService).
        return rtrim(preg_replace('~/api-gestion/?$~', '', $url) ?? '', '/');
    }
}
