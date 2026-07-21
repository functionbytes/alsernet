<?php

namespace Modules\HelpdeskEmailLog\Listeners\Concerns;

use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailLog\Contracts\RedactsEmailLogBody;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

/**
 * Shared helpers for reading data out of an outgoing Symfony Email.
 *
 * @internal
 */
trait InspectsMailMessage
{
    /**
     * @param  array<int, Address>  $addresses
     * @return list<string>
     */
    protected function addressesOf(array $addresses): array
    {
        return collect($addresses)->map(fn (Address $a) => $a->getAddress())->values()->all();
    }

    protected function messageIdOf(Email $message): ?string
    {
        $headers = $message->getHeaders();

        return $headers->has('Message-ID')
            ? trim($headers->get('Message-ID')->getBodyAsString(), '<>')
            : null;
    }

    /**
     * @param  array{mailable_class?: ?string, module?: ?string}  $context
     */
    protected function bodyOf(?string $body, array $context = []): ?string
    {
        $storeBody = (bool) Setting::get('helpdeskemaillog.store_body', config('helpdeskemaillog.store_body', true));

        if ($body === null || ! $storeBody) {
            return null;
        }

        if ($this->shouldRedactBody($context)) {
            return null;
        }

        $max = $this->maxBodyBytes();

        if ($max && strlen($body) > $max) {
            // mb_strcut respeta el límite en BYTES sin partir un carácter
            // multibyte por la mitad (substr puede dejar una secuencia UTF-8
            // inválida al final, corrompiendo el HTML almacenado).
            return mb_strcut($body, 0, $max, 'UTF-8').EmailLog::TRUNCATION_MARKER;
        }

        return $body;
    }

    protected function maxBodyBytes(): int
    {
        return (int) Setting::get('helpdeskemaillog.max_body_bytes', config('helpdeskemaillog.max_body_bytes'));
    }

    /**
     * @param  array{mailable_class?: ?string, module?: ?string}  $context
     */
    protected function shouldRedactBody(array $context): bool
    {
        $class = $context['mailable_class'] ?? null;
        $module = $context['module'] ?? null;

        if ($class && is_subclass_of($class, RedactsEmailLogBody::class)) {
            return true;
        }

        if ($class && in_array($class, (array) config('helpdeskemaillog.redact_body_for_classes', []), true)) {
            return true;
        }

        if ($module && in_array($module, (array) config('helpdeskemaillog.redact_body_for_modules', []), true)) {
            return true;
        }

        return false;
    }

    /**
     * @return list<array{name: ?string, size: ?int, mime: ?string}>
     */
    protected function attachmentsOf(Email $message): array
    {
        return collect($message->getAttachments())
            ->map(fn (DataPart $part) => [
                'name' => $part->getFilename(),
                'size' => strlen($part->getBody()),
                'mime' => $part->getContentType(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{mailable_class: ?string, module: ?string, entity_type: ?string, entity_id: ?int, external_id: ?string}
     */
    protected function contextOf(Email $message, array $data): array
    {
        $headers = $message->getHeaders();
        $header = fn (string $name): ?string => $headers->has($name) ? $headers->get($name)->getBodyAsString() : null;

        $mailableClass = $header('X-Mailable-Class')
            ?: (isset($data['__laravel_notification']) && is_object($data['__laravel_notification'])
                ? $data['__laravel_notification']::class
                : null);

        $entityId = $header('X-Entity-Id');

        return [
            'mailable_class' => $mailableClass,
            'module' => $header('X-Email-Module') ?: $this->detectModuleFromClass($mailableClass),
            'entity_type' => $header('X-Entity-Type') ?: null,
            'entity_id' => is_numeric($entityId) ? (int) $entityId : null,
            'external_id' => $header('X-External-Id') ?: null,
        ];
    }

    /**
     * Small bag of derived facts that are worth keeping even when the body
     * itself is not stored.
     *
     * @param  array{mailable_class?: ?string, module?: ?string}  $context
     * @return array<string, mixed>
     */
    protected function metaOf(Email $message, array $context = []): array
    {
        $meta = [
            'has_html' => $message->getHtmlBody() !== null,
            'has_text' => $message->getTextBody() !== null,
            'attachments_count' => count($message->getAttachments()),
        ];

        if ($this->shouldRedactBody($context)) {
            $meta['redacted'] = true;
        } elseif ($this->isBodyOverLimit($message)) {
            // El cuerpo almacenado quedó truncado a max_body_bytes; el flag
            // permite p.ej. bloquear reenvíos de contenido incompleto.
            $meta['truncated'] = true;
        }

        return $meta;
    }

    private function isBodyOverLimit(Email $message): bool
    {
        $storeBody = (bool) Setting::get('helpdeskemaillog.store_body', config('helpdeskemaillog.store_body', true));
        $max = $this->maxBodyBytes();

        if (! $storeBody || ! $max) {
            return false;
        }

        $html = $message->getHtmlBody();
        $text = $message->getTextBody();

        return ($html !== null && strlen($html) > $max)
            || ($text !== null && strlen($text) > $max);
    }

    /**
     * @return array{causer_id: int|string|null, causer_type: ?string}
     */
    protected function currentCauser(): array
    {
        $user = Auth::user();

        return [
            'causer_id' => Auth::id(),
            'causer_type' => $user ? $user::class : null,
        ];
    }

    protected function fromAddressOf(Email $message): ?Address
    {
        return collect($message->getFrom())->first();
    }

    private function detectModuleFromClass(?string $class): ?string
    {
        if ($class && preg_match('/Modules\\\\([^\\\\]+)\\\\/', $class, $m)) {
            return $m[1];
        }

        return null;
    }
}
