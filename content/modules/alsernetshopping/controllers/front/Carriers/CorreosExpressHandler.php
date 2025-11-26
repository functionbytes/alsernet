<?php

namespace AlsernetShopping\Carriers;

use Configuration;
use Context;
use Address;
use State;
use Country;
use Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CorreosExpressHandler extends AbstractCarrierHandler
{
    private $carrierId = 66;

    public function __construct(Context $context = null)
    {
        parent::__construct($context);
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
        return 'Correos Express';
    }

    public function getTemplatePath(): string
    {
        return 'module:alsernetshopping/views/templates/front/checkout/partials/delivery/carriers/66_correosexpress/interface.tpl';
    }

    public function isAvailableForCart($cart): bool
    {
        return $this->isEnabled();
    }

    public function shouldExcludeOtherCarriers($cart): bool
    {
        return false;
    }

    public function getExtraContent(Address $address, Context $context): string
    {

        $id_lang = (int)$context->language->id;
        $state_name = $address->id_state ? State::getNameById($address->id_state) : '';
        $country_name = $address->id_country ? Country::getNameById($id_lang, $address->id_country) : '';

        // Get cart correosexpress selection if exists
        $cart_correosexpress = $this->getCartCorreosData($context);

        $templateData = [
            'id_shop'          => $this->context->shop->id,
            'id_shop_group'    => $this->context->shop->id_shop_group,
            'id_cart'          => $this->context->cookie->id_cart,
            'id_carrier' => $this->getId(),
            'id_customer'      => $this->context->cart->id_customer,
            'baseUrl'          => Tools::getAdminUrl()."/modules/correosexpress/controllers/admin/index.php",
            'carrier' => new \Carrier($this->carrierId, $context->language->id),
            'carrier_id' => $this->carrierId,
            'delivery_address' => $address,
            'state_name' => $state_name,
            'country_name' => $country_name,
            'cex_token_user' => \Configuration::get('CEX_TOKEN_USER', ''),
            'correos_info' => [
                'delivery_time' => '3-5 días laborables',
                'tracking_available' => true,
                'signature_required' => false
            ],
            'cart_correosexpress' => $cart_correosexpress
        ];

        return $this->renderTemplate($this->getTemplatePath(), $templateData);

    }

    public function processForm($requestData, Context $context): array
    {
        // Correos no requiere formulario específico
        $address = isset($requestData['delivery_address']) ? $requestData['delivery_address'] : new \Address($requestData['id_address']);

        return [
            'status' => 'success',
            'html' => $this->getExtraContent($address, $context),
            'id_carrier' => $this->carrierId,
            'id_address' => $requestData['id_address'],
        ];
    }

    public function processSelection(array $requestData, \Context $context): array
    {

        $type    = isset($requestData['type']) ? (string)$requestData['type'] : '';
        $payload = isset($requestData['payload']) && is_array($requestData['payload']) ? $requestData['payload'] : [];

        if ($type === 'pickup') {
            if (!empty($payload['texto_oficina'])) {
                $parts = explode('#!#', (string)$payload['texto_oficina']);
                $payload['office_code'] = $payload['office_code'] ?? ($parts[0] ?? '');
                $payload['office_name'] = $payload['office_name'] ?? ($parts[1] ?? '');
                $payload['street']      = $payload['street']      ?? ($parts[2] ?? '');
                $payload['city']        = $payload['city']        ?? ($parts[3] ?? '');
                $payload['postcode']    = $payload['postcode']    ?? ($parts[4] ?? '');
                $payload['province']    = $payload['province']    ?? ($parts[5] ?? '');
            }

            $office_id   = trim((string)($payload['office_id']   ?? $payload['office_code'] ?? ''));
            $office_name = trim((string)($payload['office_name'] ?? ''));
            $street      = trim((string)($payload['street']      ?? ''));
            $city        = trim((string)($payload['city']        ?? ''));
            $postcode    = trim((string)($payload['postcode']    ?? ''));

            if ($office_id === '' || $office_name === '' || $street === '' || $city === '' || $postcode === '') {
                return \ResponseHelper::warning('Debes seleccionar una oficina de Correos Express.');
            }

            return [
                'status'     => 'success',
                'message'    => 'Oficina de Correos Express seleccionada.',
                'id_carrier' => $this->getId(),
                'selection'  => [
                    'type'        => 'pickup',
                    'id_carrier'  => $this->getId(),
                    'office_id'   => $office_id,
                    'office_code' => $payload['office_code'] ?? $office_id,
                    'office_name' => $office_name,
                    'street'      => $street,
                    'city'        => $city,
                    'postcode'    => $postcode,
                    'province'    => trim((string)($payload['province'] ?? '')),
                    'lat'         => isset($payload['lat']) ? (float)$payload['lat'] : null,
                    'lng'         => isset($payload['lng']) ? (float)$payload['lng'] : null,
                    'schedule'    => isset($payload['schedule']) ? (string)$payload['schedule'] : null,
                ],
            ];
        }

        // home / default
        return [
            'status'     => 'success',
            'message'    => 'Envío a domicilio con Correos Express.',
            'id_carrier' => $this->getId(),
            'selection'  => [
                'type'       => 'home',
                'id_carrier' => $this->getId(),
            ],
        ];
    }

    public function persistSelection(\Context $context, array $requestData, array $handlerResult): bool
    {



        $db    = \Db::getInstance();
        $table = _DB_PREFIX_.'cex_officedeliverycorreo';

        $cart = $context->cart ?? null;
        if (!$cart || !(int)$cart->id) {
            return false;
        }

        $id_cart     = (int)$cart->id;
        $id_customer = (int)$cart->id_customer;
        $id_carrier  = $requestData['id_carrier'] ?? $handlerResult['id_carrier'] ?? 0;
        $payload  =  $requestData['payload'];


        $sel = $handlerResult['selection'] ?? [];
        if (!is_array($sel)) { $sel = []; }

        $codigo_oficina =  $payload['office_code'] ?? 0;
        $texto_oficina  = $payload['texto_oficina'] ?? '';

        if ($id_carrier <= 0 || ($codigo_oficina === 0 && $texto_oficina === '')) {
            return false;
        }

        // 1) Borrar si ya existía un registro para este carrito
        $db->execute('DELETE FROM `'.$table.'` WHERE id_cart='.$id_cart);

        // 2) Insertar nuevo
        $sql = 'INSERT INTO `'.$table.'`
        (id_cart, id_carrier, id_customer, codigo_oficina, texto_oficina)
        VALUES (
            '.$id_cart.',
            '.$id_carrier.',
            '.$id_customer.',
            '.$codigo_oficina.',
            "'.$texto_oficina.'"
        )';


        return (bool)$db->execute($sql);
    }

    public function getAssets(): array
    {
        return [
            'css' => [
                'modules/alsernetshopping/views/css/front/checkout/carriers/correosexpress-carrier.css'
            ],
            'js' => [
                'modules/alsernetshopping/views/js/front/checkout/steps/delivery/carriers/correosexpress-carrier.js'
            ]
        ];
    }

    /**
     * Get cart correosexpress office selection from database
     * @param Context $context
     * @return array
     */
    private function getCartCorreosData(Context $context): array
    {
        $id_cart     = (int)$context->cart->id;
        $id_customer = (int)$context->customer->id;
        $id_shop     = (int)$context->shop->id;

        try {



            $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'cex_officedeliverycorreo
                WHERE id_cart = ' . $id_cart . '
                AND id_customer = ' . $id_customer ;


            $correosexpress_data = \Db::getInstance()->getRow($sql);

            if ($correosexpress_data) {

                // Si existe el campo concatenado
                if (!empty($correosexpress_data['texto_oficina'])) {
                    $parts = explode('#!#', $correosexpress_data['texto_oficina']);


                    // Mapear con seguridad para evitar warnings
                    $codigo   = $parts[0] ?? '';
                    $address  = $parts[1] ?? '';
                    $name     = $parts[2] ?? '';
                    $zip      = $parts[3] ?? '';
                    $city     = $parts[4] ?? '';

                    return [
                        'office_id'   => $correosexpress_data['office_id'] ?? '',
                        'office_code' => $codigo,
                        'office_name' => $name,
                        'street'      => $address,
                        'city'        => $city,
                        'postcode'    => $zip,
                        'province'    => $correosexpress_data['province'] ?? '',
                        'date_add'    => $correosexpress_data['date_add'] ?? '',
                        'date_upd'    => $correosexpress_data['date_upd'] ?? ''
                    ];
                }

                // fallback: si no tiene texto_oficina, usar columnas directas
                return [
                    'office_id'   => $correosexpress_data['office_id'] ?? '',
                    'office_code' => $correosexpress_data['office_code'] ?? '',
                    'office_name' => $correosexpress_data['office_name'] ?? '',
                    'street'      => $correosexpress_data['street'] ?? '',
                    'city'        => $correosexpress_data['city'] ?? '',
                    'postcode'    => $correosexpress_data['postcode'] ?? '',
                    'province'    => $correosexpress_data['province'] ?? '',
                    'date_add'    => $correosexpress_data['date_add'] ?? '',
                    'date_upd'    => $correosexpress_data['date_upd'] ?? ''
                ];
            }

            return [];

        } catch (\Exception $e) {
            error_log("CorreosExpressHandler: Error getting cart correosexpress data: " . $e->getMessage());
            return [];
        }
    }

}

