<?php

namespace Modules\HelpdeskEmailLog\Services;

use Modules\Core\Models\Setting;

/**
 * Lista de buzones IMAP a vigilar por rebotes/quejas de spam — antes cada
 * módulo (solo Document) tenía un único buzón fijo en Settings sin UI; ahora
 * es una lista gestionable desde Settings, y cualquier módulo puede quedar
 * cubierto por uno de estos buzones. Cada fila puede acotarse a uno o más
 * módulos (module_scope, usado por la correlación por destinatario cuando no
 * hay Message-ID) o dejarse sin acotar (module_scope vacío = correlación sin
 * filtrar por módulo, para un buzón de rebotes compartido por todo el
 * sistema). Mismo patrón blob-en-Setting CIFRADO que
 * Modules\HelpdeskTickets\Services\TicketEmailChannelsRepository — a
 * diferencia del Setting equivalente que usaba Document
 * (documents.bounce_imap_password), que se guardaba en plano.
 */
class BounceMailboxesRepository
{
    private const SETTING_KEY = 'helpdeskemaillog.bounce_mailboxes';

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $blob = $this->readBlob();
        $mailboxes = $blob['mailboxes'] ?? [];

        // Defensivo, mismo motivo que TicketEmailChannelsRepository::all():
        // una fila sin 'id' rompería editar/eliminar. Se autoasigna una vez.
        $healed = false;
        foreach ($mailboxes as &$mailbox) {
            if (empty($mailbox['id'])) {
                $mailbox['id'] = uniqid('bouncebox_', true);
                $healed = true;
            }
        }
        unset($mailbox);

        if ($healed) {
            $blob['mailboxes'] = $mailboxes;
            $this->writeBlob($blob);
        }

        return array_values($mailboxes);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function enabled(): array
    {
        return array_values(array_filter($this->all(), fn (array $m) => (bool) ($m['enabled'] ?? false)));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        foreach ($this->all() as $mailbox) {
            if (($mailbox['id'] ?? null) === $id) {
                return $mailbox;
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
        $mailbox = array_merge($data, [
            'id' => uniqid('bouncebox_', true),
            'created_at' => now()->toISOString(),
            'last_checked_at' => null,
            'last_success_at' => null,
            'last_error' => null,
            'consecutive_failures' => 0,
        ]);

        $blob = $this->readBlob();
        $mailboxes = $blob['mailboxes'] ?? [];
        $mailboxes[] = $mailbox;
        $blob['mailboxes'] = $mailboxes;
        $this->writeBlob($blob);

        return $mailbox;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function update(string $id, array $data): ?array
    {
        $blob = $this->readBlob();
        $mailboxes = $blob['mailboxes'] ?? [];
        $updated = null;

        foreach ($mailboxes as &$mailbox) {
            if (($mailbox['id'] ?? null) === $id) {
                // Contraseña opcional al editar: vacía/null conserva la ya
                // guardada (mismo motivo que TicketEmailChannelsRepository —
                // ConvertEmptyStringsToNull ya convirtió el input vacío a
                // null antes de llegar aquí).
                if (array_key_exists('password', $data) && in_array($data['password'], ['', null], true)) {
                    unset($data['password']);
                }

                $mailbox = array_merge($mailbox, $data);
                $updated = $mailbox;
            }
        }
        unset($mailbox);

        if ($updated === null) {
            return null;
        }

        $blob['mailboxes'] = $mailboxes;
        $this->writeBlob($blob);

        return $updated;
    }

    public function delete(string $id): void
    {
        $blob = $this->readBlob();
        $mailboxes = array_values(array_filter(
            $blob['mailboxes'] ?? [],
            fn (array $m) => ($m['id'] ?? null) !== $id,
        ));

        $blob['mailboxes'] = $mailboxes;
        $this->writeBlob($blob);
    }

    /**
     * Actualiza el estado de salud de un buzón tras una corrida del comando
     * de rebotes — incluye el contador de fallos seguidos que antes vivía en
     * un único Setting global (ahora uno por buzón).
     */
    public function recordHealth(string $id, bool $success, ?string $error = null): void
    {
        $blob = $this->readBlob();
        $mailboxes = $blob['mailboxes'] ?? [];
        $found = false;

        foreach ($mailboxes as &$mailbox) {
            if (($mailbox['id'] ?? null) === $id) {
                $mailbox['last_checked_at'] = now()->toISOString();
                $mailbox['last_error'] = $success ? null : $error;
                $mailbox['consecutive_failures'] = $success
                    ? 0
                    : ((int) ($mailbox['consecutive_failures'] ?? 0)) + 1;

                if ($success) {
                    $mailbox['last_success_at'] = now()->toISOString();
                }

                $found = true;
            }
        }
        unset($mailbox);

        if (! $found) {
            return;
        }

        $blob['mailboxes'] = $mailboxes;
        $this->writeBlob($blob);
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
