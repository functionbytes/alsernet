# Skills de este Proyecto (Alsernet)

## Skills disponibles

### /new-module
**Proposito**: Crear un modulo completo desde cero con toda la estructura
**Tipo**: Secuencial, genera todos los archivos de boilerplate
**Invocacion**: Solo manual

```
/new-module Inventory Gestion de inventario
/new-module Reports Reportes y estadisticas
```

**Que genera**:
1. Estructura de directorios completa (app/, config/, database/, resources/, routes/, tests/)
2. `module.json` con metadata del modulo
3. `composer.json` con autoloading PSR-4
4. `{ModuleName}ServiceProvider.php` con:
   - registerConfig() - merge config
   - registerViews() - carga vistas con namespace
   - registerRoutes() - carga web.php
   - registerMenus() - NavService (mini item + sidebar + settings)
   - Module::find()->isDisabled() check
5. `config/config.php` - configuracion del modulo
6. `routes/web.php` - rutas autenticadas con prefijo panel/{alias}
7. `{ModuleName}PermissionsSeeder.php` - permisos Spatie (view, create, update, delete, manage)
8. Registro en `bootstrap/providers.php` y `modules_statuses.json`
9. `composer dump-autoload`

---

### /crud
**Proposito**: Genera CRUD completo para una entidad de modulo
**Tipo**: Workflow con plan agent (context: fork)
**Invocacion**: Solo manual

```
/crud Attention/AttentionPriority
/crud Analytics/Report
```

**Que genera**:
1. Migration + Factory (database agent)
2. Model + Service + FormRequests + Controller + Routes (backend agent)
3. Index con DataGrid + Form + Modal delete (frontend agent)
4. Feature tests (testing agent)

---

### /fix-bug
**Proposito**: Workflow estructurado de bug fix
**Tipo**: Secuencial con delegacion a agentes
**Invocacion**: Solo manual

```
/fix-bug Los emails de notificacion no se envian
/fix-bug Error 500 al crear atencion
```

**Pasos**: Gather evidence -> Reproduce -> Root cause -> Fix -> Regression test -> Verify

---

### /module-audit
**Proposito**: Auditoria completa de un modulo
**Tipo**: 3 subagentes paralelos (context: fork)
**Invocacion**: Solo manual

```
/module-audit Attention
/module-audit Analytics
```

**Genera reporte** con:
- Health Score X/10
- Critical (must fix)
- Warnings (should fix)
- Performance metrics
- Recommendations

---

### /new-page
**Proposito**: Crear pagina nueva con controller + route + view
**Tipo**: Secuencial (backend + frontend)
**Invocacion**: Solo manual

```
/new-page Settings/Dashboard de metricas
/new-page Attention/Vista de historial
```

---

### /team-review
**Proposito**: Review paralelo con agent team
**Tipo**: Agent team (3 teammates)
**Invocacion**: Solo manual
**Requiere**: Agent Teams habilitado

```
/team-review PR #142
/team-review cambios en el modulo Attention
```

---

### /team-feature
**Proposito**: Implementacion paralela con agent team
**Tipo**: Agent team (backend + frontend + testing)
**Invocacion**: Solo manual
**Requiere**: Agent Teams habilitado

```
/team-feature Dashboard de analytics con graficas
/team-feature Sistema de prioridades para atenciones
```

## Rules activas (auto-cargadas)

Estas se cargan automaticamente cuando editas archivos que coinciden:

| Rule | Patron | Reglas clave |
|---|---|---|
| `blade-views.md` | `*.blade.php` | FA6, no inline styles, dropdowns, modals |
| `migrations.md` | migrations/ | ALL attrs en change(), indexes |
| `controllers.md` | Controllers/ | Form Requests, thin controllers |
| `javascript.md` | `*.js` | jQuery+AJAX, CSRF, DevExpress |
