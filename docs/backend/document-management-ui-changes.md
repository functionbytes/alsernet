# Cambios en Interfaz de Gestión de Documentos

## Resumen de Ajustes

Se ha optimizado la vista de gestión de documentos (`/accounting/documents/manage/{id}`) para mostrar solo la información crítica y las acciones necesarias.

**Archivo actualizado:** `/resources/views/accountings/views/documents/manage.blade.php`

---

## ✅ Lo que Se Muestra AHORA

### Columna Izquierda (Sidebar)

1. **Acciones de Email** (Card principal)
   - ✅ Solicitud inicial
   - ✅ Documentos específicos
   - ✅ Recordatorio
   - ✅ Confirmación de subida
   - ✅ **Notificación de aprobación** ← IMPORTANTE
   - ✅ **Notificación de rechazo** ← IMPORTANTE
   - ✅ **Correo personalizado** ← IMPORTANTE

2. **Notas del Documento**
   - ✅ Sección de notas internas (MANTENIDA)

### Columna Derecha (Contenido Principal)

1. **Listado de Productos** (si aplica)
   - Nombre del producto
   - Cantidad

2. **Detalle de la Orden**
   - Orden ID
   - Referencia
   - Tipo de documento
   - Fechas (orden, confirmación, creación)

3. **Información del Cliente** ← **DATOS ADICIONALES VISIBLES**
   - Nombres
   - Apellidos
   - DNI/NIE/CIF
   - Correo electrónico
   - Teléfono

4. **Gestión del Documento**
   - Estado del documento (select)
   - Origen/canal (select)
   - Método de carga (select)
   - Tipo de sincronización (select)
   - Tipo de subida (select)
   - Botón guardar configuración

5. **Sección de Carga de Documentos**
   - Interfaz para cargar/descargar documentos
   - Muestra documentos requeridos vs cargados
   - Gestión de archivos

---

## ❌ Lo que Se QUITÓ

### Elementos Removidos del Sidebar

1. ❌ **Historial de Acciones**
   - `@include('accountings.views.documents.includes.action-history')`
   - Contenía: Registro de todas las acciones realizadas

2. ❌ **Emails Enviados**
   - `@include('accountings.views.documents.includes.email-history')`
   - Contenía: Historial de correos enviados al cliente

3. ❌ **Historial de Estado**
   - `@include('accountings.views.documents.includes.status-timeline')`
   - Contenía: Transiciones de estado del documento

---

## 📐 Layout Actual

```
┌─────────────────────────────────────────────────────────────┐
│           GESTIONAR DOCUMENTO - Encabezado                 │
├──────────────────┬──────────────────────────────────────────┤
│                  │                                          │
│  SIDEBAR         │      CONTENIDO PRINCIPAL                │
│  (col-lg-4)      │      (col-lg-8)                         │
│                  │                                          │
│ ┌──────────────┐ │  ┌────────────────────────────────────┐ │
│ │ Acciones de  │ │  │ Listado de productos               │ │
│ │ Email        │ │  └────────────────────────────────────┘ │
│ │ ✅ Solicitar │ │  ┌────────────────────────────────────┐ │
│ │ ✅ Documentos│ │  │ Detalle de la orden                │ │
│ │ ✅ Recordat. │ │  │ - Orden ID, Referencia            │ │
│ │ ✅ Confirmar │ │  │ - Tipo, Fechas                    │ │
│ │ ✅ Aprobación│ │  └────────────────────────────────────┘ │
│ │ ✅ Rechazo   │ │  ┌────────────────────────────────────┐ │
│ │ ✅ Email pers│ │  │ Información del Cliente ← NUEVA   │ │
│ └──────────────┘ │  │ - Nombres, Apellidos              │ │
│                  │  │ - DNI, Email                       │ │
│ ┌──────────────┐ │  │ - Teléfono                        │ │
│ │ Notas del    │ │  └────────────────────────────────────┘ │
│ │ Documento    │ │  ┌────────────────────────────────────┐ │
│ │ (MANTENIDAS) │ │  │ Gestión del documento              │ │
│ │              │ │  │ - Estados, origen, carga, sync     │ │
│ └──────────────┘ │  └────────────────────────────────────┘ │
│                  │  ┌────────────────────────────────────┐ │
│ ❌ Historial    │  │ Sección de Carga de Documentos     │ │
│    REMOVIDO      │  │ - Cargar/Descargar archivos       │ │
│ ❌ Emails       │  │ - Documentos requeridos            │ │
│    REMOVIDO      │  └────────────────────────────────────┘ │
│ ❌ Estado       │                                          │
│    REMOVIDO      │                                          │
│                  │                                          │
└──────────────────┴──────────────────────────────────────────┘
```

---

## 📝 Cambios Realizados

### Antes
```blade
<!-- Sidebar contenía -->
@include('accountings.views.documents.includes.document-notes-sidebar')

<!-- Historial de acciones -->
<div id="actionHistoryContainer">
    @include('accountings.views.documents.includes.action-history')
</div>

<!-- Email History -->
@include('accountings.views.documents.includes.email-history')

<!-- Status Timeline -->
@include('accountings.views.documents.includes.status-timeline')
```

### Después
```blade
<!-- Sidebar contiene SOLO -->
@include('accountings.views.documents.includes.document-notes-sidebar')
```

---

## 🎯 Resultado

| Aspecto | Estado |
|--------|--------|
| **Información del Cliente** | ✅ Visible (Nombres, Apellidos, DNI, Email, Teléfono) |
| **Notificación de Rechazo** | ✅ Visible (Botón en Acciones de Email) |
| **Correo Personalizado** | ✅ Visible (Botón en Acciones de Email) |
| **Notificación de Aprobación** | ✅ Visible (Botón en Acciones de Email) |
| **Notas** | ✅ Mantenidas (Sidebar izquierdo) |
| **Historial de Acciones** | ❌ Removido |
| **Emails Enviados** | ❌ Removido |
| **Historial de Estado** | ❌ Removido |

---

## 🚀 Ventajas

✅ **Interfaz más limpia:** Sin historiales que distraigan
✅ **Enfoque en acciones:** Los botones de email son prominentes
✅ **Información del cliente visible:** Los datos necesarios están a la vista
✅ **Mejor UX:** Menos desplazamiento, información crítica en viewport

---

## 📂 Archivo Modificado

**Ubicación:** `/resources/views/accountings/views/documents/manage.blade.php`

**Líneas removidas:**
- Línea 159-161: Action History Container
- Línea 163-164: Email History Include
- Línea 166-167: Status Timeline Include

**Resultado:** Vista más enfocada y limpia
