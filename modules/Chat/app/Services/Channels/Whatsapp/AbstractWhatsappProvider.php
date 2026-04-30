<?php

namespace Modules\Chat\Services\Channels\Whatsapp;

use Illuminate\Http\Request;
use Modules\Chat\Contracts\WhatsappProviderInterface;
use Modules\Chat\Models\Channels\Whatsapp;

abstract class AbstractWhatsappProvider implements WhatsappProviderInterface
{
    protected Whatsapp $whatsapp;

    public function __construct(Whatsapp $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    /**
     * Get the WhatsApp channel instance.
     */
    public function getWhatsapp(): Whatsapp
    {
        return $this->whatsapp;
    }

    /**
     * Format phone number (remove + and spaces by default).
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        return preg_replace('/[^0-9]/', '', $phoneNumber);
    }

    /**
     * Get API URL from config.
     */
    abstract protected function getApiUrl(): string;

    /**
     * Get API credentials from config.
     */
    abstract protected function getApiCredential(): string;

    /**
     * Handle incoming webhook - must be implemented by each provider.
     */
    abstract public function handleWebhook(Request $request, array $data): void;
}
