# Prompt de Rediseño — Módulo HelpdeskTranslate

> **IMPORTANTE**: Leer `SHARED-DESIGN-SYSTEM.md` antes de procesar este prompt.  
> Stack: Bootstrap 5.3 + jQuery + Font Awesome 6. Módulo de soporte que se integra como partial dentro de otros módulos.

---

## Contexto del Módulo

**HelpdeskTranslate** proporciona traducción automática de mensajes del helpdesk. Tiene solo 3 vistas Blade: `settings/index.blade.php`, un partial `translate-panel.blade.php` (embebido en el composer del inbox) y un partial `composer-tab.blade.php`. Usa DeepL + LibreTranslate con caché en BD (`helpdesk_translate_cache`). Los listeners están en la cola `helpdesk-events`.

**Rutas**: `panel/settings/helpdesk-translate/*`  
**Aliases de permiso**: `settings.helpdesk-translate.*`

---

## Áreas a Rediseñar

### 1. Settings de HelpdeskTranslate (`settings/index.blade.php`)

Layout settings estándar (`SHARED-DESIGN-SYSTEM.md §4.2`) con estas secciones.

#### 1.1 Sección: Proveedor de Traducción

**Selector de proveedor** como radio cards:

```html
<div class="row g-3" id="providerSelector">

  <!-- Card DeepL -->
  <div class="col-md-6">
    <div class="card hd-provider-card" data-provider="deepl">
      <div class="card-body">
        <div class="d-flex align-items-start gap-3">
          <div class="form-check">
            <input type="radio" name="provider" value="deepl" class="form-check-input" id="providerDeepl">
          </div>
          <div class="flex-grow-1">
            <label for="providerDeepl" class="fw-semibold d-block">DeepL</label>
            <p class="text-muted small mb-2">Alta calidad, ideal para traducción profesional.</p>
            <span class="badge bg-success hd-provider-status" data-provider="deepl">Verificando...</span>
          </div>
        </div>
        <!-- Campos que aparecen al seleccionar este proveedor -->
        <div class="hd-provider-fields mt-3 d-none">
          <label class="form-label small fw-semibold">API Key</label>
          <div class="input-group input-group-sm">
            <input type="password" class="form-control" name="deepl_api_key" placeholder="xxxx-xxxx-xxxx">
            <button class="btn btn-outline-secondary" type="button" id="toggleDeeplKey">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <div class="mt-2">
            <label class="form-label small fw-semibold">Endpoint</label>
            <div class="btn-group w-100" role="group">
              <input type="radio" name="deepl_endpoint" value="free" class="btn-check" id="deeplFree">
              <label for="deeplFree" class="btn btn-outline-secondary btn-sm">Free (api-free.deepl.com)</label>
              <input type="radio" name="deepl_endpoint" value="pro" class="btn-check" id="deeplPro">
              <label for="deeplPro" class="btn btn-outline-secondary btn-sm">Pro (api.deepl.com)</label>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Card LibreTranslate -->
  <div class="col-md-6">
    <div class="card hd-provider-card" data-provider="libretranslate">
      <div class="card-body">
        <div class="d-flex align-items-start gap-3">
          <div class="form-check">
            <input type="radio" name="provider" value="libretranslate" class="form-check-input" id="providerLibre">
          </div>
          <div class="flex-grow-1">
            <label for="providerLibre" class="fw-semibold d-block">LibreTranslate</label>
            <p class="text-muted small mb-2">Open source, puede ser auto-hospedado.</p>
            <span class="badge bg-secondary hd-provider-status" data-provider="libretranslate">No configurado</span>
          </div>
        </div>
        <div class="hd-provider-fields mt-3 d-none">
          <label class="form-label small fw-semibold">URL del servidor</label>
          <input type="url" class="form-control form-control-sm" name="libretranslate_url" placeholder="https://libretranslate.com">
          <label class="form-label small fw-semibold mt-2">API Key (opcional)</label>
          <input type="password" class="form-control form-control-sm" name="libretranslate_key">
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Fallback toggle -->
<div class="form-check form-switch mt-3">
  <input class="form-check-input" type="checkbox" id="useFallback" name="use_fallback">
  <label class="form-check-label" for="useFallback">
    Usar LibreTranslate como respaldo si DeepL falla
  </label>
</div>
```

**Comportamiento JS**: al seleccionar un radio card, el `.hd-provider-fields` del card seleccionado se hace visible, el del otro se oculta. El card seleccionado obtiene `border-color: var(--hd-primary)`.

#### 1.2 Sección: Verificación y Estado

**Botón "Verificar conexión"** por proveedor configurado:
```html
<button class="btn btn-outline-primary btn-sm" id="testDeepL">
  <i class="fas fa-plug me-1"></i>Verificar conexión
</button>
<div id="deepLTestResult" class="mt-2 d-none">
  <!-- Se rellena vía AJAX -->
</div>
```

**Resultado de verificación exitosa**:
```html
<div class="alert alert-success d-flex align-items-start gap-3 py-2">
  <i class="fas fa-circle-check text-success mt-1"></i>
  <div>
    <strong>Conexión exitosa</strong> — Cuenta Pro
    <div class="mt-2">
      <div class="d-flex justify-content-between small text-muted mb-1">
        <span>Uso del período</span>
        <span>124,500 / 500,000 caracteres</span>
      </div>
      <div class="progress" style="height: 6px;">
        <div class="progress-bar bg-success" style="width: 25%"></div>
      </div>
    </div>
  </div>
</div>
```

**Resultado de error**:
```html
<div class="alert alert-danger d-flex align-items-center gap-3 py-2">
  <i class="fas fa-circle-xmark text-danger"></i>
  <div>
    <strong>Error de conexión</strong>
    <p class="mb-0 small">Invalid authentication key. Verifica que la API Key sea correcta.</p>
  </div>
</div>
```

#### 1.3 Sección: Idioma Predeterminado

- Selector de idioma base del sistema (idioma del agente): `<select class="form-select">` con opciones de idiomas
- Selector de idioma objetivo predeterminado: "Por defecto, traducir mensajes de clientes a:"

#### 1.4 Sección: Gestión de Caché

Card de estado del caché:
```html
<div class="card">
  <div class="card-body">
    <div class="row g-3 text-center">
      <div class="col-4">
        <div class="h4 fw-bold mb-0" id="cacheEntries">4,821</div>
        <small class="text-muted">Entradas en caché</small>
      </div>
      <div class="col-4">
        <div class="h4 fw-bold mb-0 text-success" id="cacheHitRate">87%</div>
        <small class="text-muted">Tasa de acierto</small>
      </div>
      <div class="col-4">
        <div class="h4 fw-bold mb-0" id="cacheAge">3d</div>
        <small class="text-muted">Última purga</small>
      </div>
    </div>
    <hr>
    <div class="d-flex gap-2 justify-content-end">
      <button class="btn btn-outline-secondary btn-sm" id="refreshCacheStats">
        <i class="fas fa-rotate me-1"></i>Actualizar
      </button>
      <button class="btn btn-outline-danger btn-sm" id="purgeCache">
        <i class="fas fa-trash me-1"></i>Purgar caché
      </button>
    </div>
  </div>
</div>
```

- "Purgar caché" pide confirmación en modal antes de ejecutar
- Tras purgar: toast de éxito + actualiza estadísticas

#### 1.5 Sección: Glosario Personalizado (DeepL Glossary)

Solo visible cuando DeepL está configurado:

```html
<div class="hd-glossary-editor">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h6 class="mb-0">Glosario personalizado</h6>
      <small class="text-muted">Términos que siempre se traducen de forma específica.</small>
    </div>
    <div class="d-flex gap-2">
      <!-- Selectores de par de idiomas -->
      <select class="form-select form-select-sm" id="glossarySourceLang" style="width: auto;">
        <option>Español (ES)</option>
        <option>English (EN)</option>
      </select>
      <span class="align-self-center"><i class="fas fa-arrow-right"></i></span>
      <select class="form-select form-select-sm" id="glossaryTargetLang" style="width: auto;">
        <option>English (EN)</option>
        <option>Français (FR)</option>
      </select>
    </div>
  </div>

  <!-- Tabla de términos -->
  <div class="table-responsive">
    <table class="table table-sm" id="glossaryTable">
      <thead>
        <tr>
          <th>Término fuente</th>
          <th>Traducción personalizada</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="glossaryRows">
        <tr>
          <td><input type="text" class="form-control form-control-sm" placeholder="Término en ES"></td>
          <td><input type="text" class="form-control form-control-sm" placeholder="Traducción en EN"></td>
          <td><button class="btn btn-sm btn-link text-danger"><i class="fas fa-trash"></i></button></td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="d-flex gap-2 mt-2">
    <button class="btn btn-sm btn-outline-secondary" id="addGlossaryRow">
      <i class="fas fa-plus me-1"></i>Añadir término
    </button>
    <button class="btn btn-sm btn-outline-secondary" id="importGlossaryTSV">
      <i class="fas fa-file-import me-1"></i>Importar TSV
    </button>
  </div>

  <!-- Importación TSV (oculta por defecto) -->
  <div id="tsvImportArea" class="mt-3 d-none">
    <label class="form-label small">Pegar contenido TSV (término_fuente[TAB]traducción):</label>
    <textarea class="form-control form-control-sm" rows="5" placeholder="soporte	support&#10;factura	invoice"></textarea>
    <button class="btn btn-sm btn-primary mt-2" id="processTSV">Importar</button>
  </div>

  <div class="mt-3 d-flex justify-content-between align-items-center">
    <small class="text-muted">
      <i class="fas fa-info-circle me-1"></i>
      Glossary ID: <code id="glossaryId">gl_abc123</code> · Creado: 1 May 2026
    </small>
    <button class="btn btn-primary btn-sm" id="saveGlossary">
      <i class="fas fa-floppy-disk me-1"></i>Guardar glosario
    </button>
  </div>
</div>
```

**Nota técnica**: los glosarios de DeepL v2 son inmutables. Al guardar, se elimina el anterior y se crea uno nuevo. Mostrar un `<div class="alert alert-info">` explicando esto antes del botón guardar.

---

### 2. Partial: Panel de Traducción en Composer (`translate-panel.blade.php`)

Este partial se embebe en el compositor de mensajes del Helpdesk inbox. Es la UI más usada del módulo.

**Posición**: barra de herramientas secundaria justo debajo del textarea del composer.

```html
<div class="hd-translate-panel border-top pt-2 mt-2">
  <div class="d-flex align-items-center gap-3 flex-wrap">

    <!-- Toggle principal -->
    <div class="form-check form-switch mb-0">
      <input class="form-check-input" type="checkbox" id="translateReply" role="switch">
      <label class="form-check-label small" for="translateReply">
        <i class="fas fa-language me-1"></i>Traducir respuesta al enviar
      </label>
    </div>

    <!-- Selector de idioma destino (visible solo cuando toggle activo) -->
    <div class="d-flex align-items-center gap-2 hd-translate-target d-none">
      <small class="text-muted">Traducir a:</small>
      <select class="form-select form-select-sm" id="translateTargetLang" style="width: auto;">
        <option value="auto">Auto-detectado</option>
        <option value="en">English</option>
        <option value="fr">Français</option>
        <option value="de">Deutsch</option>
        <option value="pt">Português</option>
      </select>
    </div>

    <!-- Preview de traducción (visible cuando toggle activo) -->
    <button class="btn btn-sm btn-outline-secondary hd-translate-preview-btn d-none" id="previewTranslation">
      <i class="fas fa-eye me-1"></i>Vista previa
    </button>

  </div>

  <!-- Preview box (oculta, se muestra al pedir preview) -->
  <div class="hd-translate-preview-box mt-2 d-none">
    <div class="card border-primary">
      <div class="card-header py-1 d-flex justify-content-between align-items-center">
        <small class="fw-semibold text-primary">
          <i class="fas fa-language me-1"></i>Traducción al inglés
        </small>
        <div class="d-flex gap-1">
          <button class="btn btn-xs btn-outline-primary py-0 px-1 small" id="useTranslation">
            Usar esta traducción
          </button>
          <button class="btn btn-xs btn-outline-secondary py-0 px-1 small" id="closePreview">
            Descartar
          </button>
        </div>
      </div>
      <div class="card-body py-2">
        <p class="mb-0 small" id="translationPreviewText"><!-- Texto traducido aquí --></p>
      </div>
    </div>
    <div class="d-flex gap-2 mt-1">
      <button class="btn btn-xs btn-link text-muted py-0 small" id="editTranslation">
        <i class="fas fa-pen-to-square me-1"></i>Editar traducción
      </button>
    </div>
  </div>
</div>
```

**Comportamiento JS**:
1. Al activar el toggle: mostrar selector de idioma + botón preview + cambiar label del botón Send a "Enviar (en English)"
2. Al hacer click en "Vista previa": AJAX POST al endpoint de traducción con el contenido actual del textarea, mostrar resultado en `.hd-translate-preview-box` con fade-in
3. "Usar esta traducción": reemplaza el contenido del textarea con el texto traducido, oculta el preview
4. "Descartar": oculta el preview sin cambiar el textarea
5. El idioma destino "Auto-detectado" usa el idioma detectado de la conversación

---

### 3. Partial: Tab de Traducción en Composer (`composer-tab.blade.php`)

Un tab específico en el composer del inbox que muestra el historial de traducciones de la conversación actual.

```html
<!-- Tab header (dentro del TabBar del composer) -->
<li class="nav-item">
  <a class="nav-link" href="#tabTranslate" data-bs-toggle="tab">
    <i class="fas fa-language me-1"></i>Traducciones
    <span class="badge bg-secondary ms-1" id="translationCount">0</span>
  </a>
</li>

<!-- Tab content -->
<div class="tab-pane fade" id="tabTranslate">
  <div class="hd-translation-tab p-3">

    <!-- Estado de detección de idioma de la conversación -->
    <div class="d-flex align-items-center gap-2 mb-3 pb-3 border-bottom">
      <i class="fas fa-globe text-muted"></i>
      <div class="flex-grow-1">
        <small class="text-muted">Idioma detectado:</small>
        <strong class="ms-1" id="detectedLang">Español</strong>
      </div>
      <button class="btn btn-xs btn-outline-secondary py-0 small" id="overrideLang">
        <i class="fas fa-pen-to-square"></i> Cambiar
      </button>
    </div>

    <!-- Mensajes pendientes de traducir -->
    <div class="hd-translate-pending d-none">
      <div class="alert alert-info d-flex align-items-center gap-3 py-2">
        <i class="fas fa-language"></i>
        <div>
          <strong>3 mensajes sin traducir</strong>
          <p class="mb-0 small">Mensajes nuevos desde la última traducción.</p>
        </div>
        <button class="btn btn-sm btn-info ms-auto" id="translateAllPending">
          Traducir conversación
        </button>
      </div>
    </div>

    <!-- Lista de mensajes con traducción -->
    <div class="hd-translation-list">
      <!-- Cada item: mensaje original + traducción -->
      <div class="hd-translation-item mb-3">
        <div class="small text-muted mb-1">
          <i class="fas fa-user me-1"></i>Juan López · hace 5m
        </div>
        <div class="hd-translation-original text-muted fst-italic small">
          "Tengo un problema con mi factura..."
        </div>
        <div class="hd-translation-result mt-1">
          <span class="badge bg-secondary bg-opacity-25 text-dark small me-1">
            <i class="fas fa-language me-1"></i>EN
          </span>
          "I have a problem with my invoice..."
        </div>
        <div class="mt-1">
          <a href="#" class="small text-muted hd-toggle-original">Ver original</a>
          <!-- Thumbs rating -->
          <span class="ms-2">
            <button class="btn btn-xs btn-link p-0 text-muted hd-rate-translation" data-value="1" title="Buena traducción">
              <i class="fas fa-thumbs-up"></i>
            </button>
            <button class="btn btn-xs btn-link p-0 text-muted hd-rate-translation" data-value="0" title="Mala traducción">
              <i class="fas fa-thumbs-down"></i>
            </button>
          </span>
        </div>
      </div>
    </div>

  </div>
</div>
```

---

### 4. Indicador de Idioma en Conversación

Un pequeño badge contextual en el header de la conversación del inbox (sidebar derecho):

```html
<!-- En el panel de contexto derecho del inbox -->
<div class="d-flex align-items-center gap-2 mb-2">
  <i class="fas fa-globe text-muted small"></i>
  <div>
    <small class="text-muted d-block">Idioma</small>
    <div class="d-flex align-items-center gap-1">
      <span class="fw-semibold small" id="convLang">Español</span>
      <button class="btn btn-xs btn-link py-0 px-1 text-muted" id="editConvLang">
        <i class="fas fa-pen-to-square" style="font-size: 10px;"></i>
      </button>
    </div>
  </div>
</div>
```

Al hacer click en editar idioma: se muestra un `<select>` de idiomas inline que al cambiar dispara AJAX y registra un evento en el thread de la conversación: `"Idioma cambiado a English por Laura García"`.

---

### 5. Funcionalidades Futuras (Espacio Reservado)

1. **Traducción de mensajes en tiempo real**: mientras el agente escribe, traducción simultánea al idioma del cliente (debounce 1s)
2. **Modelo de traducción personalizado**: DeepL permite fine-tuning — integrar selector de modelo custom
3. **Analytics de traducciones**: cuántas traducciones por idioma, tasa de acierto del caché, idiomas más frecuentes — panel separado en settings
4. **Traducción de archivos adjuntos**: para documentos PDF adjuntos en conversaciones
5. **Notificación de calidad baja**: si el rating promedio de traducciones de un idioma baja de X%, notificar al admin

---

## Archivos Clave

```
modules/HelpdeskTranslate/resources/views/
├── settings/
│   └── index.blade.php           ← Panel completo de configuración
├── partials/
│   ├── translate-panel.blade.php ← Panel embebido en el composer del inbox
│   └── composer-tab.blade.php    ← Tab de historial de traducciones
```

---

## CSS Específico

Agregar a `modules/HelpdeskTranslate/public/css/translate.css` (o al archivo compartido helpdesk-suite.css):
- `.hd-translate-panel` — barra debajo del textarea
- `.hd-translate-preview-box` — box de preview con fade-in
- `.hd-translation-item` — cada mensaje traducido en el tab
- `.hd-provider-card` — radio cards de proveedor con borde dinámico
- `.hd-provider-status` — badge de estado del proveedor
