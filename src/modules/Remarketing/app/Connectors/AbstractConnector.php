<?php

namespace Modules\Remarketing\Connectors;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Remarketing\Contracts\EcommerceConnector;
use Modules\Remarketing\DTOs\EventDTO;
use Modules\Remarketing\Models\Store;

abstract class AbstractConnector implements EcommerceConnector
{
    protected ?Store $store = null;

    public function bind(Store $store): static
    {
        $this->store = $store;

        return $this;
    }

    abstract public function platform(): string;

    abstract public function authenticate(array $credentials): bool;

    abstract public function verifyWebhook(Request $request): bool;

    abstract public function subscribeWebhooks(string $callbackBase): array;

    abstract public function syncCatalog(callable $onChunk): void;

    abstract public function syncCustomers(callable $onChunk): void;

    abstract public function syncOrders(callable $onChunk, ?Carbon $since = null): void;

    abstract public function handleWebhook(string $topic, array $payload): EventDTO;
}
