# Auditoría — HelpdeskTranslate

> Fecha: 2026-06-29 · Health score: 80/100 · Estado: solid-minor-issues

**Resumen:** Add-on de traducción bien arquitecturado para Helpdesk (DeepL + LibreTranslate con caché a nivel de BD); convenciones limpias y buena cobertura de tests. Diagnóstico: sin hallazgos críticos ni altos. Quedan tres puntos medios: el set de traducciones EN está incompleto, hay un caché estático de settings que queda obsoleto en workers de cola de larga vida, y existe un vector SSRF / exfiltración de credenciales DeepL vía URLs de proveedor sin restringir (limitado por permiso de administrador). El resto son detalles menores de convenciones, performance y tests.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| HT-01 | medium | ux | modules/HelpdeskTranslate/lang/en/messages.php:1-74 | [CONFIRMADO] | S | Falta ~42 claves en traducción EN (todo el grupo settings.*) |
| HT-02 | medium | quality | modules/HelpdeskTranslate/app/Concerns/TranslatesMessage.php:23-51 | [CONFIRMADO] | S | $settingCache estático persiste entre jobs en workers de larga vida |
| HT-03 | medium | security | modules/HelpdeskTranslate/app/Http/Requests/UpdateTranslateSettingsRequest.php:23-24 | [CONFIRMADO] | M | URL de proveedor arbitraria permite SSRF y exfiltración de key DeepL (admin-gated) |
| HT-04 | low | performance | modules/HelpdeskTranslate/app/Services/DeepLTranslationService.php:51 | [CONFIRMADO] | S | Clave de caché de detección DeepL no namespaced por proveedor |
| HT-05 | low | conventions | modules/HelpdeskTranslate/module.json:3 | [CONFIRMADO] | S | Prefijo permiso/ruta usa 'helpdesk-translate' pero alias del módulo es 'helpdesktranslate' |
| HT-06 | low | performance | modules/HelpdeskTranslate/resources/views/partials/translate-panel.blade.php:72 | [CONFIRMADO] | S | Query ejecutada directamente en Blade (translate-panel) |
| HT-07 | low | tests | modules/HelpdeskTranslate/tests/Unit/.gitkeep:1 | [CONFIRMADO] | S | Sin tests unitarios; la suite Unit está vacía |
| HT-08 | low | quality | modules/HelpdeskTranslate/app/Concerns/TranslatesMessage.php:57-63 | [CONFIRMADO] | M | agentLocale() es un setting global único, no por agente |
| HT-09 | low | conventions | modules/HelpdeskTranslate/app/Http/Controllers/Managers/TranslateController.php:9 | [CONFIRMADO] | S | TranslateController no extiende el Controller base |

## Hallazgos detallados

### Medium

#### HT-01 — Falta ~42 claves en traducción EN (todo el grupo settings.*) · [CONFIRMADO]
- **Categoría:** ux · **Esfuerzo:** S
- **Archivo:línea:** `modules/HelpdeskTranslate/lang/en/messages.php:1-74`
- **Evidencia:** `lang/es` tiene 106 strings hoja vs 64 en `lang/en`; faltan claves que cubren todo el grupo settings (`provider_label`, `deepl_key_label`, `libre_section`, `stat_cached`, `btn_save`, `js_*`, etc.). Con `locale=en` la página de settings renderiza claves crudas como `helpdesktranslate::messages.settings.provider_label`.
- **Impacto:** Los administradores con locale en inglés ven etiquetas rotas/sin traducir en toda la pantalla de settings, violando la regla obligatoria de multi-idioma del proyecto.
- **Recomendación:** Rellenar las claves faltantes en `lang/en/messages.php` espejando `lang/es` (grupos settings, thread, panel).

#### HT-02 — $settingCache estático persiste entre jobs en workers de larga vida · [CONFIRMADO]
- **Categoría:** quality · **Esfuerzo:** S
- **Archivo:línea:** `modules/HelpdeskTranslate/app/Concerns/TranslatesMessage.php:23-51`
- **Evidencia:** `private static array $settingCache` memoiza resultados de `Setting::get()`. El docblock afirma "Reset between jobs by Horizon's forked worker model", pero los workers de `queue:work` / Horizon son procesos de larga vida que NO hacen fork por job, así que el array estático sobrevive entre jobs en el mismo worker.
- **Impacto:** Cambiar `auto_translate_incoming/outgoing` o `default_target` en el panel admin es ignorado por los workers ya en ejecución hasta reiniciarlos; ambos listeners comparten el mismo caché global de proceso.
- **Recomendación:** Hacer el caché de instancia (no estático) para que se resetee por job, o eliminar la memoización (las lecturas de Setting son baratas y probablemente Setting ya cachea internamente).

#### HT-03 — URL de proveedor arbitraria permite SSRF y exfiltración de key DeepL (admin-gated) · [CONFIRMADO]
- **Categoría:** security · **Esfuerzo:** M
- **Archivo:línea:** `modules/HelpdeskTranslate/app/Http/Requests/UpdateTranslateSettingsRequest.php:23-24`
- **Evidencia:** `deepl_url` y `libretranslate_endpoint` se validan solo como `['url','max:255']` sin allowlist de host. El `<select>` Blade ofrece dos hosts fijos pero un POST manipulado lo evade. Luego `TranslateSettingsController::test()` y `DeepLTranslationService` envían `Authorization: DeepL-Auth-Key {key}` a esa base URL (`TranslateSettingsController.php:74-81`, `DeepLTranslationService.php:55-58,128-130`).
- **Impacto:** Un usuario con `helpdesk-translate.settings.update` puede apuntar peticiones a un host interno (SSRF) o a un servidor atacante que capture la API key de DeepL. Severidad limitada por el gate de permiso de nivel administrador.
- **Recomendación:** Restringir `deepl_url` a una allowlist (`api-free.deepl.com` / `api.deepl.com`) vía `Rule::in`, y validar host/esquema de `libretranslate_endpoint` (bloquear rangos privados/loopback).

### Low

#### HT-04 — Clave de caché de detección DeepL no namespaced por proveedor · [CONFIRMADO]
- **Categoría:** performance · **Esfuerzo:** S
- **Archivo:línea:** `modules/HelpdeskTranslate/app/Services/DeepLTranslationService.php:51`
- **Evidencia:** `$cacheKey = 'helpdesk:ai:detect:'.md5($text.$targetLang)` ignora proveedor/base URL; LibreTranslate usa una clave separada pero ninguna se invalida al cambiar los settings de proveedor.
- **Impacto:** Cambiar de proveedor o de endpoint DeepL puede devolver un resultado de detección obsoleto cacheado por 24h. Menor y auto-corregible tras el TTL.
- **Recomendación:** Incluir proveedor/base-url en la clave de caché de detección, o limpiar estos cachés en `clearCache()` / actualización de settings.

#### HT-05 — Prefijo permiso/ruta usa 'helpdesk-translate' pero alias del módulo es 'helpdesktranslate' · [CONFIRMADO]
- **Categoría:** conventions · **Esfuerzo:** S
- **Archivo:línea:** `modules/HelpdeskTranslate/module.json:3`
- **Evidencia:** `module.json` `alias='helpdesktranslate'` (sin guion) mientras los permisos (`helpdesk-translate.use`, `helpdesk-translate.settings.*`) y el nombre de ruta de settings (`settings.helpdesk-translate.`) usan guion.
- **Impacto:** Uso inconsistente de `{alias}` hace menos predecible el descubrimiento de permisos/rutas; todo en minúsculas, no es problema de seguridad.
- **Recomendación:** Estandarizar una sola forma (documentar el alias con guion como intencional o alinear el alias en `module.json`).

#### HT-06 — Query ejecutada directamente en Blade (translate-panel) · [CONFIRMADO]
- **Categoría:** performance · **Esfuerzo:** S
- **Archivo:línea:** `modules/HelpdeskTranslate/resources/views/partials/translate-panel.blade.php:72`
- **Evidencia:** `@php $defaultTarget = strtolower((string) (\Modules\Helpdesk\Models\Setting::get('helpdesktranslate.default_target') ...)) @endphp` ejecuta un lookup de Setting dentro de la vista.
- **Impacto:** Acceso a BD en capa de vista; una sola query barata pero rompe la separación controlador/vista y dificulta el cacheo.
- **Recomendación:** Resolver `default_target` en el controlador/view composer y pasarlo a la vista.

#### HT-07 — Sin tests unitarios; la suite Unit está vacía · [CONFIRMADO]
- **Categoría:** tests · **Esfuerzo:** S
- **Archivo:línea:** `modules/HelpdeskTranslate/tests/Unit/.gitkeep:1`
- **Evidencia:** `tests/Unit` contiene solo `.gitkeep`. La lógica pura (`CachedTranslator` hash/fallback/resolución de proveedor, `TranslatesMessage::localesMatch`) solo está cubierta indirectamente por tests Feature.
- **Impacto:** Los casos límite de hash/fallback y matching de locales carecen de cobertura rápida y aislada.
- **Recomendación:** Añadir tests unitarios para `TranslationCache::makeHash`, fallback/resolución de proveedor de `CachedTranslator` y `localesMatch`.

#### HT-08 — agentLocale() es un setting global único, no por agente · [CONFIRMADO]
- **Categoría:** quality · **Esfuerzo:** M
- **Archivo:línea:** `modules/HelpdeskTranslate/app/Concerns/TranslatesMessage.php:57-63`
- **Evidencia:** `agentLocale()` devuelve el `helpdesktranslate.default_target` global para todos los agentes a pesar de que el nombre implica un locale por agente.
- **Impacto:** Equipos de agentes multi-locale auto-traducen todos al mismo idioma destino; nombre de método engañoso.
- **Recomendación:** Documentarlo como target global o derivarlo del locale del perfil del agente actuante; renombrar a `targetLocale()`.

#### HT-09 — TranslateController no extiende el Controller base · [CONFIRMADO]
- **Categoría:** conventions · **Esfuerzo:** S
- **Archivo:línea:** `modules/HelpdeskTranslate/app/Http/Controllers/Managers/TranslateController.php:9`
- **Evidencia:** `class TranslateController { ... }` no tiene clase base, a diferencia de los hermanos `DetectLanguageController`/`TranslateItemController` que extienden `App\Http\Controllers\Controller`.
- **Impacto:** Inconsistencia cosmética; la autorización la maneja el Form Request, sin brecha funcional (no puede usar `$this->authorize()`).
- **Recomendación:** Extender `App\Http\Controllers\Controller` para consistencia con los controllers hermanos.

## Plan de ataque priorizado

1. **HT-01 (medium, ux):** Corregir el set de traducción EN incompleto para que la UI de settings sea usable en inglés.
2. **HT-02 (medium, quality):** Corregir el caché estático de settings para que los toggles del admin surtan efecto en workers en ejecución.
3. **HT-03 (medium, security):** Restringir las URLs de proveedor para prevenir SSRF / exfiltración de key DeepL.
4. **HT-04–HT-09 (low):** Resolver como mejoras incrementales (caché de detección, alias, query en Blade, tests unitarios, agentLocale, controller base).

## Quick wins

- Rellenar las ~42 claves faltantes en `lang/en/messages.php` (HT-01).
- Hacer `TranslatesMessage::$settingCache` de instancia en vez de estático (HT-02).
- Restringir `deepl_url` a una allowlist `Rule::in` y validar host de `libretranslate_endpoint` (HT-03).
- Extender el Controller base en `TranslateController` (HT-09).

## Fortalezas

- Buen layering: controllers invocables delgados delegan al orquestador `CachedTranslator` con proveedores DeepL/LibreTranslate y caché persistente en BD; sin lógica de negocio en controllers.
- Cada endpoint autoriza vía Form Requests (`helpdesk-translate.use` / `.settings.*`) y middleware throttle; sin bypasses `return true`; permisos en minúsculas y seedeados espejando los roles de helpdesk.
- Los listeners están en cola (`ShouldQueue`, `tries`/`backoff`/`failed`) y registrados vía `Event::listen` con class-string (seguro para `event:cache`, no closures).
- JS seguro frente a XSS: el texto traducido se inyecta vía `document.createTextNode`, no `html()`; header CSRF en todo AJAX; Font Awesome 6 únicamente, sin estilos inline ni iconos Tabler.
- Migraciones sólidas: índice único en `text_hash`, índices compuestos (`source_lang`,`target_lang`) + `last_used_at`, guards idempotentes `hasColumn`, métodos `down()` reales.
- 9 tests Feature cubriendo controllers, listeners, fallback, multilenguaje y limpieza de caché.

## Cobertura de la auditoría

Lectura estática completa de todo el PHP (controllers, services, models, listeners, trait, form requests, providers, migraciones, seeders), rutas, config, 3 Blades y el asset JS; ficheros de idioma comparados (diff). Los tests NO se ejecutaron según instrucciones (BD de test bloqueada); la presencia de tests se evaluó solo estáticamente. El comportamiento en runtime de los modelos del host Helpdesk (`ConversationItem.is_internal`, forma del evento `MessageReceived`) se asume correcto a partir del uso, no verificado contra el código fuente del módulo Helpdesk.

## Descartados en verificación

Ninguno. No hubo hallazgos refutados; tampoco había hallazgos critical/high que requirieran verificación adicional.
