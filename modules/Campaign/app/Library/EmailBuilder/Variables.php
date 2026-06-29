<?php

namespace Modules\Campaign\Library\EmailBuilder;

use Modules\Campaign\Models\CampaignMaillist;

/**
 * Variables disponibles para el Email Builder.
 *
 * Genera el catálogo de tags que se pueden usar dentro de un template
 * (procesados por HtmlHandler\TransformTag al enviar el email).
 *
 * Tipos:
 *   - SYSTEM: variables siempre disponibles (UNSUBSCRIBE_URL, EMAIL, etc.)
 *   - LIST_FIELD: campos de la lista concreta (FIRST_NAME, LAST_NAME y custom)
 *
 * Convención: dentro del template las variables van como {{TAG}} o {TAG};
 * TransformTag las reemplaza con el valor real al enviar el mensaje.
 */
class Variables
{
    /**
     * Variables del sistema, siempre disponibles independientemente de la lista.
     *
     * @return array<int, array{tag: string, label: string, group: string, description?: string}>
     */
    public static function system(): array
    {
        return [
            ['tag' => 'EMAIL', 'label' => 'Email del suscriptor', 'group' => 'subscriber'],
            ['tag' => 'NAME', 'label' => 'Nombre completo', 'group' => 'subscriber', 'description' => 'first_name + last_name'],
            ['tag' => 'FULL_NAME', 'label' => 'Nombre completo (alias)', 'group' => 'subscriber'],
            ['tag' => 'SUBSCRIBER_UID', 'label' => 'UID del suscriptor', 'group' => 'subscriber'],

            ['tag' => 'UNSUBSCRIBE_URL', 'label' => 'URL desuscripción', 'group' => 'links', 'description' => 'One-click compatible RFC 8058'],
            ['tag' => 'MANAGE_URL', 'label' => 'URL gestionar preferencias', 'group' => 'links'],
            ['tag' => 'UPDATE_PROFILE_URL', 'label' => 'URL actualizar perfil', 'group' => 'links'],
            ['tag' => 'WEB_VIEW_URL', 'label' => 'URL ver email en navegador', 'group' => 'links'],

            ['tag' => 'COMPANY_ADDRESS', 'label' => 'Dirección de la empresa', 'group' => 'list'],
            ['tag' => 'CAMPAIGN_NAME', 'label' => 'Nombre de la campaña', 'group' => 'campaign'],
            ['tag' => 'CAMPAIGN_SUBJECT', 'label' => 'Asunto de la campaña', 'group' => 'campaign'],
        ];
    }

    /**
     * Variables específicas de los CampaignField de una lista (FIRST_NAME,
     * LAST_NAME, custom fields, etc.).
     */
    public static function forList(CampaignMaillist $list): array
    {
        $fields = $list->fields()->orderBy('order')->get();

        return $fields->map(fn ($f) => [
            'tag' => $f->tag,
            'label' => $f->label,
            'group' => 'list_field',
            'description' => "Tipo: {$f->type}".($f->required ? ' · obligatorio' : ''),
        ])->all();
    }

    /**
     * Catálogo completo agrupado por grupo (para UI sidebar).
     *
     * @return array<string, array<int, array>>
     */
    public static function catalogForList(?CampaignMaillist $list = null): array
    {
        $all = self::system();

        if ($list) {
            $all = array_merge($all, self::forList($list));
        }

        // Agrupar
        $grouped = [];
        foreach ($all as $v) {
            $g = $v['group'];
            $grouped[$g][] = $v;
        }

        // Orden de grupos legible
        $order = ['subscriber', 'list_field', 'links', 'list', 'campaign'];
        $sorted = [];
        foreach ($order as $g) {
            if (isset($grouped[$g])) {
                $sorted[$g] = $grouped[$g];
            }
        }

        return $sorted;
    }

    /**
     * Etiqueta legible por grupo.
     */
    public static function groupLabel(string $group): string
    {
        return match ($group) {
            'subscriber' => '📧 Suscriptor',
            'list_field' => '🏷️ Campos de la lista',
            'links' => '🔗 Enlaces especiales',
            'list' => '📋 Datos de la lista',
            'campaign' => '🎯 Campaña',
            default => ucfirst($group),
        };
    }

    /**
     * Extrae todas las variables {{...}} usadas en un HTML. Útil para detectar
     * variables que no existen en la lista (validación al guardar).
     *
     * @return string[] Tags únicos encontrados
     */
    public static function extractFromHtml(string $html): array
    {
        preg_match_all('/\{\{?\s*([A-Z][A-Z0-9_]*)\s*\}\}?/', $html, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * Devuelve los tags usados en el HTML que NO están definidos
     * (ni system ni list field). Para warnings al guardar.
     *
     * @return string[]
     */
    public static function findUndefined(string $html, ?CampaignMaillist $list = null): array
    {
        $defined = collect(self::system())->pluck('tag')->all();
        if ($list) {
            $defined = array_merge($defined, collect(self::forList($list))->pluck('tag')->all());
        }

        $used = self::extractFromHtml($html);

        return array_values(array_diff($used, $defined));
    }
}
