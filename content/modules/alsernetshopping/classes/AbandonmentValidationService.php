<?php

namespace AlsernetShopping;

use Cart;
use Context;
use Db;
use DbQuery;
use Customer;
use Configuration;
use Tools;
use Validate;
use Product;
use Address;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Servicio de validaciones específico para carritos abandonados
 * 
 * Funcionalidades:
 * - Validación de datos de entrada
 * - Verificación de ownership de carritos
 * - Validación de productos y stock
 * - Verificación de configuraciones del sistema
 * - Validación de triggers y condiciones
 */
class AbandonmentValidationService
{
    /** @var Context */
    private $context;
    
    /** @var AbandonedCartManager */
    private $cartManager;
    
    /** @var array */
    private $validationRules;
    
    /** @var array */
    private $errors = [];
    
    public function __construct(Context $context = null)
    {
        $this->context = $context ?: Context::getContext();
        $this->cartManager = new AbandonedCartManager($this->context);
        $this->loadValidationRules();
    }
    
    /**
     * Cargar reglas de validación
     */
    private function loadValidationRules(): void
    {
        $this->validationRules = [
            'system' => [
                'required_configs' => ['system_active', 'abandonment_enabled'],
                'min_cart_value' => 0,
                'max_cart_value' => 50000,
                'max_products_count' => 100
            ],
            'abandonment' => [
                'valid_stages' => [
                    AbandonedCartManager::STAGE_CART,
                    AbandonedCartManager::STAGE_SHIPPING,
                    AbandonedCartManager::STAGE_PAYMENT,
                    AbandonedCartManager::STAGE_REVIEW
                ],
                'max_session_duration' => 86400, // 24 horas
                'min_inactivity_time' => 30, // 30 segundos
                'max_recovery_attempts' => 10
            ],
            'behavior' => [
                'max_pages_visited' => 1000,
                'max_product_views' => 500,
                'max_scroll_depth' => 100,
                'max_clicks' => 10000,
                'max_session_time' => 86400
            ],
            'modal' => [
                'valid_types' => [
                    AbandonedCartManager::MODAL_SIMPLE,
                    AbandonedCartManager::MODAL_DISCOUNT,
                    AbandonedCartManager::MODAL_URGENCY,
                    AbandonedCartManager::MODAL_RECOMMENDATIONS,
                    AbandonedCartManager::MODAL_RECOVERY
                ],
                'valid_interactions' => ['shown', 'clicked', 'closed', 'ignored', 'converted'],
                'max_discount_percentage' => 50,
                'min_discount_percentage' => 1
            ]
        ];
    }
    
    /**
     * Validar sistema antes de cualquier operación
     */
    public function validateSystemStatus(): array
    {
        $this->errors = [];
        
        // Verificar si el sistema está activo
        if (!$this->cartManager->isSystemActive()) {
            $this->errors[] = [
                'code' => 'SYSTEM_INACTIVE',
                'message' => 'Abandonment system is not active',
                'field' => 'system_active'
            ];
        }
        
        // Verificar configuraciones requeridas
        foreach ($this->validationRules['system']['required_configs'] as $config) {
            if (!$this->cartManager->isSystemActive() && $config === 'system_active') {
                continue; // Ya validado arriba
            }
            
            $configValue = $this->getConfigValue($config);
            if ($configValue === null || $configValue === false) {
                $this->errors[] = [
                    'code' => 'MISSING_CONFIG',
                    'message' => "Required configuration '{$config}' is missing or disabled",
                    'field' => $config
                ];
            }
        }
        
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors
        ];
    }
    
    /**
     * Validar registro de abandono
     */
    public function validateAbandonmentRegistration(Cart $cart, string $stage, array $behaviorData = [], array $sessionData = []): array
    {
        $this->errors = [];
        
        // Validar sistema primero
        $systemValidation = $this->validateSystemStatus();
        if (!$systemValidation['valid']) {
            return $systemValidation;
        }
        
        // Validar carrito
        $cartValidation = $this->validateCart($cart);
        if (!$cartValidation['valid']) {
            $this->errors = array_merge($this->errors, $cartValidation['errors']);
        }
        
        // Validar stage
        if (!in_array($stage, $this->validationRules['abandonment']['valid_stages'])) {
            $this->errors[] = [
                'code' => 'INVALID_STAGE',
                'message' => "Invalid abandonment stage: {$stage}",
                'field' => 'stage'
            ];
        }
        
        // Validar datos de comportamiento
        if (!empty($behaviorData)) {
            $behaviorValidation = $this->validateBehaviorData($behaviorData);
            if (!$behaviorValidation['valid']) {
                $this->errors = array_merge($this->errors, $behaviorValidation['errors']);
            }
        }
        
        // Validar datos de sesión
        if (!empty($sessionData)) {
            $sessionValidation = $this->validateSessionData($sessionData);
            if (!$sessionValidation['valid']) {
                $this->errors = array_merge($this->errors, $sessionValidation['errors']);
            }
        }
        
        // Verificar si ya existe un abandono para este carrito
        $existingAbandonment = $this->getExistingAbandonmentId($cart->id);
        if ($existingAbandonment) {
            // Validar si se puede actualizar
            $updateValidation = $this->validateAbandonmentUpdate($existingAbandonment, $stage);
            if (!$updateValidation['valid']) {
                $this->errors = array_merge($this->errors, $updateValidation['errors']);
            }
        }
        
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
            'existing_abandonment_id' => $existingAbandonment
        ];
    }
    
    /**
     * Validar carrito
     */
    public function validateCart(Cart $cart): array
    {
        $errors = [];
        
        // Verificar que el carrito existe y está cargado
        if (!$cart || !Validate::isLoadedObject($cart)) {
            $errors[] = [
                'code' => 'INVALID_CART',
                'message' => 'Cart is not valid or not loaded',
                'field' => 'cart'
            ];
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Verificar ownership del carrito
        if (!$this->validateCartOwnership($cart)) {
            $errors[] = [
                'code' => 'CART_OWNERSHIP',
                'message' => 'Cart does not belong to current user',
                'field' => 'cart'
            ];
        }
        
        // Verificar que el carrito tiene productos
        $products = $cart->getProducts();
        if (empty($products)) {
            $errors[] = [
                'code' => 'EMPTY_CART',
                'message' => 'Cart is empty',
                'field' => 'cart'
            ];
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Validar número de productos
        if (count($products) > $this->validationRules['system']['max_products_count']) {
            $errors[] = [
                'code' => 'TOO_MANY_PRODUCTS',
                'message' => 'Cart has too many products',
                'field' => 'cart'
            ];
        }
        
        // Validar valor del carrito
        $cartValue = $cart->getOrderTotal();
        if ($cartValue < $this->validationRules['system']['min_cart_value']) {
            $errors[] = [
                'code' => 'CART_VALUE_TOO_LOW',
                'message' => 'Cart value is too low',
                'field' => 'cart'
            ];
        }
        
        if ($cartValue > $this->validationRules['system']['max_cart_value']) {
            $errors[] = [
                'code' => 'CART_VALUE_TOO_HIGH',
                'message' => 'Cart value is too high',
                'field' => 'cart'
            ];
        }
        
        // Validar productos individualmente
        $productValidation = $this->validateCartProducts($products);
        if (!$productValidation['valid']) {
            $errors = array_merge($errors, $productValidation['errors']);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validar productos del carrito
     */
    private function validateCartProducts(array $products): array
    {
        $errors = [];
        
        foreach ($products as $product) {
            // Verificar que el producto existe
            if (!Product::existsInDatabase($product['id_product'], 'product')) {
                $errors[] = [
                    'code' => 'PRODUCT_NOT_FOUND',
                    'message' => "Product {$product['id_product']} not found",
                    'field' => 'products',
                    'product_id' => $product['id_product']
                ];
                continue;
            }
            
            // Verificar que el producto está activo
            $productObj = new Product($product['id_product'], false, $this->context->language->id);
            if (!$productObj->active) {
                $errors[] = [
                    'code' => 'PRODUCT_INACTIVE',
                    'message' => "Product {$product['id_product']} is not active",
                    'field' => 'products',
                    'product_id' => $product['id_product']
                ];
            }
            
            // Verificar stock si está habilitado
            if (Configuration::get('PS_STOCK_MANAGEMENT')) {
                $stock = Product::getQuantity($product['id_product'], $product['id_product_attribute']);
                if ($stock < $product['cart_quantity']) {
                    $errors[] = [
                        'code' => 'INSUFFICIENT_STOCK',
                        'message' => "Insufficient stock for product {$product['id_product']}",
                        'field' => 'products',
                        'product_id' => $product['id_product'],
                        'available_stock' => $stock,
                        'requested_quantity' => $product['cart_quantity']
                    ];
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validar ownership del carrito
     */
    private function validateCartOwnership(Cart $cart): bool
    {
        // Si hay customer logueado, verificar que el carrito le pertenece
        if ($this->context->customer->isLogged()) {
            return $cart->id_customer == $this->context->customer->id;
        }
        
        // Si no hay customer, verificar por guest o sesión
        $currentGuestId = (int)$this->context->cookie->id_guest;
        return $cart->id_guest == $currentGuestId || $cart->id_customer == 0;
    }
    
    /**
     * Validar datos de comportamiento
     */
    public function validateBehaviorData(array $data): array
    {
        $errors = [];
        $rules = $this->validationRules['behavior'];
        
        // Validar páginas visitadas
        if (isset($data['pages_visited'])) {
            $pages = (int)$data['pages_visited'];
            if ($pages < 0 || $pages > $rules['max_pages_visited']) {
                $errors[] = [
                    'code' => 'INVALID_PAGES_VISITED',
                    'message' => 'Invalid pages visited count',
                    'field' => 'pages_visited'
                ];
            }
        }
        
        // Validar vistas de productos
        if (isset($data['product_views'])) {
            $views = (int)$data['product_views'];
            if ($views < 0 || $views > $rules['max_product_views']) {
                $errors[] = [
                    'code' => 'INVALID_PRODUCT_VIEWS',
                    'message' => 'Invalid product views count',
                    'field' => 'product_views'
                ];
            }
        }
        
        // Validar scroll depth
        if (isset($data['max_scroll'])) {
            $scroll = (int)$data['max_scroll'];
            if ($scroll < 0 || $scroll > $rules['max_scroll_depth']) {
                $errors[] = [
                    'code' => 'INVALID_SCROLL_DEPTH',
                    'message' => 'Invalid scroll depth',
                    'field' => 'max_scroll'
                ];
            }
        }
        
        // Validar clicks
        if (isset($data['clicks'])) {
            $clicks = (int)$data['clicks'];
            if ($clicks < 0 || $clicks > $rules['max_clicks']) {
                $errors[] = [
                    'code' => 'INVALID_CLICKS_COUNT',
                    'message' => 'Invalid clicks count',
                    'field' => 'clicks'
                ];
            }
        }
        
        // Validar duración de sesión
        if (isset($data['session_duration'])) {
            $duration = (int)$data['session_duration'];
            if ($duration < 0 || $duration > $rules['max_session_time']) {
                $errors[] = [
                    'code' => 'INVALID_SESSION_DURATION',
                    'message' => 'Invalid session duration',
                    'field' => 'session_duration'
                ];
            }
        }
        
        // Validar arrays
        if (isset($data['search_queries']) && !is_array($data['search_queries'])) {
            $errors[] = [
                'code' => 'INVALID_SEARCH_QUERIES',
                'message' => 'Search queries must be an array',
                'field' => 'search_queries'
            ];
        }
        
        if (isset($data['categories']) && !is_array($data['categories'])) {
            $errors[] = [
                'code' => 'INVALID_CATEGORIES',
                'message' => 'Categories must be an array',
                'field' => 'categories'
            ];
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validar datos de sesión
     */
    public function validateSessionData(array $data): array
    {
        $errors = [];
        
        // Validar session_id
        if (isset($data['session_id'])) {
            $sessionId = $data['session_id'];
            if (!is_string($sessionId) || empty($sessionId) || strlen($sessionId) > 128) {
                $errors[] = [
                    'code' => 'INVALID_SESSION_ID',
                    'message' => 'Invalid session ID',
                    'field' => 'session_id'
                ];
            }
        }
        
        // Validar duración
        if (isset($data['duration'])) {
            $duration = (int)$data['duration'];
            if ($duration < 0 || $duration > $this->validationRules['abandonment']['max_session_duration']) {
                $errors[] = [
                    'code' => 'INVALID_DURATION',
                    'message' => 'Invalid session duration',
                    'field' => 'duration'
                ];
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validar interacción con modal
     */
    public function validateModalInteraction(int $abandonmentId, string $modalType, string $interactionType, array $data = []): array
    {
        $errors = [];
        
        // Verificar que el abandonment existe y pertenece al usuario
        if (!$this->validateAbandonmentOwnership($abandonmentId)) {
            $errors[] = [
                'code' => 'INVALID_ABANDONMENT_OWNERSHIP',
                'message' => 'Abandonment does not belong to current user',
                'field' => 'abandonment_id'
            ];
        }
        
        // Validar tipo de modal
        if (!in_array($modalType, $this->validationRules['modal']['valid_types'])) {
            $errors[] = [
                'code' => 'INVALID_MODAL_TYPE',
                'message' => "Invalid modal type: {$modalType}",
                'field' => 'modal_type'
            ];
        }
        
        // Validar tipo de interacción
        if (!in_array($interactionType, $this->validationRules['modal']['valid_interactions'])) {
            $errors[] = [
                'code' => 'INVALID_INTERACTION_TYPE',
                'message' => "Invalid interaction type: {$interactionType}",
                'field' => 'interaction_type'
            ];
        }
        
        // Validar datos adicionales
        if (isset($data['discount'])) {
            $discount = (float)$data['discount'];
            if ($discount < $this->validationRules['modal']['min_discount_percentage'] || 
                $discount > $this->validationRules['modal']['max_discount_percentage']) {
                $errors[] = [
                    'code' => 'INVALID_DISCOUNT',
                    'message' => 'Invalid discount percentage',
                    'field' => 'discount'
                ];
            }
        }
        
        if (isset($data['conversion_value'])) {
            $value = (float)$data['conversion_value'];
            if ($value < 0) {
                $errors[] = [
                    'code' => 'INVALID_CONVERSION_VALUE',
                    'message' => 'Conversion value cannot be negative',
                    'field' => 'conversion_value'
                ];
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validar ownership de abandonment
     */
    public function validateAbandonmentOwnership(int $abandonmentId): bool
    {
        $sql = new DbQuery();
        $sql->select('id_abandoned_cart')
            ->from(_DB_PREFIX_ . 'alsernetshopping_abandoned_carts')
            ->where('id_abandoned_cart = ' . (int)$abandonmentId);
        
        if ($this->context->customer->isLogged()) {
            $sql->where('id_customer = ' . (int)$this->context->customer->id);
        } else {
            $sessionId = session_id();
            $guestId = (int)$this->context->cookie->id_guest;
            $sql->where('(session_id = "' . pSQL($sessionId) . '" OR id_guest = ' . $guestId . ')');
        }
        
        return (bool)Db::getInstance()->getValue($sql);
    }
    
    /**
     * Validar triggers
     */
    public function validateTrigger(string $triggerType, array $conditions = []): array
    {
        $errors = [];
        
        // Verificar que el trigger está habilitado
        if (!$this->cartManager->isTriggerEnabled($triggerType)) {
            $errors[] = [
                'code' => 'TRIGGER_DISABLED',
                'message' => "Trigger type '{$triggerType}' is disabled",
                'field' => 'trigger_type'
            ];
        }
        
        // Validar condiciones específicas por tipo de trigger
        switch ($triggerType) {
            case AbandonedCartManager::TRIGGER_EXIT:
                if (isset($conditions['mouse_velocity']) && $conditions['mouse_velocity'] < 0) {
                    $errors[] = [
                        'code' => 'INVALID_MOUSE_VELOCITY',
                        'message' => 'Mouse velocity cannot be negative',
                        'field' => 'mouse_velocity'
                    ];
                }
                break;
                
            case AbandonedCartManager::TRIGGER_TIME:
                if (isset($conditions['inactivity_time'])) {
                    $inactivity = (int)$conditions['inactivity_time'];
                    if ($inactivity < $this->validationRules['abandonment']['min_inactivity_time']) {
                        $errors[] = [
                            'code' => 'INSUFFICIENT_INACTIVITY_TIME',
                            'message' => 'Inactivity time is too short',
                            'field' => 'inactivity_time'
                        ];
                    }
                }
                break;
                
            case AbandonedCartManager::TRIGGER_SCROLL:
                if (isset($conditions['scroll_percentage'])) {
                    $scroll = (int)$conditions['scroll_percentage'];
                    if ($scroll < 0 || $scroll > 100) {
                        $errors[] = [
                            'code' => 'INVALID_SCROLL_PERCENTAGE',
                            'message' => 'Scroll percentage must be between 0 and 100',
                            'field' => 'scroll_percentage'
                        ];
                    }
                }
                break;
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validar actualización de abandono
     */
    private function validateAbandonmentUpdate(int $abandonmentId, string $newStage): array
    {
        $errors = [];
        
        // Obtener abandono actual
        $abandonment = $this->getAbandonmentById($abandonmentId);
        if (!$abandonment) {
            $errors[] = [
                'code' => 'ABANDONMENT_NOT_FOUND',
                'message' => 'Abandonment not found',
                'field' => 'abandonment_id'
            ];
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Verificar que no esté ya recuperado
        if ($abandonment['is_recovered']) {
            $errors[] = [
                'code' => 'ABANDONMENT_ALREADY_RECOVERED',
                'message' => 'Abandonment is already recovered',
                'field' => 'abandonment_id'
            ];
        }
        
        // Verificar límite de intentos de recuperación
        if ($abandonment['recovery_attempts'] >= $this->validationRules['abandonment']['max_recovery_attempts']) {
            $errors[] = [
                'code' => 'MAX_RECOVERY_ATTEMPTS_REACHED',
                'message' => 'Maximum recovery attempts reached',
                'field' => 'recovery_attempts'
            ];
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    // Métodos auxiliares
    
    private function getConfigValue(string $key)
    {
        $sql = new DbQuery();
        $sql->select('config_value, config_type')
            ->from(_DB_PREFIX_ . 'alsernetshopping_abandonment_config')
            ->where('config_key = "' . pSQL($key) . '"')
            ->where('is_active = 1');
        
        $result = Db::getInstance()->getRow($sql);
        if (!$result) {
            return null;
        }
        
        return $this->parseConfigValue($result['config_value'], $result['config_type']);
    }
    
    private function parseConfigValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return (bool)$value;
            case 'integer':
                return (int)$value;
            case 'decimal':
                return (float)$value;
            case 'json':
                return json_decode($value, true) ?: [];
            default:
                return $value;
        }
    }
    
    private function getExistingAbandonmentId(int $cartId): ?int
    {
        $sql = new DbQuery();
        $sql->select('id_abandoned_cart')
            ->from(_DB_PREFIX_ . 'alsernetshopping_abandoned_carts')
            ->where('id_cart = ' . (int)$cartId)
            ->where('is_recovered = 0');
        
        $result = Db::getInstance()->getValue($sql);
        return $result ? (int)$result : null;
    }
    
    private function getAbandonmentById(int $abandonmentId): ?array
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from(_DB_PREFIX_ . 'alsernetshopping_abandoned_carts')
            ->where('id_abandoned_cart = ' . (int)$abandonmentId);
        
        return Db::getInstance()->getRow($sql) ?: null;
    }
    
    /**
     * Obtener todos los errores
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Limpiar errores
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }
}