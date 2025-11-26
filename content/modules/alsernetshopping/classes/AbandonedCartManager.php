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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Servicio principal para gestión robusta de carritos abandonados
 * 
 * Funcionalidades:
 * - Tracking avanzado de comportamiento
 * - Triggers inteligentes basados en contexto
 * - Personalización de modales
 * - Analytics y métricas detalladas
 */
class AbandonedCartManager
{
    /** @var Context */
    private $context;
    
    /** @var array */
    private $config;
    
    /** @var array */
    private static $cache = [];
    
    // Constantes para tipos de abandono
    const STAGE_CART = 'cart';
    const STAGE_SHIPPING = 'shipping';
    const STAGE_PAYMENT = 'payment';
    const STAGE_REVIEW = 'review';
    
    // Constantes para tipos de trigger
    const TRIGGER_TIME = 'time_based';
    const TRIGGER_EXIT = 'exit_intent';
    const TRIGGER_SCROLL = 'scroll_based';
    const TRIGGER_BEHAVIOR = 'behavior_based';
    
    // Constantes para tipos de modal
    const MODAL_SIMPLE = 'simple_reminder';
    const MODAL_DISCOUNT = 'discount_offer';
    const MODAL_URGENCY = 'urgency_alert';
    const MODAL_RECOMMENDATIONS = 'related_products';
    const MODAL_RECOVERY = 'session_recovery';
    
    // Constantes para segmentos de usuario
    const SEGMENT_NEW = 'new_visitor';
    const SEGMENT_RETURNING = 'returning_customer';
    const SEGMENT_HIGH_VALUE = 'high_value';
    const SEGMENT_MOBILE = 'mobile_user';
    
    public function __construct(Context $context = null)
    {
        $this->context = $context ?: Context::getContext();
        $this->loadConfiguration();
    }
    
    /**
     * Cargar configuración desde base de datos
     */
    private function loadConfiguration(): void
    {
        $cacheKey = 'abandonment_config';
        
        if (isset(self::$cache[$cacheKey])) {
            $this->config = self::$cache[$cacheKey];
            return;
        }
        
        $sql = new DbQuery();
        $sql->select('config_key, config_value, config_type')
            ->from(_DB_PREFIX_ . 'alsernetshopping_abandonment_config')
            ->where('is_active = 1');
        
        $results = Db::getInstance()->executeS($sql);
        $config = [];
        
        foreach ($results as $row) {
            $value = $this->parseConfigValue($row['config_value'], $row['config_type']);
            $config[$row['config_key']] = $value;
        }
        
        $this->config = $config;
        self::$cache[$cacheKey] = $config;
    }
    
    /**
     * Verificar si el sistema está completamente activo
     */
    public function isSystemActive(): bool
    {
        return $this->config['system_active'] ?? false;
    }
    
    /**
     * Verificar si el tracking está habilitado
     */
    public function isTrackingEnabled(): bool
    {
        return $this->isSystemActive() && ($this->config['tracking_enabled'] ?? false);
    }
    
    /**
     * Verificar si un trigger específico está habilitado
     */
    public function isTriggerEnabled(string $triggerType): bool
    {
        if (!$this->isSystemActive()) {
            return false;
        }
        
        $triggerConfigs = [
            self::TRIGGER_EXIT => 'exit_intent_enabled',
            self::TRIGGER_TIME => 'time_based_triggers_enabled',
            self::TRIGGER_SCROLL => 'scroll_triggers_enabled',
            self::TRIGGER_BEHAVIOR => 'behavior_triggers_enabled'
        ];
        
        $configKey = $triggerConfigs[$triggerType] ?? null;
        return $configKey ? ($this->config[$configKey] ?? false) : false;
    }
    
    /**
     * Parsear valor de configuración según su tipo
     */
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
    
    /**
     * Registrar abandono de carrito
     */
    public function registerAbandonment(Cart $cart, string $stage = self::STAGE_CART): array
    {
        try {
            // Verificar si el sistema está activo
            if (!$this->isSystemActive()) {
                return ['status' => 'disabled', 'reason' => 'system_inactive'];
            }
            
            if (!$this->config['abandonment_enabled']) {
                return ['status' => 'disabled', 'reason' => 'abandonment_disabled'];
            }
            
            $customerData = $this->getCustomerData();
            $behaviorData = $this->getBehaviorData();
            $cartData = $this->getCartData($cart);
            
            // Verificar si ya existe registro para este carrito
            $existingId = $this->getExistingAbandonmentId($cart->id);
            
            if ($existingId) {
                // Actualizar registro existente
                $result = $this->updateAbandonment($existingId, $stage, $behaviorData);
            } else {
                // Crear nuevo registro
                $result = $this->createAbandonment($cart, $stage, $customerData, $behaviorData, $cartData);
            }
            
            return [
                'status' => 'success',
                'abandonment_id' => $result['id'],
                'triggers_available' => $this->getAvailableTriggers($result['id']),
                'user_segment' => $this->determineUserSegment($customerData, $cartData)
            ];
            
        } catch (\Exception $e) {
            error_log('AbandonedCartManager Error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Crear nuevo registro de abandono
     */
    private function createAbandonment(Cart $cart, string $stage, array $customerData, array $behaviorData, array $cartData): array
    {
        $abandonmentData = [
            'id_customer' => $customerData['id_customer'],
            'id_guest' => $customerData['id_guest'],
            'id_cart' => $cart->id,
            'session_id' => session_id(),
            'cart_value' => $cartData['total_value'],
            'products_count' => $cartData['products_count'],
            'abandonment_stage' => $stage,
            'abandonment_timestamp' => date('Y-m-d H:i:s'),
            'last_activity' => date('Y-m-d H:i:s'),
            'browser_info' => json_encode($this->getBrowserInfo()),
            'device_type' => $this->getDeviceType(),
            'utm_source' => Tools::getValue('utm_source'),
            'utm_campaign' => Tools::getValue('utm_campaign'),
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s')
        ];
        
        $result = Db::getInstance()->insert(_DB_PREFIX_ . 'alsernetshopping_abandoned_carts', $abandonmentData);
        $abandonmentId = Db::getInstance()->Insert_ID();
        
        // Crear registro de comportamiento asociado
        $this->createBehaviorRecord($abandonmentId, $behaviorData);
        
        return ['id' => $abandonmentId, 'created' => $result];
    }
    
    /**
     * Crear registro de comportamiento
     */
    private function createBehaviorRecord(int $abandonmentId, array $behaviorData): bool
    {
        $behaviorRecord = [
            'id_abandoned_cart' => $abandonmentId,
            'session_id' => session_id(),
            'start_time' => $behaviorData['session_start'] ?? date('Y-m-d H:i:s', time() - 600),
            'total_session_time' => $behaviorData['session_duration'] ?? 600,
            'pages_visited' => $behaviorData['pages_visited'] ?? 1,
            'product_views' => $behaviorData['product_views'] ?? 0,
            'category_views' => $behaviorData['category_views'] ?? 0,
            'search_queries' => json_encode($behaviorData['search_queries'] ?? []),
            'scroll_depth_max' => $behaviorData['max_scroll'] ?? 0,
            'clicks_count' => $behaviorData['clicks'] ?? 0,
            'time_on_product_pages' => $behaviorData['product_page_time'] ?? 0,
            'price_range_min' => $behaviorData['price_min'] ?? null,
            'price_range_max' => $behaviorData['price_max'] ?? null,
            'categories_interest' => json_encode($behaviorData['categories'] ?? []),
            'exit_intent_triggered' => $behaviorData['exit_intent'] ?? false,
            'mobile_interactions' => json_encode($behaviorData['mobile_data'] ?? []),
            'referrer_url' => $behaviorData['referrer'] ?? $_SERVER['HTTP_REFERER'] ?? '',
            'landing_page' => $behaviorData['landing_page'] ?? $_SERVER['REQUEST_URI'] ?? '',
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s')
        ];
        
        return Db::getInstance()->insert(_DB_PREFIX_ . 'alsernetshopping_user_behavior', $behaviorRecord);
    }
    
    /**
     * Determinar triggers disponibles para el abandono
     */
    public function getAvailableTriggers(int $abandonmentId): array
    {
        $abandonment = $this->getAbandonmentById($abandonmentId);
        if (!$abandonment) {
            return [];
        }
        
        $triggers = [];
        
        // Trigger de intención de salida
        if ($this->config['exit_intent_enabled']) {
            $triggers[] = [
                'type' => self::TRIGGER_EXIT,
                'delay' => 2, // segundos
                'conditions' => ['mouse_movement' => 'upward', 'speed' => 'fast']
            ];
        }
        
        // Triggers basados en tiempo
        if ($abandonment['recovery_attempts'] < $this->config['max_reminders_per_session']) {
            $intervals = $this->config['reminder_intervals'];
            $nextInterval = $intervals[$abandonment['recovery_attempts']] ?? end($intervals);
            
            $triggers[] = [
                'type' => self::TRIGGER_TIME,
                'delay' => $nextInterval * 60, // convertir a segundos
                'conditions' => ['inactivity' => $this->config['inactivity_threshold']]
            ];
        }
        
        // Trigger basado en scroll
        $triggers[] = [
            'type' => self::TRIGGER_SCROLL,
            'delay' => 0,
            'conditions' => ['scroll_percentage' => 80, 'min_time' => 60]
        ];
        
        return $triggers;
    }
    
    /**
     * Determinar tipo de modal más efectivo
     */
    public function determineOptimalModal(int $abandonmentId): array
    {
        $abandonment = $this->getAbandonmentById($abandonmentId);
        $behavior = $this->getBehaviorByAbandonmentId($abandonmentId);
        $userSegment = $this->determineUserSegment(
            $this->getCustomerDataById($abandonment['id_customer']),
            ['total_value' => $abandonment['cart_value'], 'products_count' => $abandonment['products_count']]
        );
        
        // Lógica de decisión inteligente
        $modalType = self::MODAL_SIMPLE;
        $discount = 0;
        $urgency = false;
        
        // Si es usuario de alto valor
        if ($userSegment === self::SEGMENT_HIGH_VALUE || $abandonment['cart_value'] > $this->config['high_value_threshold']) {
            $modalType = self::MODAL_DISCOUNT;
            $discount = $this->calculateDynamicDiscount($abandonment['recovery_attempts']);
        }
        
        // Si hay productos con stock bajo
        if ($this->hasLowStockProducts($abandonment['id_cart'])) {
            $modalType = self::MODAL_URGENCY;
            $urgency = true;
        }
        
        // Si es usuario móvil con muchas interacciones
        if ($abandonment['device_type'] === 'mobile' && $behavior['clicks_count'] > 10) {
            $modalType = self::MODAL_SIMPLE; // Simplificado para móvil
        }
        
        // Si es usuario recurrente
        if ($userSegment === self::SEGMENT_RETURNING && $abandonment['recovery_attempts'] === 0) {
            $modalType = self::MODAL_RECOVERY;
        }
        
        return [
            'type' => $modalType,
            'variant' => $this->getModalVariant($modalType, $userSegment),
            'discount' => $discount,
            'urgency' => $urgency,
            'personalization' => $this->getPersonalizationData($abandonment, $behavior, $userSegment)
        ];
    }
    
    /**
     * Calcular descuento dinámico basado en intentos
     */
    private function calculateDynamicDiscount(int $attempts): float
    {
        $discounts = [
            0 => $this->config['discount_first_reminder'],
            1 => $this->config['discount_second_reminder'],
            2 => $this->config['discount_final_reminder']
        ];
        
        $discount = $discounts[$attempts] ?? $this->config['discount_final_reminder'];
        return min($discount, $this->config['max_discount_percentage']);
    }
    
    /**
     * Registrar interacción con modal
     */
    public function registerModalInteraction(int $abandonmentId, string $modalType, string $interactionType, array $data = []): bool
    {
        $interactionData = [
            'id_abandoned_cart' => $abandonmentId,
            'modal_type' => $modalType,
            'modal_variant' => $data['variant'] ?? null,
            'trigger_type' => $data['trigger'] ?? 'unknown',
            'trigger_timestamp' => date('Y-m-d H:i:s'),
            'interaction_type' => $interactionType,
            'interaction_timestamp' => date('Y-m-d H:i:s'),
            'discount_offered' => $data['discount'] ?? null,
            'discount_used' => $data['discount_used'] ?? false,
            'time_to_interaction' => $data['time_to_interaction'] ?? null,
            'user_segment' => $data['user_segment'] ?? null,
            'conversion_value' => $data['conversion_value'] ?? null,
            'date_add' => date('Y-m-d H:i:s')
        ];
        
        // Actualizar contador de intentos de recuperación
        if ($interactionType === 'shown') {
            $this->incrementRecoveryAttempt($abandonmentId);
        }
        
        // Si hay conversión, marcar como recuperado
        if ($interactionType === 'converted') {
            $this->markAsRecovered($abandonmentId, 'modal_interaction');
        }
        
        return Db::getInstance()->insert(_DB_PREFIX_ . 'alsernetshopping_modal_interactions', $interactionData);
    }
    
    /**
     * Marcar carrito como recuperado
     */
    public function markAsRecovered(int $abandonmentId, string $method = 'unknown'): bool
    {
        $updateData = [
            'is_recovered' => 1,
            'recovery_timestamp' => date('Y-m-d H:i:s'),
            'recovery_method' => $method,
            'date_upd' => date('Y-m-d H:i:s')
        ];
        
        return Db::getInstance()->update(
            _DB_PREFIX_ . 'alsernetshopping_abandoned_carts',
            $updateData,
            'id_abandoned_cart = ' . (int)$abandonmentId
        );
    }
    
    /**
     * Obtener analytics consolidados
     */
    public function getAnalytics(string $dateFrom = null, string $dateTo = null): array
    {
        $dateFrom = $dateFrom ?: date('Y-m-d', strtotime('-30 days'));
        $dateTo = $dateTo ?: date('Y-m-d');
        
        return [
            'overview' => $this->getOverviewMetrics($dateFrom, $dateTo),
            'conversion_funnel' => $this->getConversionFunnel($dateFrom, $dateTo),
            'modal_performance' => $this->getModalPerformance($dateFrom, $dateTo),
            'user_segments' => $this->getUserSegmentAnalytics($dateFrom, $dateTo),
            'device_breakdown' => $this->getDeviceBreakdown($dateFrom, $dateTo),
            'timing_analysis' => $this->getTimingAnalysis($dateFrom, $dateTo)
        ];
    }
    
    // Métodos auxiliares privados...
    
    private function getCustomerData(): array
    {
        return [
            'id_customer' => $this->context->customer->id ?? null,
            'id_guest' => $this->context->cookie->id_guest ?? null,
            'is_logged_in' => $this->context->customer->isLogged(),
            'previous_orders' => $this->context->customer->id ? Customer::getCustomerNbOrders($this->context->customer->id) : 0
        ];
    }
    
    private function getBehaviorData(): array
    {
        // En producción, estos datos vendrían del JavaScript de tracking
        return [
            'session_start' => $_SESSION['session_start'] ?? date('Y-m-d H:i:s', time() - 600),
            'session_duration' => time() - ($_SESSION['session_start_time'] ?? time() - 600),
            'pages_visited' => $_SESSION['pages_visited'] ?? 1,
            'product_views' => $_SESSION['product_views'] ?? 0,
            'category_views' => $_SESSION['category_views'] ?? 0,
            'search_queries' => $_SESSION['search_queries'] ?? [],
            'max_scroll' => $_SESSION['max_scroll'] ?? 0,
            'clicks' => $_SESSION['click_count'] ?? 0,
            'product_page_time' => $_SESSION['product_page_time'] ?? 0,
            'exit_intent' => false,
            'mobile_data' => $this->getMobileInteractionData(),
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'landing_page' => $_SESSION['landing_page'] ?? $_SERVER['REQUEST_URI']
        ];
    }
    
    private function getCartData(Cart $cart): array
    {
        $products = $cart->getProducts();
        $totalValue = $cart->getOrderTotal();
        
        $categories = [];
        $priceRange = ['min' => null, 'max' => null];
        
        foreach ($products as $product) {
            $categories[] = $product['id_category_default'];
            $price = $product['price_wt'];
            
            if ($priceRange['min'] === null || $price < $priceRange['min']) {
                $priceRange['min'] = $price;
            }
            if ($priceRange['max'] === null || $price > $priceRange['max']) {
                $priceRange['max'] = $price;
            }
        }
        
        return [
            'total_value' => $totalValue,
            'products_count' => count($products),
            'categories' => array_unique($categories),
            'price_range' => $priceRange,
            'products' => $products
        ];
    }
    
    private function determineUserSegment(array $customerData, array $cartData): string
    {
        if (!$customerData['is_logged_in']) {
            return self::SEGMENT_NEW;
        }
        
        if ($cartData['total_value'] > $this->config['high_value_threshold']) {
            return self::SEGMENT_HIGH_VALUE;
        }
        
        if ($customerData['previous_orders'] > 0) {
            return self::SEGMENT_RETURNING;
        }
        
        if ($this->getDeviceType() === 'mobile') {
            return self::SEGMENT_MOBILE;
        }
        
        return self::SEGMENT_NEW;
    }
    
    private function getDeviceType(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            return preg_match('/iPad/', $userAgent) ? 'tablet' : 'mobile';
        }
        
        return 'desktop';
    }
    
    private function getBrowserInfo(): array
    {
        return [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            'screen_resolution' => $_SESSION['screen_resolution'] ?? null,
            'timezone' => $_SESSION['timezone'] ?? null
        ];
    }
    
    private function getMobileInteractionData(): array
    {
        return [
            'touch_interactions' => $_SESSION['touch_count'] ?? 0,
            'swipe_actions' => $_SESSION['swipe_count'] ?? 0,
            'orientation_changes' => $_SESSION['orientation_changes'] ?? 0
        ];
    }
    
    // Continúa con más métodos auxiliares...
    
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
    
    private function updateAbandonment(int $abandonmentId, string $stage, array $behaviorData): array
    {
        $updateData = [
            'abandonment_stage' => $stage,
            'last_activity' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s')
        ];
        
        $result = Db::getInstance()->update(
            _DB_PREFIX_ . 'alsernetshopping_abandoned_carts',
            $updateData,
            'id_abandoned_cart = ' . (int)$abandonmentId
        );
        
        return ['id' => $abandonmentId, 'updated' => $result];
    }
    
    private function incrementRecoveryAttempt(int $abandonmentId): bool
    {
        return Db::getInstance()->execute('
            UPDATE ' . _DB_PREFIX_ . 'alsernetshopping_abandoned_carts 
            SET recovery_attempts = recovery_attempts + 1,
                date_upd = NOW()
            WHERE id_abandoned_cart = ' . (int)$abandonmentId
        );
    }
    
    private function getAbandonmentById(int $abandonmentId): ?array
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from(_DB_PREFIX_ . 'alsernetshopping_abandoned_carts')
            ->where('id_abandoned_cart = ' . (int)$abandonmentId);
        
        return Db::getInstance()->getRow($sql) ?: null;
    }
    
    private function getBehaviorByAbandonmentId(int $abandonmentId): ?array
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from(_DB_PREFIX_ . 'alsernetshopping_user_behavior')
            ->where('id_abandoned_cart = ' . (int)$abandonmentId);
        
        return Db::getInstance()->getRow($sql) ?: null;
    }
    
    private function getCustomerDataById(?int $customerId): array
    {
        if (!$customerId) {
            return ['is_logged_in' => false, 'previous_orders' => 0];
        }
        
        return [
            'is_logged_in' => true,
            'previous_orders' => Customer::getCustomerNbOrders($customerId)
        ];
    }
    
    private function hasLowStockProducts(int $cartId): bool
    {
        // Implementar lógica para verificar stock bajo
        return false; // Placeholder
    }
    
    private function getModalVariant(string $modalType, string $userSegment): string
    {
        $variants = [
            self::MODAL_SIMPLE => ['basic', 'friendly', 'urgent'],
            self::MODAL_DISCOUNT => ['percentage', 'fixed_amount', 'progressive'],
            self::MODAL_URGENCY => ['stock_low', 'time_limited', 'popularity'],
            self::MODAL_RECOMMENDATIONS => ['similar', 'complementary', 'trending'],
            self::MODAL_RECOVERY => ['welcome_back', 'price_drop', 'saved_items']
        ];
        
        $available = $variants[$modalType] ?? ['basic'];
        return $available[0]; // Por ahora retorna el primero, implementar lógica más sofisticada
    }
    
    private function getPersonalizationData(array $abandonment, array $behavior, string $userSegment): array
    {
        return [
            'user_segment' => $userSegment,
            'cart_value' => $abandonment['cart_value'],
            'products_count' => $abandonment['products_count'],
            'session_time' => $behavior['total_session_time'] ?? 0,
            'device_type' => $abandonment['device_type'],
            'previous_attempts' => $abandonment['recovery_attempts']
        ];
    }
    
    // Métodos de analytics (implementar según necesidades específicas)
    private function getOverviewMetrics(string $dateFrom, string $dateTo): array { return []; }
    private function getConversionFunnel(string $dateFrom, string $dateTo): array { return []; }
    private function getModalPerformance(string $dateFrom, string $dateTo): array { return []; }
    private function getUserSegmentAnalytics(string $dateFrom, string $dateTo): array { return []; }
    private function getDeviceBreakdown(string $dateFrom, string $dateTo): array { return []; }
    private function getTimingAnalysis(string $dateFrom, string $dateTo): array { return []; }
}