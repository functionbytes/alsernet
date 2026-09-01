<?php

namespace Modules\HelpdeskEmailLog\Services;

use Modules\Core\Models\Setting;

/**
 * Configuración del conector de webhooks de proveedor — proveedor
 * seleccionado, secreto de verificación (cifrado, mismo patrón que
 * BounceMailboxesRepository) y qué tipos de evento procesar. Un único blob,
 * no una lista: solo hay UN proveedor activo a la vez (el que de verdad
 * envía los correos), a diferencia de los buzones de rebote IMAP que sí
 * pueden ser varios en paralelo.
 */
class ProviderWebhookSettingsRepository
{
    private const SETTING_KEY = 'helpdeskemaillog.provider_webhook';

    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $blob = $this->readBlob();

        return array_merge([
            'provider' => null, // null|'mailrelay'|'ses'|'postmark'|'mailgun'
            'secret' => '',
            'process_bounces' => true,
            'process_complaints' => true,
            'process_deliveries' => false,
            'process_opens' => false,
        ], $blob);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        $current = $this->get();

        // Secreto opcional al editar: vacío conserva el ya guardado (mismo
        // criterio que BounceMailboxesRepository con la contraseña IMAP).
        if (array_key_exists('secret', $data) && in_array($data['secret'], ['', null], true)) {
            unset($data['secret']);
        }

        $this->writeBlob(array_merge($current, $data));
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
