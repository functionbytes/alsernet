<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Setting;

/**
 * Lee/escribe únicamente `imap.connections` dentro del blob cifrado
 * `incoming_email` — el mismo setting que ya consumía FetchTicketEmailsJob y
 * que hasta ahora solo se administraba desde el módulo MailsSettings
 * (Correo entrante genérico: Pipe/API/Gmail/Mailgun/phpList). Cada entrada de
 * `imap.connections` es un "canal de correo" en el sentido de Tickets: un
 * buzón que, si tiene create_tickets/create_replies activo, genera tickets.
 *
 * El blob completo se comparte con MailsSettings (misma clave de Setting),
 * así que cada escritura decodifica el JSON completo, toca solo la clave
 * 'imap' y vuelve a codificar preservando el resto tal cual — así esta
 * pantalla no puede corromper la configuración de Pipe/API/Gmail/Mailgun/
 * phpList aunque viva en otro módulo.
 */
class TicketEmailChannelsRepository
{
    private const SETTING_KEY = 'incoming_email';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $blob = $this->readBlob();
        $connections = $blob['imap']['connections'] ?? [];

        // Defensivo: una conexión sin 'id' (dato guardado antes de que este
        // campo existiera, o corrupto) rompería toda la pantalla (la usan
        // las rutas de editar/sincronizar/eliminar de cada fila). Se
        // autoasigna uno y se persiste, así se sana una sola vez.
        $healed = false;
        foreach ($connections as &$connection) {
            if (empty($connection['id'])) {
                $connection['id'] = uniqid('imapchannel_', true);
                $healed = true;
            }
        }
        unset($connection);

        if ($healed) {
            $blob['imap']['connections'] = $connections;
            $this->writeBlob($blob);
        }

        return array_map(
            fn (array $connection) => array_merge($connection, $this->readHealth($connection)),
            $connections
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        foreach ($this->all() as $connection) {
            if (($connection['id'] ?? null) === $id) {
                return $connection;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $connection = array_merge($data, [
            'id' => uniqid('imapchannel_', true),
            'created_at' => now()->toISOString(),
        ]);

        $blob = $this->readBlob();
        $connections = $blob['imap']['connections'] ?? [];
        $connections[] = $connection;
        $blob['imap']['connections'] = $connections;
        $this->writeBlob($blob);

        return array_merge($connection, $this->readHealth($connection));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function update(string $id, array $data): ?array
    {
        $blob = $this->readBlob();
        $connections = $blob['imap']['connections'] ?? [];
        $updated = null;

        foreach ($connections as &$connection) {
            if (($connection['id'] ?? null) === $id) {
                // La contraseña es opcional al editar: si llega vacía, se
                // conserva la ya guardada en vez de borrarla. Se comprueba
                // también null porque el middleware global
                // ConvertEmptyStringsToNull ya ha convertido el input vacío
                // del formulario antes de llegar aquí — comparar solo con ''
                // dejaba pasar el null y borraba la contraseña guardada.
                if (array_key_exists('password', $data) && in_array($data['password'], ['', null], true)) {
                    unset($data['password']);
                }

                $connection = array_merge($connection, $data);
                $updated = $connection;
            }
        }
        unset($connection);

        if ($updated === null) {
            return null;
        }

        $blob['imap']['connections'] = $connections;
        $this->writeBlob($blob);

        return $updated;
    }

    public function delete(string $id): void
    {
        $blob = $this->readBlob();
        $connections = $blob['imap']['connections'] ?? [];

        $connections = array_values(array_filter(
            $connections,
            fn ($connection) => ($connection['id'] ?? null) !== $id
        ));

        $blob['imap']['connections'] = $connections;
        $this->writeBlob($blob);

        Cache::forget($this->healthCacheKey($id));
    }

    /**
     * Actualiza el estado de salud de un canal tras una corrida de
     * FetchTicketEmailsJob (agendada o manual vía "Sincronizar ahora"),
     * hasta una vez por minuto por canal. No falla si el canal fue borrado
     * entre el fetch y este write.
     *
     * Vive en su propia clave de caché, NUNCA en el blob cifrado
     * `incoming_email` — antes recordHealth() leía el blob completo,
     * mutaba solo el canal afectado y volvía a escribirlo entero
     * (read-modify-write clásico). Si un administrador creaba/editaba/
     * borraba un canal desde el panel entre esa lectura y esa escritura, el
     * write de recordHealth() pisaba el blob con una copia vieja y esa
     * edición del panel desaparecía sin error visible. Al no tocar el blob
     * aquí, este método ya no puede chocar con esas escrituras — no hace
     * falta Cache::lock.
     */
    public function recordHealth(string $id, bool $success, ?string $error = null): void
    {
        $connection = $this->find($id);

        if ($connection === null) {
            return;
        }

        Cache::forever($this->healthCacheKey($id), [
            'last_checked_at' => now()->toISOString(),
            'last_success_at' => $success ? now()->toISOString() : ($connection['last_success_at'] ?? null),
            'last_error' => $success ? null : $error,
        ]);
    }

    private function healthCacheKey(string $id): string
    {
        return self::SETTING_KEY.':health:'.$id;
    }

    /**
     * Salud cacheada de un canal, o los valores heredados del propio blob
     * (compatibilidad con canales que ya tenían last_checked_at/last_error
     * escritos ahí antes de este cambio, hasta su próximo recordHealth()).
     *
     * @param  array<string, mixed>  $connection
     * @return array{last_checked_at: ?string, last_success_at: ?string, last_error: ?string}
     */
    private function readHealth(array $connection): array
    {
        $cached = Cache::get($this->healthCacheKey($connection['id'] ?? ''));

        if (is_array($cached)) {
            return $cached;
        }

        return [
            'last_checked_at' => $connection['last_checked_at'] ?? null,
            'last_success_at' => $connection['last_success_at'] ?? null,
            'last_error' => $connection['last_error'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readBlob(): array
    {
        $raw = Setting::getDecrypted(self::SETTING_KEY, '{}');
        $data = is_string($raw) ? json_decode($raw, true) : $raw;

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $blob
     */
    private function writeBlob(array $blob): void
    {
        Setting::setEncrypted(self::SETTING_KEY, json_encode($blob));
    }
}
