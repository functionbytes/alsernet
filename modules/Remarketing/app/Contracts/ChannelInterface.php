<?php

namespace Modules\Remarketing\Contracts;

use Modules\Remarketing\Models\Message;

interface ChannelInterface
{
    /**
     * Unique identifier for this channel (e.g. 'email', 'sms', 'push').
     */
    public function name(): string;

    /**
     * Dispatch a message through this channel.
     */
    public function send(Message $message): void;

    /**
     * Whether this channel supports the given message.
     */
    public function supports(Message $message): bool;
}
