<?php

namespace Modules\HelpdeskChatFlow\Services\Concerns;

use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\Support\ContextPath;

/**
 * Helpers shared by the node executor and the node handlers: localisation of
 * fixed bot texts/options into the customer's language, {{var}} interpolation
 * and graph navigation. Requires a `ChatFlowLocalizer $localizer` property.
 */
trait RendersNodeMessages
{
    /**
     * Translate a fixed bot message into the customer's language, if detected.
     */
    protected function localizeForCustomer(string $text, ChatFlowSession $session): string
    {
        return $this->localizer->localize($text, $session->getContextValue('customer_lang'));
    }

    /**
     * Traduce las etiquetas de las opciones al idioma del cliente (una sola
     * llamada al traductor para toda la lista) y guarda las mostradas en
     * `option_labels` para que el motor reconozca la respuesta del cliente
     * cuando escribe o pulsa la etiqueta (traducida o no). Las ramas del flujo siguen
     * usando la etiqueta original.
     *
     * @param  array<int, string>  $options
     * @return array<int, string>
     */
    protected function localizeOptions(array $options, ChatFlowSession $session): array
    {
        $options = array_values(array_map('strval', $options));
        $localized = $options;

        if ($options !== [] && $session->getContextValue('customer_lang')) {
            $joined = $this->localizeForCustomer(implode("\n", $options), $session);
            $parts = array_map('trim', explode("\n", trim($joined)));

            // Si el traductor une o parte líneas, no hay correspondencia fiable.
            if (count($parts) === count($options) && ! in_array('', $parts, true)) {
                $localized = $parts;
            }
        }

        // Siempre, aunque no se traduzca: los botones del widget envían la
        // etiqueta y el CSAT solo puntúa números.
        $session->setContextValue('option_labels', $localized !== [] ? $localized : null);

        return $localized;
    }

    protected function optionsHint(ChatFlowSession $session): string
    {
        return $this->localizeForCustomer('Responde con el número de la opción.', $session);
    }

    protected function interpolateContext(string $text, array $context): string
    {
        return ContextPath::interpolate($text, $context);
    }

    protected function getFirstChildId(array $node, ChatFlowSession $session): ?string
    {
        return $session->chatFlow->childrenByParent()[$node['id']][0]['id'] ?? null;
    }
}
