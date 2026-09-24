<?php

namespace Modules\HelpdeskContacts\Support;

use Modules\Helpdesk\Models\Setting;

/**
 * Estilos de la ficha de Contactos 360 (24-sep-2026). Se elige en
 * Ajustes → Helpdesk · Contactos y aplica a todos los agentes; la ficha
 * acepta además ?layout=<clave> para previsualizar sin guardar.
 *
 * Todos los estilos pintan las mismas secciones (mismos ids que rellena
 * contacts-360.js); lo que cambia es la disposición y los bloques propios
 * de cada uno, que pinta contacts-360-layouts.js.
 */
class ContactLayouts
{
    public const GROUP = 'contacts';

    public const KEY = 'contacts.detail_layout';

    public const DEFAULT = 'clasica';

    /**
     * @var array<string, array{label: string, desc: string}>
     */
    public const OPTIONS = [
        'clasica' => [
            'label' => 'Clásica',
            'desc' => 'Cabecera con métricas, avisos e historial a la izquierda y compras, fuentes y notas a la derecha.',
        ],
        'linea' => [
            'label' => 'Línea de vida',
            'desc' => 'Tres columnas: identidad y salud, una cronología única de todos los canales y la tienda al lado.',
        ],
        'acciones' => [
            'label' => 'Qué hacer ahora',
            'desc' => 'Las acciones pendientes como tarjetas, la conversación en curso y los compromisos abiertos con su plazo.',
        ],
        'valor' => [
            'label' => 'Valor del cliente',
            'desc' => 'Indicadores de compra, gasto por mes, canales por los que escribe y productos que más compra.',
        ],
        'maestro' => [
            'label' => 'Maestro-detalle',
            'desc' => 'Lista de contactos fija a la izquierda, navegación con J/K y buscador de acciones con la tecla punto.',
        ],
    ];

    /** @return string[] */
    public static function keys(): array
    {
        return array_keys(self::OPTIONS);
    }

    /**
     * El estilo guardado, o $override si es una clave válida (previsualizar).
     */
    public static function current(?string $override = null): string
    {
        if ($override !== null && isset(self::OPTIONS[$override])) {
            return $override;
        }

        try {
            $stored = (string) Setting::get(self::KEY, self::DEFAULT);
        } catch (\Throwable) {
            $stored = self::DEFAULT;
        }

        return isset(self::OPTIONS[$stored]) ? $stored : self::DEFAULT;
    }
}
