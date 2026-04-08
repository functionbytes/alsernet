<?php

namespace Modules\Notification\Services;

class NotificationTypeRegistry
{
    /** @var array<string, array{key: string, label: string, description: string, defaultChannels: string[]}> */
    private array $types = [];

    /**
     * @param  string[]  $defaultChannels
     */
    public function register(
        string $key,
        string $label,
        string $description = '',
        array $defaultChannels = ['mail', 'database']
    ): void {
        $this->types[$key] = compact('key', 'label', 'description', 'defaultChannels');
    }

    /** @return array<string, array{key: string, label: string, description: string, defaultChannels: string[]}> */
    public function all(): array
    {
        return $this->types;
    }

    /** @return array{key: string, label: string, description: string, defaultChannels: string[]}|null */
    public function get(string $key): ?array
    {
        return $this->types[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->types[$key]);
    }
}
