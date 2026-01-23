<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Setting extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const UPLOAD_PATH = 'app/setting/';

    /**
     * Get all items.
     *
     * @return collect
     */
    public static function getAll()
    {
        $settings = self::select('*')->get();
        $result = self::defaultSettings();

        foreach ($settings as $setting) {
            $result[$setting->key]['value'] = $setting->value;
        }

        return $result;
    }

    /**
     * Get setting.
     *
     * @return object
     */
    public static function get($name, $defaultValue = null)
    {
        if (config('app.sms') && in_array($name, ['frontend_scheme', 'backend_scheme'])) {
            return 'sms';
        }

        // Cache settings for 10 minutes to reduce database load
        // Cache key includes the setting name for granular invalidation
        return cache()->remember("setting_{$name}", now()->addMinutes(10), function () use ($name, $defaultValue) {
            $setting = self::where('key', $name)->first();

            if ($setting) {
                return $setting->value;
            } elseif (isset(self::defaultSettings()[$name])) {
                return self::defaultSettings()[$name]['value'];
            } else {
                // @todo exception case not handled
                return $defaultValue;
            }
        });
    }

    /**
     * Get multiple settings at once by prefix
     * Optimized to use single query instead of multiple get() calls
     *
     * @param  string  $prefix  Setting key prefix (e.g., 'documents')
     * @return array Array of settings with keys and values
     */
    public static function getAllByPrefix(string $prefix): array
    {
        return cache()->remember("settings_prefix_{$prefix}", now()->addMinutes(10), function () use ($prefix) {
            $settings = self::where('key', 'LIKE', "{$prefix}.%")->get(['key', 'value']);

            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->value;
            }

            return $result;
        });
    }

    /**
     * Clear cache for all settings with a specific prefix
     *
     * @param  string  $prefix  Setting key prefix
     */
    public static function clearPrefixCache(string $prefix): void
    {
        cache()->forget("settings_prefix_{$prefix}");

        // Also clear individual setting caches
        $settings = self::where('key', 'LIKE', "{$prefix}.%")->pluck('key');
        foreach ($settings as $key) {
            cache()->forget("setting_{$key}");
        }
    }

    /**
     * Get setting by key (static method for fluent API).
     *
     * @param  string  $key
     * @return Setting|null
     */
    public static function key($key)
    {
        return self::where('key', $key)->first();
    }

    /**
     * Check setting EQUAL.
     *
     * @return object
     */
    public static function isYes($key)
    {
        return strtolower(self::get($key)) == 'yes';
    }

    /**
     * Set YES.
     *
     * @return object
     */
    public static function setYes($key)
    {
        return self::set($key, 'yes');
    }

    /**
     * Set setting value.
     *
     * @return object
     */
    public static function set($name, $val)
    {
        $option = self::where('key', $name)->first();

        if ($option) {
            $option->value = $val;
        } else {
            $option = new self;
            $option->key = $name;
            $option->value = $val;
        }
        $option->save();

        // Invalidate cache for this specific setting
        cache()->forget("setting_{$name}");

        return $option;
    }

    /**
     * Get setting rules.
     *
     * @return object
     */
    public static function rules()
    {
        $rules = [];
        $settings = self::getAll();

        foreach ($settings as $name => $setting) {
            if (! isset($setting['not_required'])) {
                $rules[$name] = 'required';
            }
        }

        return $rules;
    }

    /**
     * Default setting.
     *
     * @return object
     */
    public static function defaultSettings()
    {
        return [
            'site_name' => [
                'cat' => 'general',
                'value' => config('app.name'),
                'type' => 'text',
            ],
            'site_keyword' => [
                'cat' => 'general',
                'value' => 'Email Marketing, Campaigns, Lists',
                'type' => 'text',
            ],
            'site_logo_light' => [
                'cat' => 'general',
                'value' => '',
                'type' => 'image',
            ],
            'site_logo_dark' => [
                'cat' => 'general',
                'value' => '',
                'type' => 'image',
            ],
            'site_favicon' => [
                'cat' => 'general',
                'value' => '',
                'type' => 'image',
            ],
            'license' => [
                'cat' => 'license',
                'value' => '',
                'type' => 'text',
                'not_required' => true,
            ],
            'license_type' => [
                'cat' => 'license',
                'value' => '',
                'type' => 'text',
                'not_required' => true,
            ],
            'license_status' => [
                'cat' => 'license',
                'value' => '',
                'type' => 'text',
                'not_required' => true,
            ],
            'license_supported_until' => [
                'cat' => 'license',
                'value' => '',
                'type' => 'text',
                'not_required' => true,
            ],
            'site_online' => [
                'cat' => 'general',
                'value' => 'true',
                'type' => 'checkbox',
                'options' => [
                    'false', 'true',
                ],
            ],
            'site_offline_message' => [
                'cat' => 'general',
                'value' => 'Application currently offline. We will come back soon!',
                'type' => 'textarea',
            ],
            'site_description' => [
                'cat' => 'general',
                'value' => 'Makes it easy for you to create, send, and optimize your email marketing campaigns.',
                'type' => 'textarea',
            ],
            'default_language' => [
                'cat' => 'general',
                'value' => 'en',
                'type' => 'select',
                'options' => \Modules\Core\Models\Lang::getSelectOptions(),
            ],
            'frontend_scheme' => [
                'cat' => 'general',
                'value' => 'default',
                'type' => 'select',
                'options' => self::colors(),
            ],
            'backend_scheme' => [
                'cat' => 'general',
                'value' => 'default',
                'type' => 'select',
                'options' => self::colors(),
            ],
            'captcha_engine' => [
                'cat' => 'general',
                'value' => 'recaptcha',
                'type' => 'select',
                'options' => array_map(function ($cap) {
                    return ['value' => $cap['id'], 'text' => $cap['title']];
                }, class_exists(\Modules\Campaign\Library\Facades\Hook::class) ? \Modules\Campaign\Library\Facades\Hook::execute('captcha_method') : []),
            ],
            'login_recaptcha' => [
                'cat' => 'general',
                'value' => 'no',
                'type' => 'checkbox',
                'options' => ['no', 'yes'],
            ],
            'embedded_form_recaptcha' => [
                'cat' => 'general',
                'value' => 'no',
                'type' => 'checkbox',
                'options' => ['no', 'yes'],
            ],
            'list_sign_up_captcha' => [
                'cat' => 'general',
                'value' => 'no',
                'type' => 'checkbox',
                'options' => ['no', 'yes'],
            ],
            'enable_user_registration' => [
                'cat' => 'general',
                'value' => 'yes',
                'type' => 'checkbox',
                'options' => ['no', 'yes'],
            ],
            'registration_recaptcha' => [
                'cat' => 'general',
                'value' => 'yes',
                'type' => 'checkbox',
                'options' => ['no', 'yes'],
            ],
            'custom_script' => [
                'cat' => 'general',
                'value' => '',
                'type' => 'textarea',
                'not_required' => 'yes',
            ],
            'builder' => [
                'cat' => 'general',
                'value' => 'both',
                'type' => 'select',
                'options' => self::builderOptions(),
            ],
            'import_subscribers_commitment' => [
                'cat' => 'others',
                'value' => null,
                'type' => 'textarea',
            ],
            'sending_campaigns_at_once' => [
                'cat' => 'sending',
                'value' => '10',
                'type' => 'text',
                'class' => 'numeric',
            ],
            'sending_change_server_time' => [
                'cat' => 'sending',
                'value' => '300',
                'type' => 'text',
                'class' => 'numeric',
            ],
            'sending_emails_per_minute' => [
                'cat' => 'sending',
                'value' => '150',
                'type' => 'text',
                'class' => 'numeric',
            ],
            'sending_pause' => [
                'cat' => 'sending',
                'value' => '10',
                'type' => 'text',
                'class' => 'numeric',
            ],
            'sending_at_once' => [
                'cat' => 'sending',
                'value' => '50',
                'type' => 'text',
                'class' => 'numeric',
            ],
            'sending_subscribers_at_once' => [
                'cat' => 'sending',
                'value' => '100',
                'type' => 'text',
                'class' => 'numeric',
            ],
            'url_unsubscribe' => [
                'cat' => 'url',
                'value' => '',
                'type' => 'text',
                'not_required' => true,
            ],
            'url_open_track' => [
                'cat' => 'url',
                'value' => '', // action('CampaignController@open', ["message_id" => trans("messages.MESSAGE_ID")]),
                'type' => 'text',
                'not_required' => true,
            ],
            'url_click_track' => [
                'cat' => 'url',
                'value' => '', // action('CampaignController@click', ["message_id" => trans("messages.MESSAGE_ID"), "url" => trans("messages.URL")]),
                'type' => 'text',
                'not_required' => true,
            ],
            'url_delivery_handler' => [
                'cat' => 'url',
                'value' => '', // action('DeliveryController@notify'),
                'type' => 'text',
                'not_required' => true,
            ],
            'url_update_profile' => [
                'cat' => 'url',
                'value' => '',
                'type' => 'text',
                'not_required' => true,
            ],
            'url_web_view' => [
                'cat' => 'url',
                'value' => '',
                'type' => 'text',
                'not_required' => true,
            ],
            'php_bin_path' => [
                'cat' => 'cronjob',
                'value' => '',
                'type' => 'text',
                'not_required' => true,
            ],
            'composer_path' => [
                'cat' => 'maintenance',
                'value' => '',
                'type' => 'text',
                'not_required' => true,
                'label' => 'Ruta de Composer',
                'description' => 'Ruta completa al ejecutable de Composer. Déjalo vacío para auto-detectar.',
            ],
            'php_path' => [
                'cat' => 'maintenance',
                'value' => '',
                'type' => 'text',
                'not_required' => true,
                'label' => 'Ruta de PHP',
                'description' => 'Ruta completa al ejecutable de PHP. Déjalo vacío para usar el PHP actual.',
            ],
            'remote_job_token' => [
                'cat' => 'cronjob',
                'value' => '',
                'type' => 'text',
                'not_required' => true,
            ],
            'cronjob_last_execution' => [
                'cat' => 'monitor',
                'value' => 0,
                'type' => 'text',
                'not_required' => true,
            ],
            'cronjob_min_interval' => [
                'cat' => 'monitor',
                'value' => '15 minutes',
                'type' => 'text',
                'not_required' => true,
            ],
            'spf_record' => [
                'cat' => 'dns',
                'value' => null,
                'type' => 'text',
                'not_required' => true,
            ],
            'spf_host' => [
                'cat' => 'dns',
                'value' => null,
                'type' => 'text',
                'not_required' => true,
            ],
            'verification_hostname' => [
                'cat' => 'dns',
                'value' => 'emarketing',
                'type' => 'text',
                'not_required' => true,
            ],
            'dkim_selector' => [
                'cat' => 'dns',
                'value' => 'mailer',
                'type' => 'text',
                'not_required' => true,
            ],
            'allow_send_from_unverified_domain' => [
                'cat' => 'others',
                'value' => 'yes',
                'type' => 'text',
                'not_required' => true,
            ],
            'allow_turning_off_dkim_signing' => [
                'cat' => 'others',
                'value' => 'yes',
                'type' => 'text',
                'not_required' => true,
            ],
            'escape_dkim_dns_value' => [
                'cat' => 'others',
                'value' => 'no',
                'type' => 'text',
                'not_required' => true,
            ],
            'verify_subscriber_email' => [
                'cat' => 'others',
                'value' => 'no',
                'type' => 'text',
                'not_required' => true,
            ],
            'send_notification_email_for_list_subscription' => [
                'cat' => 'others',
                'value' => 'no',
                'type' => 'text',
                'not_required' => true,
            ],
            'aws_verification_server' => [
                'cat' => 'others',
                'value' => 'no',
                'type' => 'text',
                'not_required' => true,
            ],
            'geoip.engine' => [
                'cat' => 'others',
                'value' => 'sqlite', // available values are sqlite|nekudo|mysql
                'type' => 'text',
                'not_required' => true,
            ],
            'geoip.enabled' => [
                'cat' => 'others',
                'value' => 'no',
                'type' => 'text',
                'not_required' => true,
            ],
            'geoip.last_message' => [
                'cat' => 'others',
                'value' => null,
                'type' => 'text',
                'not_required' => true,
            ],
            'geoip.sqlite.dbname' => [
                'cat' => 'others',
                'value' => 'storage/app/GeoLite2-City.mmdb',
                'type' => 'text',
                'not_required' => true,
            ],
            'geoip.sqlite.source_url' => [
                'cat' => 'others',
                'value' => 'https://acellemail.s3.amazonaws.com/GeoLite2-City_20230404.mmdb',
                'type' => 'text',
                'not_required' => true,
            ],
            'geoip.sqlite.source_hash' => [
                'cat' => 'others',
                'value' => '1b6368f0e80b1be2dd5be3606d25ac16',
                'type' => 'text',
                'not_required' => true,
            ],
            'delivery.sendmail' => [
                'cat' => 'others',
                'value' => 'yes',
                'type' => 'text',
                'not_required' => true,
            ],
            'delivery.phpmail' => [
                'cat' => 'others',
                'value' => 'yes',
                'type' => 'text',
                'not_required' => true,
            ],
            'subscription.expiring_period' => [
                'cat' => 'payment',
                'value' => '7',
                'type' => 'text',
            ],
            'subscription.auto_billing_period' => [
                'cat' => 'payment',
                'value' => '3',
                'type' => 'text',
            ],
            'allowed_due_subscription' => [
                'cat' => 'payment',
                'value' => 'no',
                'type' => 'text',
            ],
            'theme.beta' => [
                'cat' => 'others',
                'value' => 'no',
                'type' => 'text',
                'not_required' => true,
            ],
            'spamassassin.command' => [
                'cat' => 'others',
                'value' => 'spamc -R',
                'type' => 'text',
                'not_required' => true,
            ],
            'spamassassin.required' => [
                'cat' => 'others',
                'value' => 'no',
                'type' => 'text',
                'not_required' => true,
            ],
            'spamassassin.enabled' => [
                'cat' => 'others',
                'value' => 'no',
                'type' => 'text',
                'not_required' => true,
            ],
            'mta.api_endpoint' => [
                'cat' => 'others',
                'value' => null,
                'type' => 'text',
                'not_required' => true,
            ],
            'mta.api_key' => [
                'cat' => 'others',
                'value' => null,
                'type' => 'text',
                'not_required' => true,
            ],
            'storage.s3' => [
                'cat' => 'others',
                'value' => null,
                'type' => 'text',
                'not_required' => true,
            ],
            'rss.enabled' => [
                'cat' => 'others',
                'value' => 'yes',
                'type' => 'text',
                'not_required' => true,
            ],
            'list.clone_for_others' => [
                'cat' => 'others',
                'value' => 'no',
                'type' => 'text',
                'not_required' => true,
            ],
            'gateways' => [
                'cat' => 'others',
                'value' => '["direct"]',
                'type' => 'array',
                'not_required' => true,
            ],
            'automation.trigger_imported_contacts' => [
                'cat' => 'others',
                'value' => 'no',
                'type' => 'text',
                'not_required' => true,
            ],
            'campaign.bcc' => [
                'cat' => 'others',
                'value' => '',
                'type' => 'text',
                'not_required' => true,
            ],
            'campaign.cc' => [
                'cat' => 'others',
                'value' => '',
                'type' => 'text',
                'not_required' => true,
            ],
            'list.allow_single_optin' => [
                'cat' => 'others',
                'value' => 'yes',
                'type' => 'text',
                'not_required' => true,
            ],
            'campaign.enforce_unsubscribe_url_check' => [
                'cat' => 'others',
                'value' => 'no',
                'type' => 'text',
                'not_required' => true,
            ],
            'layout.menu_bar' => [
                'cat' => 'others',
                'value' => 'left',
                'type' => 'text',
                'not_required' => true,
            ],
            'invoice.current' => [
                'cat' => 'general',
                'value' => '1',
                'type' => 'number',
            ],
            'invoice.format' => [
                'cat' => 'general',
                'value' => '%08d',  // a number of 8 digit, for example: sprintf('%08d', 15) -> 00000015
                'type' => 'text',
            ],
            'customer_can_change_language' => [
                'cat' => 'others',
                'value' => 'yes',
                'type' => 'checkbox',
                'options' => ['no', 'yes'],
            ],
            'customer_can_change_personality' => [
                'cat' => 'others',
                'value' => 'yes',
                'type' => 'checkbox',
                'options' => ['no', 'yes'],
            ],
            'campaign.stop_on_error' => [
                'cat' => 'others',
                'value' => 'no',
                'type' => 'checkbox',
                'options' => ['no', 'yes'],
            ],
            'not_require_card_for_trial' => [
                'cat' => 'others',
                'value' => 'no',
                'type' => 'checkbox',
                'options' => ['no', 'yes'],
            ],
            // ERP Integration Settings
            'erp_integration_enabled' => [
                'cat' => 'erp_integration',
                'value' => 'no',
                'type' => 'checkbox',
                'options' => ['no', 'yes'],
                'label' => 'Habilitar integración con ERP',
                'description' => 'Activa/desactiva la importación de órdenes desde el ERP',
            ],
            'erp_import_documents' => [
                'cat' => 'erp_integration',
                'value' => 'yes',
                'type' => 'checkbox',
                'options' => ['no', 'yes'],
                'label' => 'Importar documentos',
                'description' => 'Permite importar órdenes del ERP como documentos',
            ],
            'erp_auto_detect_type' => [
                'cat' => 'erp_integration',
                'value' => 'yes',
                'type' => 'checkbox',
                'options' => ['no', 'yes'],
                'label' => 'Detectar tipo automáticamente',
                'description' => 'Detecta automáticamente el tipo de documento basándose en los productos',
            ],
            'erp_sync_products' => [
                'cat' => 'erp_integration',
                'value' => 'yes',
                'type' => 'checkbox',
                'options' => ['no', 'yes'],
                'label' => 'Sincronizar productos',
                'description' => 'Sincroniza los productos de las órdenes del ERP',
            ],
            'erp_sync_customers' => [
                'cat' => 'erp_integration',
                'value' => 'yes',
                'type' => 'checkbox',
                'options' => ['no', 'yes'],
                'label' => 'Sincronizar clientes',
                'description' => 'Sincroniza la información del cliente desde el ERP',
            ],
            'erp_document_source' => [
                'cat' => 'erp_integration',
                'value' => 'erp',
                'type' => 'text',
                'label' => 'Fuente de documentos',
                'description' => 'Identifica el origen de los documentos importados',
            ],
        ];
    }

    /**
     * Color array.
     *
     * @return array
     */
    public static function colors()
    {
        return [
            ['value' => 'default', 'text' => trans('messages.default')],
            ['value' => 'blue', 'text' => trans('messages.blue')],
            ['value' => 'green', 'text' => trans('messages.green')],
            ['value' => 'brown', 'text' => trans('messages.brown')],
            ['value' => 'pink', 'text' => trans('messages.pink')],
            ['value' => 'grey', 'text' => trans('messages.grey')],
            ['value' => 'white', 'text' => trans('messages.white')],
        ];
    }

    /**
     * Color array.
     *
     * @return array
     */
    public static function builderOptions()
    {
        return [
            ['value' => 'both', 'text' => trans('messages.builder.both')],
            ['value' => 'pro', 'text' => trans('messages.builder.pro')],
            ['value' => 'classic', 'text' => trans('messages.builder.classic')],
        ];
    }

    /**
     * Upload site logo.
     *
     * @var bool
     */
    public static function uploadSiteLogo($file, $name = null)
    {
        $path = 'images/';
        $upload_path = public_path($path);

        if (! file_exists($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        $md5file = \md5_file($file);

        $filename = $md5file.'.'.$file->getClientOriginalExtension();

        // save to server
        $file->move($upload_path, $filename);

        // create thumbnails
        $img = \Image::make($upload_path.$filename);

        self::set($name, $path.$filename);

        return true;
    }

    /**
     * Upload site logo.
     *
     * @var bool
     */
    public static function uploadFile($file, $type = null, $thumbnail = true)
    {
        $uploadPath = storage_path(self::UPLOAD_PATH);

        if (! file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $md5file = \md5_file($file);

        $filename = $type.'-'.$md5file.'.'.$file->getClientOriginalExtension();

        // save to server
        $file->move($uploadPath, $filename);

        // create thumbnails
        if ($thumbnail) {
            $img = \Image::make($uploadPath.$filename);
        }

        self::set($type, $filename);

        return true;
    }

    /**
     * gET uploaded file location.
     *
     * @var bool
     */
    public static function getUploadFilePath($filename)
    {
        $uploadPath = storage_path(self::UPLOAD_PATH);

        return $uploadPath.$filename;
    }

    /**
     * Write default backups to DB.
     *
     * @var bool
     */
    public static function writeDefaultSettings()
    {
        foreach (self::defaultSettings() as $name => $setting) {
            if (! self::where('key', $name)->exists()) {
                $value = (is_null($setting['value'])) ? '' : $setting['value'];

                $setting = new self;
                $setting->key = $name;
                $setting->value = $value;
                $setting->save();
            }
        }
    }

    public static function getTaxSettings()
    {
        if (self::get('tax') == null) {
            return [
                'enabled' => 'no',
                'default_rate' => 10,
                'countries' => [],
            ];
        }

        return json_decode(self::get('tax'), true);
    }

    public static function setTaxSettings($params)
    {
        $settings = self::getTaxSettings();
        $countries = $settings['countries'];

        if (isset($params['countries'])) {
            $countries = array_merge($countries, $params['countries']);
        }

        $settings = array_merge($settings, $params);
        $settings['countries'] = $countries;

        self::set('tax', json_encode($settings));
    }

    public static function getTaxByCountry($country = null)
    {
        if (self::getTaxSettings()['enabled'] !== 'yes') {
            return 0;
        }

        if ($country == null) {
            return self::getTaxSettings()['default_rate'];
        }

        $countries = self::getTaxSettings()['countries'];

        if (isset($countries[$country->code])) {
            return $countries[$country->code];
        } else {
            return self::getTaxSettings()['default_rate'];
        }
    }

    public static function removeTaxCountryByCode($code)
    {
        $settings = self::getTaxSettings();
        $countries = $settings['countries'];

        unset($countries[$code]);

        $settings['countries'] = $countries;

        self::set('tax', json_encode($settings));
    }

    public static function getCaptchaProvider()
    {
        $captcha = self::get('captcha_engine');

        if (in_array(
            $captcha,
            array_map(
                function ($cap) {
                    return $cap['id'];
                },
                class_exists(\Modules\Campaign\Library\Facades\Hook::class) ? \Modules\Campaign\Library\Facades\Hook::execute('captcha_method') : []
            )
        )
        ) {
            return $captcha;
        }

        return 'recaptcha';
    }

    public static function isListSignupCaptchaEnabled()
    {
        return self::get('list_sign_up_captcha') == 'yes';
    }

    /**
     * ERP Settings Management
     * Métodos para gestionar la configuración del ERP
     */

    /**
     * Obtener todas las configuraciones ERP
     */
    public static function getErpSettings(): array
    {
        $erpSettings = [];
        $erpKeys = [
            'erp_api_url',
            'erp_sync_url',
            'erp_xmlrpc_url',
            'erp_sms_url',
            'erp_is_active',
            'erp_timeout',
            'erp_connect_timeout',
            'erp_retry_attempts',
            'erp_sync_destination_id',
            'erp_sync_batch_size',
            'erp_bizum_tpv_id',
            'erp_google_tpv_id',
            'erp_apple_tpv_id',
            'erp_enable_cache',
            'erp_cache_ttl',
            'erp_enable_debug_logs',
            'erp_sms_username',
            'erp_sms_password',
            'erp_last_connection_check',
            'erp_last_connection_status',
            'erp_total_requests',
            'erp_failed_requests',
            'erp_integration_enabled',
            'erp_import_documents',
            'erp_auto_detect_type',
            'erp_sync_products',
            'erp_sync_customers',
            'erp_document_source',
        ];

        foreach ($erpKeys as $key) {
            $erpSettings[$key] = self::get($key, self::getErpDefaultValue($key));
        }

        return $erpSettings;
    }

    /**
     * Obtener valor por defecto para configuración ERP
     */
    private static function getErpDefaultValue(string $key): mixed
    {
        $defaults = [
            'erp_api_url' => env('ERP_URL', 'http://interges:8080/api-gestion'),
            'erp_sync_url' => env('ERP_SYNC_URL', 'http://223.1.1.18:9000/integracion'),
            'erp_xmlrpc_url' => env('ERP_XMLRPC_URL', 'http://192.168.1.6:8081'),
            'erp_sms_url' => env('ERP_SMS_URL', 'http://213.134.40.126:8080'),
            'erp_is_active' => 'yes',
            'erp_timeout' => '30',
            'erp_connect_timeout' => '10',
            'erp_retry_attempts' => '3',
            'erp_sync_destination_id' => '1',
            'erp_sync_batch_size' => '100',
            'erp_enable_cache' => 'yes',
            'erp_cache_ttl' => '3600',
            'erp_enable_debug_logs' => 'no',
            'erp_total_requests' => '0',
            'erp_failed_requests' => '0',
            'erp_integration_enabled' => 'no',
            'erp_import_documents' => 'yes',
            'erp_auto_detect_type' => 'yes',
            'erp_sync_products' => 'yes',
            'erp_sync_customers' => 'yes',
            'erp_document_source' => 'erp',
        ];

        return $defaults[$key] ?? null;
    }

    /**
     * Establecer configuraciones ERP
     */
    public static function setErpSettings(array $data): void
    {
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'erp_')) {
                self::set($key, $value);
            }
        }
    }

    /**
     * Obtener las reglas de validación para ERP
     */
    public static function getErpRules(): array
    {
        return [
            'erp_api_url' => 'required|url',
            'erp_sync_url' => 'required|url',
            'erp_xmlrpc_url' => 'required|url',
            'erp_sms_url' => 'required|url',
            'erp_timeout' => 'required|integer|min:1|max:300',
            'erp_connect_timeout' => 'required|integer|min:1|max:60',
            'erp_retry_attempts' => 'required|integer|min:0|max:10',
            'erp_sync_destination_id' => 'required|integer|min:1',
            'erp_sync_batch_size' => 'required|integer|min:1|max:1000',
            'erp_cache_ttl' => 'required|integer|min:60|max:86400',
        ];
    }

    /**
     * Obtener estadísticas ERP
     */
    public static function getErpStats(): ?array
    {
        return [
            'total_requests' => (int) self::get('erp_total_requests', 0),
            'failed_requests' => (int) self::get('erp_failed_requests', 0),
            'success_rate' => self::calculateErpSuccessRate(),
            'last_connection_check' => self::get('erp_last_connection_check'),
            'last_connection_status' => self::get('erp_last_connection_status'),
            'is_active' => self::get('erp_is_active', 'no') === 'yes',
        ];
    }

    /**
     * Calcular tasa de éxito de ERP
     */
    private static function calculateErpSuccessRate(): float
    {
        $total = (int) self::get('erp_total_requests', 0);
        $failed = (int) self::get('erp_failed_requests', 0);

        if ($total === 0) {
            return 100.0;
        }

        $successful = $total - $failed;

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Actualizar estado de conexión ERP
     */
    public static function updateErpConnectionStatus(string $status): void
    {
        self::set('erp_last_connection_check', now()->toDateTimeString());
        self::set('erp_last_connection_status', $status);
    }

    /**
     * Resetear estadísticas ERP
     */
    public static function resetErpStats(): void
    {
        self::set('erp_total_requests', '0');
        self::set('erp_failed_requests', '0');
    }

    /**
     * Incrementar contador de peticiones ERP
     */
    public static function incrementErpRequests(): void
    {
        $current = (int) self::get('erp_total_requests', 0);
        self::set('erp_total_requests', (string) ($current + 1));
    }

    /**
     * Incrementar contador de errores ERP
     */
    public static function incrementErpErrors(): void
    {
        $current = (int) self::get('erp_failed_requests', 0);
        self::set('erp_failed_requests', (string) ($current + 1));
    }

    /**
     * PRESTASHOP Settings Management
     * Métodos para gestionar la configuración de PrestaShop
     */

    /**
     * Obtener todas las configuraciones PrestaShop
     */
    public static function getPrestashopSettings(): array
    {
        $psSettings = [];
        $psKeys = [
            'prestashop_enabled',
            'prestashop_db_host',
            'prestashop_db_port',
            'prestashop_db_database',
            'prestashop_db_username',
            'prestashop_db_password',
            'prestashop_url',
            'prestashop_api_key',
            'prestashop_timeout',
            'prestashop_connect_timeout',
            'prestashop_sync_enabled',
            'prestashop_sync_products',
            'prestashop_sync_orders',
            'prestashop_sync_customers',
            'prestashop_documents_portal_url',
            'prestashop_documents_paid_status_ids',
            'prestashop_last_sync_check',
            'prestashop_last_sync_status',
            'prestashop_total_syncs',
            'prestashop_failed_syncs',
        ];

        foreach ($psKeys as $key) {
            $psSettings[$key] = self::get($key, self::getPrestashopDefaultValue($key));
        }

        return $psSettings;
    }

    /**
     * Obtener valor por defecto para configuración PrestaShop
     */
    private static function getPrestashopDefaultValue(string $key): mixed
    {
        $defaults = [
            'prestashop_enabled' => 'no',
            'prestashop_db_host' => env('DB_HOST_PRESTASHOP', '192.168.1.120'),
            'prestashop_db_port' => env('DB_PORT_PRESTASHOP', '3306'),
            'prestashop_db_database' => env('DB_DATABASE_PRESTASHOP', 'prestashop'),
            'prestashop_db_username' => env('DB_USERNAME_PRESTASHOP', ''),
            'prestashop_db_password' => env('DB_PASSWORD_PRESTASHOP', ''),
            'prestashop_url' => env('PRESTASHOP_URL', 'https://www.a-alvarez.com'),
            'prestashop_api_key' => env('PRESTASHOP_API_KEY', ''),
            'prestashop_timeout' => '30',
            'prestashop_connect_timeout' => '10',
            'prestashop_sync_enabled' => 'no',
            'prestashop_sync_products' => 'yes',
            'prestashop_sync_orders' => 'yes',
            'prestashop_sync_customers' => 'yes',
            'prestashop_documents_portal_url' => env('DOCUMENTS_UPLOAD_PORTAL_URL', 'https://www.a-alvarez.com/solicitud-documentos?token={uid}'),
            'prestashop_documents_paid_status_ids' => env('DOCUMENTS_PRESTASHOP_PAID_STATUS_IDS', ''),
            'prestashop_total_syncs' => '0',
            'prestashop_failed_syncs' => '0',
        ];

        return $defaults[$key] ?? null;
    }

    /**
     * Establecer configuraciones PrestaShop
     */
    public static function setPrestashopSettings(array $data): void
    {
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'prestashop_')) {
                self::set($key, $value);
            }
        }
    }

    /**
     * Obtener las reglas de validación para PrestaShop
     */
    public static function getPrestashopRules(): array
    {
        return [
            'prestashop_db_host' => 'required|string',
            'prestashop_db_port' => 'required|integer|min:1|max:65535',
            'prestashop_db_database' => 'required|string',
            'prestashop_db_username' => 'required|string',
            'prestashop_db_password' => 'nullable|string',
            'prestashop_url' => 'required|url',
            'prestashop_api_key' => 'nullable|string',
            'prestashop_timeout' => 'required|integer|min:1|max:300',
            'prestashop_connect_timeout' => 'required|integer|min:1|max:60',
            'prestashop_documents_portal_url' => 'nullable|url',
            'prestashop_documents_paid_status_ids' => 'nullable|string',
        ];
    }

    /**
     * Obtener estadísticas PrestaShop
     */
    public static function getPrestashopStats(): ?array
    {
        return [
            'enabled' => self::get('prestashop_enabled', 'no') === 'yes',
            'total_syncs' => (int) self::get('prestashop_total_syncs', 0),
            'failed_syncs' => (int) self::get('prestashop_failed_syncs', 0),
            'success_rate' => self::calculatePrestashopSuccessRate(),
            'last_sync_check' => self::get('prestashop_last_sync_check'),
            'last_sync_status' => self::get('prestashop_last_sync_status'),
        ];
    }

    /**
     * Calcular tasa de éxito de PrestaShop
     */
    private static function calculatePrestashopSuccessRate(): float
    {
        $total = (int) self::get('prestashop_total_syncs', 0);
        $failed = (int) self::get('prestashop_failed_syncs', 0);

        if ($total === 0) {
            return 100.0;
        }

        $successful = $total - $failed;

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Actualizar estado de sincronización PrestaShop
     */
    public static function updatePrestashopSyncStatus(string $status): void
    {
        self::set('prestashop_last_sync_check', now()->toDateTimeString());
        self::set('prestashop_last_sync_status', $status);
    }

    /**
     * Resetear estadísticas PrestaShop
     */
    public static function resetPrestashopStats(): void
    {
        self::set('prestashop_total_syncs', '0');
        self::set('prestashop_failed_syncs', '0');
    }

    /**
     * Incrementar contador de sincronizaciones PrestaShop
     */
    public static function incrementPrestashopSyncs(): void
    {
        $current = (int) self::get('prestashop_total_syncs', 0);
        self::set('prestashop_total_syncs', (string) ($current + 1));
    }

    /**
     * Incrementar contador de errores PrestaShop
     */
    public static function incrementPrestashopErrors(): void
    {
        $current = (int) self::get('prestashop_failed_syncs', 0);
        self::set('prestashop_failed_syncs', (string) ($current + 1));
    }

    /**
     * EMAIL/SMTP Settings Management
     * Métodos para gestionar la configuración de correo
     */

    /**
     * Obtener todas las configuraciones de Email
     */
    public static function getEmailSettings(): array
    {
        return [
            'mail_mailer' => self::get('mail_mailer', env('MAIL_MAILER', 'smtp')),
            'mail_host' => self::get('mail_host', env('MAIL_HOST', 'smtp.mailtrap.io')),
            'mail_port' => self::get('mail_port', env('MAIL_PORT', '2525')),
            'mail_username' => self::get('mail_username', env('MAIL_USERNAME', '')),
            'mail_password' => self::get('mail_password', env('MAIL_PASSWORD', '')),
            'mail_encryption' => self::get('mail_encryption', env('MAIL_ENCRYPTION', 'tls')),
            'mail_from_address' => self::get('mail_from_address', env('MAIL_FROM_ADDRESS', 'mail@example.com')),
            'mail_from_name' => self::get('mail_from_name', env('MAIL_FROM_NAME', env('APP_NAME', 'Alsernet'))),
        ];
    }

    /**
     * Establecer configuraciones de Email
     */
    public static function setEmailSettings(array $data): void
    {
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'mail_')) {
                self::set($key, $value);
            }
        }
    }

    /**
     * Obtener las reglas de validación para Email
     */
    public static function getEmailRules(): array
    {
        return [
            'mail_mailer' => 'required|string|in:smtp,sendmail,mailgun,ses,resend,log,array',
            'mail_host' => 'required|string',
            'mail_port' => 'required|integer|min:1|max:65535',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|string|in:tls,ssl',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
        ];
    }

    /**
     * Obtener configuraciones de Incoming Email
     */
    public static function getIncomingEmailSettings(): array
    {
        $rawSettings = self::get('incoming_email', '{}');
        $settings = is_string($rawSettings) ? json_decode($rawSettings, true) : $rawSettings;

        return [
            'imap' => [
                'connections' => $settings['imap']['connections'] ?? [],
            ],
            'pipe' => [
                'enabled' => $settings['pipe']['enabled'] ?? false,
                'mail_address' => $settings['pipe']['mail_address'] ?? '',
                'script_path' => $settings['pipe']['script_path'] ?? config_path('pipe.php'),
            ],
            'api' => [
                'enabled' => $settings['api']['enabled'] ?? false,
                'api_key' => $settings['api']['api_key'] ?? '',
                'api_url' => $settings['api']['api_url'] ?? url('/api/v1/incoming-email'),
            ],
            'gmail' => [
                'enabled' => $settings['gmail']['enabled'] ?? false,
                'client_id' => $settings['gmail']['client_id'] ?? '',
                'client_secret' => $settings['gmail']['client_secret'] ?? '',
                'redirect_uri' => $settings['gmail']['redirect_uri'] ?? route('settings.incoming-email.gmail.callback'),
                'connections' => $settings['gmail']['connections'] ?? [],
            ],
            'mailgun' => [
                'enabled' => $settings['mailgun']['enabled'] ?? false,
                'api_key' => $settings['mailgun']['api_key'] ?? '',
                'domain' => $settings['mailgun']['domain'] ?? '',
                'webhook_url' => $settings['mailgun']['webhook_url'] ?? url('/webhooks/mailgun'),
            ],
            'phplist' => [
                'enabled' => $settings['phplist']['enabled'] ?? false,
                'api_url' => $settings['phplist']['api_url'] ?? '',
                'api_key' => $settings['phplist']['api_key'] ?? '',
                'default_list' => $settings['phplist']['default_list'] ?? null,
            ],
        ];
    }

    /**
     * Establecer configuraciones de Incoming Email
     */
    public static function setIncomingEmailSettings(array $data): void
    {
        $current = self::getIncomingEmailSettings();

        // Update Pipe handler
        if (isset($data['pipe_enabled'])) {
            $current['pipe']['enabled'] = (bool) $data['pipe_enabled'];
        }
        if (isset($data['pipe_email_address'])) {
            $current['pipe']['mail_address'] = $data['pipe_email_address'];
        }

        // Update REST API handler
        if (isset($data['api_enabled'])) {
            $current['api']['enabled'] = (bool) $data['api_enabled'];
        }
        if (isset($data['api_key'])) {
            $current['api']['api_key'] = $data['api_key'];
        }

        // Update Gmail handler
        if (isset($data['gmail_enabled'])) {
            $current['gmail']['enabled'] = (bool) $data['gmail_enabled'];
        }
        if (isset($data['gmail_client_id'])) {
            $current['gmail']['client_id'] = $data['gmail_client_id'];
        }
        if (isset($data['gmail_client_secret'])) {
            $current['gmail']['client_secret'] = $data['gmail_client_secret'];
        }
        if (isset($data['gmail_redirect_uri'])) {
            $current['gmail']['redirect_uri'] = $data['gmail_redirect_uri'];
        }

        // Update Mailgun handler
        if (isset($data['mailgun_enabled'])) {
            $current['mailgun']['enabled'] = (bool) $data['mailgun_enabled'];
        }
        if (isset($data['mailgun_api_key'])) {
            $current['mailgun']['api_key'] = $data['mailgun_api_key'];
        }
        if (isset($data['mailgun_domain'])) {
            $current['mailgun']['domain'] = $data['mailgun_domain'];
        }

        // Update phpList handler
        if (isset($data['phplist_enabled'])) {
            $current['phplist']['enabled'] = (bool) $data['phplist_enabled'];
        }
        if (isset($data['phplist_api_url'])) {
            $current['phplist']['api_url'] = $data['phplist_api_url'];
        }
        if (isset($data['phplist_api_key'])) {
            $current['phplist']['api_key'] = $data['phplist_api_key'];
        }
        if (isset($data['phplist_default_list'])) {
            $current['phplist']['default_list'] = $data['phplist_default_list'];
        }

        self::set('incoming_email', json_encode($current));
    }

    /**
     * DATABASE Settings Management
     * Métodos para gestionar la configuración de base de datos
     */

    /**
     * Obtener todas las configuraciones de Base de Datos
     */
    public static function getDatabaseSettings(): array
    {
        return [
            'db_connection' => self::get('db_connection', env('DB_CONNECTION', 'mysql')),
            'db_host' => self::get('db_host', env('DB_HOST', 'localhost')),
            'db_port' => self::get('db_port', env('DB_PORT', '3306')),
            'db_database' => self::get('db_database', env('DB_DATABASE', '')),
            'db_username' => self::get('db_username', env('DB_USERNAME', 'root')),
            'db_password' => self::get('db_password', env('DB_PASSWORD', '')),
            'db_charset' => self::get('db_charset', 'utf8mb4'),
            'db_collation' => self::get('db_collation', 'utf8mb4_unicode_ci'),
        ];
    }

    /**
     * Establecer configuraciones de Base de Datos
     */
    public static function setDatabaseSettings(array $data): void
    {
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'db_')) {
                self::set($key, $value);
            }
        }
    }

    /**
     * Obtener las reglas de validación para Base de Datos
     */
    public static function getDatabaseRules(): array
    {
        return [
            'db_connection' => 'required|string|in:mysql,pgsql,sqlite,sqlsrv',
            'db_host' => 'required|string',
            'db_port' => 'required|integer|min:1|max:65535',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ];
    }

    /**
     * Obtener configuraciones de búsqueda
     */
    public static function getSearchSettings(): array
    {
        return [
            'search_enabled' => (bool) self::get('search_enabled', true),
            'search_driver' => self::get('search_driver', 'database'),
            'min_search_length' => (int) self::get('min_search_length', 3),
            'search_results_per_page' => (int) self::get('search_results_per_page', 20),
            'search_highlight_results' => (bool) self::get('search_highlight_results', true),
            'search_modules' => json_decode(self::get('search_modules', '[]'), true) ?: [],
        ];
    }

    /**
     * Establecer configuraciones de búsqueda
     */
    public static function setSearchSettings(array $data): void
    {
        if (isset($data['search_enabled'])) {
            self::set('search_enabled', (bool) $data['search_enabled']);
        }

        if (isset($data['search_driver'])) {
            self::set('search_driver', $data['search_driver']);
        }

        if (isset($data['min_search_length'])) {
            self::set('min_search_length', (int) $data['min_search_length']);
        }

        if (isset($data['search_results_per_page'])) {
            self::set('search_results_per_page', (int) $data['search_results_per_page']);
        }

        if (isset($data['search_highlight_results'])) {
            self::set('search_highlight_results', (bool) $data['search_highlight_results']);
        }

        if (isset($data['search_modules'])) {
            self::set('search_modules', json_encode($data['search_modules']));
        }
    }

    /**
     * Obtener configuraciones de localización
     */
    public static function getLocalizationSettings(): array
    {
        return [
            'default_language' => self::get('default_language', config('app.locale', 'es')),
            'timezone' => self::get('timezone', config('app.timezone', 'America/Bogota')),
            'date_format' => self::get('date_format', 'd/m/Y'),
            'time_format' => self::get('time_format', 'H:i'),
            'currency' => self::get('currency', 'EUR'),
            'currency_position' => self::get('currency_position', 'before'),
        ];
    }

    /**
     * Establecer configuraciones de localización
     */
    public static function setLocalizationSettings(array $data): void
    {
        if (isset($data['default_language'])) {
            self::set('default_language', $data['default_language']);
        }

        if (isset($data['timezone'])) {
            self::set('timezone', $data['timezone']);
        }

        if (isset($data['date_format'])) {
            self::set('date_format', $data['date_format']);
        }

        if (isset($data['time_format'])) {
            self::set('time_format', $data['time_format']);
        }

        if (isset($data['currency'])) {
            self::set('currency', $data['currency']);
        }

        if (isset($data['currency_position'])) {
            self::set('currency_position', $data['currency_position']);
        }
    }

    /**
     * Obtener configuraciones de carga de archivos
     */
    public static function getUploadingSettings(): array
    {
        return [
            'max_file_size' => (int) self::get('max_file_size', 10240), // KB
            'allowed_file_types' => json_decode(self::get('allowed_file_types', '["jpg","jpeg","png","pdf","doc","docx","xls","xlsx"]'), true) ?: [],
            'allowed_image_types' => json_decode(self::get('allowed_image_types', '["jpg","jpeg","png","gif","webp"]'), true) ?: [],
            'allowed_document_types' => json_decode(self::get('allowed_document_types', '["pdf","doc","docx","xls","xlsx","txt"]'), true) ?: [],
            'max_files_per_upload' => (int) self::get('max_files_per_upload', 10),
            'enable_virus_scan' => (bool) self::get('enable_virus_scan', false),
            'storage_driver' => self::get('storage_driver', 'local'),
            's3_bucket' => self::get('s3_bucket', ''),
            's3_region' => self::get('s3_region', 'us-east-1'),
        ];
    }

    /**
     * Establecer configuraciones de carga de archivos
     */
    public static function setUploadingSettings(array $data): void
    {
        if (isset($data['max_file_size'])) {
            self::set('max_file_size', (int) $data['max_file_size']);
        }

        if (isset($data['allowed_file_types'])) {
            self::set('allowed_file_types', json_encode($data['allowed_file_types']));
        }

        if (isset($data['allowed_image_types'])) {
            self::set('allowed_image_types', json_encode($data['allowed_image_types']));
        }

        if (isset($data['allowed_document_types'])) {
            self::set('allowed_document_types', json_encode($data['allowed_document_types']));
        }

        if (isset($data['max_files_per_upload'])) {
            self::set('max_files_per_upload', (int) $data['max_files_per_upload']);
        }

        if (isset($data['enable_virus_scan'])) {
            self::set('enable_virus_scan', (bool) $data['enable_virus_scan']);
        }

        if (isset($data['storage_driver'])) {
            self::set('storage_driver', $data['storage_driver']);
        }

        if (isset($data['s3_bucket'])) {
            self::set('s3_bucket', $data['s3_bucket']);
        }

        if (isset($data['s3_region'])) {
            self::set('s3_region', $data['s3_region']);
        }
    }
}
