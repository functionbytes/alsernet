<?php

include_once(_PS_MODULE_DIR_.'alsernetforms/classes/ApiManager.php');
class Order extends OrderCore
{

    public function __construct($id = null, $order_presenter = null)
    {
        parent::__construct($id);
        $this->order_presenter = $order_presenter ?: new PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderPresenter();
    }

    public function requestDeliveryTimes()
    {

        $context = Context::getContext();
        $delivery_times_cms_url = $context->link->getCMSLink(
            25,
            null,
            null,
            $context->language->id,
            $context->shop->id
        );

        $excluded_references = ["LOTERIA-BRUJA2025", "LOTERIA-MANOLITA2025"];
        $products = $this->getProducts();
        $template = '';

        foreach ($products as $product) {
            if (!in_array($product['reference'], $excluded_references)) {

                $trans_line = $this->trans(
                    'Check [b]HERE[/b] the information about [b]DELIVERY TIMES[/b]',
                    ['[b]' => '<strong>', '[/b]' => '</strong>'],
                    'Modules.Alsernetforms.Shop'
                );

                $template = '
                    <tr>
                        <td align="left" bgcolor="#90bb13" style="border-radius: 4px; font-family: Open Sans, Arial, sans-serif; font-size: 14px; background-color: #90bb13; color: #ffffff; font-weight: 400; padding: 5px 10px; border: 0; text-align: left;">
                            <p style="padding: 0; margin: 0;">
                                <a href="' . $delivery_times_cms_url . '" target="_blank" style="text-decoration: none;">
                                    <span style="font-family: Open Sans, Arial, sans-serif; font-size: 14px; color: #ffffff; font-weight: 400;">' . $trans_line . '</span>
                                </a>
                            </p>
                        </td>
                    </tr>';

                return $template;

            }
        }

        return $template;
    }


    public function getSaleType(): ?string
    {

        $products = $this->getProducts();

        foreach ($products as $product) {
            $features = \Product::getFeaturesStatic((int) $product['id_product'], null);

            if (empty($features)) {
                continue;
            }

            foreach ($features as $feature) {

                if ((int) $feature['id_feature'] !== 23) {
                    continue;
                }

                switch ((int) $feature['id_feature_value']) {
                    case 263658:
                        return 'dni';
                    case 263659:
                        return 'escopeta';
                    case 263660:
                        return 'rifle';
                    case 263661:
                        return 'corta';
                }
            }
        }

        return null;
    }

    public function hasDniRequiredProducts(): bool
    {
        $products = $this->getProducts();

        foreach ($products as $product) {

            $features = \Product::getFeaturesStatic((int)$product['id_product'], null);

            if (empty($features)) {
                continue;
            }

            foreach ($features as $feature) {
                if ((int) $feature['id_feature'] === 23) {
                    return true;
                }
            }
        }

        return false;
    }

    public function requesLottery(): ?string
    {
        return '';
    }


    public function sendDocumentRequest(): ?string
    {
        $saleType = $this->getSaleType();

        if (!$saleType) {
            return null;
        }

        if (!empty($this->document_number)) {
            return $this->document_number;
        }

        // Validar que esté pagada antes de procesar
        if (!$this->isPaid()) {
            return null;
        }

        // Obtener productos y datos completos
        $products = $this->getProducts();
        $customer = new Customer($this->id_customer);
        $address = new Address($this->id_address_delivery);

        $apiManager = new ApiManager();
        $response = $apiManager->sendRequest('POST', 'api/documents', [
            'action'   => 'request',
            'type'     => $saleType,
            'order_id' => $this->id,
            'reference'    => $this->reference,
            'cart_id'     => $this->id_cart,
            'customer' => [
                'id' => $customer->id,
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
                'email' => $customer->email,
                'siret' => $customer->siret ?? null,
                'company' => $address->company ?? null,
            ],
            'products' => array_map(function($product) {
                return [
                    'id' => $product['id_product'],
                    'name' => $product['name'],
                    'reference' => $product['reference'],
                    'quantity' => $product['quantity'],
                    'price' => $product['price'],
                ];
            }, $products),
            'date_add' => $this->date_add,
        ], 'documents');

        $documentNumber = $response['response']['data']['uid'] ?? null;
        $status = $response['response']['status'] ?? null;

        if ($status === 'success' && !empty($documentNumber)) {
            $this->document_type = $saleType;
            $this->document_number = $documentNumber;
            $this->update();
            return $documentNumber;
        }

        return null;
    }

    public function getDocumentInstructions(?string $documentNumber = null): ?string
    {
        $documentNumber = $documentNumber ?: $this->document_number;

        if (empty($documentNumber)) {
            return null;
        }

        $validation = self::validateDniDocuments($documentNumber);
        if (($validation['status'] ?? null) !== 'success') {
            return null;
        }

        $saleType = $validation['type'] ?? $this->document_type ?? $this->getSaleType();
        if (!$saleType) {
            return null;
        }

        $context = Context::getContext();
        $iso = $context->language->iso_code;
        $documents_url = $context->link->getCMSLink(136) . '?token=' . urlencode($documentNumber);

        [$trans_remember, $trans_list] = $this->getDocumentReminderTexts($saleType, $iso);

        $trans_instruction = $this->l('Please click on the following link and follow the instructions:', 'alsernetforms', $iso);
        $trans_upload = $this->l('Upload documentation', 'alsernetforms', $iso);

        return '
                <div style="margin: 20px 0; padding: 15px; background-color: #f5f5f5; border-left: 4px solid #90bb13;">
                    <p style="margin: 0 0 5px; font-size: 14px;">' . $trans_remember . '</p>
                    ' . $trans_list . '
                    <p style="margin: 0; font-size: 14px;">
                        <strong>' . $trans_instruction . '</strong>
                        <a href="' . $documents_url . '" target="_blank" style="color: #90bb13; text-decoration: underline;">
                            ' . $trans_upload . '
                        </a>
                    </p>
                </div>';
    }

    private function getDocumentReminderTexts(string $saleType, string $iso): array
    {
        switch ($saleType) {
            case 'corta':
                $trans_remember = strtr(
                    $this->l(
                        '[b]REMEMBER:[/b] In order to ship your firearm, we need you to send us the following documentation:',
                        'alsernetforms',
                        $iso
                    ),
                    ['[b]' => '<strong>', '[/b]' => '</strong>']
                );

                $trans_list = '<ul style="padding-left: 20px; margin: 8px 0;">'
                    . '<li>' . $this->l('A photocopy of your ID (both sides)', 'alsernetforms', $iso) . '</li>'
                    . '<li>' . $this->l('A photocopy of your handgun permit (type B) or Olympic shooting permit (type F)', 'alsernetforms', $iso) . '</li>'
                    . '</ul>';
                break;

            case 'rifle':
                $trans_remember = strtr(
                    $this->l(
                        '[b]REMEMBER:[/b] In order to ship your firearm, we need you to send us the following documentation:',
                        'alsernetforms',
                        $iso
                    ),
                    ['[b]' => '<strong>', '[/b]' => '</strong>']
                );

                $trans_list = '<ul style="padding-left: 20px; margin: 8px 0;">'
                    . '<li>' . $this->l('A photocopy of your ID (both sides)', 'alsernetforms', $iso) . '</li>'
                    . '<li>' . $this->l('A photocopy of your rifled long-range firearm permit (type D)', 'alsernetforms', $iso) . '</li>'
                    . '</ul>';
                break;

            case 'escopeta':
                $trans_remember = strtr(
                    $this->l(
                        '[b]REMEMBER:[/b] In order to ship your weapon, we need you to send us the following documentation:',
                        'alsernetforms',
                        $iso
                    ),
                    ['[b]' => '<strong>', '[/b]' => '</strong>']
                );

                $trans_list = '<ul style="padding-left: 20px; margin: 8px 0;">'
                    . '<li>' . $this->l('A photocopy of your ID (both sides)', 'alsernetforms', $iso) . '</li>'
                    . '<li>' . $this->l('A photocopy of a shotgun license (type E)', 'alsernetforms', $iso) . '</li>'
                    . '</ul>';
                break;

            case 'dni':
                $trans_remember = strtr(
                    $this->l(
                        '[b]REMEMBER:[/b] In order to ship your weapon, we need you to send us the following documentation:',
                        'alsernetforms',
                        $iso
                    ),
                    ['[b]' => '<strong>', '[/b]' => '</strong>']
                );

                $trans_list = '<ul style="padding-left: 20px; margin: 8px 0;">'
                    . '<li>' . $this->l('A photocopy of your ID (both sides)', 'alsernetforms', $iso) . '</li>'
                    . '</ul>';
                break;

            default:
                $trans_remember = strtr(
                    $this->l(
                        '[b]REMEMBER:[/b] In order to ship your air rifle, you must provide us with a copy of your passport or driving licence (both sides if it\'s a card).',
                        'alsernetforms',
                        $iso
                    ),
                    ['[b]' => '<strong>', '[/b]' => '</strong>']
                );
                $trans_list = '';
                break;
        }

        return [$trans_remember, $trans_list];
    }

    public function requestDocuments(): ?string
    {
        $documentNumber = $this->sendDocumentRequest();

        return $this->getDocumentInstructions($documentNumber) ?? '';
    }


    public static function validateDniDocuments(string $uid): array
    {
        $apiManager = new ApiManager();
        $response = $apiManager->sendRequest('POST', 'api/documents', [
            'action' => 'validate',
            'uid'    => $uid,
        ], 'documents');

        return [
            'reference' => $response['response']['reference'] ?? 'failed',
            'status' => $response['response']['status'] ?? 'failed',
            'type'   => $response['response']['data']['type'] ?? null,
            'upload' => $response['response']['data']['can_upload'] ?? null,
        ];
    }

    public function getCarrier()
    {

    }


    public function getUrlDocuments(): ?string
    {

        if (empty($this->document_number)) {
            return null;
        }

        $iso = Context::getContext()->language->iso_code;
        $link = Context::getContext()->link;
        $documents_url = $link->getCMSLink(136) . '?token=' . urlencode($this->document_number);

        switch ($this->document_type) {
                case 'corta':
                    $trans_remember = strtr(
                        $this->l(
                            '[b]REMEMBER:[/b] In order to ship your firearm, we need you to send us the following documentation:',
                            'alsernetforms',
                            $iso
                        ),
                        ['[b]' => '<strong>', '[/b]' => '</strong>']
                    );

                    $trans_list = '<ul style="padding-left: 20px; margin: 8px 0;">'
                        . '<li>' . $this->l('A photocopy of your ID (both sides)', 'alsernetforms', $iso) . '</li>'
                        . '<li>' . $this->l('A photocopy of your handgun permit (type B) or Olympic shooting permit (type F)', 'alsernetforms', $iso) . '</li>'
                        . '</ul>';
                    break;

                case 'rifle':
                    $trans_remember = strtr(
                        $this->l(
                            '[b]REMEMBER:[/b] In order to ship your firearm, we need you to send us the following documentation:',
                            'alsernetforms',
                            $iso
                        ),
                        ['[b]' => '<strong>', '[/b]' => '</strong>']
                    );

                    $trans_list = '<ul style="padding-left: 20px; margin: 8px 0;">'
                        . '<li>' . $this->l('A photocopy of your ID (both sides)', 'alsernetforms', $iso) . '</li>'
                        . '<li>' . $this->l('A photocopy of your rifled long-range firearm permit (type D)', 'alsernetforms', $iso) . '</li>'
                        . '</ul>';
                    break;

                case 'escopeta':
                    $trans_remember = strtr(
                        $this->l(
                            '[b]REMEMBER:[/b] In order to ship your weapon, we need you to send us the following documentation:',
                            'alsernetforms',
                            $iso
                        ),
                        ['[b]' => '<strong>', '[/b]' => '</strong>']
                    );

                    $trans_list = '<ul style="padding-left: 20px; margin: 8px 0;">'
                        . '<li>' . $this->l('A photocopy of your ID (both sides)', 'alsernetforms', $iso) . '</li>'
                        . '<li>' . $this->l('A photocopy of a shotgun license (type E)', 'alsernetforms', $iso) . '</li>'
                        . '</ul>';
                    break;

                case 'dni':
                    $trans_remember = strtr(
                        $this->l(
                            '[b]REMEMBER:[/b] In order to ship your weapon, we need you to send us the following documentation:',
                            'alsernetforms',
                            $iso
                        ),
                        ['[b]' => '<strong>', '[/b]' => '</strong>']
                    );

                    $trans_list = '<ul style="padding-left: 20px; margin: 8px 0;">'
                        . '<li>' . $this->l('A photocopy of your ID (both sides)', 'alsernetforms', $iso) . '</li>'
                        . '</ul>';

                    break;

                default:
                    $trans_remember = strtr(
                        $this->l(
                            '[b]REMEMBER:[/b] In order to ship your air rifle, you must provide us with a copy of your passport or driving licence (both sides if it\'s a card).',
                            'alsernetforms',
                            $iso
                        ),
                        ['[b]' => '<strong>', '[/b]' => '</strong>']
                    );
                    $trans_list = '';
                    break;
            }


             $trans_instruction = $this->l('Please click on the following link and follow the instructions:', 'alsernetforms', $iso);
             $trans_upload = $this->l('Upload documentation', 'alsernetforms', $iso);

        $template = '

                    <p >' . $trans_remember . '</p>
                    ' . $trans_list . '
                    <p ><strong>' . $trans_instruction . '</strong> <a href="' . $documents_url . '" target="_blank" style="color: #90bb13; text-decoration: underline;">' . $trans_upload . '</a></p>
                    ';

        return $template;

    }

    public function verificationDniDocuments(string $order): array
    {
        $apiManager = new ApiManager();
        $response = $apiManager->sendRequest('POST', 'api/documents', [
            'action' => 'verification',
            'order'  => $order,
        ], 'documents');

        return [
            'status' => $response['response']['status'] ?? 'failed',
            'type'   => $response['response']['data']['type'] ?? null,
            'upload' => $response['response']['data']['can_upload'] ?? null,
        ];
    }

    public function getTradeDoublerPixels(): array
    {
        $presentedOrder = $this->order_presenter->present($this);

        if (!isset($presentedOrder['products']) || !is_array($presentedOrder['products'])) {
            return [];
        }

        $totalTextile = 0;
        $totalNonTextile = 0;
        $pixels = [];

        foreach ($presentedOrder['products'] as $product) {
            $price = (float) ($product['total_price_tax_excl'] ?? 0.0);
            $isTextile = in_array($product['id_category_default'], _PSALV_TRADEDOUBLER_CATEGORIAS_ROPA_CALZADO);

            if ($isTextile) {
                $totalTextile += $price;
            } else {
                $totalNonTextile += $price;
            }
        }

        if ($totalTextile > 0) {
            $pixels[] = $this->buildPixel(_PSALV_TRADEDOUBLER_EVENTID_TEXTIL, $totalTextile);
        }

        if ($totalNonTextile > 0) {
            $pixels[] = $this->buildPixel(_PSALV_TRADEDOUBLER_EVENTID_RESTO, $totalNonTextile);
        }

        return $pixels;
    }

    protected function buildPixel($eventId, $amount): array
    {
        return [
            'eventID'       => $eventId,
            'TDOrderValue'  => number_format($amount, 2, '.', ''),
            'TDOrderNumber' => $this->id,
            'TDCurrency'    => 'EUR',
        ];
    }

    public static function getCustomerOrders($id_customer, $show_hidden_status = false, Context $context = null)
    {
        $orders = parent::getCustomerOrders($id_customer, $show_hidden_status, $context);
        $module = 'AddisDemoday';

        foreach ($orders as $key => $order) {
            if (!empty($order['module']) && strtolower($order['module']) == strtolower($module)) {
                unset($orders[$key]);
            }
        }

        return $orders;
    }

    public static function getByReferenceAndEmail($reference, $email)
    {
        $sql = '
          SELECT id_order
          FROM `' . _DB_PREFIX_ . 'orders` o
          LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (o.`id_customer` = c.`id_customer`)
          WHERE o.`id_order` = \'' . pSQL($reference) . '\' AND c.`email` = \'' . pSQL($email) . '\'
        ';

        $id = (int) Db::getInstance()->getValue($sql);
        return new Order($id);
    }

    public static function getTracking($id)
    {
        return Db::getInstance()->ExecuteS("
            SELECT aaot.fenvio, mana.description, aaot.codtracking, aaot.url
            FROM " . _DB_PREFIX_ . "alsernet_orders_tracking aaot
            LEFT JOIN " . _DB_PREFIX_ . "alsernet_orders_carrier_management mana
                ON mana.management = aaot.id_carrier_management
            WHERE mana.id_lang = 1 AND aaot.id_order = " . (int)$id . "
            ORDER BY aaot.fenvio DESC
        ");
    }

    public function getCurrentOrderState($id_lang = null)
    {
        if ($this->current_state) {
            return new OrderState($this->current_state, $id_lang);
        }

        return null;
    }

    public function getOrderShippingTracking(Order $order, int $langId): array
    {
        $sql = '
            SELECT t.url, t.codtracking, t.fenvio, mana.description AS show_name
            FROM ' . _DB_PREFIX_ . 'alsernet_orders_tracking t
            LEFT JOIN ' . _DB_PREFIX_ . 'alsernet_orders_carrier_management mana ON mana.management = t.id_carrier_management
            WHERE mana.id_lang = ' . (int)$langId . ' AND t.id_order = ' . (int)$order->id . '
            ORDER BY t.fenvio DESC
        ';

        $trackingRows = Db::getInstance()->executeS($sql);

        $shipping = ['all' => [], 'latest' => null];

        foreach ($trackingRows as $line) {
            if (!empty($line['codtracking'])) {
                $shipping['all'][] = [
                    'fenvio'     => Tools::displayDate($line['fenvio']),
                    'description'=> $line['show_name'],
                    'codtracking'=> $line['codtracking'],
                    'url'        => $line['url'],
                ];
            }
        }

        if (!empty($shipping['all'])) {
            $shipping['latest'] = $shipping['all'][0];
        }

        return $shipping;
    }

    public function l($string, $specific = false, $locale = null){

        $module =  Module::getInstanceByName("alsernetforms");

        return $this->getModuleTranslation(
            $module,
            $string,
            ($specific) ? $specific : "alsernetforms",
            null,
            false,
            $locale
        );
    }

    public  function getModuleTranslation(
        $module,
        $originalString,
        $source,
        $sprintf = null,
        $js = false,
        $locale = null,
        $fallback = true,
        $escape = true
    ) {
        global $_MODULES, $_MODULE, $_LANGADM;

        static $langCache = [];
        static $name = null;

        static $translationsMerged = [];

        $name = $module->name;

        if (null !== $locale) {
            $iso = Language::getIsoByLocale($locale);
        }

        if (empty($iso)) {
            $iso = Context::getContext()->language->iso_code;
        }

        if (!isset($translationsMerged[$name][$iso])) {
            $filesByPriority = [
                // PrestaShop 1.5 translations
                _PS_MODULE_DIR_ . $name . '/translations/' . $iso . '.php',
                // PrestaShop 1.4 translations
                _PS_MODULE_DIR_ . $name . '/' . $iso . '.php',
                // Translations in theme
                _PS_THEME_DIR_ . 'modules/' . $name . '/translations/' . $iso . '.php',
                _PS_THEME_DIR_ . 'modules/' . $name . '/' . $iso . '.php',
            ];
            foreach ($filesByPriority as $file) {
                if (file_exists($file)) {
                    include_once $file;
                    $_MODULES = !empty($_MODULES) ? array_merge($_MODULES, $_MODULE) : $_MODULE;
                }
            }
            $translationsMerged[$name][$iso] = true;
        }


        $string = preg_replace("/\\\*'/", "\'", $originalString);
        $key = md5($string);

        $cacheKey = $name . '|' . $string . '|' . $source . '|' . (int) $js . '|' . $iso;
        if (isset($langCache[$cacheKey])) {
            $ret = $langCache[$cacheKey];
        } else {
            $currentKey = strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $source) . '_' . $key;
            $defaultKey = strtolower('<{' . $name . '}prestashop>' . $source) . '_' . $key;

            if ('controller' == substr($source, -10, 10)) {
                $file = substr($source, 0, -10);
                $currentKeyFile = strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $file) . '_' . $key;
                $defaultKeyFile = strtolower('<{' . $name . '}prestashop>' . $file) . '_' . $key;
            }

            if (isset($currentKeyFile) && !empty($_MODULES[$currentKeyFile])) {
                $ret = stripslashes($_MODULES[$currentKeyFile]);
            } elseif (isset($defaultKeyFile) && !empty($_MODULES[$defaultKeyFile])) {
                $ret = stripslashes($_MODULES[$defaultKeyFile]);
            } elseif (!empty($_MODULES[$currentKey])) {
                $ret = stripslashes($_MODULES[$currentKey]);
            } elseif (!empty($_MODULES[$defaultKey])) {
                $ret = stripslashes($_MODULES[$defaultKey]);
            } elseif (!empty($_LANGADM)) {
                // if translation was not found in module, look for it in AdminController or Helpers
                $ret = stripslashes(Translate::getGenericAdminTranslation($string, $key, $_LANGADM));
            } else {
                $ret = stripslashes($string);
            }

            if (
                $sprintf !== null &&
                (!is_array($sprintf) || !empty($sprintf)) &&
                !(count($sprintf) === 1 && isset($sprintf['legacy']))
            ) {
                $ret = Translate::checkAndReplaceArgs($ret, $sprintf);
            }

            if ($js) {
                $ret = addslashes($ret);
            } elseif ($escape) {
                $ret = htmlspecialchars($ret, ENT_COMPAT, 'UTF-8');
            }

            if ($sprintf === null) {
                $langCache[$cacheKey] = $ret;
            }
        }

        if (!is_array($sprintf) && null !== $sprintf) {
            $sprintf_for_trans = [$sprintf];
        } elseif (null === $sprintf) {
            $sprintf_for_trans = [];
        } else {
            $sprintf_for_trans = $sprintf;
        }

        if ($ret === $originalString && $fallback) {
            $ret = Context::getContext()->getTranslator()->trans($originalString, $sprintf_for_trans, null, $locale);
        }

        return $ret;
    }

 }
