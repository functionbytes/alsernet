# Auditoría — HelpdeskHelpcenter

> Fecha: 2026-06-29 · Health score: 84/100 · Estado: solid-minor-issues

**Resumen:** Módulo de base de conocimiento bien estructurado (policies registradas, Form Requests en español, búsqueda FULLTEXT, embeddings en cola, eager loading y buena cobertura de tests). El único problema de relevancia es un XSS por ruptura del bloque `<script>` JSON-LD en la página pública de artículo, acompañado de algunos detalles menores de convención y cableado. No se detectaron hallazgos critical/high.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| hh-01 | medium | security | resources/views/public/helpcenter/show.blade.php:33-46 | [CONFIRMADO] | S | Bloque JSON-LD con `JSON_UNESCAPED_SLASHES` permite ruptura `</script>` (XSS almacenado) |
| hh-02 | low | security | app/Http/Controllers/Managers/HelpCenterController.php:482,502,521 | [CONFIRMADO] | S | Endpoints JSON de lectura sin `authorize()` (solo middleware de rol) |
| hh-03 | low | tests | app/Services/EmbeddingsService.php:69-194 | [CONFIRMADO] | M | `EmbeddingsService` sin tests unitarios |
| hh-04 | low | performance | app/Http/Controllers/Managers/HelpCenterController.php:543-547,558-562 | [CONFIRMADO] | S | Reordenar emite un UPDATE por id dentro de un bucle |
| hh-05 | low | quality | app/Http/Controllers/Managers/HelpCenterController.php:313 | [CONFIRMADO] | S | Captura por referencia muerta de `$article` en `storeArticle()` |
| hh-06 | low | conventions | routes/managers.php:10-37 | [CONFIRMADO] | M | Rutas manager se desvían de las convenciones REST/route |

## Hallazgos detallados

### hh-01 · [CONFIRMADO] · medium · security
**Bloque JSON-LD con `JSON_UNESCAPED_SLASHES` permite ruptura `</script>` (XSS almacenado)**
`resources/views/public/helpcenter/show.blade.php:33-46`

- **Evidencia:** Dentro de `<script type="application/ld+json">` los datos (`headline=$articleTitle`, `description` desde `$articleBody`) se emiten con `{!! json_encode([...], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}`. Con las barras sin escapar, un título de artículo que contenga `</script><script>...` rompe el bloque JSON-LD y se ejecuta en todos los visitantes públicos. El título es asignable por cualquiera con `helpdesk.helpcenter.articles.create/update` (la policy también concede `update` al autor del artículo).
- **Impacto:** XSS almacenado contra visitantes públicos no autenticados, inyectable por un autor con privilegios.
- **Recomendación:** No usar `JSON_UNESCAPED_SLASHES` dentro de una etiqueta `<script>`; añadir `JSON_HEX_TAG|JSON_HEX_AMP` (manteniendo `JSON_UNESCAPED_UNICODE`), o construir el JSON-LD con `Illuminate\Support\Js::from()`. El caso de `admin edit.blade.php:237` ya es seguro porque `json_encode` por defecto escapa las barras.
- **Esfuerzo:** S

### hh-02 · [CONFIRMADO] · low · security
**Endpoints JSON de lectura sin `authorize()` (solo middleware de rol)**
`app/Http/Controllers/Managers/HelpCenterController.php:482,502,521`

- **Evidencia:** `apiCategories()`, `apiSections()` y `apiSectionArticles()` no tienen llamada `$this->authorize()` (todas las demás acciones sí). `apiSectionArticles()` además devuelve títulos de artículos en borrador (no publicados). Solo están protegidos por el middleware del grupo de rutas `role:super-admin|super-settings`.
- **Impacto:** Autorización inconsistente; cualquier portador de esos roles amplios puede leer datos de categorías/secciones/borradores aunque se hubieran revocado los permisos granulares de helpcenter.
- **Recomendación:** Añadir `$this->authorize('viewAny', HelpCenterCategory::class)` / `HelpCenterArticle::class` al inicio de estos métodos para alinearse con el resto del controlador.
- **Esfuerzo:** S

### hh-03 · [CONFIRMADO] · low · tests
**`EmbeddingsService` sin tests unitarios**
`app/Services/EmbeddingsService.php:69-194`

- **Evidencia:** `tests/` contiene solo 4 archivos Feature; no hay directorio `tests/Unit`. Las funciones puras `chunkText()` (lógica de límites/solapamiento) y `cosineSimilarity()` son altamente testeables sin DB ni HTTP y están actualmente sin cobertura.
- **Impacto:** La lógica de chunking/solapamiento y la matemática de similitud pueden regresar en silencio; se ejecutan durante la búsqueda/indexado con IA.
- **Recomendación:** Añadir `tests/Unit` cubriendo casos límite de `chunkText()` (texto corto, texto largo con solapamiento, límites de oración, filtrado de <50 caracteres) y `cosineSimilarity()` (longitudes dispares, vectores cero).
- **Esfuerzo:** M

### hh-04 · [CONFIRMADO] · low · performance
**Reordenar emite un UPDATE por id dentro de un bucle**
`app/Http/Controllers/Managers/HelpCenterController.php:543-547,558-562`

- **Evidencia:** `apiReorderCategories()`/`apiReorderArticles()` hacen `foreach ($ids as $position => $id) { Model::where('id',$id)->update(['position'=>$position]); }` — N escrituras por reordenamiento.
- **Impacto:** Menor; aceptable para listas pequeñas pero escala mal y añade round-trips en secciones grandes.
- **Recomendación:** Usar un único bulk update con `CASE WHEN` (o `upsert`) dentro de la transacción existente.
- **Esfuerzo:** S

### hh-05 · [CONFIRMADO] · low · quality
**Captura por referencia muerta de `$article` en `storeArticle()`**
`app/Http/Controllers/Managers/HelpCenterController.php:313`

- **Evidencia:** `transaction(function () use ($request, $validated, &$article) {...})` captura `&$article`, pero `$article` nunca se declara antes del closure ni se lee después de que la transacción retorna.
- **Impacto:** Código muerto confuso; depende de que PHP cree implícitamente la variable externa.
- **Recomendación:** Eliminar la referencia `&$article` (o retornar el artículo creado desde el closure si se necesita).
- **Esfuerzo:** S

### hh-06 · [CONFIRMADO] · low · conventions
**Rutas manager se desvían de las convenciones REST/route**
`routes/managers.php:10-37`

- **Evidencia:** Usa `POST /categories/store`, `POST /categories/update` y `POST /articles/update` (id en el body) en lugar de `POST store` + `PUT /{id}`; no hay ruta `bulk-action`. El naming es `manager.helpcenter.*` bajo el prefijo `panel/helpdesk` en vez del estándar `{alias}`. El naming/prefix es una excepción documentada de Helpdesk, pero el uso de verbos no lo es.
- **Impacto:** Inconsistencia cosmética con las reglas de routing del proyecto; dificulta razonar sobre la semántica REST.
- **Recomendación:** Donde sea práctico, mover las actualizaciones a `PUT /{id}` y mantener la excepción documentada de naming de helpdesk. Como mínimo documentar el uso intencional de POST estilo SPA.
- **Esfuerzo:** M

## Plan de ataque priorizado

1. **hh-01 (medium, S):** Eliminar `JSON_UNESCAPED_SLASHES` del bloque JSON-LD y añadir `JSON_HEX_TAG|JSON_HEX_AMP` o usar `Js::from()`. Es el único riesgo de seguridad real (XSS público).
2. **hh-02 (low, S):** Añadir `authorize('viewAny')` a los tres métodos `api*` de lectura para defensa en profundidad.
3. **hh-03 (low, M):** Crear `tests/Unit` para `chunkText()` y `cosineSimilarity()`.
4. **hh-04 / hh-05 / hh-06 (low):** Limpiezas de calidad/rendimiento/convención cuando se toque el área.

## Quick wins

- Eliminar la captura por referencia muerta `&$article` en `storeArticle()` (hh-05).
- Reemplazar el bucle de UPDATE por fila en `apiReorder*` por un único `CASE`/batch update (hh-04).
- Añadir `authorize('viewAny')` a los tres métodos `api*` de lectura del manager (hh-02).

## Fortalezas

- Policies registradas vía `Gate::policy` en el ServiceProvider; los controladores llaman consistentemente a `$this->authorize()`; los permisos Spatie siguen la convención `helpdesk.helpcenter.{entity}.{action}`.
- Toda la validación pasa por Form Requests dedicados con `messages()`/`attributes()` en español; los modelos usan el método `casts()` y `$fillable` explícito.
- Búsqueda FULLTEXT (`MATCH...AGAINST`) vía trait compartido `BuildsFulltextSearch` con binds parametrizados y fallback LIKE; el SQL crudo usa bindings (sin inyección).
- Job de embeddings en cola con `tries`/`timeout`/`backoff` y `failed()`; las llamadas a OpenAI van a una URL fija (sin SSRF) con retry/backoff y key desde config.
- El cuerpo público del artículo se renderiza mediante `clean()` (HTMLPurifier); buen eager loading (`categories.parent`, `articles.author`) evita N+1; los listados están paginados.
- Cobertura sólida de tests Feature (~42 métodos entre manager, public, translation, vote); sin iconos Tabler, sin atributos `style=` inline, sin tema `bootstrap-5` en select2.

## Cobertura de la auditoría

Análisis estático únicamente (DB de test bloqueada, según instrucciones). Leídos por completo: ServiceProvider, ambos archivos de rutas + la ruta del widget Livechat que monta `HelpcenterWidgetController` (confirmado no huérfano), todos los controladores (Managers, Public, Settings, Api, Sitemap), ambos Services, ambos modelos, ambas policies, los 6 Form Requests, los 3 Observers, el Listener, Job, Console Command, seeder de permisos, config, y las migraciones base de la tabla de artículos en el módulo core Helpdesk (confirmado defaults activos true). Blades auditados vía greps dirigidos (iconos Tabler, `style=` inline, tema select2, `{!! !!}`) más una lectura completa de `public/helpcenter/show.blade.php`. No leídos en profundidad: el JS de cada blade CRUD del manager, el cableado de translation/edit Quill más allá de la línea 237, y las migraciones restantes (índice único de votos, cast JSON de embeddings) solo se revisaron por muestreo. No se encontraron hallazgos critical/high.

## Descartados en verificación

Sin hallazgos refutados. Tampoco había hallazgos critical/high que verificar.
