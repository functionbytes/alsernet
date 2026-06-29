<?php

return [

    'sidebar' => [
        'section' => 'Helpdesk · Traducciones',
        'item' => 'Traducciones (DeepL)',
    ],

    'errors' => [
        'service_unavailable' => 'El servicio de traducción no está disponible.',
        'item_empty' => 'El item no tiene texto para traducir.',
        'no_api_key' => 'No hay API key configurada.',
        'deepl_status' => 'DeepL respondió con código :status.',
        'deepl_connect' => 'Error contactando DeepL: :message',
        'cache_clear_failed' => 'No se pudo limpiar el caché.',
    ],

    'success' => [
        'settings_saved' => 'Configuración de traducciones actualizada correctamente.',
        'deepl_ok' => 'Conexión OK.',
        'cache_cleared' => 'Caché limpiado: :count traducciones eliminadas.',
    ],

    'settings' => [
        'page_title' => 'Configuración de traducciones',
        'card_title' => 'Traducciones (HelpdeskTranslate)',
        'card_description' => 'Configura el proveedor de traducciones, la API key de DeepL y el idioma destino predeterminado. Estos ajustes se aplican al botón <strong>Traducir</strong> del chat y a la traducción automática de mensajes entrantes.',

        'cache_section' => 'Caché local de traducciones',
        'stat_cached' => 'Frases cacheadas',
        'stat_hits' => 'Hits totales',
        'stat_chars_saved' => 'Caracteres ahorrados',
        'stat_saving' => 'Ahorro estimado',
        'btn_clear_cache' => 'Limpiar caché',

        'provider_section' => 'Proveedor y comportamiento',
        'provider_label' => 'Proveedor',
        'provider_deepl' => 'DeepL (en la nube · pago)',
        'provider_libre' => 'LibreTranslate (self-hosted · gratis)',
        'provider_help' => 'DeepL ofrece mejor calidad. LibreTranslate corre local (Docker) sin coste por uso.',

        'target_label' => 'Idioma destino predeterminado',
        'target_help' => 'Idioma usado cuando el agente no especifica uno explícitamente.',

        'auto_incoming_label' => 'Auto-traducir mensajes entrantes',
        'auto_incoming_help' => 'Traduce automáticamente los mensajes del visitante cuando el idioma detectado no coincide con el del agente. Configurable también por bandeja en <code>additional_attributes.auto_translate_enabled</code>.',
        'auto_outgoing_label' => 'Auto-traducir respuestas del agente',
        'auto_outgoing_help' => 'Traduce automáticamente las respuestas del agente al idioma detectado del visitante. Requiere que el idioma del visitante haya sido detectado previamente.',

        'libre_section' => 'LibreTranslate (self-hosted)',
        'libre_endpoint' => 'Endpoint',
        'libre_key_label' => 'API key (opcional)',
        'libre_key_saved' => 'Ya hay una API key guardada. Déjalo vacío para conservarla.',
        'libre_key_none' => 'Por defecto LibreTranslate no requiere autenticación.',
        'libre_info' => '¿Aún no tienes LibreTranslate corriendo? Levanta el contenedor con: <code>docker compose -f docker-compose.libretranslate.yml up -d</code> (incluido en la raíz del proyecto). Tarda unos minutos la primera vez en bajar los modelos.',

        'deepl_section' => 'Credenciales DeepL',
        'deepl_key_label' => 'API key',
        'deepl_key_env' => 'Hay una key configurada en <code>.env</code> (<code>DEEPL_API_KEY</code>) — la key guardada aquí la sobrescribe.',
        'deepl_key_saved' => 'Ya hay una API key guardada. Déjalo vacío para conservarla.',
        'deepl_key_none' => 'Genera una key gratuita en <a href="https://www.deepl.com/pro-api" target="_blank" rel="noopener">deepl.com/pro-api</a>.',
        'deepl_key_ph_saved' => '•••••••• (key guardada — déjalo vacío para no cambiarla)',
        'deepl_url_label' => 'URL de la API',
        'deepl_url_help' => 'Usa Pro si tu key es de pago.',
        'deepl_url_free' => 'Free (api-free.deepl.com)',
        'deepl_url_pro' => 'Pro (api.deepl.com)',

        'libre_key_ph_saved' => '•••••••• (key guardada — déjalo vacío para no cambiarla)',

        'btn_save' => 'Guardar configuración',
        'btn_test' => 'Probar conexión',

        'js_confirm_clear' => '¿Eliminar todas las traducciones cacheadas? Esto no se puede deshacer.',
        'js_clearing' => 'Limpiando...',
        'js_testing' => 'Probando…',
        'js_test_usage' => ' — Uso: :count / :limit caracteres',
        'js_test_ok_default' => 'Conexión OK',
        'js_error_default' => 'Error de conexión',
    ],

    'panel' => [
        'title' => 'Traducción de conversación',
        'mode_label' => 'Modo de traducción',
        'mode_incoming' => 'Entrantes',
        'mode_incoming_desc' => 'Traducir mensajes del cliente',
        'mode_outgoing' => 'Salientes',
        'mode_outgoing_desc' => 'Traducir mis mensajes antes de enviar',
        'mode_both' => 'Ambos',
        'mode_both_desc' => 'Traducción bidireccional',
        'lang_from' => 'Idioma origen',
        'lang_to' => 'Idioma destino',
        'btn_disable' => 'Desactivar',
        'btn_enable' => 'Activar traducción',
    ],

    'composer' => [
        'tab' => 'Traducir',
    ],

    'thread' => [
        'btn_translate' => 'Traducir',
        'btn_translate_title' => 'Traducir este mensaje',
        'js_translated' => 'Mensaje traducido',
        'js_error' => 'No se pudo traducir.',
        'js_from' => 'Traducido desde :lang',
    ],

    'languages' => [
        'auto' => 'Detectar automáticamente',
        'es' => 'Español',
        'en' => 'Inglés',
        'fr' => 'Francés',
        'de' => 'Alemán',
        'pt' => 'Portugués',
        'it' => 'Italiano',
    ],

    'validation' => [
        // TranslateRequest
        'text_required' => 'El texto a traducir es obligatorio.',
        'text_max' => 'El texto no puede superar los 2000 caracteres.',
        'from_in' => 'El idioma de origen no es válido.',
        'to_required' => 'El idioma de destino es obligatorio.',
        'to_regex' => 'El idioma de destino no es válido (usa un código ISO como `es` o `en-US`).',
        // TranslateItemRequest
        'target_required' => 'El idioma de destino es obligatorio.',
        'target_regex' => 'El idioma de destino no es válido (usa un código ISO como `es` o `en-US`).',
        // UpdateTranslateSettingsRequest
        'provider_required' => 'El proveedor de traducción es obligatorio.',
        'provider_in' => 'El proveedor seleccionado no es válido.',
        'default_target_required' => 'El idioma de destino predeterminado es obligatorio.',
        'default_target_in' => 'El idioma de destino seleccionado no está soportado.',
        'deepl_key_max' => 'La API key de DeepL no puede superar los 255 caracteres.',
        'deepl_url_in' => 'La URL de DeepL debe ser uno de los endpoints oficiales (Free o Pro).',
        'libretranslate_endpoint_url' => 'El endpoint de LibreTranslate debe ser una URL http o https válida.',
        // DetectLanguageRequest
        'detect_text_required' => 'El texto es obligatorio.',
        'detect_text_max' => 'El texto no puede superar los 2000 caracteres.',
    ],

    'attributes' => [
        // TranslateRequest
        'text' => 'texto',
        'from' => 'idioma origen',
        'to' => 'idioma destino',
        // TranslateItemRequest
        'target' => 'idioma de destino',
        // UpdateTranslateSettingsRequest
        'provider' => 'proveedor',
        'default_target' => 'idioma destino predeterminado',
        'auto_translate_incoming' => 'auto-traducir entrantes',
        'auto_translate_outgoing' => 'auto-traducir salientes',
        'deepl_key' => 'API key DeepL',
        'deepl_url' => 'URL DeepL',
        'libretranslate_endpoint' => 'endpoint LibreTranslate',
    ],

];
