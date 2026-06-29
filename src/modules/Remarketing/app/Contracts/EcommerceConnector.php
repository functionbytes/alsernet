<?php

namespace Modules\Remarketing\Contracts;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Remarketing\DTOs\EventDTO;
use Modules\Remarketing\Models\Store;

interface EcommerceConnector
{
    public function platform(): string;

    public function bind(Store $store): static;

    public function authenticate(array $credentials): bool;

    public function verifyWebhook(Request $request): bool;

    public function subscribeWebhooks(string $callbackBase): array;

    public function syncCatalog(callable $onChunk): void;

    public function syncCustomers(callable $onChunk): void;

    public function syncOrders(callable $onChunk, ?Carbon $since = null): void;

    public function handleWebhook(string $topic, array $payload): EventDTO;
}
