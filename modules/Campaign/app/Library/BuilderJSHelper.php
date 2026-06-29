<?php

namespace Modules\Campaign\Library;

/**
 * Carga las plantillas EJS de un tema BuilderJS (pre-inyectadas en la vista
 * builder.default para evitar fetches uno a uno en el navegador).
 *
 * Portado de acellemail (App\Library\BuilderJSHelper), leyendo desde
 * resources/themes del módulo Campaign en vez del resource_path del host.
 */
class BuilderJSHelper
{
    /**
     * @return array<string, string> nombre de plantilla => HTML EJS
     */
    public static function loadTemplates(string $theme = 'default'): array
    {
        $themePath = module_path('Campaign', 'resources/themes/'.$theme);
        $indexFile = $themePath.'/index.json';

        if (! file_exists($indexFile)) {
            return [];
        }

        $configData = json_decode(file_get_contents($indexFile));
        $allNames = array_merge($configData->pages ?? [], $configData->templates ?? []);

        $templates = [];
        foreach ($allNames as $name) {
            $file = $themePath.'/'.$name.'.template.html';
            if (file_exists($file)) {
                $templates[$name] = file_get_contents($file);
            }
        }

        return $templates;
    }
}
