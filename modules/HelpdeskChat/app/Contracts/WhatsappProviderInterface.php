<?php

namespace Modules\HelpdeskChat\Contracts;

use Illuminate\Http\Request;

interface WhatsappProviderInterface
{
    /**
     * Handle incoming webhook from the provider.
     */
    public function handleWebhook(Request $request, array $data): void;

    /**
     * Send a text message.
     */
    public function sendTextMessage(string $to, string $message): array;

    /**
     * Send a media message (image, video, audio, document).
     */
    public function sendMediaMessage(string $to, string $mediaUrl, string $mediaType, ?string $caption = null): array;

    /**
     * Send a location message.
     */
    public function sendLocationMessage(string $to, float $latitude, float $longitude, ?string $name = null, ?string $address = null): array;

    /**
     * Get connection status of the instance.
     */
    public function getConnectionStatus(): array;

    /**
     * Connect to WhatsApp (get QR code if needed).
     */
    public function connect(): array;

    /**
     * Disconnect from WhatsApp.
     */
    public function disconnect(): bool;

    /**
     * Restart the instance.
     */
    public function restart(): array;

    /**
     * Get instance settings.
     */
    public function getSettings(): array;

    /**
     * Update instance settings.
     */
    public function updateSettings(array $settings): array;

    /**
     * Fetch contacts from WhatsApp.
     */
    public function fetchContacts(): array;

    /**
     * Fetch chats from WhatsApp.
     */
    public function fetchChats(): array;

    /**
     * Delete the instance.
     */
    public function deleteInstance(): bool;

    /**
     * Check if instance is within message window.
     * (24-hour window for official API, always true for Baileys).
     */
    public function isWithinMessageWindow(): bool;

    /**
     * Get message templates (for official API providers).
     */
    public function getMessageTemplates(): array;

    /**
     * Set webhook configuration.
     */
    public function setWebhook(string $webhookUrl, array $events = []): array;
}
