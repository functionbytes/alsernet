<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $twilioSid;
    protected $twilioToken;
    protected $twilioFrom;

    public function __construct()
    {
        $this->twilioSid = config('services.twilio.sid');
        $this->twilioToken = config('services.twilio.token');
        $this->twilioFrom = config('services.twilio.from');
    }

    /**
     * Enviar SMS usando Twilio
     */
    public function send(string $to, string $message): bool
    {
        try {
            $response = Http::withBasicAuth($this->twilioSid, $this->twilioToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->twilioSid}/Messages.json", [
                    'From' => $this->twilioFrom,
                    'To' => $to,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                Log::info("SMS enviado exitosamente a {$to}");
                return true;
            }

            Log::error("Error enviando SMS a {$to}: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("Excepción enviando SMS a {$to}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar SMS masivo
     */
    public function sendBulk(array $recipients, string $message): array
    {
        $results = [];

        foreach ($recipients as $recipient) {
            $results[$recipient] = $this->send($recipient, $message);
        }

        return $results;
    }

    /**
     * Validar formato de número de teléfono
     */
    public function isValidPhoneNumber(string $phone): bool
    {
        // Formato internacional básico
        return preg_match('/^\+[1-9]\d{1,14}$/', $phone);
    }

    /**
     * Formatear número de teléfono
     */
    public function formatPhoneNumber(string $phone, string $countryCode = '+1'): string
    {
        // Remover espacios y caracteres especiales
        $phone = preg_replace('/[^\d]/', '', $phone);

        // Añadir código de país si no existe
        if (!str_starts_with($phone, '+')) {
            $phone = $countryCode . $phone;
        }

        return $phone;
    }

    /**
     * Obtener estado de mensaje SMS
     */
    public function getMessageStatus(string $messageSid): ?array
    {
        try {
            $response = Http::withBasicAuth($this->twilioSid, $this->twilioToken)
                ->get("https://api.twilio.com/2010-04-01/Accounts/{$this->twilioSid}/Messages/{$messageSid}.json");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error("Error obteniendo estado de SMS {$messageSid}: " . $e->getMessage());
            return null;
        }
    }
}
