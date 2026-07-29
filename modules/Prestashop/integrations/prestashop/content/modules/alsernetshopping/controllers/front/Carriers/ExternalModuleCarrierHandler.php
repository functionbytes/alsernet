<?php

namespace AlsernetShopping\Carriers;

use Address;
use Context;
use Module;

abstract class ExternalModuleCarrierHandler extends AbstractCarrierHandler
{
    protected $externalModule;
    protected $externalModuleName;
    protected $moduleEnabled = false;

    /**
     * Constructor
     */
    public function __construct(Context $context = null)
    {
        parent::__construct($context);
        $this->externalModuleName = $this->getExternalModuleName();
        $this->initializeExternalModule();
    }

    abstract protected function getExternalModuleName(): string;

    abstract protected function getExternalModuleConfig(): array;

    abstract protected function processExternalModuleData(array $data, Context $context): array;

    protected function initializeExternalModule(): void
    {
        try {
            if (Module::isEnabled($this->externalModuleName)) {
                $this->externalModule = Module::getInstanceByName($this->externalModuleName);
                $this->moduleEnabled = ($this->externalModule !== false);

                if ($this->moduleEnabled) {
                    $this->debug("External module initialized", ['module' => $this->externalModuleName]);
                } else {
                    $this->debug("External module instance failed", ['module' => $this->externalModuleName]);
                }
            } else {
                $this->debug("External module not enabled", ['module' => $this->externalModuleName]);
            }
        } catch (\Exception $e) {
            $this->debug("Error initializing external module", [
                'module' => $this->externalModuleName,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function isEnabled(): bool
    {
        return parent::isEnabled() && $this->moduleEnabled;
    }

    public function validateAvailability(Context $context): array
    {
        $baseValidation = parent::validateAvailability($context);

        if (!$baseValidation['valid']) {
            return $baseValidation;
        }

        // Validar módulo externo
        if (!$this->moduleEnabled) {
            return [
                'valid' => false,
                'message' => "External module '{$this->externalModuleName}' is not available"
            ];
        }

        return [
            'valid' => true,
            'message' => 'External module carrier is available'
        ];
    }

    public function getExtraContent(Address $address, Context $context): string
    {
        if (!$this->moduleEnabled) {
            return $this->getErrorTemplate("Module {$this->externalModuleName} not available");
        }

        try {
            $moduleConfig = $this->getExternalModuleConfig();
            $templateData = [
                'carrier_id' => $this->getId(),
                'delivery_address' => $address,
                'state_name' => $address->id_state ? \State::getNameById($address->id_state) : '',
                'country_name' => $address->id_country ? \Country::getNameById($context->language->id, $address->id_country) : '',
                'external_module' => $this->externalModuleName,
                'module_config' => $moduleConfig,
                'module_enabled' => $this->moduleEnabled,
            ];

            return $this->renderTemplate($this->getTemplatePath(), $templateData);

        } catch (\Exception $e) {
            $this->debug("Error generating extraContent", ['error' => $e->getMessage()]);
            return $this->getErrorTemplate('Error loading carrier interface');
        }
    }

    public function processSelection(array $data, Context $context): array
    {
        if (!$this->moduleEnabled) {
            return $this->createResponse('error', '', [], 'External module not available');
        }

        try {
            $this->debug("Processing external module selection", [
                'module' => $this->externalModuleName,
                'carrier_id' => $this->getId()
            ]);

            // Procesar datos específicos del módulo
            $moduleData = $this->processExternalModuleData($data, $context);

            if ($moduleData['status'] !== 'success') {
                return $this->createResponse('error', '', [], $moduleData['message'] ?? 'External module processing failed');
            }

            // Renderizar template con datos del módulo
            $templateData = array_merge($data, $moduleData['data'] ?? []);
            $html = $this->renderTemplate($this->getTemplatePath(), $templateData);

            return $this->createResponse('success', $html, [
                'carrier_id' => $this->getId(),
                'external_module' => $this->externalModuleName,
                'module_data' => $moduleData['data'] ?? []
            ]);

        } catch (\Exception $e) {
            $this->debug("Error processing external module selection", ['error' => $e->getMessage()]);
            return $this->createResponse('error', '', [], 'Error processing selection: ' . $e->getMessage());
        }
    }

    public function getRequiredAssets(): array
    {
        $assets = parent::getRequiredAssets();

        if ($this->moduleEnabled && $this->externalModule) {
            // Agregar assets del módulo externo si los tiene
            $externalAssets = $this->getExternalModuleAssets();
            $assets = array_merge($assets, $externalAssets);
        }

        return $assets;
    }

    protected function getExternalModuleAssets(): array
    {
        $assets = [];
        $moduleDir = _PS_MODULE_DIR_ . $this->externalModuleName . '/';

        // CSS del módulo
        $cssPath = $moduleDir . 'views/css/front/';
        if (is_dir($cssPath)) {
            $cssFiles = glob($cssPath . '*.css');
            foreach ($cssFiles as $cssFile) {
                $relativePath = 'modules/' . $this->externalModuleName . '/views/css/front/' . basename($cssFile);
                $assets[] = [
                    'type' => 'css',
                    'path' => $relativePath,
                    'priority' => 100
                ];
            }
        }

        // JS del módulo
        $jsPath = $moduleDir . 'views/js/front/';
        if (is_dir($jsPath)) {
            $jsFiles = glob($jsPath . '*.js');
            foreach ($jsFiles as $jsFile) {
                $relativePath = 'modules/' . $this->externalModuleName . '/views/js/front/' . basename($jsFile);
                $assets[] = [
                    'type' => 'js',
                    'path' => $relativePath,
                    'priority' => 200
                ];
            }
        }

        return $assets;
    }

    public function cleanup(): void
    {
        parent::cleanup();

        // Limpiar datos específicos del módulo externo si es necesario
        if ($this->externalModule && method_exists($this->externalModule, 'cleanup')) {
            try {
                $this->externalModule->cleanup();
            } catch (\Exception $e) {
                $this->debug("Error during external module cleanup", ['error' => $e->getMessage()]);
            }
        }
    }

    protected function getErrorTemplate(string $message): string
    {
        return '<div class="alert alert-warning">
            <i class="fa-solid fa-exclamation-triangle me-2"></i>
            ' . htmlspecialchars($message) . '
        </div>';
    }

    protected function hasExternalMethod(string $methodName): bool
    {
        return $this->moduleEnabled &&
            $this->externalModule &&
            method_exists($this->externalModule, $methodName);
    }

    protected function callExternalMethod(string $methodName, array $params = [])
    {
        if (!$this->hasExternalMethod($methodName)) {
            throw new \Exception("Method {$methodName} not available in external module {$this->externalModuleName}");
        }

        try {
            return call_user_func_array([$this->externalModule, $methodName], $params);
        } catch (\Exception $e) {
            $this->debug("Error calling external method", [
                'method' => $methodName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getExternalModuleInfo(): array
    {
        if (!$this->moduleEnabled) {
            return ['enabled' => false];
        }

        return [
            'enabled' => true,
            'name' => $this->externalModuleName,
            'version' => $this->externalModule->version ?? 'unknown',
            'author' => $this->externalModule->author ?? 'unknown',
            'has_config' => method_exists($this->externalModule, 'getConfigFormValues'),
            'has_cleanup' => method_exists($this->externalModule, 'cleanup')
        ];
    }

}