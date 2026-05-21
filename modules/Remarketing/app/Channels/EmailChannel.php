<?php

namespace Modules\Remarketing\Channels;

use Modules\Remarketing\Contracts\ChannelInterface;
use Modules\Remarketing\Jobs\SendEmailJob;
use Modules\Remarketing\Models\Message;

class EmailChannel implements ChannelInterface
{
    public function name(): string
    {
        return 'email';
    }

    public function send(Message $message): void
    {
        SendEmailJob::dispatch($message)->onQueue('remarketing');
    }

    public function supports(Message $message): bool
    {
        return filter_var($message->email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
