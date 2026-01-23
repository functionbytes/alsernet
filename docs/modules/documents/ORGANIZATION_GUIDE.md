# Guía de Organización - Documentación del Módulo Documents

**Fecha:** 2025-12-28
**Propósito:** Mantener la documentación organizada, fechada y fácil de encontrar

---

## 🎯 Objetivo

Esta guía establece las convenciones para mantener la documentación del Módulo Documents organizada de forma que:

1. **Context7 pueda indexarla correctamente**
2. **Los desarrolladores encuentren información rápidamente**
3. **El historial de cambios sea rastreable**
4. **La documentación no se vuelva obsoleta sin notarlo**

---

## 📁 Estructura de Carpetas

```
docs/modules/documents/
├── README.md                       # Índice principal (actualizar siempre)
├── ORGANIZATION_GUIDE.md           # Este archivo
│
├── architecture/                   # Arquitectura y diseño
│   ├── system-overview.md
│   ├── permissions.md
│   ├── workflow.md
│   └── quick-reference.md
│
├── api/                            # Documentación de API
│   ├── endpoints.md
│   ├── testing.md
│   └── authentication.md
│
├── features/                       # Características funcionales
│   ├── validation.md
│   ├── email-system.md
│   ├── sla-policies.md
│   └── storage.md
│
├── configuration/                  # Guías de configuración
│   ├── setup.md
│   ├── storage.md
│   ├── mailers-integration.md
│   └── environment.md
│
├── ui/                             # Interfaz de usuario
│   ├── administrative-views.md
│   ├── manager-views.md
│   ├── components.md
│   └── changes-log.md
│
├── database/                       # Base de datos
│   ├── schema.md
│   ├── migrations.md
│   └── relationships.md
│
├── implementation/                 # Implementaciones específicas
│   ├── 2025-12-28-profile-based-listing.md
│   ├── 2025-12-22-storage-configuration.md
│   ├── 2025-12-21-validation-workflow.md
│   └── archive/                    # Implementaciones antiguas
│       └── 2025-11-*.md
│
└── changelog/                      # Registro de cambios
    ├── 2025-12.md
    ├── 2025-11.md
    └── 2025-10.md
```

---

## 📝 Convenciones de Nomenclatura

### Archivos de Arquitectura y Features

**Formato:** `nombre-descriptivo.md` (kebab-case)

✅ **Correcto:**
- `system-overview.md`
- `email-system.md`
- `validation-workflow.md`
- `profile-based-listing.md`

❌ **Incorrecto:**
- `SystemOverview.md` (no PascalCase)
- `email_system.md` (no snake_case)
- `VALIDATION-WORKFLOW.md` (no uppercase)

---

### Archivos de Implementación

**Formato:** `YYYY-MM-DD-descripcion-corta.md`

✅ **Correcto:**
- `2025-12-28-profile-based-listing.md`
- `2025-12-21-validation-workflow.md`
- `2025-12-18-email-integration.md`

❌ **Incorrecto:**
- `profile-based-listing.md` (falta fecha)
- `12-28-2025-profile-listing.md` (formato de fecha incorrecto)
- `28-12-2025-profile-listing.md` (formato europeo, usar ISO)

**Razón:** La fecha ISO (YYYY-MM-DD) permite ordenamiento cronológico natural y es estándar internacional.

---

### Archivos de Changelog

**Formato:** `YYYY-MM.md` (año-mes)

✅ **Correcto:**
- `2025-12.md`
- `2025-11.md`
- `2026-01.md`

❌ **Incorrecto:**
- `diciembre-2025.md`
- `2025-december.md`
- `changelog-2025-12.md`

---

## 📄 Estructura de Documentos

### Header Obligatorio

**Cada archivo debe iniciar con:**

```markdown
# Título del Documento

**Fecha:** YYYY-MM-DD
**Autor:** Claude Code / Nombre del desarrollador
**Estado:** En desarrollo / Completado / Deprecado
**Relacionado con:** Enlaces a docs relacionados

## Contenido...
```

**Ejemplo:**

```markdown
# Sistema de Validación Multi-Etapa

**Fecha:** 2025-12-21
**Autor:** Claude Code
**Estado:** Completado
**Relacionado con:**
- [Permisos](./permissions.md)
- [Workflow](./workflow.md)

## Descripción

El sistema de validación...
```

---

### Secciones Recomendadas

Para documentos de **Features**:

```markdown
# [Nombre del Feature]

**Fecha:** YYYY-MM-DD

## Descripción
Breve descripción del feature

## Casos de Uso
Cuándo y por qué usar este feature

## Cómo Funciona
Explicación técnica

## Configuración
Pasos de configuración necesarios

## Ejemplos de Uso
Ejemplos de código prácticos

## Limitaciones
Qué no puede hacer

## Documentación Relacionada
Enlaces a otros docs
```

Para documentos de **Implementación**:

```markdown
# [Título de la Implementación]

**Fecha:** YYYY-MM-DD
**Implementado por:** Nombre
**Issue/Ticket:** #123

## Problema
Qué problema resuelve

## Solución
Cómo se resolvió

## Cambios Realizados
- Modelo X modificado
- Controlador Y creado
- Vista Z actualizada

## Migraciones
Migraciones ejecutadas

## Tests
Tests creados/modificados

## Notas
Consideraciones importantes

## Rollback (si aplica)
Cómo revertir cambios
```

---

## 🔄 Flujo de Trabajo

### Cuando Crees Nueva Documentación

1. **Determinar categoría**
   - ¿Es arquitectura? → `architecture/`
   - ¿Es un feature? → `features/`
   - ¿Es una implementación específica? → `implementation/`
   - ¿Es configuración? → `configuration/`
   - ¿Es API? → `api/`

2. **Nombrar correctamente**
   - Features: `nombre-descriptivo.md`
   - Implementaciones: `YYYY-MM-DD-descripcion.md`

3. **Crear el archivo**
   ```bash
   # Ejemplo para un feature
   touch docs/modules/documents/features/nuevo-feature.md

   # Ejemplo para una implementación
   touch docs/modules/documents/implementation/2025-12-28-nuevo-feature.md
   ```

4. **Agregar header completo**
   ```markdown
   # Mi Nuevo Feature

   **Fecha:** 2025-12-28
   **Autor:** Tu Nombre
   **Estado:** En desarrollo
   ```

5. **Actualizar README.md**
   - Agregar entrada en la tabla correspondiente
   - Agregar enlace al archivo

6. **Actualizar Changelog**
   - Agregar entrada en `changelog/YYYY-MM.md`
   - Describir brevemente el cambio

---

### Cuando Modifiques Documentación Existente

1. **Actualizar fecha en header**
   ```markdown
   **Fecha:** 2025-12-28 (última actualización)
   ```

2. **Agregar nota de cambio (opcional)**
   ```markdown
   **Historial de cambios:**
   - 2025-12-28: Agregada sección de ejemplos
   - 2025-12-21: Creación inicial
   ```

3. **Actualizar changelog mensual**
   ```markdown
   ### 2025-12-28
   - 📝 Actualizado documento de validación con nuevos ejemplos
   ```

---

### Cuando Archives Documentación Antigua

1. **Mover a subcarpeta archive/**
   ```bash
   mv docs/modules/documents/implementation/2025-10-15-feature-viejo.md \
      docs/modules/documents/implementation/archive/
   ```

2. **Actualizar README.md**
   - Remover de tabla principal
   - Agregar nota de archivo

3. **Actualizar changelog**
   ```markdown
   ### 2025-12-28
   - 🗄️ Archivado documento de feature obsoleto
   ```

---

## 📅 Actualización del README.md

### Proceso de Actualización

Cada vez que agregues/modifiques documentación:

1. **Actualizar "Última actualización"**
   ```markdown
   **Última actualización:** 2025-12-28
   ```

2. **Agregar entrada en tabla correspondiente**

   **Para features:**
   ```markdown
   | Feature | Descripción | Fecha | Ruta |
   |---------|-------------|-------|------|
   | **Nuevo Feature** | Descripción breve | 2025-12-28 | [`features/nuevo-feature.md`](./features/nuevo-feature.md) |
   ```

   **Para implementaciones:**
   ```markdown
   | Fecha | Documento | Descripción |
   |-------|-----------|-------------|
   | 2025-12-28 | [`implementation/2025-12-28-nuevo-feature.md`](./implementation/2025-12-28-nuevo-feature.md) | Implementación de X |
   ```

3. **Actualizar sección "Últimos Cambios"**
   ```markdown
   #### 2025-12-28
   - ✅ Implementado nuevo feature X
   - 📝 Actualizado documento de Y
   ```

---

## 📊 Actualización del Changelog

### Estructura del Changelog Mensual

**Archivo:** `changelog/YYYY-MM.md`

```markdown
# Changelog - Módulo Documents
## [Mes] YYYY

---

## [YYYY-MM-DD] - [Día de la semana]

### 🗂️ Categoría
- **Descripción en negrita**
  - Detalle 1
  - Detalle 2
- Enlace: [`archivo.md`](../carpeta/archivo.md)

### 📝 Archivos Creados/Modificados
- `ruta/al/archivo.md` - Descripción

---
```

### Categorías de Changelog

Usa estas categorías con emojis:

- 🗂️ **Documentación** - Cambios en documentación
- ✨ **Features** - Nuevas características
- 🔐 **Permisos** - Cambios en permisos/autorización
- 📧 **Emails** - Sistema de emails
- 🗄️ **Database** - Cambios en BD
- 🎨 **UI/UX** - Cambios de interfaz
- 📊 **Logging** - Sistemas de auditoría
- 🧪 **Testing** - Tests y QA
- 🚀 **Deployment** - Deploy y producción
- 🔧 **Refactoring** - Refactorizaciones
- 🐛 **Bugfix** - Corrección de errores
- ⚡ **Performance** - Mejoras de rendimiento
- 🔒 **Security** - Mejoras de seguridad

---

## 🔍 Context7 Indexing

### Qué Indexa Context7

Context7 indexa **todos los archivos `.md`** en `docs/modules/documents/`:

✅ **Indexado:**
- `architecture/*.md`
- `features/*.md`
- `api/*.md`
- `implementation/*.md`
- `changelog/*.md`
- `README.md`

❌ **NO indexado (por exclusión en context7.json):**
- Archivos `.sh`
- Archivos `.txt`
- Archivos `.log`
- `node_modules/`, `vendor/`

### Optimizar para Context7

**Usa títulos descriptivos:**
```markdown
# Sistema de Validación Multi-Etapa
```
Mejor que:
```markdown
# Validación
```

**Incluye keywords:**
```markdown
## Sistema de Permisos (Spatie Permission + ValidatorGroup)
```
Esto ayuda a Context7 a encontrar contenido relevante.

**Enlaza documentos relacionados:**
```markdown
Ver también:
- [Workflow](./workflow.md)
- [Permisos](./permissions.md)
```

---

## ✅ Checklist de Documentación

Antes de commitear nueva documentación:

### Nuevo Documento

- [ ] Archivo nombrado correctamente (kebab-case o fecha ISO)
- [ ] Header completo con fecha, autor, estado
- [ ] Contenido estructurado con secciones claras
- [ ] README.md actualizado con nueva entrada
- [ ] Changelog actualizado con el cambio
- [ ] Enlaces relativos funcionan correctamente
- [ ] Markdown válido (sin errores de sintaxis)

### Modificación de Documento

- [ ] Fecha de actualización modificada
- [ ] Cambios documentados (opcional: historial de cambios)
- [ ] Changelog actualizado
- [ ] README.md actualizado si cambia el título/descripción

### Archivar Documento

- [ ] Movido a `archive/` correspondiente
- [ ] README.md actualizado (entrada removida)
- [ ] Changelog actualizado con nota de archivo
- [ ] Enlaces rotos reparados

---

## 🚫 Qué Evitar

### ❌ NO hacer:

1. **Archivos sueltos en la raíz de docs/**
   ```
   ❌ docs/NUEVO_DOCUMENTO.md
   ✅ docs/modules/documents/features/nuevo-documento.md
   ```

2. **Nombres genéricos**
   ```
   ❌ implementation/fix.md
   ❌ features/update.md
   ✅ implementation/2025-12-28-fix-validation-bug.md
   ✅ features/email-notifications.md
   ```

3. **Documentos sin fecha**
   ```
   ❌ # Mi Documento

       Contenido...

   ✅ # Mi Documento

       **Fecha:** 2025-12-28
       **Autor:** Claude Code

       Contenido...
   ```

4. **Dejar documentos obsoletos sin archivar**
   - Si un documento ya no es relevante, muévelo a `archive/`

5. **No actualizar README.md**
   - El README es el índice principal, mantenerlo actualizado es crítico

6. **Duplicar información**
   - Si la información existe en otro doc, enlázalo en vez de duplicar

---

## 💡 Tips y Mejores Prácticas

### 1. Usa Títulos Descriptivos

❌ Malo: `# API`
✅ Bueno: `# API Endpoints - Módulo Documents`

### 2. Incluye Ejemplos de Código

Siempre que sea posible:

```php
// ❌ No solo teoría
"Usar el servicio de permisos para verificar acceso"

// ✅ Incluir código
use Modules\Documents\Services\PermissionService;

if ($permissionService->can($user, 'manage', 'administrative')) {
    // Usuario tiene acceso
}
```

### 3. Mantén Enlaces Relativos

```markdown
❌ [Ver permisos](/docs/modules/documents/architecture/permissions.md)
✅ [Ver permisos](./permissions.md)
✅ [Ver permisos](../architecture/permissions.md)
```

### 4. Usa Tablas para Datos Tabulares

```markdown
| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/documents` | GET | Listar documentos |
| `/api/documents/{id}` | GET | Ver documento |
```

### 5. Fecha de Creación vs Última Actualización

Para documentos que se actualizan frecuentemente:

```markdown
**Fecha creación:** 2025-12-21
**Última actualización:** 2025-12-28
```

---

## 🔄 Proceso de Commit

### Mensajes de Commit

Usa conventional commits:

```bash
# Nueva documentación
git commit -m "docs(documents): add email system documentation"

# Actualización
git commit -m "docs(documents): update validation workflow"

# Reorganización
git commit -m "docs(documents): reorganize implementation files"

# Archivo
git commit -m "docs(documents): archive obsolete documentation"
```

### Formato de Mensajes

```
<type>(<scope>): <description>

[optional body]
```

**Types:**
- `docs` - Cambios de documentación
- `feat` - Nuevo feature
- `fix` - Corrección de bug
- `refactor` - Refactorización

**Scopes:**
- `documents` - Módulo de documentos
- `api` - API documentation
- `architecture` - Documentación de arquitectura

---

## 📞 Ayuda

Si tienes dudas sobre dónde colocar documentación:

1. **Consulta este guide**
2. **Revisa la estructura en README.md**
3. **Mira ejemplos de documentos similares**
4. **Cuando en duda, pregunta a Claude Code** mencionando esta guía

---

## 📚 Documentos Relacionados

- [README principal](./README.md) - Índice de toda la documentación
- [Changelog actual](./changelog/2025-12.md) - Cambios del mes
- [Context7 config](../../context7.json) - Configuración de indexado

---

**Última actualización:** 2025-12-28
**Mantenido por:** Claude Code + Equipo de desarrollo
