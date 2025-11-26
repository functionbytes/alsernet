<?php

namespace AlsernetShopping\Carriers;

use Address;
use Context;
use Configuration;
use Module;

abstract class AbstractCarrierHandler implements CarrierHandlerInterface
{
    protected $context;
    protected $module;
    protected $configuration = [];

    public function __construct(Context $context = null)
    {
        $this->context = $context ?: Context::getContext();
        $this->module = Module::getInstanceByName('alsernetshopping');
        $this->loadConfiguration();
    }

    protected function loadConfiguration(): void
    {
        // Configuración básica común
        $this->configuration = [
            'enabled' => true,
            'debug' => (bool)Configuration::get('ALSERNET_CARRIER_DEBUG'),
            'cache_enabled' => true,
        ];
    }

    public function isEnabled(): bool
    {
        return (bool)($this->configuration['enabled'] ?? true);
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function validateAvailability(Context $context): array
    {

        if (!$this->isEnabled()) {
            return [
                'valid' => false,
                'message' => 'Carrier is not enabled'
            ];
        }

        if (!$context->cart || !$context->cart->id) {
            return [
                'valid' => false,
                'message' => 'No cart found'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Carrier is available'
        ];
    }

    public function cleanup(): void{
    }
    public function processSelection(array $requestData, \Context $context): array
    {
        return $this->processForm($requestData, $context);
    }

    public function getRequiredAssets(): array
    {
        // Assets por defecto - sobrescribir en clases hijas
        return [
            [
                'type' => 'css',
                'path' => 'modules/alsernetshopping/views/css/front/carriers/default.css'
            ]
        ];
    }
    public function getTemplatePath(): string
    {
        return 'module:alsernetshopping/views/templates/front/checkout/carriers/default/interface.tpl';
    }

    protected function l(string $string, string $specific = ''): string
    {
        if ($this->module) {
            return $this->module->l($string, $specific ?: static::class);
        }
        return $string;
    }

    protected function renderTemplate(string $template, array $data = []): string
    {
        try {
            // Asignar datos del template + link automáticamente para todos los carriers
            $templateData = array_merge($data, [
                'link' => $this->context->link
            ]);

            foreach ($templateData as $key => $value) {
                $this->context->smarty->assign($key, $value);
            }

            return $this->context->smarty->fetch($template);
        } catch (\Exception $e) {
            // Log error y retornar mensaje de error
            $errorMsg = "CarrierHandler template error - Template: {$template}, Error: " . $e->getMessage() . ", File: " . $e->getFile() . ", Line: " . $e->getLine();
            error_log($errorMsg);

            if ($this->configuration['debug']) {
                error_log("CarrierHandler debug info - Data keys: " . implode(', ', array_keys($data)));
            }
            return '<div class="alert alert-warning">Error loading carrier interface</div>';
        }
    }
    protected function validateData(array $data, array $required = []): array
    {
        $errors = [];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    protected function createResponse(string $status, string $html = '', array $data = [], string $message = ''): array
    {
        return [
            'status' => $status,
            'html' => $html,
            'data' => $data,
            'message' => $message,
            'carrier_id' => $this->getId(),
            'timestamp' => time()
        ];
    }

    protected function debug(string $message, array $context = []): void
    {
        if ($this->configuration['debug']) {
            $carrierName = static::class;
            $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
            error_log("[$carrierName] $message$contextStr");
        }
    }
}