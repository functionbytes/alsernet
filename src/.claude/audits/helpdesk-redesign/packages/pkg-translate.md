# Paquete Claude Design — HelpdeskTranslate

## Archivos a incluir

```
modules/HelpdeskTranslate/resources/css/translate-panel.css ← CSS esqueleto existente
modules/Helpdesk/resources/css/conversations-identity.css   ← tokens --bv-* (hereda)
.claude/audits/helpdesk-redesign/HelpdeskTranslate.md       ← especificación completa
.claude/audits/helpdesk-redesign/SHARED-DESIGN-SYSTEM.md    ← sistema compartido
```

## Qué debe entregar Claude Design

1. **`translate-settings.html`** — página de configuración del proveedor
   - Radio cards de proveedor (DeepL / LibreTranslate)
   - Health check badge (verde = OK, rojo = error) con botón "Probar conexión"
   - Progress bar de cuota mensual (X de Y caracteres usados)
   - Tabla de glosario: término → traducción + acciones inline (editar/eliminar)
   - Botón "+ Agregar término al glosario"

2. **`translate-composer.html`** — partial del composer del inbox (integrado)
   - Toggle switch "Traducir automáticamente" en la toolbar del composer
   - Preview de traducción: cuadro gris debajo del textarea con el texto traducido
   - Indicador de idioma detectado: badge pequeño "Detectado: 🇧🇷 PT"
   - Rating de traducción: 👍 / 👎 con feedback breve al hover
   - Botón "Aplicar traducción" que reemplaza el texto del composer

3. **`translate-history.html`** — tab historial de traducciones en el thread
   - Banner "Traducir todo el hilo" con botón y spinner de carga
   - Lista de mensajes traducidos con: idioma origen → idioma destino, fecha, proveedor usado
   - Toggle para ver original / traducción en cada mensaje

4. **`translate-panel-refined.css`** — CSS refinado
   - Mantiene herencia de `--bv-*` del sistema de conversations
   - Prefijo `bv-tp-*` (convención existente)

## Restricciones

- El partial del composer se inyecta DENTRO del `.bv-composer` existente — NO es pantalla completa
- El toggle es un Bootstrap switch nativo (`<input type="checkbox" class="form-check-input">`)
- La traducción es asíncrona — mostrar spinner mientras carga
- NO inline styles
- Pequeño módulo (3 vistas) — prioridad baja, mantener mínimo

## Componentes críticos

```html
<!-- Toggle en el composer -->
<div class="bv-tp-toggle d-flex align-items-center gap-2">
  <div class="form-check form-switch mb-0">
    <input class="form-check-input" type="checkbox" id="toggleTranslate">
    <label class="form-check-label small" for="toggleTranslate">
      <i class="fas fa-language me-1"></i>Traducir automáticamente
    </label>
  </div>
  <span class="bv-tp-lang-badge badge bg-light text-dark border">
    <i class="fas fa-globe me-1"></i>ES → EN
  </span>
</div>

<!-- Preview de traducción -->
<div class="bv-translate-panel open">
  <div class="bv-tp-header">
    <i class="fas fa-language"></i>
    <span>Traducción (DeepL)</span>
    <small class="text-muted ms-auto">Detectado: 🇧🇷 Português</small>
  </div>
  <div class="bv-tp-preview">
    Olá, preciso de ajuda com meu pedido #12345. A entrega está atrasada há 3 dias...
  </div>
  <div class="bv-tp-actions">
    <div class="d-flex gap-2 align-items-center">
      <small class="text-muted">¿Buena traducción?</small>
      <button class="btn btn-sm btn-outline-secondary py-0"><i class="fas fa-thumbs-up"></i></button>
      <button class="btn btn-sm btn-outline-secondary py-0"><i class="fas fa-thumbs-down"></i></button>
    </div>
    <button class="btn btn-sm btn-primary ms-auto">Aplicar traducción</button>
  </div>
</div>

<!-- Radio card de proveedor -->
<label class="bv-tp-provider-card" for="providerDeepl">
  <input type="radio" name="provider" id="providerDeepl" value="deepl" class="d-none">
  <div class="bv-tp-provider-card__body">
    <img src="deepl-logo.svg" height="28" alt="DeepL">
    <div class="mt-2 small fw-600">DeepL</div>
    <div class="text-muted" style="font-size:11px">Alta precisión, 500K chars/mes gratis</div>
  </div>
  <span class="bv-tp-provider-card__check"><i class="fas fa-circle-check"></i></span>
</label>
```
