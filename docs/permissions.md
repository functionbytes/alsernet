# Alsernet - Permissions Reference

Run the master seeder: `php artisan db:seed --class=PermissionsAuditSeeder`

## Roles

| Role | Description |
|---|---|
| `super-settings` | Full access to everything. Bypassed by `Gate::before` in `AuthServiceProvider`. |
| `settings` | Full access except `system.maintenance`, `system.api.manage`, `database.cleanup.truncate`, backup deletion. |
| `manager` | Operational management. Can view/create/edit most resources but cannot delete or access critical system settings. |
| `administrative` | Administrative processing. View-only on communication modules. |
| `customer` | End-user. Access to own notifications and media only. |
| `attention-settings` | Full control of Attention (PQRSF) module. |
| `attention-manager` | Supervisor of PQRSF. Can view-all, assign, resolve. |
| `attention-agent` | Agent handling assigned PQRSF. |
| `attention-user` | Basic user: can create and view own PQRSF. |
| `blog-admin` | Full Blog module control. |
| `blog-editor` | Create, edit, publish content. No delete. |
| `blog-author` | Create own posts only. |
| `cms-settings` | Full CMS (Page, Menu, SEO, Shortcode, Sitemap). |
| `cms-editor` | Edit/publish content. No delete or structure management. |
| `cms-author` | Create and edit own pages. |
| `cms-viewer` | Read-only CMS access. |
| `helpdesk-agent` | Helpdesk agent handling assigned tickets. |

---

## Super-admin Gate Bypass

Defined in `Modules/Auth/app/Providers/AuthServiceProvider.php`:

```php
Gate::before(function ($user, $ability) {
    return $user->hasRole('super-settings') ? true : null;
});
```

This means `super-settings` bypasses all policy and permission checks automatically.

---

## Permissions by Module

### Dashboard
| Permission | Description | super-settings | settings | manager |
|---|---|:---:|:---:|:---:|
| `dashboard.view` | Ver dashboard | Y | Y | Y |
| `dashboard.statistics` | Ver estadísticas | Y | Y | Y |
| `dashboard.reports` | Generar reportes | Y | Y | Y |

### Users (Modules/User)
| Permission | Description | super-settings | settings | manager |
|---|---|:---:|:---:|:---:|
| `view-users` | Ver usuarios (UserPolicy) | Y | Y | Y |
| `create-users` | Crear usuarios (UserPolicy) | Y | Y | - |
| `edit-users` | Editar usuarios (UserPolicy) | Y | Y | - |
| `delete-users` | Eliminar usuarios (UserPolicy) | Y | Y | - |
| `export-users` | Exportar usuarios (UserPolicy) | Y | Y | - |
| `users.view` | Ver usuarios (canonical) | Y | Y | Y |
| `users.create` | Crear usuarios (canonical) | Y | Y | - |
| `users.update` | Actualizar usuarios (canonical) | Y | Y | - |
| `users.delete` | Eliminar usuarios (canonical) | Y | Y | - |
| `users.export` | Exportar usuarios (canonical) | Y | Y | - |
| `users.roles.assign` | Asignar roles a usuarios | Y | Y | - |
| `users.permissions.assign` | Asignar permisos a usuarios | Y | Y | - |

> Note: `UserPolicy` uses hyphenated names (`view-users`). `CompleteRolesAndPermissionsSeeder` uses dot notation (`users.view`). Both exist for backward compatibility.

### Roles & Permissions (Modules/Role)
| Permission | Description | super-settings | settings | manager |
|---|---|:---:|:---:|:---:|
| `roles.view` | Ver roles | Y | Y | Y |
| `roles.create` | Crear roles | Y | Y | - |
| `roles.edit` | Editar roles | Y | Y | - |
| `roles.delete` | Eliminar roles | Y | Y | - |
| `roles.show.permissions` | Ver permisos de un rol | Y | Y | Y |
| `roles.update.permissions` | Actualizar permisos de un rol | Y | Y | - |
| `roles.show.users` | Ver usuarios de un rol | Y | Y | Y |
| `roles.assign.users` | Asignar usuarios a un rol | Y | Y | - |
| `permissions.view` | Ver permisos | Y | Y | Y |
| `permissions.create` | Crear permisos | Y | Y | - |
| `permissions.edit` | Editar permisos | Y | Y | - |
| `permissions.delete` | Eliminar permisos | Y | Y | - |

### Attention / PQRSF (Modules/Attention)
| Permission | Description | super-settings | settings | manager | attention-settings | attention-manager | attention-agent | attention-user |
|---|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `attention.view` | Ver PQRSF asignados | Y | Y | - | Y | - | Y | Y |
| `attention.view-all` | Ver todos los PQRSF | Y | Y | Y | Y | Y | - | - |
| `attention.create` | Crear PQRSF | Y | Y | Y | Y | Y | Y | Y |
| `attention.update` | Actualizar PQRSF | Y | Y | Y | Y | Y | Y | - |
| `attention.delete` | Eliminar PQRSF | Y | Y | - | Y | - | - | - |
| `attention.manage` | Gestionar completamente | Y | Y | Y | Y | Y | - | - |
| `attention.assign` | Asignar PQRSF | Y | Y | Y | Y | Y | - | - |
| `attention.change-status` | Cambiar estado | Y | Y | Y | Y | Y | Y | - |
| `attention.resolve` | Resolver | Y | Y | Y | Y | Y | Y | - |
| `attention.close` | Cerrar | Y | Y | Y | Y | Y | - | - |
| `attention.send-email` | Enviar emails | Y | Y | Y | Y | Y | Y | - |
| `attention.manage-notes` | Gestionar notas | Y | Y | Y | Y | Y | Y | - |
| `attention.view-history` | Ver historial | Y | Y | Y | Y | Y | Y | - |
| `attention.manage-departments` | Gestionar departamentos | Y | Y | - | Y | - | - | - |
| `attention.manage-types` | Gestionar tipos | Y | Y | - | Y | - | - | - |
| `attention.manage-settings` | Configuración del módulo | Y | Y | - | Y | - | - | - |
| `attention.view-reports` | Ver reportes | Y | Y | Y | Y | Y | - | - |

### Analytics (Modules/Analytics)
| Permission | Description | super-settings | settings | manager |
|---|---|:---:|:---:|:---:|
| `analytics.view` | Ver dashboard | Y | Y | Y |
| `analytics.settings.view` | Ver configuración | Y | Y | Y |
| `analytics.settings.update` | Actualizar configuración | Y | Y | - |
| `analytics.schedules.view` | Ver reportes programados | Y | Y | Y |
| `analytics.schedules.manage` | Gestionar reportes programados | Y | Y | - |

> Note: Analytics routes only use `auth` middleware — no Spatie permission checks in routes. Controllers should be updated to use `$this->authorize()`.

### Backup (Modules/Backup)
| Permission | Description | super-settings | settings |
|---|---|:---:|:---:|
| `Backup.backups.index` | Ver backups | Y | Y |
| `Backup.backups.download` | Descargar backups | Y | Y |
| `Backup.backups.delete` | Eliminar backups | Y | - |
| `Backup.schedules.index` | Ver programaciones | Y | Y |
| `Backup.schedules.create` | Crear programaciones | Y | Y |
| `Backup.schedules.update` | Actualizar programaciones | Y | Y |
| `Backup.schedules.delete` | Eliminar programaciones | Y | - |

### Blog (Modules/Blog)
| Permission | Description | super-settings | settings | manager | blog-admin | blog-editor | blog-author |
|---|---|:---:|:---:|:---:|:---:|:---:|:---:|
| `blog.post.view` | Ver propias | Y | Y | Y | Y | - | Y |
| `blog.post.view-all` | Ver todas | Y | Y | Y | Y | Y | - |
| `blog.post.create` | Crear | Y | Y | Y | Y | Y | Y |
| `blog.post.update` | Editar | Y | Y | Y | Y | Y | - |
| `blog.post.delete` | Eliminar | Y | Y | - | Y | - | - |
| `blog.post.publish` | Publicar | Y | Y | Y | Y | Y | - |
| `blog.category.view` | Ver categorías | Y | Y | Y | Y | Y | Y |
| `blog.category.create` | Crear categorías | Y | Y | Y | Y | Y | - |
| `blog.category.update` | Editar categorías | Y | Y | Y | Y | Y | - |
| `blog.category.delete` | Eliminar categorías | Y | Y | - | Y | - | - |
| `blog.tag.view` | Ver etiquetas | Y | Y | Y | Y | Y | Y |
| `blog.tag.create` | Crear etiquetas | Y | Y | Y | Y | Y | - |
| `blog.tag.update` | Editar etiquetas | Y | Y | Y | Y | Y | - |
| `blog.tag.delete` | Eliminar etiquetas | Y | Y | - | Y | - | - |
| `blog.comment.view` | Ver comentarios | Y | Y | Y | Y | Y | - |
| `blog.comment.moderate` | Moderar comentarios | Y | Y | Y | Y | Y | - |
| `blog.comment.delete` | Eliminar comentarios | Y | Y | - | Y | - | - |
| `blog.settings` | Configuración módulo | Y | Y | - | Y | - | - |

### Cache (Modules/Cache)
| Permission | Description | super-settings | settings |
|---|---|:---:|:---:|
| `Cache.index` | Acceder al módulo | Y | Y |
| `Cache.settings.index` | Ver configuración | Y | Y |
| `Cache.settings.update` | Actualizar configuración | Y | Y |

### Cookie (Modules/Cookie)
| Permission | Description | super-settings | settings |
|---|---|:---:|:---:|
| `Cookie.settings.index` | Ver configuración | Y | Y |
| `Cookie.settings.update` | Actualizar configuración | Y | Y |

### Database (Modules/Database)
| Permission | Description | super-settings | settings | manager |
|---|---|:---:|:---:|:---:|
| `database.backups.view` | Ver configuración BD | Y | Y | Y |
| `database.backups.update` | Actualizar configuración BD | Y | Y | - |
| `database.backups.test_connection` | Probar conexión | Y | Y | - |
| `database.cleanup.view` | Ver herramienta limpieza | Y | Y | Y |
| `database.cleanup.truncate` | Truncar tablas | Y | - | - |
| `database.cleanup.get_table_count` | Contar registros | Y | Y | Y |

> Note: Database routes use `can:` middleware directly — well protected.

### Forms (Modules/Forms)
| Permission | Description | super-settings | settings | manager |
|---|---|:---:|:---:|:---:|
| `Forms.forms.index` | Ver formularios | Y | Y | Y |
| `Forms.forms.create` | Crear formularios | Y | Y | Y |
| `Forms.forms.edit` | Editar formularios | Y | Y | Y |
| `Forms.forms.delete` | Eliminar formularios | Y | Y | - |
| `Forms.submissions.index` | Ver respuestas | Y | Y | Y |
| `Forms.submissions.export` | Exportar respuestas | Y | Y | Y |
| `Forms.submissions.delete` | Eliminar respuestas | Y | Y | - |
| `Forms.categories.manage` | Gestionar categorías | Y | Y | Y |
| `Forms.analytics.index` | Ver analíticas | Y | Y | Y |
| `Forms.settings.manage` | Configuración módulo | Y | - | - |

### Helpdesk (Modules/Helpdesk)
Two sets of permissions exist: legacy flat names and namespaced `manager.helpdesk.*` names.

**Legacy permissions:**
| Permission | super-settings | settings | manager | helpdesk-agent |
|---|:---:|:---:|:---:|:---:|
| `manage_helpdesk_settings` | Y | Y | Y | - |
| `access_helpdesk` | Y | Y | Y | Y |
| `create_tickets` | Y | Y | - | Y |
| `view_all_tickets` | Y | Y | Y | - |
| `view_assigned_tickets` | Y | Y | - | Y |
| `edit_tickets` | Y | Y | Y | Y |
| `delete_tickets` | Y | Y | - | - |
| `assign_tickets` | Y | Y | Y | - |
| `close_tickets` | Y | Y | Y | Y |
| `reopen_tickets` | Y | Y | Y | Y |
| `merge_tickets` | Y | Y | Y | - |
| `view_internal_notes` | Y | Y | - | Y |
| `create_internal_notes` | Y | Y | - | Y |
| `manage_canned_replies` | Y | Y | Y | - |
| `view_reports` | Y | Y | Y | Y |
| `export_reports` | Y | Y | Y | - |

### Mailer (Modules/Mailer)
| Permission | super-settings | settings | manager | administrative |
|---|:---:|:---:|:---:|:---:|
| `mailer.templates.view` | Y | Y | Y | Y |
| `mailer.templates.create` | Y | Y | Y | - |
| `mailer.templates.update` | Y | Y | Y | - |
| `mailer.templates.delete` | Y | Y | - | - |
| `mailer.templates.preview` | Y | Y | Y | Y |
| `mailer.components.view` | Y | Y | Y | Y |
| `mailer.components.create` | Y | Y | Y | - |
| `mailer.components.update` | Y | Y | Y | - |
| `mailer.components.delete` | Y | Y | - | - |
| `mailer.components.preview` | Y | Y | Y | Y |
| `mailer.variables.view` | Y | Y | Y | Y |
| `mailer.variables.create` | Y | Y | Y | - |
| `mailer.variables.update` | Y | Y | Y | - |
| `mailer.variables.delete` | Y | Y | - | - |
| `mailer.endpoints.view` | Y | Y | Y | Y |
| `mailer.endpoints.create` | Y | Y | Y | - |
| `mailer.endpoints.update` | Y | Y | Y | - |
| `mailer.endpoints.delete` | Y | Y | - | - |
| `mailer.endpoints.logs` | Y | Y | Y | Y |
| `mailer.endpoints.regenerate-token` | Y | Y | - | - |
| `mailer.manage` | Y | Y | - | - |

### Mailrelay (Modules/Mailrelay)
| Permission | super-settings | settings | manager | administrative |
|---|:---:|:---:|:---:|:---:|
| `mailrelay.access` | Y | Y | Y | Y |
| `mailrelay.dashboard.view` | Y | Y | Y | - |
| `mailrelay.campaigns.view` | Y | Y | Y | Y |
| `mailrelay.campaigns.create` | Y | Y | Y | - |
| `mailrelay.campaigns.update` | Y | Y | Y | - |
| `mailrelay.campaigns.delete` | Y | Y | - | - |
| `mailrelay.campaigns.send` | Y | Y | Y | - |
| `mailrelay.campaigns.duplicate` | Y | Y | Y | - |
| `mailrelay.campaigns.analytics` | Y | Y | Y | - |
| `mailrelay.subscribers.view` | Y | Y | Y | Y |
| `mailrelay.subscribers.create` | Y | Y | Y | - |
| `mailrelay.subscribers.update` | Y | Y | Y | - |
| `mailrelay.subscribers.delete` | Y | Y | - | - |
| `mailrelay.subscribers.import` | Y | Y | Y | - |
| `mailrelay.subscribers.export` | Y | Y | Y | - |
| `mailrelay.lists.view` | Y | Y | Y | Y |
| `mailrelay.lists.create` | Y | Y | Y | - |
| `mailrelay.lists.update` | Y | Y | Y | - |
| `mailrelay.lists.delete` | Y | Y | - | - |
| `mailrelay.settings.manage` | Y | Y | - | - |

### Media (Modules/Media)
| Permission | Description | super-settings | settings | manager | customer |
|---|---|:---:|:---:|:---:|:---:|
| `media.view` | Ver archivos multimedia | Y | Y | Y | Y |
| `media.upload` | Subir archivos | Y | Y | Y | Y |
| `media.update` | Actualizar archivos | Y | Y | Y | Y |
| `media.delete` | Eliminar archivos | Y | Y | Y | - |
| `media.folders.view` | Ver carpetas | Y | Y | Y | - |
| `media.folders.create` | Crear carpetas | Y | Y | Y | - |
| `media.folders.update` | Actualizar carpetas | Y | Y | Y | - |
| `media.folders.delete` | Eliminar carpetas | Y | Y | - | - |

> Note: Media module uses `auth` middleware + ownership-based policies (MediaFilePolicy). No Spatie permission middleware in routes.

### Page / CMS (Modules/Page)
| Permission | super-settings | settings | manager | cms-settings | cms-editor | cms-author | cms-viewer |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `page.view` | Y | Y | Y | Y | Y | Y | Y |
| `page.view-all` | Y | Y | Y | Y | Y | - | - |
| `page.create` | Y | Y | Y | Y | Y | Y | - |
| `page.update` | Y | Y | Y | Y | Y | Y | - |
| `page.delete` | Y | Y | - | Y | - | - | - |
| `page.publish` | Y | Y | Y | Y | Y | - | - |
| `page.manage` | Y | Y | - | Y | - | - | - |
| `page.approve` | Y | Y | - | Y | - | - | - |
| `page.manage-categories` | Y | Y | - | Y | - | - | - |
| `page.export` | Y | Y | Y | Y | Y | - | - |
| `page.view-analytics` | Y | Y | Y | Y | Y | - | - |
| `menu.view` | Y | Y | Y | Y | Y | - | Y |
| `menu.create` | Y | Y | - | Y | - | - | - |
| `menu.update` | Y | Y | Y | Y | Y | - | - |
| `menu.manage-items` | Y | Y | Y | Y | Y | - | - |
| `shortcode.use` | Y | Y | Y | Y | Y | Y | - |
| `shortcode.manage` | Y | Y | - | Y | - | - | - |
| `sitemap.view` | Y | Y | Y | Y | Y | - | Y |
| `sitemap.generate` | Y | Y | - | Y | - | - | - |

### Reviews (Modules/Reviews)
| Permission | super-settings | settings | manager |
|---|:---:|:---:|:---:|
| `reviews.connections.view` | Y | Y | Y |
| `reviews.connections.create` | Y | Y | - |
| `reviews.connections.delete` | Y | Y | - |
| `reviews.reviews.view` | Y | Y | Y |
| `reviews.reviews.export` | Y | Y | Y |
| `reviews.moderate` | Y | Y | Y |
| `reviews.moderate.featured` | Y | Y | Y |
| `reviews.replies.create` | Y | Y | Y |
| `reviews.replies.approve` | Y | Y | - |
| `reviews.replies.publish` | Y | Y | - |
| `reviews.replies.delete` | Y | Y | - |
| `reviews.templates.view` | Y | Y | Y |
| `reviews.templates.create` | Y | Y | Y |
| `reviews.templates.update` | Y | Y | Y |
| `reviews.templates.delete` | Y | Y | - |
| `reviews.settings.manage` | Y | - | - |

### SEO (Modules/Seo)
| Permission | super-settings | settings | manager |
|---|:---:|:---:|:---:|
| `Seo.metas.index` | Y | Y | Y |
| `Seo.metas.create` | Y | Y | Y |
| `Seo.metas.update` | Y | Y | Y |
| `Seo.metas.delete` | Y | Y | - |
| `Seo.redirects.index` | Y | Y | Y |
| `Seo.redirects.create` | Y | Y | Y |
| `Seo.redirects.update` | Y | Y | Y |
| `Seo.redirects.delete` | Y | Y | - |
| `Seo.robots.index` | Y | Y | - |
| `Seo.robots.update` | Y | Y | - |
| `Seo.static-urls.index` | Y | Y | Y |
| `Seo.dashboard.view` | Y | Y | Y |
| `Seo.report.view` | Y | Y | Y |
| `Seo.orphans.view` | Y | Y | Y |
| `Seo.404-logs.view` | Y | Y | Y |
| `Seo.404-logs.delete` | Y | Y | - |
| `Seo.templates.index` | Y | Y | Y |
| `Seo.audit.history` | Y | Y | Y |

### System (Modules/System)
| Permission | Description | super-settings | settings |
|---|---|:---:|:---:|
| `system.backups.manage` | Gestionar configuración | Y | Y |
| `system.maintenance` | Modo mantenimiento | Y | - |
| `system.logs.view` | Ver logs | Y | Y |
| `system.api.manage` | Gestionar API tokens | Y | - |
| `system.emails.manage` | Configurar emails | Y | Y |
| `system.hours.manage` | Configurar horarios | Y | Y |

### Template (Modules/Template)
| Permission | Description | super-settings | settings | manager |
|---|---|:---:|:---:|:---:|
| `template.view` | Ver plantillas | Y | Y | Y |
| `template.create` | Crear plantillas | Y | Y | - |
| `template.update` | Actualizar plantillas | Y | Y | - |
| `template.delete` | Eliminar plantillas | Y | Y | - |
| `template.manage` | Gestionar plantillas | Y | Y | - |

### Storage (Modules/Storage)
| Permission | Description | super-settings | settings |
|---|---|:---:|:---:|
| `storage.view` | Ver configuración | Y | Y |
| `storage.create` | Crear discos | Y | Y |
| `storage.update` | Actualizar discos | Y | Y |
| `storage.delete` | Eliminar discos | Y | - |

---

## Naming Convention Issues

The codebase has inconsistent permission naming:

| Module | Convention Used | Example |
|---|---|---|
| Attention | `module.action` | `attention.view-all` |
| Blog | `module.resource.action` | `blog.post.create` |
| Backup | `Module.resource.action` (PascalCase) | `Backup.backups.index` |
| Cookie | `Module.resource.action` (PascalCase) | `Cookie.settings.update` |
| Database | `module.resource.action` | `database.backups.view` |
| Forms | `Module.resource.action` (PascalCase) | `Forms.forms.index` |
| Helpdesk (legacy) | `verb_noun` (snake_case) | `view_all_tickets` |
| Helpdesk (new) | `scope.module.resource.action` | `manager.helpdesk.tickets.index` |
| Mailer | `module.resource.action` | `mailer.templates.view` |
| Reviews | `module.resource.action` | `reviews.replies.create` |
| SEO | `Module.resource.action` (PascalCase) | `Seo.metas.index` |
| User (policy) | `verb-noun` (kebab-case) | `view-users` |
| User (seeder) | `module.action` | `users.view` |

**Recommendation**: Standardize on lowercase `module.resource.action` in future modules.

---

## Unprotected Routes (Audit Findings)

The following routes rely only on `auth` middleware without Spatie permission checks. They are protected from unauthenticated users but any authenticated user can access them.

| Module | Route Group | Risk | Recommendation |
|---|---|---|---|
| Analytics | `setting/analytics`, `analytics/dashboard` | Medium — any logged-in user can view/edit analytics settings | Add `analytics.settings.view` / `analytics.settings.update` checks in controller |
| Cookie | `setting/cookie` (via ServiceProvider) | Low — uses `auth` middleware, admin-like URL | Add `Cookie.settings.index` check to controller |
| Notification | All routes | Low — user-specific notifications | Acceptable as-is; all operations are user-scoped |
| Storage | `storage/` | High — can create/delete storage disks | Add `storage.view` / `storage.delete` checks |
| SEO | `setting/seo` | Medium — any auth user can access | Uses `auth` only; should add `Seo.dashboard.view` check |
| Media | `media/` | Low — ownership policy in MediaFilePolicy | Acceptable; ownership-based policy covers it |

---

## Adding Permissions

When adding a new module resource, follow this pattern in the module's permission seeder:

```php
// module.resource.view   — view list and detail
// module.resource.create — create new records
// module.resource.update — edit existing records
// module.resource.delete — delete records
// module.resource.export — export data (when applicable)
```

Register the new permissions in `PermissionsAuditSeeder::allPermissions()` and assign them to the appropriate roles.
