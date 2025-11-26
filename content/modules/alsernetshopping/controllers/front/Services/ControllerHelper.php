<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Helper para operaciones comunes de controladores
 * Reduce duplicación de código
 */
class ControllerHelper
{
    /**
     * Valida que el usuario esté autenticado
     */
    public static function validateAuthentication(\Context $context): ?array
    {
        $customer = $context->customer;

        if (!$customer || !$customer->isLogged()) {
            return \ResponseHelper::authRequired($context);
        }

        return null; // Todo OK
    }

    /**
     * Valida parámetros básicos para carriers
     */
    public static function validateCarrierParams(int $carrierId): ?array
    {
        if (!$carrierId) {
            return \ResponseHelper::warning('ID de carrier requerido');
        }

        return null; // Todo OK
    }


    /**
     * Obtiene datos comunes de dirección para carriers
     */
    public static function getAddressData(\Address $address, \Context $context): array
    {
        $id_lang = (int)$context->language->id;

        return [
            'delivery_address' => $address,
            'state_name' => $address->id_state ? \State::getNameById($address->id_state) : '',
            'country_name' => $address->id_country ? \Country::getNameById($id_lang, $address->id_country) : '',
        ];
    }

    public static function processCarrierSelection(int $carrierId, array $requestData, \Context $context): array
    {


        $carrierRegistry = \AlsernetShopping\Carriers\CarrierRegistry::getInstance();
        $handler = $carrierRegistry->getHandler($carrierId);

        error_log("ControllerHelper: Processing selection for carrier {$carrierId}, handler: " . ($handler ? 'YES' : 'NO'));

        if ($handler && $handler->isEnabled()) {
            try {
                // Cada handler decide qué campos necesita y devuelve status success|warning|error
                $result = $handler->processSelection($requestData, $context);
                return is_array($result) ? $result : \ResponseHelper::error('Respuesta inválida del handler');
            } catch (\Exception $e) {
                error_log("Error processing selection for carrier {$carrierId}: " . $e->getMessage());
                return \ResponseHelper::error('Error interno procesando la selección del carrier');
            }
        }

        return \ResponseHelper::warning('Carrier no disponible para selección específica.');
    }

    public static function getCarrierSelectionByCart($idCart, $idLang = null)
    {
        if (!(int)$idCart) {
            return null;
        }

        $ctx = Context::getContext();
        if ($idLang === null) {
            $idLang = (int)$ctx->language->id;
        }

        $db = Db::getInstance();

        // ---- 0) ¿Existe la tabla cart_carrier?
        $tableName = pSQL(_DB_PREFIX_.'cart_carrier');
        $hasCartCarrier = (bool)$db->getValue("SHOW TABLES LIKE '".$tableName."'");

        // ---- 1) cart_carrier si existe
        if ($hasCartCarrier) {
            $sql = new DbQuery();
            $sql->select('cc.id_carrier, cc.id_carrier_reference, cl.delay, cl.name')
                ->from('cart_carrier', 'cc')
                ->leftJoin('carrier_lang', 'cl', 'cl.id_carrier = cc.id_carrier AND cl.id_lang = '.(int)$idLang)
                ->where('cc.id_cart = '.(int)$idCart)
                ->orderBy('cc.date_add DESC');

            if ($row = $db->getRow($sql)) {
                $row['id_carrier'] = (int)$row['id_carrier'];
                $row['id_carrier_reference'] = (int)$row['id_carrier_reference'];
                $row['source'] = 'cart_carrier';
                return $row;
            }
        }

        // ---- 2) cart.delivery_option (JSON/serialize con mapping id_address => "id_carrier,")
        $cartRow = $db->getRow('
            SELECT id_carrier, delivery_option, id_address_delivery
            FROM '._DB_PREFIX_.'cart
            WHERE id_cart = '.(int)$idCart
        );

        if ($cartRow) {
            $idCarrierFromCart = (int)$cartRow['id_carrier'];

            $selectedCarrier = 0;
            $deliveryOptionRaw = $cartRow['delivery_option'];

            // delivery_option puede ser JSON o serialize; probamos ambos
            $map = null;

            if ($deliveryOptionRaw) {
                // Intento JSON
                $decoded = json_decode($deliveryOptionRaw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $map = $decoded;
                } else {
                    // Intento serialize
                    $unser = @unserialize($deliveryOptionRaw);
                    if ($unser !== false && is_array($unser)) {
                        $map = $unser;
                    }
                }
            }

            if (is_array($map) && !empty($map)) {
                // Para carritos estándar 1 dirección: primera clave
                $firstAddressId = (int)key($map);
                $opt = (string)reset($map); // ejemplo "12," o "12,12"
                // Extraer primer número (id_carrier)
                if (preg_match('/^\s*(\d+)\s*,?/', $opt, $m)) {
                    $selectedCarrier = (int)$m[1];
                }
            }

            // Fallback: si no salió de delivery_option, usa id_carrier del carrito
            if ($selectedCarrier <= 0 && $idCarrierFromCart > 0) {
                $selectedCarrier = $idCarrierFromCart;
            }

            if ($selectedCarrier > 0) {
                $sql = new DbQuery();
                $sql->select('c.id_carrier, c.id_reference as id_carrier_reference, cl.delay, cl.name')
                    ->from('carrier', 'c')
                    ->leftJoin('carrier_lang', 'cl', 'cl.id_carrier = c.id_carrier AND cl.id_lang = '.(int)$idLang)
                    ->where('c.id_carrier = '.(int)$selectedCarrier);

                if ($row = $db->getRow($sql)) {
                    $row['id_carrier'] = (int)$row['id_carrier'];
                    $row['id_carrier_reference'] = (int)$row['id_carrier_reference'];
                    $row['source'] = ($hasCartCarrier ? 'fallback_delivery_option' : 'delivery_option');
                    return $row;
                }
            }

            // ---- 3) Último recurso: id_carrier del carrito con sus datos
            if ($idCarrierFromCart > 0) {
                $sql = new DbQuery();
                $sql->select('c.id_carrier, c.id_reference as id_carrier_reference, cl.delay, cl.name')
                    ->from('carrier', 'c')
                    ->leftJoin('carrier_lang', 'cl', 'cl.id_carrier = c.id_carrier AND cl.id_lang = '.(int)$idLang)
                    ->where('c.id_carrier = '.(int)$idCarrierFromCart);

                if ($row = $db->getRow($sql)) {
                    $row['id_carrier'] = (int)$row['id_carrier'];
                    $row['id_carrier_reference'] = (int)$row['id_carrier_reference'];
                    $row['source'] = 'cart.id_carrier';
                    return $row;
                }
            }
        }

        return null;
    }


    public static function persistCarrierSelection(\Context $context, array $requestData, array $handlerResult): bool
    {


        $carrierId = (int)$requestData['id_carrier'];
        $registry  = \AlsernetShopping\Carriers\CarrierRegistry::getInstance();
        $handler   = $registry->getHandler($carrierId);

        return $handler->persistSelection($context, $requestData, $handlerResult);

    }
    public static function persistCarrierUnselection(\Context $context, array $requestData, array $handlerResult): bool
    {


        $carrierId = (int)$requestData['id_carrier'];
        $registry  = \AlsernetShopping\Carriers\CarrierRegistry::getInstance();
        $handler   = $registry->getHandler($carrierId);

        return $handler->persistSelection($context, $requestData, $handlerResult);

    }


    /**
     * Procesa carrier usando el sistema modular
     */
    public static function processCarrierRequest(int $carrierId, array $requestData, \Context $context): array
    {
        $carrierRegistry = \AlsernetShopping\Carriers\CarrierRegistry::getInstance();
        $handler = $carrierRegistry->getHandler($carrierId);

        $cart = $context->cart;
        $id_address = $cart->id_address_delivery;
        $id_lang = (int) $context->language->id;
        $address = new Address($id_address);
        $state_name = $address->id_state ? State::getNameById($address->id_state) : '';
        $country_name = $address->id_country ? Country::getNameById($id_lang, $address->id_country) : '';
        $carrier = new Carrier($carrierId, $context->language->id);

        $data = [
            'delivery_address' => $address,
            'state_name' => $state_name,
            'country_name' => $country_name,
            'carrier' => $carrier,
        ];

        $context->smarty->assign($data);

        error_log("ControllerHelper: Processing carrier {$carrierId}, handler found: " . ($handler ? 'YES' : 'NO'));

        if ($handler && $handler->isEnabled()) {
            try {

                $result = $handler->processForm($requestData, $context);

                if ($result['status'] === 'success') {
                    return $result;
                }
            } catch (\Exception $e) {
                error_log("Error processing carrier {$carrierId}: " . $e->getMessage());
            }
        }

        // No handler found - check if it's a standard carrier
        error_log("No handler found for carrier {$carrierId}. Checking carrier type...");

        $carrierType = $carrierRegistry->getCarrierType($carrierId);

        if ($carrierType === 'standard') {
            // Standard carriers should return success even without specific handlers
            error_log("Standard carrier {$carrierId} processed successfully without handler");

            return \ResponseHelper::carrierResponse(
                'success',
                false,
                $carrierId,
                $requestData['id_address'] ?? 0,
                "Standard carrier {$carrierId} selected successfully"
            );
        } elseif ($carrierType === 'custom') {
            // Custom carriers require handlers
            error_log("Custom carrier {$carrierId} requires a specific handler");

            return \ResponseHelper::carrierResponse(
                'error',
                '',
                $carrierId,
                $requestData['id_address'] ?? 0,
                "Custom carrier {$carrierId} requires handler"
            );
        } else {
            // Unknown carrier type
            error_log("Unknown carrier type for {$carrierId}. Consider adding it to CarrierRegistry configuration.");

            return \ResponseHelper::carrierResponse(
                'warning',
                '<div class="alert alert-warning">Este método de envío requiere configuración adicional.</div>',
                $carrierId,
                $requestData['id_address'] ?? 0,
                "Unknown carrier {$carrierId} requires configuration"
            );
        }
    }

    /**
     * Actualiza el carrier del carrito
     */
    public static function updateCartCarrier(\Cart $cart, int $carrierId): void
    {
        $cart->id_carrier = $carrierId;
        $cart->step = 'delivery';
        $cart->update();
    }

    /**
     * Obtiene configuración estandarizada para addresses
     */
    public static function getAddressConfiguration(): array
    {
        return \Configuration::getMultiple([
            'PS_TAX_ADDRESS_TYPE',
            'PS_INVOICE',
            'VATNUMBER_MANAGEMENT'
        ]);
    }
}