<?php

namespace Modules\HelpdeskIntegration\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskIntegration\Jobs\SendIdentityCodeSmsJob;
use Modules\HelpdeskIntegration\Mail\CustomerIdentityCodeMail;
use Modules\HelpdeskIntegration\Models\CustomerIdentityVerification;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;

/**
 * Gate de identidad del cliente antes de exponer sus integraciones externas.
 * `expires_at` acota la ventana para introducir el codigo (10 min);
 * `verified_at` marca el inicio de la sesion verificada (TTL de 30 min).
 */
class CustomerIdentityVerificationService
{
    private const CODE_TTL_MINUTES = 10;

    private const SESSION_TTL_MINUTES = 30;

    /**
     * Intentos fallidos permitidos POR CÓDIGO antes de quemarlo. Complementa
     * al AuthRateLimiter (cache, por identifier+IP): aquel se reinicia con su
     * decay y se diluye rotando IPs; este contador vive en la fila del código
     * y acota los intentos totales contra un mismo OTP a un máximo fijo.
     */
    private const MAX_CODE_ATTEMPTS = 5;

    private const MAIL_TEMPLATE_KEY = 'helpdesk_integration.customer_identity_code';

    public function requestCode(Customer $customer, string $channel): void
    {
        // Invalida cualquier código previo aún pendiente del cliente: solo
        // debe existir un código válido a la vez (varios reenvíos dejaban
        // varios códigos vivos simultáneamente).
        CustomerIdentityVerification::query()
            ->where('customer_id', $customer->id)
            ->whereNull('verified_at')
            ->where('expires_at', '>=', now())
            ->update(['expires_at' => now()->subSecond()]);

        $code = (string) random_int(100000, 999999);

        $verification = CustomerIdentityVerification::query()->create([
            'customer_id' => $customer->id,
            'channel' => $channel,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ]);

        $this->deliver($customer, $channel, $code, $verification);
    }

    public function confirmCode(Customer $customer, string $code, ?int $conversationId = null): bool
    {
        $verification = CustomerIdentityVerification::query()
            ->where('customer_id', $customer->id)
            ->whereNull('verified_at')
            ->where('expires_at', '>=', now())
            ->latest('id')
            ->first();

        if (! $verification || $verification->attempts >= self::MAX_CODE_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($code, $verification->code_hash)) {
            $verification->increment('attempts');

            // Al agotar los intentos el código se quema: deja de ser "activo"
            // para futuras confirmaciones y el agente debe solicitar uno nuevo.
            if ($verification->attempts >= self::MAX_CODE_ATTEMPTS) {
                $verification->update(['expires_at' => now()->subSecond()]);
            }

            return false;
        }

        $verification->update([
            'verified_at' => now(),
            'verified_by' => auth()->id(),
            'conversation_id' => $conversationId,
        ]);

        return true;
    }

    /**
     * El agente confirma la identidad sin enviar ningun codigo (queda
     * registrado quien y cuando la marco). No hay ventana de codigo
     * pendiente que proteger, por eso expires_at no es relevante aqui:
     * activeVerification() solo mira verified_at.
     */
    public function verifyManually(Customer $customer, ?int $conversationId, int $verifiedByUserId): void
    {
        CustomerIdentityVerification::query()
            ->where('customer_id', $customer->id)
            ->whereNull('verified_at')
            ->where('expires_at', '>=', now())
            ->update(['expires_at' => now()->subSecond()]);

        CustomerIdentityVerification::query()->create([
            'customer_id' => $customer->id,
            'conversation_id' => $conversationId,
            'channel' => 'manual',
            'code_hash' => Hash::make((string) random_int(100000, 999999)),
            'expires_at' => now(),
            'verified_at' => now(),
            'verified_by' => $verifiedByUserId,
        ]);
    }

    public function isVerified(Customer $customer): bool
    {
        return $this->activeVerification($customer) !== null;
    }

    /**
     * Resumen de la sesion verificada activa (para el banner "identidad
     * validada" y su cuenta atras de caducidad). Null si no hay ninguna.
     *
     * @return array{channel: string, verified_at: string, verified_by: ?string, expires_at: string, conversation_id: ?int}|null
     */
    public function summary(Customer $customer): ?array
    {
        $verification = $this->activeVerification($customer);

        if (! $verification) {
            return null;
        }

        return [
            'channel' => $verification->channel,
            'verified_at' => $verification->verified_at->toIso8601String(),
            'verified_by' => $verification->verifiedBy?->full_name,
            'expires_at' => $verification->verified_at->addMinutes(self::SESSION_TTL_MINUTES)->toIso8601String(),
            'conversation_id' => $verification->conversation_id,
        ];
    }

    private function activeVerification(Customer $customer): ?CustomerIdentityVerification
    {
        return CustomerIdentityVerification::query()
            ->where('customer_id', $customer->id)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes(self::SESSION_TTL_MINUTES))
            ->with('verifiedBy')
            ->latest('verified_at')
            ->first();
    }

    private function deliver(Customer $customer, string $channel, string $code, CustomerIdentityVerification $verification): void
    {
        if ($channel === 'sms') {
            $to = $customer->whatsapp_phone ?: $customer->phone;

            if ($to) {
                SendIdentityCodeSmsJob::dispatch($to, "Tu código de verificación es {$code}. Caduca en 10 minutos.");
            }

            return;
        }

        if ($customer->email) {
            [$subject, $content] = $this->renderEmail($customer, $code);

            Mail::to($customer->email)->queue(new CustomerIdentityCodeMail($customer, $subject, $content, $verification->id));
        }
    }

    /**
     * Resuelve y renderiza la plantilla del codigo de verificacion desde el
     * catalogo del modulo Mailer (sembrada por HelpdeskIntegrationEmailTemplatesSeeder).
     *
     * @return array{0: string, 1: string}
     */
    private function renderEmail(Customer $customer, string $code): array
    {
        $template = MailerTemplate::query()
            ->where('key', self::MAIL_TEMPLATE_KEY)
            ->where('is_enabled', true)
            ->first();

        $variables = [
            'CUSTOMER_NAME' => $customer->name,
            'CODE' => $code,
            'COMPANY_NAME' => config('app.name'),
        ];

        if (! $template) {
            Log::warning('Plantilla de codigo de identidad no encontrada en Mailer', ['key' => self::MAIL_TEMPLATE_KEY]);

            return ['Tu código de verificación', "Tu código es: {$code}"];
        }

        $subject = MailerTemplateRendererService::replaceVariables($template->subject ?? 'Tu código de verificación', $variables);
        $content = MailerTemplateRendererService::renderEmailTemplate($template, $variables);

        return [$subject, $content];
    }
}
