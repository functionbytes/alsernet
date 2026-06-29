<?php

namespace Modules\EcommercePayment\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\EcommercePayment\Services\GatewayRegistry;
use Modules\EcommercePayment\Services\PaymentGatewayManager;

class PaymentMethodsController extends Controller
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected GatewayRegistry $registry,
    ) {}

    public function index(): View
    {
        $moduleGateways = $this->registry->getGatewayModules();

        $gateways = collect($this->gatewayManager->all())
            ->map(function (string $class, string $key) use ($moduleGateways) {
                try {
                    $instance = app($class);
                    $moduleInfo = $moduleGateways[$key] ?? null;

                    return [
                        'key' => $key,
                        'name' => $instance->getName(),
                        'enabled' => $instance->isEnabled(),
                        'settings_route' => $instance->getSettingsRoute(),
                        'is_module' => $moduleInfo !== null,
                        'module_name' => $moduleInfo['module_name'] ?? null,
                        'module_alias' => $moduleInfo['module_alias'] ?? null,
                        'module_enabled' => $moduleInfo['module_enabled'] ?? null,
                        'version' => $moduleInfo['version'] ?? null,
                        'description' => $moduleInfo['description'] ?? null,
                    ];
                } catch (\Throwable) {
                    return [
                        'key' => $key,
                        'name' => ucfirst(str_replace('_', ' ', $key)),
                        'enabled' => false,
                        'settings_route' => null,
                        'is_module' => isset($moduleGateways[$key]),
                        'module_name' => $moduleGateways[$key]['module_name'] ?? null,
                        'module_alias' => $moduleGateways[$key]['module_alias'] ?? null,
                        'module_enabled' => $moduleGateways[$key]['module_enabled'] ?? null,
                        'version' => null,
                        'description' => null,
                    ];
                }
            })
            ->values();

        return view('ecommerce-payment::admin.methods.index', compact('gateways'));
    }
}
