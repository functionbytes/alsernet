<?php

namespace Modules\EcommercePayment\Services;

use Illuminate\Container\Container;
use InvalidArgumentException;

class PaymentGatewayManager
{
    protected array $gateways = [];

    protected array $resolved = [];

    public function __construct(protected Container $container) {}

    public function register(string $name, string $class): self
    {
        $this->gateways[$name] = $class;

        return $this;
    }

    public function get(string $name): object
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        if (! isset($this->gateways[$name])) {
            throw new InvalidArgumentException("Payment gateway [{$name}] is not registered.");
        }

        $class = $this->gateways[$name];
        $instance = $this->container->make($class);
        $this->resolved[$name] = $instance;

        return $instance;
    }

    public function has(string $name): bool
    {
        return isset($this->gateways[$name]);
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->gateways;
    }
}
