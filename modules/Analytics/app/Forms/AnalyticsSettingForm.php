<?php

namespace Modules\Analytics\Forms;

class AnalyticsSettingForm
{
    /**
     * Get form fields configuration.
     */
    public static function getFields(): array
    {
        return [
            'general' => [
                'title' => 'Configuración General',
                'description' => 'Configuración básica de Google Analytics',
                'fields' => [
                    'google_analytics_enable' => [
                        'type' => 'checkbox',
                        'label' => 'Habilitar Google Analytics',
                        'help' => 'Activa el seguimiento de Google Analytics en tu sitio web',
                        'default' => false,
                        'attributes' => [
                            'class' => 'form-check-input',
                        ],
                    ],
                    'google_analytics_property_id' => [
                        'type' => 'text',
                        'label' => 'GA4 Property ID',
                        'help' => 'Número de 9-10 dígitos encontrado en Admin → Properties & apps',
                        'placeholder' => '123456789',
                        'required' => false,
                        'attributes' => [
                            'class' => 'form-control',
                            'pattern' => '[0-9]+',
                            'maxlength' => 50,
                        ],
                    ],
                    'google_analytics_credentials' => [
                        'type' => 'textarea',
                        'label' => 'JSON de credenciales',
                        'help' => 'Pega aquí el contenido completo del archivo JSON de credenciales descargado desde Google Cloud Console',
                        'placeholder' => '{"type": "service_account", "project_id": "...", ...}',
                        'required' => false,
                        'attributes' => [
                            'class' => 'form-control font-monospace',
                            'rows' => 8,
                        ],
                    ],
                ],
            ],
            'cache' => [
                'title' => 'Configuración de Caché',
                'description' => 'Optimización del rendimiento mediante caché',
                'fields' => [
                    'analytics_cache_lifetime' => [
                        'type' => 'number',
                        'label' => 'Tiempo de vida de caché (minutos)',
                        'help' => 'Duración en minutos para mantener los datos en caché. Recomendado: 60 minutos',
                        'default' => 60,
                        'min' => 1,
                        'max' => 1440, // 24 hours
                        'attributes' => [
                            'class' => 'form-control',
                        ],
                    ],
                ],
            ],
            'widgets' => [
                'title' => 'Widgets del Dashboard',
                'description' => 'Selecciona los widgets que deseas mostrar en el dashboard',
                'fields' => [
                    'analytics_dashboard_widgets' => [
                        'type' => 'checkbox-group',
                        'label' => 'Widgets activos',
                        'help' => 'Selecciona los widgets que deseas mostrar',
                        'options' => [
                            'general' => 'Estadísticas Generales',
                            'top_pages' => 'Páginas Más Visitadas',
                            'top_browsers' => 'Navegadores Principales',
                            'top_referrers' => 'Fuentes de Tráfico',
                        ],
                        'default' => ['general', 'top_pages', 'top_browsers', 'top_referrers'],
                        'attributes' => [
                            'class' => 'form-check-input',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get available metrics.
     */
    public static function getAvailableMetrics(): array
    {
        return [
            'sessions' => 'Sesiones',
            'totalUsers' => 'Usuarios Totales',
            'screenPageViews' => 'Vistas de Página',
            'bounceRate' => 'Tasa de Rebote',
            'engagementRate' => 'Tasa de Engagement',
            'averageSessionDuration' => 'Duración Promedio de Sesión',
        ];
    }

    /**
     * Get available dimensions.
     */
    public static function getAvailableDimensions(): array
    {
        return [
            'date' => 'Fecha',
            'pageTitle' => 'Título de Página',
            'fullPageUrl' => 'URL Completa',
            'browser' => 'Navegador',
            'country' => 'País',
            'city' => 'Ciudad',
            'sessionSource' => 'Fuente de Sesión',
            'deviceCategory' => 'Categoría de Dispositivo',
        ];
    }

    /**
     * Get available date ranges.
     */
    public static function getDateRanges(): array
    {
        return [
            'today' => 'Hoy',
            'yesterday' => 'Ayer',
            'last_7_days' => 'Últimos 7 días',
            'last_30_days' => 'Últimos 30 días',
            'this_month' => 'Este mes',
            'last_month' => 'Mes pasado',
            'this_year' => 'Este año',
        ];
    }

    /**
     * Get widget types.
     */
    public static function getWidgetTypes(): array
    {
        return [
            'general' => [
                'name' => 'Estadísticas Generales',
                'description' => 'Resumen general de métricas clave',
                'icon' => 'fa-chart-line',
            ],
            'top_pages' => [
                'name' => 'Páginas Más Visitadas',
                'description' => 'Lista de las páginas con más visitas',
                'icon' => 'fa-file-alt',
            ],
            'top_browsers' => [
                'name' => 'Navegadores Principales',
                'description' => 'Distribución de navegadores de los visitantes',
                'icon' => 'fa-globe',
            ],
            'top_referrers' => [
                'name' => 'Fuentes de Tráfico',
                'description' => 'Principales fuentes de tráfico web',
                'icon' => 'fa-external-link-alt',
            ],
        ];
    }

    /**
     * Validate form data.
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // Validate enable field
        if (isset($data['google_analytics_enable']) && $data['google_analytics_enable']) {
            // Property ID is required when enabled
            if (empty($data['google_analytics_property_id'])) {
                $errors['google_analytics_property_id'] = 'El ID de propiedad es obligatorio cuando Google Analytics está habilitado.';
            }

            // Credentials are required when enabled
            if (empty($data['google_analytics_credentials'])) {
                $errors['google_analytics_credentials'] = 'Las credenciales son obligatorias cuando Google Analytics está habilitado.';
            }
        }

        // Validate cache lifetime
        if (isset($data['analytics_cache_lifetime'])) {
            $lifetime = (int) $data['analytics_cache_lifetime'];
            if ($lifetime < 1 || $lifetime > 1440) {
                $errors['analytics_cache_lifetime'] = 'El tiempo de vida de caché debe estar entre 1 y 1440 minutos.';
            }
        }

        return $errors;
    }
}
