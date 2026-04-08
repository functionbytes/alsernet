<?php

namespace Modules\Reviews\Services;

use Modules\Reviews\Contracts\ReviewPlatform;

class PlatformRegistry
{
    /** @var array<string, ReviewPlatform> */
    private array $platforms = [];

    public function register(ReviewPlatform $platform): void
    {
        $this->platforms[$platform->getName()] = $platform;
    }

    /**
     * @throws \InvalidArgumentException When the platform is not registered
     */
    public function get(string $name): ReviewPlatform
    {
        if (! $this->supports($name)) {
            throw new \InvalidArgumentException("Platform '{$name}' is not registered.");
        }

        return $this->platforms[$name];
    }

    /** @return array<string, ReviewPlatform> */
    public function all(): array
    {
        return $this->platforms;
    }

    public function supports(string $name): bool
    {
        return isset($this->platforms[$name]);
    }
}
