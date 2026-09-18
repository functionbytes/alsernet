<?php

namespace Modules\HelpdeskSocial\Services\Channels;

use Illuminate\Support\Collection;
use Modules\HelpdeskSocial\Contracts\SocialChannelInterface;
use RuntimeException;

class SocialChannelRegistry
{
    /** @var array<string, class-string<SocialChannelInterface>> */
    private array $channels = [];

    /** @var array<string, SocialChannelInterface> */
    private array $resolved = [];

    /**
     * Register a channel provider class.
     *
     * @param  class-string<SocialChannelInterface>  $class
     */
    public function register(string $identifier, string $class): void
    {
        $this->channels[$identifier] = $class;
    }

    /**
     * Resolve a channel by its identifier.
     */
    public function get(string $identifier): SocialChannelInterface
    {
        if (isset($this->resolved[$identifier])) {
            return $this->resolved[$identifier];
        }

        if (! isset($this->channels[$identifier])) {
            throw new RuntimeException("Social channel '{$identifier}' is not registered.");
        }

        $instance = app($this->channels[$identifier]);

        if (! $instance instanceof SocialChannelInterface) {
            throw new RuntimeException("Social channel class for '{$identifier}' must implement SocialChannelInterface.");
        }

        $this->resolved[$identifier] = $instance;

        return $instance;
    }

    /**
     * Check if a channel is registered.
     */
    public function has(string $identifier): bool
    {
        return isset($this->channels[$identifier]);
    }

    /**
     * Get all registered channel identifiers.
     *
     * @return array<int, string>
     */
    public function identifiers(): array
    {
        return array_keys($this->channels);
    }

    /**
     * Get all resolved channel instances.
     *
     * @return Collection<string, SocialChannelInterface>
     */
    public function all(): Collection
    {
        return collect($this->channels)
            ->map(fn (string $class, string $identifier) => $this->get($identifier));
    }
}
