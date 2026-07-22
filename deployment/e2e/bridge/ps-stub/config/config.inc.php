<?php

/**
 * PrestaShop STUB bootstrap — E2E staging environment (deployment/e2e).
 *
 * Replaces PrestaShop's config/config.inc.php so the alsernetbridge module
 * endpoints (api.php, track.php, health.php, metrics.php, cron-webhook-retry.php)
 * can run against a small MariaDB with fixtures instead of a full shop.
 *
 * Scope: implements ONLY the PS surface that the module actually uses
 * (audited 2026-07): Db, Configuration, Context, Tools, Validate,
 * PrestaShopLogger, pSQL(), Customer (load + getStats), Cart (fallback path,
 * CartPresenter is intentionally absent so the module's Throwable fallback
 * kicks in), Currency, Order/OrderState/OrderHistory/OrderCarrier/Address
 * (minimal), Mail (no-op).
 *
 * NOT covered: cron.php catalog sync (requires the Alsernetbridge Module
 * class / full PS Module base) and CartPresenter-rich cart output.
 *
 * PHP 7.4 compatible.
 */

if (defined('_PS_VERSION_')) {
    return; // already bootstrapped
}

define('_PS_VERSION_', '1.7.8.11');
define('_PS_ROOT_DIR_', dirname(__DIR__));
define('_PS_MODULE_DIR_', _PS_ROOT_DIR_ . '/modules/');
define('_PS_CACHE_DIR_', '/tmp/ps-var/cache/prod/');
define('_PS_MAIL_DIR_', '/tmp/ps-mails/');
define('_DB_PREFIX_', getenv('ALSERNET_STUB_DB_PREFIX') ?: 'aalv_');

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// ---------------------------------------------------------------------------
// Db — thin PDO wrapper mimicking PS Db semantics used by the module
// ---------------------------------------------------------------------------

class Db
{
    /** @var Db|null */
    private static $instance = null;

    /** @var PDO */
    private $pdo;

    private function __construct()
    {
        $host = getenv('ALSERNET_STUB_DB_HOST') ?: 'db';
        $name = getenv('ALSERNET_STUB_DB_NAME') ?: 'bridge';
        $user = getenv('ALSERNET_STUB_DB_USER') ?: 'root';
        $pass = getenv('ALSERNET_STUB_DB_PASS') ?: '';

        $this->pdo = new PDO(
            'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4',
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    public static function getInstance($master = true)
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /** @return PDO */
    public function pdo()
    {
        return $this->pdo;
    }

    /** SELECT returning all rows (array) or false. */
    public function executeS($sql)
    {
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        return $rows === false ? false : $rows;
    }

    /** SELECT returning first row — PS appends LIMIT 1 automatically. */
    public function getRow($sql)
    {
        if (stripos($sql, 'LIMIT') === false) {
            $sql .= ' LIMIT 1';
        }
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();

        return $row === false ? false : $row;
    }

    /** SELECT returning first column of first row. */
    public function getValue($sql)
    {
        $row = $this->getRow($sql);
        if (! $row) {
            return false;
        }

        return array_shift($row);
    }

    /** Raw statement (UPDATE/DELETE/DDL). */
    public function execute($sql)
    {
        return $this->pdo->exec($sql) !== false;
    }

    /** PS-style insert: table WITHOUT prefix, assoc data. */
    public function insert($table, array $data, $nullValues = false, $useCache = true, $type = 1, $addPrefix = true)
    {
        $table = ($addPrefix ? _DB_PREFIX_ : '') . $table;
        $cols = [];
        $marks = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $cols[] = '`' . str_replace('`', '', $col) . '`';
            $marks[] = '?';
            $vals[] = $val;
        }
        $sql = 'INSERT INTO `' . $table . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $marks) . ')';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($vals);
    }

    /** PS-style update: table WITHOUT prefix, assoc data, raw where. */
    public function update($table, array $data, $where = '', $limit = 0, $nullValues = false, $useCache = true, $addPrefix = true)
    {
        $table = ($addPrefix ? _DB_PREFIX_ : '') . $table;
        $sets = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $sets[] = '`' . str_replace('`', '', $col) . '` = ?';
            $vals[] = $val;
        }
        $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $sets)
            . ($where ? ' WHERE ' . $where : '')
            . ($limit ? ' LIMIT ' . (int) $limit : '');
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($vals);
    }

    /** PS-style delete: table WITHOUT prefix, raw where. */
    public function delete($table, $where = '', $limit = 0, $useCache = true, $addPrefix = true)
    {
        $table = ($addPrefix ? _DB_PREFIX_ : '') . $table;
        $sql = 'DELETE FROM `' . $table . '`'
            . ($where ? ' WHERE ' . $where : '')
            . ($limit ? ' LIMIT ' . (int) $limit : '');

        return $this->pdo->exec($sql) !== false;
    }

    public function Insert_ID()
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function escape($string, $htmlOK = false, $bqSQL = false)
    {
        $quoted = $this->pdo->quote((string) $string);

        return substr($quoted, 1, -1);
    }
}

function pSQL($string, $htmlOK = false)
{
    return Db::getInstance()->escape($string, $htmlOK);
}

function bqSQL($string)
{
    return str_replace('`', '\`', pSQL($string));
}

// ---------------------------------------------------------------------------
// Configuration — backed by the {prefix}configuration table + static cache
// ---------------------------------------------------------------------------

class Configuration
{
    /** @var array<string,string|false>|null */
    private static $cache = null;

    private static function load()
    {
        if (self::$cache === null) {
            self::$cache = [];
            $rows = Db::getInstance()->executeS(
                'SELECT name, value FROM `' . _DB_PREFIX_ . 'configuration`'
            );
            foreach ($rows ?: [] as $row) {
                self::$cache[$row['name']] = $row['value'];
            }
        }
    }

    public static function get($key, $idLang = null, $idShopGroup = null, $idShop = null, $default = false)
    {
        self::load();

        return array_key_exists($key, self::$cache) ? self::$cache[$key] : $default;
    }

    public static function updateValue($key, $values, $html = false, $idShopGroup = null, $idShop = null)
    {
        self::load();
        $now = date('Y-m-d H:i:s');
        if (array_key_exists($key, self::$cache)) {
            Db::getInstance()->update('configuration', [
                'value' => (string) $values,
                'date_upd' => $now,
            ], 'name = "' . pSQL($key) . '"');
        } else {
            Db::getInstance()->insert('configuration', [
                'name' => pSQL($key),
                'value' => (string) $values,
                'date_add' => $now,
                'date_upd' => $now,
            ]);
        }
        self::$cache[$key] = (string) $values;

        return true;
    }

    public static function deleteByName($key)
    {
        self::load();
        Db::getInstance()->delete('configuration', 'name = "' . pSQL($key) . '"');
        unset(self::$cache[$key]);

        return true;
    }

    public static function hasKey($key, $idLang = null, $idShopGroup = null, $idShop = null)
    {
        self::load();

        return array_key_exists($key, self::$cache);
    }
}

// ---------------------------------------------------------------------------
// Tools / Validate / PrestaShopLogger
// ---------------------------------------------------------------------------

class Tools
{
    public static function getValue($key, $default = false)
    {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }

        return $default;
    }

    public static function isSubmit($submit)
    {
        return isset($_POST[$submit]) || isset($_GET[$submit]);
    }

    public static function passwdGen($length = 8, $flag = 'ALPHANUMERIC')
    {
        $chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < (int) $length; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $out;
    }

    public static function getShopDomain($http = false, $entities = false)
    {
        $domain = getenv('BRIDGE_DOMAIN') ?: ($_SERVER['HTTP_HOST'] ?? 'bridge');

        return ($http ? 'http://' : '') . $domain;
    }

    public static function getShopDomainSsl($http = false, $entities = false)
    {
        // The stub serves plain HTTP inside the compose network.
        return self::getShopDomain($http, $entities);
    }

    public static function getAdminTokenLite($tab, $context = null)
    {
        return 'e2e-stub-token';
    }
}

class Validate
{
    public static function isLoadedObject($object)
    {
        return is_object($object) && ! empty($object->id);
    }

    public static function isEmail($email)
    {
        return (bool) filter_var((string) $email, FILTER_VALIDATE_EMAIL);
    }

    public static function isUnsignedId($id)
    {
        return (string) (int) $id === (string) $id && (int) $id >= 0;
    }
}

class PrestaShopLogger
{
    public static function addLog(
        $message,
        $severity = 1,
        $errorCode = null,
        $objectType = null,
        $objectId = null,
        $allowDuplicate = false,
        $idEmployee = null
    ) {
        error_log('[alsernetbridge-e2e][sev=' . (int) $severity . '] ' . $message);

        return true;
    }
}

// ---------------------------------------------------------------------------
// StubObjectModel — shared "load row into public props" base
// ---------------------------------------------------------------------------

abstract class StubObjectModel
{
    /** @var int|null */
    public $id = null;

    /** Table name WITHOUT prefix. */
    protected static $stubTable = '';

    /** Primary key column. */
    protected static $stubPk = '';

    public function __construct($id = null)
    {
        if (! $id) {
            return;
        }
        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . static::$stubTable . '`
             WHERE `' . static::$stubPk . '` = ' . (int) $id
        );
        if ($row) {
            foreach ($row as $col => $val) {
                $this->{$col} = $val;
            }
            $this->id = (int) $row[static::$stubPk];
        }
    }

    public function update($nullValues = false)
    {
        return true; // write-back not needed for the E2E contract
    }

    public function add($autoDate = true, $nullValues = false)
    {
        return true;
    }

    public function save()
    {
        return true;
    }
}

// ---------------------------------------------------------------------------
// Customer / Cart / Currency / Order family / Address / Mail
// ---------------------------------------------------------------------------

class Customer extends StubObjectModel
{
    protected static $stubTable = 'customer';
    protected static $stubPk = 'id_customer';

    public function getStats()
    {
        $row = Db::getInstance()->getRow(
            'SELECT COUNT(*) AS nb_orders,
                    COALESCE(SUM(total_paid_real), 0) AS total_orders,
                    MAX(date_add) AS last_visit
             FROM `' . _DB_PREFIX_ . 'orders`
             WHERE id_customer = ' . (int) $this->id . ' AND valid = 1'
        );

        return $row ?: ['nb_orders' => 0, 'total_orders' => 0, 'last_visit' => null];
    }
}

class Cart extends StubObjectModel
{
    const ONLY_PRODUCTS = 1;
    const ONLY_DISCOUNTS = 2;
    const BOTH = 3;
    const BOTH_WITHOUT_SHIPPING = 4;
    const ONLY_SHIPPING = 5;
    const ONLY_WRAPPING = 6;

    protected static $stubTable = 'cart';
    protected static $stubPk = 'id_cart';

    /** Approximate default VAT applied by the stub when computing tax-incl prices. */
    const STUB_TAX_RATE = 21.0;

    public function isVirtualCart()
    {
        return false;
    }

    /** Alvarez override — fitting services. None in the stub. */
    public function getProductsFitting()
    {
        return [];
    }

    public function getProducts($refresh = false)
    {
        $idLang = (int) ($this->id_lang ?: 1);
        $rows = Db::getInstance()->executeS(
            'SELECT cp.id_product, cp.id_product_attribute, cp.quantity AS cart_quantity,
                    p.reference, p.ean13, p.price, pl.name, pl.link_rewrite
             FROM `' . _DB_PREFIX_ . 'cart_product` cp
             INNER JOIN `' . _DB_PREFIX_ . 'product` p ON p.id_product = cp.id_product
             LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                 ON pl.id_product = cp.id_product AND pl.id_lang = ' . $idLang . '
             WHERE cp.id_cart = ' . (int) $this->id
        );

        $products = [];
        foreach ($rows ?: [] as $r) {
            $priceExcl = (float) $r['price'];
            $priceWt = round($priceExcl * (1 + self::STUB_TAX_RATE / 100), 6);
            $qty = (int) $r['cart_quantity'];
            $products[] = [
                'id_product' => (int) $r['id_product'],
                'id_product_attribute' => (int) $r['id_product_attribute'],
                'reference' => (string) $r['reference'],
                'ean13' => (string) ($r['ean13'] ?? ''),
                'name' => (string) ($r['name'] ?? ''),
                'attributes' => null,
                'cart_quantity' => $qty,
                'price' => $priceExcl,
                'price_wt' => $priceWt,
                'price_without_reduction' => $priceWt,
                'reduction' => 0.0,
                'has_discount' => false,
                'total_wt' => round($priceWt * $qty, 2),
                'rate' => self::STUB_TAX_RATE,
                'is_virtual' => false,
                'id_image' => 0,
                'link_rewrite' => (string) ($r['link_rewrite'] ?? ''),
                'link' => null,
            ];
        }

        return $products;
    }

    public function getOrderTotal($withTaxes = true, $type = self::BOTH, $products = null, $idCarrier = null)
    {
        if ($type === self::ONLY_SHIPPING || $type === self::ONLY_DISCOUNTS || $type === self::ONLY_WRAPPING) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($this->getProducts() as $p) {
            $unit = $withTaxes ? $p['price_wt'] : $p['price'];
            $total += $unit * $p['cart_quantity'];
        }

        return round($total, 2);
    }

    public function getCartRules($filter = 3)
    {
        return [];
    }
}

class Currency extends StubObjectModel
{
    protected static $stubTable = 'currency';
    protected static $stubPk = 'id_currency';
}

class Order extends StubObjectModel
{
    protected static $stubTable = 'orders';
    protected static $stubPk = 'id_order';

    public static function getCustomerOrders($idCustomer, $showHiddenStatus = false, $context = null)
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'orders`
             WHERE id_customer = ' . (int) $idCustomer . '
             ORDER BY date_add DESC'
        ) ?: [];
    }

    public function getIdOrderCarrier()
    {
        return (int) Db::getInstance()->getValue(
            'SELECT id_order_carrier FROM `' . _DB_PREFIX_ . 'order_carrier`
             WHERE id_order = ' . (int) $this->id
        );
    }

    public function getInvoicesCollection()
    {
        return [];
    }
}

class OrderState extends StubObjectModel
{
    protected static $stubTable = 'order_state';
    protected static $stubPk = 'id_order_state';

    /** @var string|null */
    public $name = null;

    public function __construct($id = null, $idLang = null)
    {
        parent::__construct($id);
        if ($this->id && $idLang) {
            $this->name = (string) Db::getInstance()->getValue(
                'SELECT name FROM `' . _DB_PREFIX_ . 'order_state_lang`
                 WHERE id_order_state = ' . (int) $this->id . ' AND id_lang = ' . (int) $idLang
            );
        }
    }
}

class OrderHistory extends StubObjectModel
{
    protected static $stubTable = 'order_history';
    protected static $stubPk = 'id_order_history';

    public $id_order = 0;
    public $id_employee = 0;
    public $id_order_state = 0;

    public function changeIdOrderState($newOrderStateId, $order, $useExistingPayment = false)
    {
        $idOrder = is_object($order) ? (int) $order->id : (int) $order;
        $this->id_order = $idOrder;
        $this->id_order_state = (int) $newOrderStateId;
        Db::getInstance()->insert('order_history', [
            'id_order' => $idOrder,
            'id_employee' => 0,
            'id_order_state' => (int) $newOrderStateId,
            'date_add' => date('Y-m-d H:i:s'),
        ]);
        Db::getInstance()->update('orders', [
            'current_state' => (int) $newOrderStateId,
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_order = ' . $idOrder);

        return true;
    }

    public function addWithemail($autodate = true, $templateVars = false)
    {
        return true;
    }
}

class OrderCarrier extends StubObjectModel
{
    protected static $stubTable = 'order_carrier';
    protected static $stubPk = 'id_order_carrier';
}

class Address extends StubObjectModel
{
    protected static $stubTable = 'address';
    protected static $stubPk = 'id_address';
}

class Mail
{
    const TYPE_HTML = 2;
    const TYPE_TEXT = 1;
    const TYPE_BOTH = 3;

    public static function Send(
        $idLang,
        $template,
        $subject,
        $templateVars,
        $to,
        $toName = null,
        $from = null,
        $fromName = null,
        $fileAttachment = null,
        $modeSmtp = null,
        $templatePath = null,
        $die = false,
        $idShop = null,
        $bcc = null,
        $replyTo = null
    ) {
        error_log('[alsernetbridge-e2e] Mail::Send stub — template=' . $template . ' to=' . (is_array($to) ? implode(',', $to) : $to));

        return true;
    }
}

// ---------------------------------------------------------------------------
// Context — language / shop / currency / link singletons
// ---------------------------------------------------------------------------

class StubLanguage
{
    public $id = 1;
    public $iso_code = 'es';
    public $locale = 'es-ES';
}

class StubShop
{
    public $id = 1;
    public $id_shop_group = 1;
    public $name = 'Alsernet E2E';

    public function getBaseURL($autoSecure = false, $addBaseUri = true)
    {
        $base = getenv('BRIDGE_BASE_URL') ?: 'http://bridge/';

        return rtrim($base, '/') . '/';
    }
}

class StubLink
{
    public function getImageLink($name, $idImage, $type = null)
    {
        $base = getenv('BRIDGE_BASE_URL') ?: 'http://bridge/';

        return rtrim($base, '/') . '/img/p/' . (int) $idImage . ($type ? '-' . $type : '') . '.jpg';
    }

    public function getProductLink($product, $alias = null)
    {
        $base = getenv('BRIDGE_BASE_URL') ?: 'http://bridge/';
        $id = is_object($product) ? (int) $product->id : (int) $product;

        return rtrim($base, '/') . '/product/' . $id;
    }
}

class Context
{
    /** @var Context|null */
    private static $instance = null;

    /** @var StubLanguage */
    public $language;

    /** @var StubShop */
    public $shop;

    /** @var Currency|null */
    public $currency;

    /** @var StubLink */
    public $link;

    public $cookie = null;
    public $customer = null;
    public $employee = null;
    public $cart = null;

    public static function getContext()
    {
        if (self::$instance === null) {
            $ctx = new self();
            $ctx->language = new StubLanguage();
            $idLangCfg = (int) Configuration::get('PS_LANG_DEFAULT');
            if ($idLangCfg > 0) {
                $ctx->language->id = $idLangCfg;
            }
            $ctx->shop = new StubShop();
            $ctx->link = new StubLink();
            $defaultCurrency = new Currency(1);
            $ctx->currency = $defaultCurrency->id ? $defaultCurrency : null;
            self::$instance = $ctx;
        }

        return self::$instance;
    }
}
