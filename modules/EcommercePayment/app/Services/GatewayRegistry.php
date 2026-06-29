<?php

namespace Modules\EcommercePayment\Services;

use Illuminate\Support\Facades\Log;
use Modules\EcommercePayment\Contracts\PaymentGatewayContract;
use Nwidart\Modules\Facades\Module;

/**
 * Scans all enabled modules for payment gateways declared in module.json
 * with "type": "payment-gateway". Registers them automatically.
 *
 * Convention for external gateway modules:
 * {
 *   "type": "payment-gateway",
 *   "gateway_channel": "stripe",
 *   "gateway_class": "Modules\\PaymentGatewayStripe\\Services\\StripeGateway"
 * }
 */
class GatewayRegistry
{
    /**
     * Returns [channel => class] for all valid gateway modules found.
     *
     * @return array<string, string>
     */
    public function discover(): array
    {
        $gateways = [];

        foreach (Module::allEnabled() as $module) {
            $moduleJsonPath = $module->getPath().DIRECTORY_SEPARATOR.'module.json';

            if (! file_exists($moduleJsonPath)) {
                continue;
            }

            $config = json_decode(file_get_contents($moduleJsonPath), true);

            if (($config['type'] ?? null) !== 'payment-gateway') {
                continue;
            }

            $channel = $config['gateway_channel'] ?? null;
            $class = $config['gateway_class'] ?? null;

            if (! $channel || ! $class) {
                Log::warning("Payment gateway module '{$module->getName()}' is missing gateway_channel or gateway_class in module.json.");

                continue;
            }

            if (! class_exists($class)) {
                Log::warning("Payment gateway class '{$class}' from module '{$module->getName()}' not found. Run composer dump-autoload.");

                continue;
            }

            if (! is_a($class, PaymentGatewayContract::class, true)) {
                Log::warning("Class '{$class}' from module '{$module->getName()}' does not implement PaymentGatewayContract.");

                continue;
            }

            if (isset($gateways[$channel])) {
                Log::warning("Duplicate payment gateway channel '{$channel}': '{$class}' overrides '{$gateways[$channel]}'.");
            }

            $gateways[$channel] = $class;
        }

        return $gateways;
    }

    /**
     * Returns the module.json config for modules of type "payment-gateway".
     *
     * @return array<string, array<string, mixed>>
     */
    public function getGatewayModules(): array
    {
        $modules = [];

        foreach (Module::all() as $module) {
            $moduleJsonPath = $module->getPath().DIRECTORY_SEPARATOR.'module.json';

            if (! file_exists($moduleJsonPath)) {
                continue;
            }

            $config = json_decode(file_get_contents($moduleJsonPath), true);

            if (($config['type'] ?? null) !== 'payment-gateway') {
                continue;
            }

            $modules[$config['gateway_channel'] ?? $module->getName()] = array_merge($config, [
                'module_name' => $module->getName(),
                'module_enabled' => $module->isEnabled(),
                'module_alias' => $module->getAlias(),
            ]);
        }

        return $modules;
    }

    /**
     * Validates that a module.json array meets the payment-gateway requirements.
     * Returns array of validation error strings, empty if valid.
     *
     * @param  array<string, mixed>  $config
     * @return string[]
     */
    public static function validate(array $config): array
    {
        if (($config['type'] ?? null) !== 'payment-gateway') {
            return [];
        }

        $errors = [];

        if (empty($config['gateway_channel'])) {
            $errors[] = 'Falta el campo "gateway_channel" en module.json.';
        }

        if (empty($config['gateway_class'])) {
            $errors[] = 'Falta el campo "gateway_class" en module.json.';
        }

        return $errors;
    }
}
