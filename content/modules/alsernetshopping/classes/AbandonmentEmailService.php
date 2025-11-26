<?php

namespace AlsernetShopping;

use Context;
use Db;
use DbQuery;
use Mail;
use Configuration;
use Customer;
use Cart;
use Tools;
use Language;
use Shop;
use Validate;
use PrestaShopLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Servicio de gestión de emails para carritos abandonados
 * 
 * Funcionalidades:
 * - Plantillas de email personalizadas
 * - Envío programado de recordatorios
 * - Seguimiento de apertura y clicks
 * - A/B Testing de plantillas
 * - Integración con sistema de descuentos
 */
class AbandonmentEmailService
{
    /** @var Context */
    private $context;
    
    /** @var AbandonedCartManager */
    private $cartManager;
    
    /** @var array */
    private $config;
    
    // Tipos de email
    const EMAIL_FIRST_REMINDER = 'first_reminder';
    const EMAIL_SECOND_REMINDER = 'second_reminder';
    const EMAIL_FINAL_REMINDER = 'final_reminder';
    const EMAIL_WELCOME_BACK = 'welcome_back';
    const EMAIL_DISCOUNT_OFFER = 'discount_offer';
    const EMAIL_STOCK_ALERT = 'stock_alert';
    
    // Estados de envío
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_OPENED = 'opened';
    const STATUS_CLICKED = 'clicked';
    const STATUS_BOUNCED = 'bounced';
    const STATUS_FAILED = 'failed';
    
    public function __construct(Context $context = null)
    {
        $this->context = $context ?: Context::getContext();
        $this->cartManager = new AbandonedCartManager($this->context);
        $this->loadConfiguration();
    }
    
    /**
     * Enviar email de carrito abandonado
     */
    public function sendAbandonmentEmail(int $abandonmentId, string $emailType, array $options = []): array
    {
        try {
            // Obtener datos del abandono
            $abandonment = $this->getAbandonmentData($abandonmentId);
            if (!$abandonment) {
                return ['success' => false, 'error' => 'Abandonment not found'];
            }
            
            // Verificar si se puede enviar email
            $canSend = $this->canSendEmail($abandonment, $emailType);
            if (!$canSend['allowed']) {
                return ['success' => false, 'error' => $canSend['reason']];
            }
            
            // Obtener plantilla de email
            $template = $this->getEmailTemplate($emailType, $abandonment['id_lang']);
            if (!$template) {
                return ['success' => false, 'error' => 'Email template not found'];
            }
            
            // Preparar variables para la plantilla
            $templateVars = $this->prepareTemplateVariables($abandonment, $emailType, $options);
            
            // Generar contenido del email
            $emailContent = $this->renderEmailTemplate($template, $templateVars);
            
            // Generar enlace de tracking
            $trackingToken = $this->generateTrackingToken($abandonmentId, $emailType);
            $emailContent = $this->addTrackingToEmail($emailContent, $trackingToken);
            
            // Enviar email
            $emailResult = $this->sendEmail(
                $abandonment['email'],
                $abandonment['customer_name'],
                $template['subject'],
                $emailContent,
                $abandonment['id_lang']
            );
            
            if ($emailResult) {
                // Registrar envío
                $this->registerEmailSent($abandonmentId, $emailType, $trackingToken, $abandonment['email'], $templateVars);
                
                // Programar siguiente email si corresponde
                $this->scheduleNextEmail($abandonmentId, $emailType);
                
                return [
                    'success' => true,
                    'email_type' => $emailType,
                    'tracking_token' => $trackingToken,
                    'scheduled_next' => $this->getNextScheduledEmail($emailType)
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to send email'];
            }
            
        } catch (Exception $e) {
            PrestaShopLogger::addLog('AbandonmentEmailService error: ' . $e->getMessage(), 3);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Procesar emails programados
     */
    public function processPendingEmails(int $limit = 50): array
    {
        $results = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0
        ];
        
        $pendingEmails = $this->getPendingEmails($limit);
        
        foreach ($pendingEmails as $emailQueue) {
            $results['processed']++;
            
            $sendResult = $this->sendAbandonmentEmail(
                $emailQueue['id_abandoned_cart'],
                $emailQueue['email_type'],
                json_decode($emailQueue['email_data'], true) ?: []
            );
            
            if ($sendResult['success']) {
                $this->markEmailAsSent($emailQueue['id']);
                $results['sent']++;
            } else {
                $this->markEmailAsFailed($emailQueue['id'], $sendResult['error']);
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Obtener plantilla de email
     */
    public function getEmailTemplate(string $emailType, int $langId): ?array
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from(_DB_PREFIX_ . 'alsernetshopping_email_templates')
            ->where('template_type = "' . pSQL($emailType) . '"')
            ->where('id_lang = ' . (int)$langId)
            ->where('is_active = 1');
            
        $template = Db::getInstance()->getRow($sql);
        
        if (!$template && $langId != Configuration::get('PS_LANG_DEFAULT')) {
            // Fallback al idioma por defecto
            return $this->getEmailTemplate($emailType, Configuration::get('PS_LANG_DEFAULT'));
        }
        
        return $template ?: null;
    }
    
    /**
     * Preparar variables para la plantilla
     */
    private function prepareTemplateVariables(array $abandonment, string $emailType, array $options): array
    {
        $cart = new Cart($abandonment['id_cart']);
        $customer = new Customer($abandonment['id_customer']);
        $products = $cart->getProducts();
        
        // Obtener contexto de idioma
        $langContext = $this->getLanguageContext($abandonment['id_lang']);
        
        // Establecer contexto de idioma para formateo
        $oldLang = $this->context->language;
        $this->context->language = new Language($langContext['id_lang']);
        
        // Variables básicas
        $vars = [
            // Datos del cliente
            'customer_name' => $customer->firstname . ' ' . $customer->lastname,
            'customer_firstname' => $customer->firstname,
            'customer_email' => $customer->email,
            
            // Datos del carrito
            'cart_id' => $cart->id,
            'cart_total' => Tools::displayPrice($cart->getOrderTotal(), null, $langContext['id_lang']),
            'cart_total_raw' => $cart->getOrderTotal(),
            'products_count' => count($products),
            'abandonment_date' => $this->formatDateForLanguage(
                $abandonment['abandonment_timestamp'], 
                $langContext
            ),
            
            // Enlaces
            'cart_recovery_url' => $this->generateRecoveryUrl($abandonment['id_abandoned_cart']),
            'shop_url' => $this->context->shop->getBaseURL(true),
            'unsubscribe_url' => $this->generateUnsubscribeUrl($abandonment['id_abandoned_cart']),
            
            // Productos del carrito (formateados según idioma)
            'products' => $this->formatProductsForEmail($products, $langContext['id_lang']),
            
            // Información de la tienda (multiidioma)
            'shop_name' => $this->getShopNameForLanguage($langContext['id_lang']),
            'shop_email' => Configuration::get('PS_SHOP_EMAIL'),
            'shop_logo' => $this->getShopLogoUrl(),
            
            // Contexto de idioma
            'language_context' => $langContext,
        ];
        
        // Variables específicas por tipo de email (formateadas según idioma)
        switch ($emailType) {
            case self::EMAIL_DISCOUNT_OFFER:
                $discountCode = $options['discount_code'] ?? $this->generateDiscountCode($abandonment['id_abandoned_cart']);
                $vars['discount_code'] = $discountCode;
                $vars['discount_percentage'] = $options['discount_percentage'] ?? 10;
                $vars['discount_expiry'] = $this->formatDateForLanguage(
                    date('Y-m-d H:i:s', time() + (24 * 60 * 60)), 
                    $langContext
                );
                break;
                
            case self::EMAIL_STOCK_ALERT:
                $vars['low_stock_products'] = $this->getLowStockProducts($products, $langContext['id_lang']);
                break;
                
            case self::EMAIL_FINAL_REMINDER:
                $vars['urgency_message'] = $this->getTranslatedString('Last chance to complete your purchase!', $langContext['id_lang']);
                break;
        }
        
        // Información de urgencia
        $vars['hours_since_abandonment'] = $this->getHoursSinceAbandonment($abandonment['abandonment_timestamp']);
        $vars['is_urgent'] = $vars['hours_since_abandonment'] > 24;
        
        // Restaurar contexto de idioma anterior
        $this->context->language = $oldLang;
        
        return $vars;
    }
    
    /**
     * Renderizar plantilla de email
     */
    private function renderEmailTemplate(array $template, array $vars): string
    {
        $content = $template['html_content'];
        
        // Reemplazar variables simples
        foreach ($vars as $key => $value) {
            if (is_scalar($value)) {
                $content = str_replace('{' . $key . '}', $value, $content);
            }
        }
        
        // Renderizar lista de productos
        if (isset($vars['products']) && is_array($vars['products'])) {
            $productsHtml = $this->renderProductsList($vars['products']);
            $content = str_replace('{products_list}', $productsHtml, $content);
        }
        
        // Renderizar productos con stock bajo
        if (isset($vars['low_stock_products']) && is_array($vars['low_stock_products'])) {
            $lowStockHtml = $this->renderLowStockProducts($vars['low_stock_products']);
            $content = str_replace('{low_stock_products}', $lowStockHtml, $content);
        }
        
        return $content;
    }
    
    /**
     * Renderizar lista de productos para email
     */
    private function renderProductsList(array $products): string
    {
        $html = '<table style="width: 100%; border-collapse: collapse;">';
        
        foreach ($products as $product) {
            $html .= '<tr style="border-bottom: 1px solid #eee;">';
            $html .= '<td style="padding: 15px; width: 80px;">';
            $html .= '<img src="' . $product['image'] . '" style="width: 60px; height: 60px; object-fit: cover;" alt="' . htmlspecialchars($product['name']) . '">';
            $html .= '</td>';
            $html .= '<td style="padding: 15px;">';
            $html .= '<h3 style="margin: 0 0 5px 0; font-size: 16px;">' . htmlspecialchars($product['name']) . '</h3>';
            $html .= '<p style="margin: 0; color: #666;">Cantidad: ' . $product['quantity'] . '</p>';
            $html .= '</td>';
            $html .= '<td style="padding: 15px; text-align: right;">';
            $html .= '<strong style="font-size: 16px;">' . $product['price'] . '</strong>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        return $html;
    }
    
    /**
     * Generar URL de recuperación del carrito
     */
    private function generateRecoveryUrl(int $abandonmentId): string
    {
        $token = md5($abandonmentId . Configuration::get('PS_COOKIE_KEY'));
        
        return $this->context->link->getModuleLink(
            'alsernetshopping',
            'recovery',
            [
                'id' => $abandonmentId,
                'token' => $token
            ]
        );
    }
    
    /**
     * Generar URL de unsubscribe
     */
    private function generateUnsubscribeUrl(int $abandonmentId): string
    {
        $token = md5($abandonmentId . 'unsubscribe' . Configuration::get('PS_COOKIE_KEY'));
        
        return $this->context->link->getModuleLink(
            'alsernetshopping',
            'routes',
            [
                'modalitie' => 'abandonment',
                'action' => 'email_unsubscribe',
                'abandonment_id' => $abandonmentId,
                'token' => $token
            ]
        );
    }
    
    /**
     * Obtener URL del logo de la tienda
     */
    private function getShopLogoUrl(): string
    {
        $logoPath = Configuration::get('PS_LOGO');
        if ($logoPath) {
            return $this->context->shop->getBaseURL(true) . 'img/' . $logoPath;
        }
        
        return $this->context->shop->getBaseURL(true) . 'img/logo.jpg';
    }
    
    /**
     * Obtener horas desde el abandono
     */
    private function getHoursSinceAbandonment(string $timestamp): int
    {
        $abandonTime = strtotime($timestamp);
        $currentTime = time();
        
        return (int)floor(($currentTime - $abandonTime) / 3600);
    }
    
    /**
     * Generar código de descuento
     */
    private function generateDiscountCode(int $abandonmentId): string
    {
        return 'CART' . $abandonmentId . rand(100, 999);
    }
    
    /**
     * Generar token de tracking para emails
     */
    private function generateTrackingToken(int $abandonmentId, string $emailType): string
    {
        return md5($abandonmentId . $emailType . time() . uniqid());
    }
    
    /**
     * Registrar envío de email
     */
    private function registerEmailSent(int $abandonmentId, string $emailType, string $trackingToken, string $recipientEmail, array $templateVars): bool
    {
        return Db::getInstance()->insert(_DB_PREFIX_ . 'alsernetshopping_email_log', [
            'id_abandoned_cart' => $abandonmentId,
            'email_type' => pSQL($emailType),
            'tracking_token' => pSQL($trackingToken),
            'recipient_email' => pSQL($recipientEmail),
            'status' => pSQL(self::STATUS_SENT),
            'template_vars' => pSQL(json_encode($templateVars)),
            'sent_at' => date('Y-m-d H:i:s'),
            'date_add' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Crear plantillas por defecto para todos los idiomas activos
     */
    public function createDefaultTemplates(int $langId = null): bool
    {
        $success = true;
        
        // Si se especifica un idioma, crear solo para ese idioma
        if ($langId) {
            return $this->createTemplatesForLanguage($langId);
        }
        
        // Obtener todos los idiomas activos
        $languages = Language::getLanguages(true);
        
        foreach ($languages as $language) {
            $result = $this->createTemplatesForLanguage($language['id_lang']);
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Crear plantillas para un idioma específico
     */
    private function createTemplatesForLanguage(int $langId): bool
    {
        $templates = $this->getDefaultSubjectsForLanguage($langId);
        $success = true;
        
        foreach ($templates as $type => $template) {
            // Verificar si ya existe la plantilla para este idioma
            $existing = $this->getEmailTemplate($type, $langId);
            if ($existing) {
                continue; // Skip if already exists
            }
            
            $result = Db::getInstance()->insert(_DB_PREFIX_ . 'alsernetshopping_email_templates', [
                'template_type' => pSQL($type),
                'id_lang' => (int)$langId,
                'subject' => pSQL($template['subject']),
                'html_content' => pSQL($template['html_content']),
                'is_active' => 1,
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s')
            ]);
            
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Obtener sujetos por defecto según el idioma
     */
    private function getDefaultSubjectsForLanguage(int $langId): array
    {
        $subjects = [
            // Español (1)
            1 => [
                self::EMAIL_FIRST_REMINDER => '¿Olvidaste algo en tu carrito?',
                self::EMAIL_SECOND_REMINDER => 'Tus productos te esperan - ¡Completa tu compra!',
                self::EMAIL_FINAL_REMINDER => '¡Última oportunidad! Tu carrito expira pronto',
                self::EMAIL_DISCOUNT_OFFER => '¡Oferta especial solo para ti! {discount_percentage}% de descuento',
                self::EMAIL_STOCK_ALERT => '⚠️ Stock limitado en tu carrito',
                self::EMAIL_WELCOME_BACK => '🌟 ¡Bienvenido/a de vuelta!'
            ],
            // Inglés (2)
            2 => [
                self::EMAIL_FIRST_REMINDER => 'Did you forget something in your cart?',
                self::EMAIL_SECOND_REMINDER => 'Your products are waiting - Complete your purchase!',
                self::EMAIL_FINAL_REMINDER => 'Last chance! Your cart expires soon',
                self::EMAIL_DISCOUNT_OFFER => 'Special offer just for you! {discount_percentage}% off',
                self::EMAIL_STOCK_ALERT => '⚠️ Limited stock in your cart',
                self::EMAIL_WELCOME_BACK => '🌟 Welcome back!'
            ],
            // Francés (3)
            3 => [
                self::EMAIL_FIRST_REMINDER => 'Avez-vous oublié quelque chose dans votre panier ?',
                self::EMAIL_SECOND_REMINDER => 'Vos produits vous attendent - Finalisez votre achat !',
                self::EMAIL_FINAL_REMINDER => 'Dernière chance ! Votre panier expire bientôt',
                self::EMAIL_DISCOUNT_OFFER => 'Offre spéciale rien que pour vous ! {discount_percentage}% de réduction',
                self::EMAIL_STOCK_ALERT => '⚠️ Stock limité dans votre panier',
                self::EMAIL_WELCOME_BACK => '🌟 Bon retour !'
            ],
            // Italiano (4)
            4 => [
                self::EMAIL_FIRST_REMINDER => 'Hai dimenticato qualcosa nel tuo carrello?',
                self::EMAIL_SECOND_REMINDER => 'I tuoi prodotti ti aspettano - Completa il tuo acquisto!',
                self::EMAIL_FINAL_REMINDER => 'Ultima possibilità! Il tuo carrello scade presto',
                self::EMAIL_DISCOUNT_OFFER => 'Offerta speciale solo per te! {discount_percentage}% di sconto',
                self::EMAIL_STOCK_ALERT => '⚠️ Stock limitato nel tuo carrello',
                self::EMAIL_WELCOME_BACK => '🌟 Bentornato/a!'
            ],
            // Portugués (5)
            5 => [
                self::EMAIL_FIRST_REMINDER => 'Esqueceu algo no seu carrinho?',
                self::EMAIL_SECOND_REMINDER => 'Seus produtos estão esperando - Complete sua compra!',
                self::EMAIL_FINAL_REMINDER => 'Última chance! Seu carrinho expira em breve',
                self::EMAIL_DISCOUNT_OFFER => 'Oferta especial só para você! {discount_percentage}% de desconto',
                self::EMAIL_STOCK_ALERT => '⚠️ Stock limitado no seu carrinho',
                self::EMAIL_WELCOME_BACK => '🌟 Bem-vindo/a de volta!'
            ],
            // Alemán (6)
            6 => [
                self::EMAIL_FIRST_REMINDER => 'Haben Sie etwas in Ihrem Warenkorb vergessen?',
                self::EMAIL_SECOND_REMINDER => 'Ihre Produkte warten - Schließen Sie Ihren Kauf ab!',
                self::EMAIL_FINAL_REMINDER => 'Letzte Chance! Ihr Warenkorb läuft bald ab',
                self::EMAIL_DISCOUNT_OFFER => 'Sonderangebot nur für Sie! {discount_percentage}% Rabatt',
                self::EMAIL_STOCK_ALERT => '⚠️ Begrenzter Vorrat in Ihrem Warenkorb',
                self::EMAIL_WELCOME_BACK => '🌟 Willkommen zurück!'
            ]
        ];
        
        // Obtener sujetos para el idioma específico o fallback al español
        $langSubjects = $subjects[$langId] ?? $subjects[1];
        
        $templates = [];
        foreach ($langSubjects as $type => $subject) {
            $templates[$type] = [
                'subject' => $subject,
                'html_content' => $this->getDefaultTemplate($type)
            ];
        }
        
        return $templates;
    }
    
    // Métodos auxiliares
    
    private function canSendEmail(array $abandonment, string $emailType): array
    {
        // Verificar si el email está habilitado
        if (!$this->config['emails_enabled']) {
            return ['allowed' => false, 'reason' => 'Email system disabled'];
        }
        
        // Verificar si el carrito ya fue recuperado
        if ($abandonment['is_recovered']) {
            return ['allowed' => false, 'reason' => 'Cart already recovered'];
        }
        
        // Verificar límites diarios
        $dailyCount = $this->getDailyEmailCount($abandonment['email']);
        if ($dailyCount >= $this->config['max_daily_emails']) {
            return ['allowed' => false, 'reason' => 'Daily email limit reached'];
        }
        
        return ['allowed' => true];
    }
    
    private function formatProductsForEmail(array $products, int $langId = null): array
    {
        $formatted = [];
        $langId = $langId ?: (int)Configuration::get('PS_LANG_DEFAULT');
        
        foreach ($products as $product) {
            // Obtener nombre del producto en el idioma correcto
            $productName = $this->getProductNameForLanguage($product['id_product'], $langId) ?: $product['name'];
            
            $formatted[] = [
                'name' => $productName,
                'quantity' => $product['cart_quantity'],
                'price' => Tools::displayPrice($product['price_wt'], null, $langId),
                'image' => $this->context->link->getImageLink(
                    $product['link_rewrite'],
                    $product['id_image'],
                    'medium_default'
                ),
                'url' => $this->context->link->getProductLink($product['id_product'], null, null, null, $langId)
            ];
        }
        
        return $formatted;
    }
    
    private function loadConfiguration(): void
    {
        $this->config = [
            'emails_enabled' => (bool)Configuration::get('ALSERNETSHOPPING_EMAILS_ENABLED'),
            'max_daily_emails' => (int)Configuration::get('ALSERNETSHOPPING_MAX_DAILY_EMAILS') ?: 3,
            'first_reminder_delay' => (int)Configuration::get('ALSERNETSHOPPING_FIRST_REMINDER_DELAY') ?: 60, // minutos
            'second_reminder_delay' => (int)Configuration::get('ALSERNETSHOPPING_SECOND_REMINDER_DELAY') ?: 1440, // 24 horas
            'final_reminder_delay' => (int)Configuration::get('ALSERNETSHOPPING_FINAL_REMINDER_DELAY') ?: 4320, // 72 horas
        ];
    }
    
    private function getDefaultTemplate(string $type): string
    {
        $templatePath = _PS_MODULE_DIR_ . 'alsernetshopping/views/templates/email/abandoned/' . $type . '.html';
        
        if (file_exists($templatePath)) {
            return file_get_contents($templatePath);
        }
        
        // Fallback: plantilla básica si no existe el archivo
        return $this->getBasicTemplate($type);
    }
    
    /**
     * Plantilla básica de fallback
     */
    private function getBasicTemplate(string $type): string
    {
        $templates = [
            self::EMAIL_FIRST_REMINDER => [
                'subject' => '👋 ¿Olvidaste algo en tu carrito?',
                'content' => 'Hola {customer_firstname}, notamos que dejaste algunos productos en tu carrito. ¡No queremos que te los pierdas!'
            ],
            self::EMAIL_SECOND_REMINDER => [
                'subject' => '🎁 Tus productos + descuento especial',
                'content' => '¡Hola {customer_firstname}! Como agradecimiento por tu interés, tenemos un descuento especial: {discount_code}'
            ],
            self::EMAIL_FINAL_REMINDER => [
                'subject' => '⚡ ¡Última oportunidad!',
                'content' => '{customer_firstname}, esta es tu última oportunidad. Tu carrito expira pronto y estos productos podrían agotarse.'
            ],
            self::EMAIL_DISCOUNT_OFFER => [
                'subject' => '🎉 ¡Oferta especial solo para ti!',
                'content' => '¡Hola {customer_firstname}! Tenemos un descuento exclusivo: {discount_code} - {discount_percentage}% OFF'
            ],
            self::EMAIL_STOCK_ALERT => [
                'subject' => '⚠️ Stock limitado en tu carrito',
                'content' => '{customer_firstname}, algunos productos en tu carrito tienen stock limitado. ¡Asegura tu compra ahora!'
            ],
            self::EMAIL_WELCOME_BACK => [
                'subject' => '🌟 ¡Bienvenido/a de vuelta!',
                'content' => '¡Qué alegría verte de nuevo, {customer_firstname}! Tus productos te estaban esperando.'
            ]
        ];
        
        $template = $templates[$type] ?? $templates[self::EMAIL_FIRST_REMINDER];
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . $template['subject'] . '</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 8px; margin-bottom: 30px; }
                .cta-button { display: inline-block; background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . $template['subject'] . '</h1>
                </div>
                <p>' . $template['content'] . '</p>
                {products_list}
                <p style="text-align: center; margin: 30px 0;">
                    <a href="{cart_recovery_url}" class="cta-button">Completar Compra</a>
                </p>
                <div class="footer">
                    <p><a href="{unsubscribe_url}">Cancelar suscripción</a></p>
                </div>
            </div>
            <!-- Tracking pixel -->
            <img src="{tracking_pixel_url}" width="1" height="1" style="display: none;" alt="">
        </body>
        </html>';
    }
    
    private function getPendingEmails(int $limit): array
    {
        $sql = new DbQuery();
        $sql->select('*')
            ->from(_DB_PREFIX_ . 'alsernetshopping_email_queue')
            ->where('status = "' . pSQL(self::STATUS_PENDING) . '"')
            ->where('scheduled_for <= NOW()')
            ->orderBy('scheduled_for ASC')
            ->limit($limit);
            
        return Db::getInstance()->executeS($sql) ?: [];
    }
    
    private function getAbandonmentData(int $abandonmentId): ?array
    {
        $sql = new DbQuery();
        $sql->select('ac.*, c.email, c.firstname as customer_name, c.id_lang as customer_lang, cart.id_lang as cart_lang')
            ->from(_DB_PREFIX_ . 'alsernetshopping_abandoned_carts', 'ac')
            ->leftJoin('customer', 'c', 'c.id_customer = ac.id_customer')
            ->leftJoin('cart', 'cart', 'cart.id_cart = ac.id_cart')
            ->where('ac.id_abandoned_cart = ' . (int)$abandonmentId);
            
        $data = Db::getInstance()->getRow($sql);
        
        if ($data) {
            // Determinar el idioma a usar con prioridad:
            // 1. Idioma del carrito (más reciente)
            // 2. Idioma del customer 
            // 3. Idioma por defecto de la tienda
            $data['id_lang'] = $this->determineEmailLanguage(
                $data['cart_lang'] ?? null,
                $data['customer_lang'] ?? null
            );
        }
        
        return $data ?: null;
    }
    
    /**
     * Determinar idioma para el email basado en prioridades
     */
    private function determineEmailLanguage(?int $cartLang, ?int $customerLang): int
    {
        // Prioridad 1: Idioma del carrito (más reciente y específico)
        if ($cartLang && $this->isLanguageActive($cartLang)) {
            return $cartLang;
        }
        
        // Prioridad 2: Idioma del customer
        if ($customerLang && $this->isLanguageActive($customerLang)) {
            return $customerLang;
        }
        
        // Prioridad 3: Idioma por defecto de la tienda
        return (int)Configuration::get('PS_LANG_DEFAULT');
    }
    
    /**
     * Verificar si un idioma está activo
     */
    private function isLanguageActive(int $langId): bool
    {
        $sql = new DbQuery();
        $sql->select('active')
            ->from('lang')
            ->where('id_lang = ' . (int)$langId);
            
        return (bool)Db::getInstance()->getValue($sql);
    }
    
    /**
     * Obtener contexto de idioma para plantillas
     */
    private function getLanguageContext(int $langId): array
    {
        $language = new Language($langId);
        
        if (!Validate::isLoadedObject($language)) {
            $language = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        }
        
        return [
            'id_lang' => (int)$language->id,
            'iso_code' => $language->iso_code,
            'language_code' => $language->language_code,
            'name' => $language->name,
            'is_rtl' => (bool)$language->is_rtl,
            'date_format_lite' => $language->date_format_lite,
            'date_format_full' => $language->date_format_full
        ];
    }
    
    /**
     * Formatear fecha según el idioma
     */
    private function formatDateForLanguage(string $date, array $langContext): string
    {
        $timestamp = strtotime($date);
        
        if (!$timestamp) {
            return $date;
        }
        
        // Usar formato de fecha según el idioma
        $format = $langContext['date_format_full'] ?: 'd/m/Y H:i';
        
        return date($format, $timestamp);
    }
    
    /**
     * Obtener nombre de la tienda en idioma específico
     */
    private function getShopNameForLanguage(int $langId): string
    {
        // Intentar obtener nombre de tienda específico por idioma
        $shopName = Configuration::get('PS_SHOP_NAME', $langId);
        
        if (!$shopName) {
            $shopName = Configuration::get('PS_SHOP_NAME');
        }
        
        return $shopName ?: 'Shop';
    }
    
    /**
     * Obtener nombre de producto en idioma específico
     */
    private function getProductNameForLanguage(int $productId, int $langId): ?string
    {
        $sql = new DbQuery();
        $sql->select('name')
            ->from('product_lang')
            ->where('id_product = ' . (int)$productId)
            ->where('id_lang = ' . (int)$langId);
            
        return Db::getInstance()->getValue($sql) ?: null;
    }
    
    /**
     * Obtener productos con stock bajo en idioma específico
     */
    private function getLowStockProducts(array $products, int $langId): array
    {
        $lowStockProducts = [];
        
        foreach ($products as $product) {
            $stock = (int)Product::getQuantity($product['id_product']);
            
            if ($stock <= 5) { // Threshold configurable
                $productName = $this->getProductNameForLanguage($product['id_product'], $langId) ?: $product['name'];
                
                $lowStockProducts[] = [
                    'name' => $productName,
                    'stock' => $stock,
                    'quantity_ordered' => $product['cart_quantity'],
                    'price' => Tools::displayPrice($product['price_wt'], null, $langId)
                ];
            }
        }
        
        return $lowStockProducts;
    }
    
    /**
     * Obtener string traducido para idioma específico
     */
    private function getTranslatedString(string $key, int $langId): string
    {
        // Esta sería una implementación básica
        // En un sistema más completo, usarías un sistema de traducciones
        $translations = [
            'Last chance to complete your purchase!' => [
                1 => '¡Última oportunidad para completar tu compra!', // Español
                2 => 'Last chance to complete your purchase!', // Inglés
                3 => 'Dernière chance de finaliser votre achat !', // Francés
                4 => 'Ultima possibilità per completare il tuo acquisto!', // Italiano
                5 => 'Última chance de completar a sua compra!', // Portugués
                6 => 'Letzte Chance, Ihren Kauf abzuschließen!' // Alemán
            ]
        ];
        
        return $translations[$key][$langId] ?? $key;
    }
    
    /**
     * Obtener conteo de emails enviados hoy para un email específico
     */
    private function getDailyEmailCount(string $email): int
    {
        $sql = new DbQuery();
        $sql->select('COUNT(*)')
            ->from(_DB_PREFIX_ . 'alsernetshopping_email_log')
            ->where('recipient_email = "' . pSQL($email) . '"')
            ->where('DATE(sent_at) = CURDATE()');
            
        return (int)Db::getInstance()->getValue($sql);
    }
    
    /**
     * Programar el siguiente email en la secuencia
     */
    private function scheduleNextEmail(int $abandonmentId, string $currentEmailType): void
    {
        $nextEmailMap = [
            self::EMAIL_FIRST_REMINDER => self::EMAIL_SECOND_REMINDER,
            self::EMAIL_SECOND_REMINDER => self::EMAIL_FINAL_REMINDER,
            // Los otros tipos no tienen secuencia automática
        ];
        
        if (!isset($nextEmailMap[$currentEmailType])) {
            return; // No hay siguiente email programado
        }
        
        $nextEmailType = $nextEmailMap[$currentEmailType];
        $delay = $this->getDelayForEmailType($nextEmailType);
        
        // Obtener email del abandonment
        $abandonment = $this->getAbandonmentData($abandonmentId);
        if (!$abandonment) {
            return;
        }
        
        // Programar en cola de emails
        Db::getInstance()->insert(_DB_PREFIX_ . 'alsernetshopping_email_queue', [
            'id_abandoned_cart' => $abandonmentId,
            'email_type' => pSQL($nextEmailType),
            'recipient_email' => pSQL($abandonment['email']),
            'scheduled_for' => date('Y-m-d H:i:s', time() + ($delay * 60)), // delay en minutos
            'status' => pSQL(self::STATUS_PENDING),
            'date_add' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Obtener delay para tipo de email
     */
    private function getDelayForEmailType(string $emailType): int
    {
        $delays = [
            self::EMAIL_FIRST_REMINDER => $this->config['first_reminder_delay'],
            self::EMAIL_SECOND_REMINDER => $this->config['second_reminder_delay'],
            self::EMAIL_FINAL_REMINDER => $this->config['final_reminder_delay'],
        ];
        
        return $delays[$emailType] ?? 1440; // Default 24 horas
    }
    
    /**
     * Obtener próximo email programado
     */
    private function getNextScheduledEmail(string $currentEmailType): ?string
    {
        $nextEmailMap = [
            self::EMAIL_FIRST_REMINDER => self::EMAIL_SECOND_REMINDER,
            self::EMAIL_SECOND_REMINDER => self::EMAIL_FINAL_REMINDER,
        ];
        
        return $nextEmailMap[$currentEmailType] ?? null;
    }
    
    private function l(string $string): string
    {
        // Implementar traducción básica
        return $string;
    }
}