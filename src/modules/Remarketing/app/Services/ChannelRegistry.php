<?php

namespace Modules\Remarketing\Services;

use Modules\Remarketing\Contracts\ChannelInterface;
use Modules\Remarketing\Models\Message;

class ChannelRegistry
{
    /** @var array<string, ChannelInterface> */
    private array $channels = [];

    public function register(ChannelInterface $channel): void
    {
        $this->channels[$channel->name()] = $channel;
    }

    public function get(string $name): ?ChannelInterface
    {
        return $this->channels[$name] ?? null;
    }

    public function send(Message $message, string $channel = 'email'): void
    {
        $handler = $this->get($channel);

        if ($handler === null || ! $handler->supports($message)) {
            return;
        }

        $handler->send($message);
    }

    /** @return string[] */
    public function names(): array
    {
        return array_keys($this->channels);
    }
}
