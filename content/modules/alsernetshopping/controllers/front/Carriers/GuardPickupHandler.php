<?php

namespace AlsernetShopping\Carriers;

use Db;
use Configuration;
use Context;
use Address;
use State;
use Country;

if (!defined('_PS_VERSION_')) {
    exit;
}

class GuardPickupHandler extends AbstractCarrierHandler
{
    private $carrierId = 39;

    public function __construct(Context $context = null)
    {
        parent::__construct($context);
        $this->context = $this->context ?: \Context::getContext(); // fallback

        // error_log("=== GuardPickupHandler: Constructor called for carrier 78 ===");
        // error_log("GuardPickupHandler: Context cart ID: " . ($context && $context->cart ? $context->cart->id : 'none'));

        // Configurar automáticamente el Store Locator si no existe
        $this->ensureStoreLocatorConfig();
    }

    public function getId(): int
    {
        return $this->carrierId;
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function getAnalyticName(): string
    {
        return 'Recogida en Tienda';
    }

    public function getTemplatePath(): string
    {

        return 'module:alsernetshopping/views/templates/front/checkout/partials/delivery/carriers/39_guard_pickup/interface.tpl';
    }

    public function validateAvailability(Context $context): array
    {
        // error_log("=== GuardPickupHandler: validateAvailability() called ===");
        $cart = $context->cart;

        if (!$cart || !$cart->id) {
            error_log("GuardPickupHandler: Invalid cart - no cart or cart ID");
            return [
                'valid' => false,
                'message' => 'Invalid cart'
            ];
        }

        // error_log("GuardPickupHandler: Validating for cart ID: " . $cart->id);

        // Verificar si hay tiendas configuradas
        $hasStores = $this->hasAvailableStores($context);

        // error_log("GuardPickupHandler: hasAvailableStores result: " . ($hasStores ? 'TRUE' : 'FALSE'));

        return [
            'valid' => $hasStores,
            'message' => $hasStores ? 'Store pickup available' : 'No stores available for pickup'
        ];
    }

    public function getExtraContent(Address $address, Context $context): string
    {
        // error_log("=== GuardPickupHandler: getExtraContent called ===");
        // error_log("GuardPickupHandler: getExtraContent called for address ID: " . $address->id);

        try {
            $id_lang = (int)$context->language->id;
            $state_name = $address->id_state ? State::getNameById($address->id_state) : '';
            $country_name = $address->id_country ? Country::getNameById($id_lang, $address->id_country) : '';

            // error_log("GuardPickupHandler: State: $state_name, Country: $country_name");
        } catch (\Exception $e) {
            error_log("GuardPickupHandler: ERROR in initial data extraction: " . $e->getMessage());
            return '<div class="alert alert-danger">Error extracting address data: ' . $e->getMessage() . '</div>';
        }

        // Usar exactamente el código original del Store Locator
        try {
            $storeLocatorConfig = \Tools::jsonDecode(\Configuration::get('KB_STORE_LOCATOR_GENERAL_SETTING'), true);
            $pickup_settings = \Tools::jsonDecode(\Configuration::get('KB_PICKUP_TIME_SETTINGS'), true);

            if (empty($storeLocatorConfig)) {
                $storeLocatorConfig = $this->getDefaultStoreConfig();
            }
            if (empty($pickup_settings)) {
                $pickup_settings = $this->getDefaultPickupSettings();
            }

            // error_log("GuardPickupHandler: Configuration loaded successfully");
        } catch (\Exception $e) {
            error_log("GuardPickupHandler: ERROR loading configuration: " . $e->getMessage());
            return '<div class="alert alert-danger">Error loading configuration: ' . $e->getMessage() . '</div>';
        }

        if (empty($storeLocatorConfig)) {
            // error_log("GuardPickupHandler: Store locator config is empty");
            return '<div class="alert alert-warning">Store locator configuration not found</div>';
        }

        // Debug: mostrar configuración actual
        // error_log("GuardPickupHandler: Store locator config: " . json_encode($storeLocatorConfig));

        // Verificar y corregir la configuración si es necesaria
        if (empty($storeLocatorConfig['enable']) || empty($storeLocatorConfig['enable_store_locator'])) {
            // error_log("GuardPickupHandler: Store locator not properly configured, fixing...");

            // Forzar habilitación
            $storeLocatorConfig['enable'] = 1;
            $storeLocatorConfig['enable_store_locator'] = 1;
            $storeLocatorConfig['homepage'] = 1;

            // Si no hay tienda por defecto, usar la primera disponible
            if (empty($storeLocatorConfig['default_store'])) {
                $firstStore = \Db::getInstance()->getRow('SELECT id_store FROM `' . _DB_PREFIX_ . 'store` WHERE active = 1 ORDER BY id_store LIMIT 1');
                $storeLocatorConfig['default_store'] = $firstStore ? $firstStore['id_store'] : 1;
            }

            \Configuration::updateValue('KB_STORE_LOCATOR_GENERAL_SETTING', json_encode($storeLocatorConfig));
            // error_log("GuardPickupHandler: Fixed store locator configuration");
        }

        if (!($storeLocatorConfig['enable'] && $storeLocatorConfig['enable_store_locator'])) {
            // error_log("GuardPickupHandler: Store locator still not enabled after fix attempt");
            return '<div class="alert alert-warning">Store locator is not enabled</div>';
        }

        // Código exacto del original
        $av_store_detail = array();
        $default_store = $storeLocatorConfig['default_store'];
        $store = new \Store($default_store, $context->language->id);
        $latitude = $store->latitude;
        $longitude = $store->longitude;
        $selected_store = new \Store($default_store);
        $selected_store = (array) $selected_store;
        $selected_store['country'] = \Country::getNameById($context->language->id, $selected_store['id_country']);
        $default_latitude = $store->latitude;
        $default_longitude = $store->longitude;
        $current_selected_shipping = null;


        $current_selected_shipping = current($context->cart->getDeliveryOption(null, false, false));
        if (isset($storeLocatorConfig['enable_all_store']) && $storeLocatorConfig['enable_all_store'] == 1) {
            if (version_compare(_PS_VERSION_, '1.7.4.0', '>=')) {
                $available_store = \Db::getInstance()->executeS(
                    'SELECT s.id_store,ss.name FROM `' . _DB_PREFIX_ . 'store` s '
                    . 'INNER JOIN ' . _DB_PREFIX_ . 'store_lang ss '
                    . 'on (s.id_store=ss.id_store AND ss.id_lang='
                    . (int) $context->language->id . ') '
                    . 'WHERE s.active=1 ORDER BY s.`id_store`'
                );
            } else {
                $available_store = \Store::getStores($context->language->id);
            }
            $enabled_store = array();
            foreach ($available_store as $key => $value) {
                $enabled_store[] = $value['id_store'];
            }
        } else {
            $enabled_store = $storeLocatorConfig['avilable_store'];
        }

        // Usar la clase correcta del módulo original
        if (class_exists('Kbgcstorelocatorpickup')) {
            $available_stores = \Kbgcstorelocatorpickup::kbGcAvailableStores($enabled_store);
        } else {
            // error_log("GuardPickupHandler: Kbgcstorelocatorpickup class not found, using fallback");
            $available_stores = $this->getAvailableStores($address, $storeLocatorConfig);
        }

        // Encontrar la tienda seleccionada en las tiendas disponibles para usarla como template store
        $template_store = null;
        foreach ($available_stores as $key => $store_item) {
            if (class_exists('Kbgcstorelocatorpickup')) {
                $workingHours = \Kbgcstorelocatorpickup::renderKbGcStoreWorkingHours($store_item);
            } else {
                $workingHours = $store_item['opening_hours'] ?? [];
            }
            $available_stores[$key]['hours'] = $workingHours;

            // Para av_store_detail, convertir las horas a string para evitar error htmlspecialchars
            $hoursString = '';
            if (is_array($workingHours)) {
                $hoursArray = [];
                foreach ($workingHours as $hourInfo) {
                    if (is_array($hourInfo) && isset($hourInfo['day']) && isset($hourInfo['hours'])) {
                        $hoursArray[] = $hourInfo['day'] . ': ' . $hourInfo['hours'];
                    }
                }
                $hoursString = implode(' | ', $hoursArray);
            } else {
                $hoursString = (string)$workingHours;
            }

            $av_store_detail[$store_item['id_store']] = $hoursString;

            // Sanitizar datos de la tienda
            $available_stores[$key] = $this->sanitizeStoreData($available_stores[$key]);

            // Si es la tienda por defecto, usarla como template store
            if ($store_item['id_store'] == $default_store) {
                $template_store = $available_stores[$key];
            }
        }

        // Si no encontramos la tienda por defecto en disponibles, usar la primera disponible
        if (!$template_store && !empty($available_stores)) {
            $template_store = reset($available_stores);
        }


        // Debug: Log el template store para ver qué contiene
        // error_log("GuardPickupHandler: Template store data BEFORE sanitization: " . json_encode($template_store));

        // Asegurar que todos los campos son strings y no arrays - aplicar sanitización más agresiva
        $template_store = $this->deepSanitizeForTemplate($template_store);

        // Debug: Log el template store sanitizado
        // error_log("GuardPickupHandler: Template store AFTER sanitization: " . json_encode($template_store));

        $time = time();
        $marker = $this->getModuleDirUrl() . 'kbgcstorelocatorpickup/views/img/marker.png?time=' . $time;
        $exist_file = _PS_MODULE_DIR_ . 'kbgcstorelocatorpickup/views/img/user_marker.*';
        $match1 = glob($exist_file);
        if (count($match1) > 0) {
            $ban = explode('/', $match1[0]);
            $ban = end($ban);
            $ban = trim($ban);
            if (file_exists($match1[0])) {
                $marker = $this->getModuleDirUrl() . 'kbgcstorelocatorpickup/views/img/' . $ban . '?time=' . $time;
            }
        }

        // Lógica para kb_all_stores (mapa)
        $marker_json_data = array();
        if ($storeLocatorConfig['show_stores'] == 0) {
            $kb_all_stores = '';
        } else {
            $radius_val = 0;
            $filter_pickup = 1;
            $available_stores1 = array();

            if (isset($storeLocatorConfig['enable_all_store']) && $storeLocatorConfig['enable_all_store'] == 1) {
                if (version_compare(_PS_VERSION_, '1.7.4.0', '>=')) {
                    $available_store = \Db::getInstance()->executeS(
                        'SELECT s.id_store,ss.name FROM `' . _DB_PREFIX_ . 'store` s '
                        . 'INNER JOIN ' . _DB_PREFIX_ . 'store_lang ss '
                        . 'on (s.id_store=ss.id_store AND ss.id_lang='
                        . (int) $context->language->id . ') '
                        . 'WHERE s.active=1 ORDER BY s.`id_store`'
                    );
                } else {
                    $available_store = \Store::getStores($context->language->id);
                }
                $enabled_store = array();
                foreach ($available_store as $key => $value) {
                    $enabled_store[] = $value['id_store'];
                }
            } else {
                $enabled_store = $storeLocatorConfig['avilable_store'];
            }

            if (class_exists('Kbgcstorelocatorpickup')) {
                $available_stores1 = \Kbgcstorelocatorpickup::kbGcAvailableStores($enabled_store);
            } else {
                $available_stores1 = $available_stores;
            }

            $distance_unit = "M";
            if ($storeLocatorConfig['distance_unit'] == 'km') {
                $distance_unit = "K";
            }

            $near_by_store = array();
            $default_store = $storeLocatorConfig['default_store'];

            foreach ($available_stores1 as $key => $store1) {
                $near_by_distance = \Tools::ps_round($this->calculateDistance($latitude, $longitude, $store1['latitude'], $store1['longitude'], $distance_unit), 2);
                if ($near_by_distance <= $radius_val || $radius_val == 0) {
                    $json_data = array();
                    $available_stores1[$key]['store_distance'] = $near_by_distance;

                    if (class_exists('Kbgcstorelocatorpickup')) {
                        $workingHours = \Kbgcstorelocatorpickup::renderKbGcStoreWorkingHours($store1);
                    } else {
                        $workingHours = $store1['opening_hours'] ?? [];
                    }
                    $available_stores1[$key]['hours'] = $workingHours;
                    $near_by_store[] = $available_stores1[$key];

                    // Para el JSON del mapa necesitamos el template, por ahora usamos HTML básico
                    $popup_html = '<div class="velo-popup" data-store-id="' . (int)$store1['id_store'] . '">' .
                        '<div class="title-address">' . htmlspecialchars($store1['name']) . '</div>' .
                        '<div class="detail-address">' . htmlspecialchars($store1['address1']) . '</div>' .
                        // Enlace de seleccionar con data-store-id para “puentear” al listado
                        '<div class="actions"><a href="#" class="velo-store-select-link" data-store-id="' . (int)$store1['id_store'] . '">Seleccionar esta tienda</a></div>' .
                        '</div>';

                    $json_data[] = $popup_html;
                    $json_data[] = $store1['latitude'];
                    $json_data[] = $store1['longitude'];
                    $marker_json_data[] = $json_data;

                    // Para av_store_detail, convertir las horas a string para evitar error htmlspecialchars
                    $hoursString = '';
                    if (is_array($workingHours)) {
                        $hoursArray = [];
                        foreach ($workingHours as $hourInfo) {
                            if (is_array($hourInfo) && isset($hourInfo['day']) && isset($hourInfo['hours'])) {
                                $hoursArray[] = $hourInfo['day'] . ': ' . $hourInfo['hours'];
                            }
                        }
                        $hoursString = implode(' | ', $hoursArray);
                    } else {
                        $hoursString = (string)$workingHours;
                    }
                    $av_store_detail[$store1['id_store']] = $hoursString;
                }
            }
            $kb_all_stores = json_encode($marker_json_data);
        }

        // Configuraciones de fecha para pickup
        if (!empty($pickup_settings)) {
            $date_format = str_replace(
                ['%y', '%m', '%d', '%t24', '%t12'],
                ['yyyy', 'mm', 'dd', $pickup_settings['time_slot'] ? 'hh:00' : '', $pickup_settings['time_slot'] ? 'HH:00 P' : ''],
                $pickup_settings['format'] ?? '%d/%m/%Y'
            );

            $hour_gap = time() + ($pickup_settings['days_gap'] * 86400) + ($pickup_settings['hours_gap'] * 3600);
            $hour_gap_date = date('j', $hour_gap);
            $hour_gap_hour = date('H', $hour_gap);
            $hour_gap_month = date('n', $hour_gap) - 1;
        } else {
            $date_format = 'dd/mm/yyyy';
            $hour_gap = time() + 86400; // 1 día por defecto
            $hour_gap_date = date('j', $hour_gap);
            $hour_gap_hour = date('H', $hour_gap);
            $hour_gap_month = date('n', $hour_gap) - 1;
        }

        try {
            // error_log("GuardPickupHandler: Starting template data sanitization");

            // Sanitizar completamente todas las variables que van al template
            // Asegurar que selected_store está sanitizado
            if (is_array($selected_store)) {
                $selected_store = $this->sanitizeStoreData($selected_store);
            }

            // Sanitizar available_stores completamente
            $sanitizedAvailableStores = [];
            if (is_array($available_stores)) {
                foreach ($available_stores as $store) {
                    $sanitizedAvailableStores[] = $this->sanitizeStoreData($store);
                }
            }

            // error_log("GuardPickupHandler: Template data sanitization completed");
        } catch (\Exception $e) {
            error_log("GuardPickupHandler: ERROR during template data sanitization: " . $e->getMessage());
            return '<div class="alert alert-danger">Error sanitizing template data: ' . $e->getMessage() . '</div>';
        }

        try {
            // error_log("GuardPickupHandler: Creating template data array");

            // Get cart guard pickup selection if exists
            $cart_guard_pickup = $this->getCartGuardPickupData($context);

            // error_log("GuardPickupHandler: cart_guard_pickup data: " . json_encode($cart_guard_pickup));

            // Configurar datos del template con sanitización completa
            $templateData = [
                'current_selected_shipping' => $current_selected_shipping,
                'carrier' => new \Carrier($this->carrierId, $context->language->id),
                'carrier_id' => (int)$this->carrierId,
                'kbgcs_store_url' => (string)$context->link->getModuleLink('kbgcstorelocatorpickup', 'stores'),
                'map_api' => (string)($storeLocatorConfig['api_key'] ?? ''),
                'is_enabled_website_link' => (int)($storeLocatorConfig['website_link'] ?? 0),
                'is_enabled_store_image' => (int)($storeLocatorConfig['store_image'] ?? 0),
                'is_enabled_show_stores' => (int)($storeLocatorConfig['show_stores'] ?? 1),
                'is_enabled_direction_link' => (int)($storeLocatorConfig['get_direction_link'] ?? 1),
                'index_page_link' => (string)$context->link->getPageLink('index'),
                'selected_store' => $selected_store,
                'lang_iso' => (string)$context->language->iso_code,
                'zoom_level' => (int)($storeLocatorConfig['zoom_level'] ?? 10),
                'default_latitude' => (string)$default_latitude,
                'default_longitude' => (string)$default_longitude,
                'display_phone' => (int)($storeLocatorConfig['display_phone'] ?? 1),
                'default_store' => (string)$default_store,
                'distance_unit' => (string)($storeLocatorConfig['distance_unit'] ?? 'km'),
                'distance_icon' => (string)($this->getModuleDirUrl() . 'kbgcstorelocatorpickup/views/img/front/distance.png'),
                'phone_icon' => (string)($this->getModuleDirUrl() . 'kbgcstorelocatorpickup/views/img/front/call.png'),
                'web_icon' => (string)($this->getModuleDirUrl() . 'kbgcstorelocatorpickup/views/img/front/web.png'),
                'clock_icon' => (string)($this->getModuleDirUrl() . 'kbgcstorelocatorpickup/views/img/front/clock.png'),
                'store_icon' => (string)($this->getModuleDirUrl() . 'kbgcstorelocatorpickup/views/img/front/store.png'),
                'up_arrow' => (string)($this->getModuleDirUrl() . 'kbgcstorelocatorpickup/views/img/front/up_arrow.png'),
                'down_arrow' => (string)($this->getModuleDirUrl() . 'kbgcstorelocatorpickup/views/img/front/down_arrow.png'),
                'locator_icon' => (string)$marker,
                'current_location_icon' => (string)($this->getModuleDirUrl() . 'kbgcstorelocatorpickup/views/img/front/current_location.png'),
                'available_stores' => $sanitizedAvailableStores, // Todas las tiendas ya sanitizadas
                'av_store_detail' => (string)json_encode($av_store_detail), // Ya son strings
                'default_store_detail' => (string)json_encode($av_store_detail[$default_store] ?? ''),
                'id_lang' => (int)$context->language->id,
                'spinner' => (string)($this->getModuleDirUrl() . 'kbgcstorelocatorpickup/views/img/front/spinner.gif'),
                'search_as_move' => (int)(isset($storeLocatorConfig['search_as_move']) && $storeLocatorConfig['search_as_move'] == 1 ? 1 : 0),
                'kb_all_stores' => (string)$kb_all_stores, // Ya es JSON string
                'is_all_stores_disabled' => (int)(count($available_stores) === 0 ? 1 : 0),
                'store' => $template_store, // Ya sanitizado
                'is_enabled_date_selcetion' => (int)(!empty($pickup_settings['enable_date_selection']) ? 1 : 0),
                'days_gap' => (string)(!empty($pickup_settings) ? date('Y-m-d', strtotime('+' . $pickup_settings['days_gap'] . ' days')) : date('Y-m-d', strtotime('+1 day'))),
                'maximum_days' => (string)(!empty($pickup_settings) ? date('Y-m-d', strtotime('+' . $pickup_settings['maximum_days'] . ' days')) : date('Y-m-d', strtotime('+7 days'))),
                'time_slot' => (int)(!empty($pickup_settings) ? $pickup_settings['time_slot'] : 1),
                'hours_gap' => (int)$hour_gap,
                'hour_gap_date' => (int)$hour_gap_date,
                'hour_gap_hour' => (int)$hour_gap_hour,
                'hour_gap_month' => (int)$hour_gap_month,
                'format' => (string)$date_format,
                'pickup_carrier_id' => (int)$this->carrierId,
                'cart_guard_pickup' => $cart_guard_pickup
            ];

            // error_log("GuardPickupHandler: Template data array created successfully");
        } catch (\Exception $e) {
            error_log("GuardPickupHandler: ERROR creating template data array: " . $e->getMessage());
            return '<div class="alert alert-danger">Error creating template data: ' . $e->getMessage() . '</div>';
        }

        try {
            // error_log("GuardPickupHandler: Applying deep sanitization");
            // Aplicar sanitización profunda a todo el array de template data
            $finalTemplateData = $this->deepSanitizeForTemplate($templateData);
            // error_log("GuardPickupHandler: Deep sanitization completed");
        } catch (\Exception $e) {
            error_log("GuardPickupHandler: ERROR during deep sanitization: " . $e->getMessage());
            return '<div class="alert alert-danger">Error during deep sanitization: ' . $e->getMessage() . '</div>';
        }

        // Logging adicional para debug
        // error_log("GuardPickupHandler: Template data keys: " . implode(', ', array_keys($finalTemplateData)));
        // error_log("GuardPickupHandler: Available stores count: " . count($finalTemplateData['available_stores']));
        // error_log("GuardPickupHandler: av_store_detail type: " . gettype($finalTemplateData['av_store_detail']));

        // Log específico para verificar que no hay arrays problemáticos
        // foreach ($finalTemplateData as $key => $value) {
        //     if (is_array($value) && $key !== 'available_stores' && $key !== 'store' && $key !== 'selected_store' && $key !== 'carrier' && $key !== 'cart_guard_pickup') {
        //         error_log("GuardPickupHandler: WARNING - Array found in template data key: $key");
        //     }
        // }

        // error_log("GuardPickupHandler: Template path: " . $this->getTemplatePath());
        // error_log("GuardPickupHandler: Final template data structure verification:");

        // Verificar estructura final antes del renderizado
        // foreach ($finalTemplateData as $key => $value) {
        //     if (is_array($value)) {
        //         if ($key === 'store') {
        //             error_log("GuardPickupHandler: 'store' data structure: " . json_encode($value));
        //             if (isset($value['hours'])) {
        //                 error_log("GuardPickupHandler: 'store.hours' type: " . gettype($value['hours']));
        //             }
        //         } elseif ($key === 'available_stores') {
        //             error_log("GuardPickupHandler: 'available_stores' count: " . count($value));
        //         } elseif ($key === 'cart_guard_pickup') {
        //             error_log("GuardPickupHandler: 'cart_guard_pickup' data: " . json_encode($value));
        //         } else {
        //             error_log("GuardPickupHandler: Array key '$key' with type: " . gettype($value));
        //         }
        //     }
        // }

        try {
            $result = $this->renderTemplate($this->getTemplatePath(), $finalTemplateData);
            // error_log("GuardPickupHandler: Template rendered successfully, length: " . strlen($result));
            return $result;
        } catch (\Exception $e) {
            error_log("GuardPickupHandler: Template render error: " . $e->getMessage());
            error_log("GuardPickupHandler: Error file: " . $e->getFile() . ", Line: " . $e->getLine());
            error_log("GuardPickupHandler: Stack trace: " . $e->getTraceAsString());
            return '<div class="alert alert-danger">Error loading Store pickup interface: ' . $e->getMessage() . '</div>';
        }
    }

    public function processForm($requestData, Context $context): array
    {
        // error_log("=== GuardPickupHandler: processForm called for carrier 78 ===");
        // error_log("GuardPickupHandler: Request data: " . json_encode($requestData));

        // Procesar selección de tienda si es necesario
        $address = isset($requestData['delivery_address']) ? $requestData['delivery_address'] : new \Address($requestData['id_address']);

        // error_log("GuardPickupHandler: Getting extra content for address ID: " . $requestData['id_address']);

        try {
            $html = $this->getExtraContent($address, $context);
            // error_log("GuardPickupHandler: Extra content generated successfully");

            $result = [
                'status' => 'success',
                'html' => $html,
                'id_carrier' => $this->carrierId,
                'id_address' => $requestData['id_address'],
                'carrier_id' => $this->carrierId,
                'address_id' => $requestData['id_address'],
                'message' => 'Store pickup interface loaded successfully'
            ];

            // error_log("GuardPickupHandler: processForm completed successfully");
            return $result;

        } catch (\Exception $e) {
            error_log("GuardPickupHandler: processForm error: " . $e->getMessage());
            return [
                'status' => 'error',
                'html' => '<div class="alert alert-danger">Error processing Store pickup form</div>',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }


    private function hasAvailableStores(Context $context): bool
    {
        // Lógica para verificar si hay tiendas disponibles
        // Por ahora retornamos true, pero se puede implementar lógica más específica
        return true;
    }

    public function getRequiredAssets(): array
    {
        return [
            [
                'type' => 'css',
                'path' => 'modules/alsernetshopping/views/css/front/checkout/carriers/store-pickup.css',
                'priority' => 100
            ],
            [
                'type' => 'js',
                'path' => 'modules/alsernetshopping/views/js/front/checkout/steps/delivery/carriers/store-pickup.js',
                'priority' => 100
            ]
        ];
    }

    public function cleanup(): void
    {
        // No hay recursos específicos que limpiar
        parent::cleanup();
    }


    public function processSelection(array $requestData, \Context $context): array
    {


        $payload = $requestData['payload'];
        $preferred_store  = $payload['preferred_store'] ?? '';
        $preferred_date  = $payload['preferred_date'] ?? '';

        if ($preferred_store === '') {
            return \ResponseHelper::warning('Debes seleccionar una punto.');
        }

        return [
            'status'     => 'success',
            'message'    => 'Punto de store seleccionada.',
            'id_carrier' => $this->getId(),
            'selection'  => [
                'type'        => 'pickup',
                'id_carrier'  => $this->getId(),
                'preferred_store'   => $preferred_store,
                'preferred_date'   => $preferred_date,
            ],
        ];
    }

    public function persistSelection(\Context $context, array $requestData, array $handlerResult): bool
    {
        $payload = $requestData['payload'];
        $preferred_store  = $payload['preferred_store'] ?? '';
        $preferred_date  = $payload['preferred_date'] ?? '';

        if (!empty($preferred_date) && !empty($preferred_store)) {
            $id_cart = $context->cart->id;
            $id_customer = $context->customer->id;
            $id_shop = $context->shop->id;
            $id_address = Db::getInstance()->getValue('SELECT id_address FROM ' . _DB_PREFIX_ . 'kb_gc_pickup_store_address_mapping where id_store=' . (int) $preferred_store);
            $exist_data = Db::getInstance()->getValue('SELECT id_pickup FROM ' . _DB_PREFIX_ . 'kb_gc_pickup_at_store_time WHERE id_cart=' . (int) $id_cart . ' AND id_customer=' . (int) $id_customer . ' AND id_shop=' . (int) $id_shop);
            //                $query = 0;
            if (empty($exist_data)) {
                $query = Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'kb_gc_pickup_at_store_time set id_shop=' . (int) $id_shop . ',id_cart=' . (int) $id_cart . ',id_customer=' . (int) $id_customer . ',id_store=' . (int) $preferred_store . ',id_address_delivery=' . (int) $id_address . ',preferred_date="' . pSQL($preferred_date) . '",date_add=now(),date_upd=now()');
            } else {
                $query = Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'kb_gc_pickup_at_store_time  set id_shop=' . (int) $id_shop . ',id_store=' . (int) $preferred_store . ',id_address_delivery=' . (int) $id_address . ',preferred_date="' . pSQL($preferred_date) . '",date_upd=now() WHERE id_cart=' . (int) $id_cart . ' AND id_customer=' . (int) $id_customer . ' AND id_shop=' . (int) $id_shop);
            }
        }

        if (empty($preferred_date) && !empty($preferred_store)) {
            $id_cart = $context->cart->id;
            $id_customer = $context->customer->id;
            $id_shop = $context->shop->id;
            $id_address = Db::getInstance()->getValue('SELECT id_address FROM ' . _DB_PREFIX_ . 'kb_gc_pickup_store_address_mapping where id_store=' . (int) $preferred_store);
            $exist_data = Db::getInstance()->getValue('SELECT id_pickup FROM ' . _DB_PREFIX_ . 'kb_gc_pickup_at_store_time WHERE id_cart=' . (int) $id_cart . ' AND id_customer=' . (int) $id_customer . ' AND id_shop=' . (int) $id_shop);
            //                $query = 0;
            if (empty($exist_data)) {
                $query = Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'kb_gc_pickup_at_store_time set id_shop=' . (int) $id_shop . ',id_cart=' . (int) $id_cart . ',id_customer=' . (int) $id_customer . ',id_store=' . (int) $preferred_store . ',id_address_delivery=' . (int) $id_address . ',preferred_date="' . pSQL($preferred_date) . '",date_add=now(),date_upd=now()');
            } else {
                $query = Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'kb_gc_pickup_at_store_time  set id_shop=' . (int) $id_shop . ',id_store=' . (int) $preferred_store . ',id_address_delivery=' . (int) $id_address . ',preferred_date="' . pSQL($preferred_date) . '",date_upd=now() WHERE id_cart=' . (int) $id_cart . ' AND id_customer=' . (int) $id_customer . ' AND id_shop=' . (int) $id_shop);
            }
        }

        return $query;

    }


    private function getRegularStorePickupData($id_address_delivery, $context)
    {
        // error_log("GuardPickupHandler: getRegularStorePickupData called for address: $id_address_delivery");

        $storeLocatorConfig = \Tools::jsonDecode(\Configuration::get('KB_STORE_LOCATOR_GENERAL_SETTING'), true);
        $cart = $context->cart;
        $iso = \Tools::getValue('iso') ?: $context->language->iso_code;

        // error_log("GuardPickupHandler: Raw config: " . print_r($storeLocatorConfig, true));

        // Check if store locator is enabled using the real config structure
        $isEnabled = ($storeLocatorConfig &&
                isset($storeLocatorConfig['enable']) && $storeLocatorConfig['enable'] &&
                isset($storeLocatorConfig['enable_store_locator']) && $storeLocatorConfig['enable_store_locator']) ||
            ($storeLocatorConfig && isset($storeLocatorConfig['enable_pickup']) && $storeLocatorConfig['enable_pickup']);

        if (!$isEnabled) {
            // error_log("GuardPickupHandler: Store locator not properly enabled - using existing config with forced enablement");
            // Use the existing config but ensure it's marked as enabled
            if ($storeLocatorConfig) {
                $storeLocatorConfig['enable_store_locator'] = 1;
                $storeLocatorConfig['enable_pickup'] = 1;
            } else {
                $storeLocatorConfig = [
                    'enable' => 1,
                    'enable_store_locator' => 1,
                    'enable_pickup' => 1,
                    'pickup_days_ahead' => 7,
                    'max_distance' => 50
                ];
            }
            // error_log("GuardPickupHandler: Forced enablement config: " . print_r($storeLocatorConfig, true));
        }

        $delivery_address = new \Address($id_address_delivery);
        $country = new \Country($delivery_address->id_country);
        $state = new \State($delivery_address->id_state);

        // Get available pickup locations
        $stores = $this->getAvailableStores($delivery_address, $storeLocatorConfig);

        // Get pickup dates if configured
        $pickupDates = $this->getAvailablePickupDates($storeLocatorConfig, 'regular');

        // error_log("GuardPickupHandler: Found " . count($stores) . " stores and " . count($pickupDates) . " pickup dates");

        return [
            'status' => 'success',
            'message' => 'Pickup locations loaded successfully',
            'data' => [
                'stores' => $stores,
                'pickup_dates' => $pickupDates,
                'delivery_address' => [
                    'country' => $country->name,
                    'state' => $state->name,
                    'city' => $delivery_address->city,
                    'postcode' => $delivery_address->postcode
                ],
                'config' => [
                    'map_enabled' => isset($storeLocatorConfig['show_map']) ? $storeLocatorConfig['show_map'] : true,
                    'max_distance' => isset($storeLocatorConfig['max_distance']) ? $storeLocatorConfig['max_distance'] : 50
                ]
            ]
        ];
    }

    private function getAvailableStores($delivery_address, $config)
    {
        // error_log("GuardPickupHandler: getAvailableStores called");

        // Por ahora devolvemos tiendas de ejemplo hasta que se configure el módulo real
        // error_log("GuardPickupHandler: Returning mock stores for testing");

        $stores = [
            [
                'id_store' => '1',
                'name' => 'Tienda Principal Madrid',
                'address1' => 'Calle Gran Vía 123',
                'address2' => '',
                'city' => 'Madrid',
                'postcode' => '28013',
                'phone' => '912345678',
                'email' => 'madrid@tienda.com',
                'latitude' => '40.420000',
                'longitude' => '-3.700000',
                'state' => 'Madrid',
                'country' => 'España',
                'distance' => '2.5 km',
                'hours' => [
                    [
                        'day' => 'Lunes',
                        'hours' => '09:00-20:00'
                    ],
                    [
                        'day' => 'Martes',
                        'hours' => '09:00-20:00'
                    ],
                    [
                        'day' => 'Miércoles',
                        'hours' => '09:00-20:00'
                    ],
                    [
                        'day' => 'Jueves',
                        'hours' => '09:00-20:00'
                    ],
                    [
                        'day' => 'Viernes',
                        'hours' => '09:00-20:00'
                    ],
                    [
                        'day' => 'Sábado',
                        'hours' => '10:00-18:00'
                    ],
                    [
                        'day' => 'Domingo',
                        'hours' => ''
                    ]
                ],
                'opening_hours' => [
                    'monday' => '09:00-20:00',
                    'tuesday' => '09:00-20:00',
                    'wednesday' => '09:00-20:00',
                    'thursday' => '09:00-20:00',
                    'friday' => '09:00-20:00',
                    'saturday' => '10:00-18:00',
                    'sunday' => 'Cerrado'
                ]
            ],
            [
                'id_store' => '2',
                'name' => 'Tienda Centro Comercial',
                'address1' => 'Centro Comercial ABC, Local 45',
                'address2' => '',
                'city' => $delivery_address->city,
                'postcode' => $delivery_address->postcode,
                'phone' => '912345679',
                'email' => 'centro@tienda.com',
                'latitude' => '40.425000',
                'longitude' => '-3.705000',
                'state' => 'Madrid',
                'country' => 'España',
                'distance' => '5.2 km',
                'hours' => [
                    [
                        'day' => 'Lunes',
                        'hours' => '10:00-22:00'
                    ],
                    [
                        'day' => 'Martes',
                        'hours' => '10:00-22:00'
                    ],
                    [
                        'day' => 'Miércoles',
                        'hours' => '10:00-22:00'
                    ],
                    [
                        'day' => 'Jueves',
                        'hours' => '10:00-22:00'
                    ],
                    [
                        'day' => 'Viernes',
                        'hours' => '10:00-22:00'
                    ],
                    [
                        'day' => 'Sábado',
                        'hours' => '10:00-22:00'
                    ],
                    [
                        'day' => 'Domingo',
                        'hours' => '12:00-20:00'
                    ]
                ],
                'opening_hours' => [
                    'monday' => '10:00-22:00',
                    'tuesday' => '10:00-22:00',
                    'wednesday' => '10:00-22:00',
                    'thursday' => '10:00-22:00',
                    'friday' => '10:00-22:00',
                    'saturday' => '10:00-22:00',
                    'sunday' => '12:00-20:00'
                ]
            ]
        ];

        return $stores;
    }

    private function getAvailablePickupDates($config, $type = 'regular')
    {
        $dates = [];
        $startDate = date('Y-m-d');
        $daysAhead = isset($config['pickup_days_ahead']) ? (int)$config['pickup_days_ahead'] : 7;

        // Generar fechas disponibles
        for ($i = 1; $i <= $daysAhead; $i++) {
            $date = date('Y-m-d', strtotime("+{$i} days"));
            $dayName = date('l', strtotime($date));

            $dates[] = [
                'date' => $date,
                'formatted' => date('d/m/Y', strtotime($date)),
                'day_name' => $dayName
            ];
        }

        return $dates;
    }

    private function ensureStoreLocatorConfig()
    {
        // Configuración Store Locator - usar keys correctas del módulo original
        $configKey = 'KB_STORE_LOCATOR_GENERAL_SETTING';
        $existingConfig = \Configuration::get($configKey);

        if (empty($existingConfig)) {
            // Obtener la primera tienda disponible para configuración por defecto
            $firstStore = \Db::getInstance()->getRow('SELECT id_store FROM `' . _DB_PREFIX_ . 'store` WHERE active = 1 ORDER BY id_store LIMIT 1');
            $defaultStoreId = $firstStore ? $firstStore['id_store'] : 1;

            // Configuración por defecto para Store Locator original
            $defaultConfig = [
                'enable' => 1,
                'enable_store_locator' => 1,
                'homepage' => 1,
                'api_key' => 'AIzaSyCZVYdC6xXkZLwpR27YzwsCADj2Ui5hDcY', // API key desde el error log
                'default_store' => $defaultStoreId,
                'zoom_level' => 10,
                'display_phone' => 1,
                'website_link' => 1,
                'store_image' => 1,
                'show_stores' => 1,
                'get_direction_link' => 1,
                'search_as_move' => 0,
                'distance_unit' => 'km',
                'enable_all_store' => 1,
                'avilable_store' => []
            ];

            $configJson = json_encode($defaultConfig);
            \Configuration::updateValue($configKey, $configJson);
            // error_log("GuardPickupHandler: Created default Store Locator configuration with store ID: $defaultStoreId");
        } else {
            // error_log("GuardPickupHandler: Store Locator configuration already exists: " . $existingConfig);

            // Verificar que la configuración existente tenga los campos necesarios
            $config = json_decode($existingConfig, true);
            $needsUpdate = false;

            if (empty($config['enable'])) {
                $config['enable'] = 1;
                $needsUpdate = true;
            }
            if (empty($config['enable_store_locator'])) {
                $config['enable_store_locator'] = 1;
                $needsUpdate = true;
            }
            if (empty($config['homepage'])) {
                $config['homepage'] = 1;
                $needsUpdate = true;
            }
            if (empty($config['default_store'])) {
                $firstStore = \Db::getInstance()->getRow('SELECT id_store FROM `' . _DB_PREFIX_ . 'store` WHERE active = 1 ORDER BY id_store LIMIT 1');
                $config['default_store'] = $firstStore ? $firstStore['id_store'] : 1;
                $needsUpdate = true;
            }
            // Forzar show_stores = 1 para que funcione el mapa
            if (empty($config['show_stores']) || $config['show_stores'] == '0') {
                $config['show_stores'] = '1';
                $needsUpdate = true;
            }
            // Forzar display_phone = 1 para mostrar teléfonos
            if (!isset($config['display_phone']) || $config['display_phone'] == '0') {
                $config['display_phone'] = '1';
                $needsUpdate = true;
            }
            // Forzar get_direction_link = 1 para mostrar direcciones
            if (!isset($config['get_direction_link']) || $config['get_direction_link'] == '0') {
                $config['get_direction_link'] = '1';
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                \Configuration::updateValue($configKey, json_encode($config));
                // error_log("GuardPickupHandler: Updated existing Store Locator configuration with forced values");
            }
        }

        // Configuración Pickup Time Settings
        $pickupConfigKey = 'KB_PICKUP_TIME_SETTINGS';
        $existingPickupConfig = \Configuration::get($pickupConfigKey);

        if (empty($existingPickupConfig)) {
            $defaultPickupConfig = [
                'enable_date_selection' => 1,
                'days_gap' => 1,
                'maximum_days' => 7,
                'hours_gap' => 2,
                'time_slot' => 1,
                'format' => '%d/%m/%Y'
            ];

            $pickupConfigJson = json_encode($defaultPickupConfig);
            \Configuration::updateValue($pickupConfigKey, $pickupConfigJson);
            // error_log("GuardPickupHandler: Created default Pickup Time Settings configuration");
        } else {
            // error_log("GuardPickupHandler: Pickup Time Settings configuration already exists");
        }


        // Configurar también el carrier ID para pickup si no existe
        $pickupCarrierId = \Configuration::get('KB_GC_PICKUP_AT_STORE_SHIPPING');
        if (empty($pickupCarrierId)) {
            \Configuration::updateValue('KB_GC_PICKUP_AT_STORE_SHIPPING', $this->carrierId);
            // error_log("GuardPickupHandler: Set pickup carrier ID to " . $this->carrierId);
        }
    }

    private function getDefaultStoreConfig(): array
    {
        return [
            'api_key' => '',
            'default_store' => 1,
            'zoom_level' => 10,
            'display_phone' => 1,
            'website_link' => 1,
            'store_image' => 1,
            'show_stores' => 1,
            'get_direction_link' => 1,
            'search_as_move' => 0,
            'distance_unit' => 'km'
        ];
    }

    private function getDefaultPickupSettings(): array
    {
        return [
            'enable_date_selection' => 1,
            'days_gap' => 1,
            'maximum_days' => 7,
            'hours_gap' => 2,
            'time_slot' => 1
        ];
    }

    private function formatStoresForMap($stores): array
    {
        $mapData = [];
        foreach ($stores as $store) {
            $html = '<div class="velo-popup">' .
                '<div class="title-address">' . htmlspecialchars($store['name']) . '</div>' .
                '<div class="detail-address">' . htmlspecialchars($store['address1']);

            if (!empty($store['address2'])) {
                $html .= '<br/>' . htmlspecialchars($store['address2']);
            }

            $html .= '</div></div>';

            $mapData[] = [
                $html,
                $store['latitude'] ?? '40.416775',
                $store['longitude'] ?? '-3.703790'
            ];
        }
        return $mapData;
    }

    private function getModuleDirUrl(): string
    {
        return _MODULE_DIR_;
    }

    private function sanitizeStoreData($store)
    {
        $sanitized = [];

        if (!is_array($store)) {
            return (string)$store;
        }

        foreach ($store as $key => $value) {
            try {
                if (is_array($value)) {
                    // Si es array, convertir a string (excepto 'hours' que necesita tratamiento especial)
                    if ($key === 'hours') {
                        // Validar que hours sea un array válido con la estructura correcta
                        if (is_array($value) && !empty($value)) {
                            $validHours = [];
                            foreach ($value as $hourEntry) {
                                if (is_array($hourEntry) && isset($hourEntry['day']) && isset($hourEntry['hours'])) {
                                    $validHours[] = [
                                        'day' => (string)$hourEntry['day'],
                                        'hours' => (string)$hourEntry['hours']
                                    ];
                                } else {
                                    // Si no tiene la estructura correcta, crear una entrada válida
                                    $validHours[] = [
                                        'day' => is_string($hourEntry) ? (string)$hourEntry : 'N/A',
                                        'hours' => ''
                                    ];
                                }
                            }
                            $sanitized[$key] = $validHours;
                        } else {
                            // Si hours no es array válido, crear array vacío
                            $sanitized[$key] = [];
                        }
                    } else {
                        // Para otros arrays, convertir a string seguro
                        $stringValues = [];
                        foreach ($value as $item) {
                            if (!is_array($item) && !is_object($item)) {
                                $stringValues[] = (string)$item;
                            }
                        }
                        $sanitized[$key] = implode(', ', $stringValues);
                    }
                } elseif (is_object($value)) {
                    // Para objetos, convertir a string o usar propiedades específicas
                    $sanitized[$key] = (string)$value;
                } elseif (is_null($value)) {
                    $sanitized[$key] = '';
                } elseif (is_bool($value)) {
                    $sanitized[$key] = $value ? '1' : '0';
                } else {
                    $sanitized[$key] = (string)$value;
                }
            } catch (\Exception $e) {
                error_log("GuardPickupHandler: Error sanitizing key '$key': " . $e->getMessage());
                $sanitized[$key] = '';
            }
        }

        // Asegurar campos obligatorios
        $required_fields = ['id_store', 'name', 'address1', 'address2', 'city', 'postcode', 'state', 'country', 'phone', 'email', 'latitude', 'longitude'];
        foreach ($required_fields as $field) {
            if (!isset($sanitized[$field])) {
                $sanitized[$field] = '';
            }
        }

        return $sanitized;
    }

    private function deepSanitizeForTemplate($data)
    {
        try {
            if (is_array($data)) {
                $sanitized = [];
                foreach ($data as $key => $value) {
                    try {
                        // Claves especiales que necesitan manejo específico
                        if ($key === 'carrier' && is_object($value)) {
                            // Mantener objeto Carrier como está
                            $sanitized[$key] = $value;
                        } elseif ($key === 'hours' && is_array($value)) {
                            // Mantener hours como array pero asegurar que todos los elementos sean strings
                            $sanitized[$key] = $this->sanitizeHoursArray($value);
                        } elseif (in_array($key, ['available_stores']) && is_array($value)) {
                            // Para available_stores, aplicar sanitización pero mantener estructura
                            $sanitized[$key] = [];
                            foreach ($value as $storeItem) {
                                $sanitized[$key][] = $this->deepSanitizeForTemplate($storeItem);
                            }
                        } elseif ($key === 'cart_guard_pickup' && is_array($value)) {
                            // Para cart_guard_pickup, mantener estructura sin sanitización agresiva
                            $sanitized[$key] = $value;
                        } elseif (in_array($key, ['store', 'selected_store']) && is_array($value)) {
                            // Para store y selected_store, sanitizar completamente SIN mantener arrays
                            $storeData = [];
                            foreach ($value as $storeKey => $storeValue) {
                                if ($storeKey === 'hours' && is_array($storeValue)) {
                                    // Para hours dentro de store, mantener como array válido
                                    $storeData[$storeKey] = $this->sanitizeHoursArray($storeValue);
                                } elseif (is_array($storeValue)) {
                                    // Cualquier otro array dentro de store convertirlo a string
                                    $stringValues = [];
                                    foreach ($storeValue as $item) {
                                        if (!is_array($item) && !is_object($item)) {
                                            $stringValues[] = (string)$item;
                                        }
                                    }
                                    $storeData[$storeKey] = implode(', ', $stringValues);
                                } else {
                                    $storeData[$storeKey] = (string)$storeValue;
                                }
                            }
                            $sanitized[$key] = $storeData;
                        } elseif (is_array($value)) {
                            // Para cualquier otro array, convertir a string seguro
                            $stringValues = [];
                            foreach ($value as $item) {
                                if (!is_array($item) && !is_object($item)) {
                                    $stringValues[] = (string)$item;
                                }
                            }
                            $sanitized[$key] = implode(', ', $stringValues);
                        } else {
                            $sanitized[$key] = $this->deepSanitizeForTemplate($value);
                        }
                    } catch (\Exception $e) {
                        error_log("GuardPickupHandler: Error in deepSanitizeForTemplate for key '$key': " . $e->getMessage());
                        $sanitized[$key] = '';
                    }
                }
                return $sanitized;
            } elseif (is_object($data)) {
                // Para objetos que no son Carrier, mantener como están (PrestaShop los maneja)
                return $data;
            } elseif (is_bool($data)) {
                // Convertir boolean a int para evitar problemas con Smarty
                return $data ? 1 : 0;
            } elseif (is_null($data)) {
                // Convertir null a string vacío
                return '';
            } else {
                // Para valores escalares, convertir a string
                return (string)$data;
            }
        } catch (\Exception $e) {
            error_log("GuardPickupHandler: Error in deepSanitizeForTemplate: " . $e->getMessage());
            return '';
        }
    }

    private function calculateDistance($lat1, $lng1, $lat2, $lng2, $unit = 'M')
    {
        if (($lat1 == $lat2) && ($lng1 == $lng2)) {
            return 0;
        } else {
            $theta = $lng1 - $lng2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            $unit = strtoupper($unit);

            if ($unit == "K") {
                return ($miles * 1.609344);
            } elseif ($unit == "N") {
                return ($miles * 0.8684);
            } else {
                return $miles;
            }
        }
    }

    protected function renderTemplate(string $template, array $data = []): string
    {
        try {
            // Aplicar sanitización profunda a todos los datos antes de pasarlos a Smarty
            $sanitizedData = $this->deepSanitizeForTemplate($data);

            // Asignar datos del template + link automáticamente para todos los carriers
            $templateData = array_merge($sanitizedData, [
                'link' => $this->context->link
            ]);

            // Log adicional para debug específico de GuardPickupHandler
            // error_log("GuardPickupHandler: Rendering template with " . count($templateData) . " variables");

            // Verificar que no hay arrays problemáticos antes de asignar a Smarty
            foreach ($templateData as $key => $value) {
                if (is_array($value) && !in_array($key, ['available_stores', 'store', 'selected_store', 'carrier', 'cart_guard_pickup'])) {
                    // error_log("GuardPickupHandler: WARNING - Problematic array found in key '$key': " . json_encode($value));
                    // Convertir a string seguro
                    if ($key === 'hours') {
                        // Para hours, mantener como array válido
                        $templateData[$key] = $this->sanitizeHoursArray($value);
                    } else {
                        $templateData[$key] = is_array($value) ? json_encode($value) : (string)$value;
                    }
                }

                $this->context->smarty->assign($key, $templateData[$key]);
            }

            return $this->context->smarty->fetch($template);
        } catch (\Exception $e) {
            // Log error y retornar mensaje de error
            $errorMsg = "GuardPickupHandler template error - Template: {$template}, Error: " . $e->getMessage() . ", File: " . $e->getFile() . ", Line: " . $e->getLine();
            error_log($errorMsg);

            if ($this->configuration['debug']) {
                error_log("GuardPickupHandler debug info - Data keys: " . implode(', ', array_keys($data)));
            }

            return '<div class="alert alert-danger">Error rendering Store Pickup template: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    private function sanitizeHoursArray($hours)
    {
        if (!is_array($hours)) {
            return [];
        }

        $sanitized = [];
        foreach ($hours as $entry) {
            if (is_array($entry)) {
                $sanitized[] = [
                    'day' => isset($entry['day']) ? (string)$entry['day'] : '',
                    'hours' => isset($entry['hours']) ? (string)$entry['hours'] : ''
                ];
            } else {
                $sanitized[] = [
                    'day' => 'N/A',
                    'hours' => (string)$entry
                ];
            }
        }

        return $sanitized;
    }

    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => true,
            'requires_store_selection' => true,
            'show_store_details' => true,
            'cache_ttl' => 3600
        ];
    }

    /**
     * Get cart guard pickup selection from database
     * @param Context $context
     * @return array
     */
    private function getCartGuardPickupData(Context $context): array
    {
        if (!$context->cart || !$context->cart->id || !$context->customer || !$context->customer->id) {
            return [];
        }

        $id_cart = (int)$context->cart->id;
        $id_customer = (int)$context->customer->id;
        $id_shop = (int)$context->shop->id;

        try {
            // Use the same logic as the order confirmation hook
            $pickup = \Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'kb_gc_pickup_at_store_time WHERE id_cart=' . (int)$id_cart . ' AND id_shop=' . (int)$id_shop . ' AND id_customer=' . (int)$id_customer);

            if (!empty($pickup)) {
                // Get store data using Store class like in the original logic
                $store = new \Store($pickup['id_store'], $context->language->id);

                return [
                    'id_store' => $pickup['id_store'],
                    'store_name' => $store->name ?? '',
                    'address1' => $store->address1 ?? '',
                    'address2' => $store->address2 ?? '',
                    'city' => $store->city ?? '',
                    'postcode' => $store->postcode ?? '',
                    'latitude' => $store->latitude ?? '',
                    'longitude' => $store->longitude ?? '',
                    'phone' => $store->phone ?? '',
                    'email' => $store->email ?? '',
                    'preferred_date' => $pickup['preferred_date'] ?? '',
                    'pickup_time' => $pickup['preferred_date'] ?? '', // Alias for compatibility
                    'date_add' => $pickup['date_add'] ?? '',
                    'date_upd' => $pickup['date_upd'] ?? '',
                    // Additional store data
                    'hours' => [], // Will be populated if needed
                    'country' => '', // Will be populated if needed
                    'state' => '' // Will be populated if needed
                ];
            }

            return [];

        } catch (\Exception $e) {
            error_log("GuardPickupHandler: Error getting cart guard pickup data: " . $e->getMessage());
            return [];
        }
    }

}