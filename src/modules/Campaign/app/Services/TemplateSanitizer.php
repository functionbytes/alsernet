<?php

namespace Modules\Campaign\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Sanitiza el HTML de plantillas de email antes de guardarlas o enviarlas.
 * Elimina scripts, eventos inline peligrosos y mantiene el markup seguro.
 */
class TemplateSanitizer
{
    private static ?HTMLPurifier $purifier = null;

    public static function purify(string $html): string
    {
        if (self::$purifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('Cache.SerializerPath', storage_path('framework/cache/htmlpurifier'));

            self::$purifier = new HTMLPurifier($config);
        }

        return self::$purifier->purify($html);
    }

    /**
     * Verifica problemas comunes en plantillas de email.
     *
     * @return array{ok: bool, issues: string[]}
     */
    public static function validate(string $html): array
    {
        $issues = [];

        if (preg_match('/<script\b/i', $html)) {
            $issues[] = 'La plantilla contiene etiquetas <script>.';
        }

        if (preg_match('/javascript:/i', $html)) {
            $issues[] = 'La plantilla contiene URLs javascript:.';
        }

        if (preg_match('/on\w+\s*=/i', $html)) {
            $issues[] = 'La plantilla contiene event handlers inline (onclick, onload, etc.).';
        }

        if (preg_match('/<img[^>]+src=["\'][^"\']+[">]/i', $html, $m)) {
            if (! preg_match('/alt=/i', $m[0])) {
                $issues[] = 'Hay imágenes sin atributo alt.';
            }
        }

        if (preg_match('/<a\s+[^>]*href=["\']http:\/\//i', $html)) {
            $issues[] = 'Hay links con protocolo HTTP inseguro.';
        }

        $unresolvedVars = [];
        if (preg_match_all('/\{\{([^}]+)\}\}/', $html, $m)) {
            foreach ($m[1] as $var) {
                if (! in_array(trim($var), self::allowedVariables(), true)) {
                    $unresolvedVars[] = trim($var);
                }
            }
        }
        if (! empty($unresolvedVars)) {
            $issues[] = 'Variables no reconocidas: '.implode(', ', array_slice($unresolvedVars, 0, 5));
        }

        return [
            'ok' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Variables permitidas en plantillas.
     */
    public static function allowedVariables(): array
    {
        return [
            'SUBSCRIBER_EMAIL',
            'SUBSCRIBER_UID',
            'UNSUBSCRIBE_URL',
            'WEB_VIEW_URL',
            'UPDATE_PROFILE_URL',
            'CAMPAIGN_NAME',
            'CAMPAIGN_UID',
            'CAMPAIGN_SUBJECT',
            'CAMPAIGN_FROM_EMAIL',
            'CAMPAIGN_FROM_NAME',
            'CAMPAIGN_REPLY_TO',
            'CURRENT_YEAR',
            'CURRENT_MONTH',
            'CURRENT_DAY',
            'LIST_NAME',
            'LIST_FROM_NAME',
            'LIST_FROM_EMAIL',
        ];
    }
}
