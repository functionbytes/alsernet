<?php

namespace Modules\HelpdeskChatFlow\Services;

use App\Helpers\PiiMasker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;

/**
 * Verificación de identidad por OTP para el chatbot, antes de exponer datos
 * sensibles del cliente (pedidos). El código se envía FUERA DE BANDA al
 * email registrado del cliente resuelto — nunca por el propio chat — de modo
 * que quien solo conoce el email de un tercero (pero no accede a su buzón) no
 * pueda hacerse pasar por él. Cierra el IDOR del nodo identify_customer.
 *
 * El estado vive en el contexto de la sesión (hasheado), sin tablas nuevas.
 */
class ChatFlowIdentityOtp
{
    private const TTL_SECONDS = 600; // 10 min

    private const MAX_ATTEMPTS = 5;

    /**
     * Genera un código y lo entrega al email del cliente resuelto. Devuelve
     * false si no hay canal fuera de banda (sin email): en ese caso el llamante
     * debe tratar la identificación como NO verificada (fail-closed).
     *
     * @param  array<string,mixed>  $customer
     */
    public function challenge(ChatFlowSession $session, array $customer): bool
    {
        $email = is_string($customer['email'] ?? null) ? trim($customer['email']) : '';

        if ($email === '') {
            return false;
        }

        $code = (string) random_int(100000, 999999);

        $session->setContextValues([
            '_otp_pending' => true,
            '_otp_hash' => Hash::make($code),
            '_otp_expires' => now()->addSeconds(self::TTL_SECONDS)->timestamp,
            '_otp_attempts' => 0,
            '_otp_customer' => $customer,
        ]);

        $this->deliver($email, $code);

        return true;
    }

    public function isPending(ChatFlowSession $session): bool
    {
        return (bool) $session->getContextValue('_otp_pending');
    }

    /**
     * Verifica el código introducido por el cliente.
     *
     * @return 'ok'|'invalid'|'expired'|'locked'|'none'
     */
    public function verify(ChatFlowSession $session, string $code): string
    {
        if (! $this->isPending($session)) {
            return 'none';
        }

        if ((int) $session->getContextValue('_otp_expires') < now()->timestamp) {
            return 'expired';
        }

        $attempts = (int) $session->getContextValue('_otp_attempts') + 1;
        $session->setContextValue('_otp_attempts', $attempts);

        if ($attempts > self::MAX_ATTEMPTS) {
            return 'locked';
        }

        if (! Hash::check(trim($code), (string) $session->getContextValue('_otp_hash'))) {
            return 'invalid';
        }

        return 'ok';
    }

    /**
     * El cliente que quedó pendiente de verificar en el challenge.
     *
     * @return array<string,mixed>|null
     */
    public function pendingCustomer(ChatFlowSession $session): ?array
    {
        $customer = $session->getContextValue('_otp_customer');

        return is_array($customer) ? $customer : null;
    }

    public function clear(ChatFlowSession $session): void
    {
        $session->setContextValues([
            '_otp_pending' => null,
            '_otp_hash' => null,
            '_otp_expires' => null,
            '_otp_attempts' => null,
            '_otp_customer' => null,
        ]);
    }

    private function deliver(string $email, string $code): void
    {
        try {
            Mail::raw(
                "Tu código de verificación es: {$code}\n\nCaduca en 10 minutos. Si no has solicitado este código, ignora este mensaje.",
                function ($message) use ($email) {
                    $message->to($email)->subject('Tu código de verificación');
                }
            );
        } catch (\Throwable $e) {
            Log::warning('ChatFlowIdentityOtp: no se pudo enviar el código', [
                'email' => PiiMasker::email($email),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
