# Inventario Completo de Modulos (40 modulos)

Este archivo documenta TODOS los modulos existentes para que al crear uno nuevo se sigan los mismos patrones.

## Tabla de modulos

| Modulo | Alias | Estado | Controllers | Models | Migrations | Sub-Providers | NavService | Features especiales |
|--------|-------|--------|------------|--------|-----------|---------------|------------|-------------------|
| Activity | activity | ON | 1 | 1 | 0 | - | settings sidebar | Logging, scheduled commands |
| Analytics | analytics | ON | 7 | 1 | 4 | - | settings sidebar | Facade, scheduled 15min, events |
| Attention | attention | ON | 13 | 13 | 24 | Route+Event+Blade | mini+sidebar+settings | 3 jobs, 5 events, policy, 12 seeders |
| Auth | auth | ON/critico | 12 | 1 | 16 | - | ninguno | Gates, 37 middleware, 2FA |
| Backup | backup | ON | 3 | 2 | 3 | Route | settings sidebar | Scheduled 3/4/5 AM, helpers |
| Blog | blog | ON | 10 | 11 | 22 | Route | mini+sidebar+settings | Observer, 3 jobs, 4 events |
| Cache | Cache | ON | 1 | 0 | 0 | Route | addItemsToSection | Config management |
| Captcha | captcha | ON | 1 | 0 | 0 | Route | addItemsToSection | Facade, validator rules |
| Cookie | Cookie | ON | 2 | 1 | 3 | - | settings sidebar | GDPR consent, helpers |
| Core | core | ON/critico | 1 | 5 | 14 | - | mini+sidebar(dashboard) | 2 jobs, dashboard KPIs |
| Database | database | ON | 3 | 0 | 0 | Route | settings sidebar | Dynamic DB config |
| Forms | forms | ON | 15 | 15 | 22 | - | mini+sidebar+settings | Shortcodes, 2 jobs, events |
| Health | health | ON | 2 | 0 | 1 | Route | settings sidebar | Spatie Health, 15min checks |
| Helpdesk | helpdesk | ON | 51 | 55 | 60 | Route+Event | ninguno | 10 jobs, 21 events, 15 policies, SLA |
| Locales | locales | ON | 2 | 1 | 1 | Route+Event | addItemsToSection | LocaleService singleton |
| Mailer | mailer | ON | 5 | 10 | 11 | Route | mini+sidebar | 4 observers, 3 jobs, policies |
| Mailrelay | mailrelay | ON | 36 | 34 | 63 | Event | mini+sidebar+settings | 8 jobs, scheduled hourly, Blade directives |
| MailsSettings | mails-settings | ON | 3 | 0 | 0 | - | settings sidebar | Dynamic SMTP/IMAP from DB |
| Media | media | ON | 4 | 3 | 5 | - | mini+sidebar | Repository pattern, 1 observer, 3 jobs |
| Menu | menu | OFF | 1 | 1 | 1 | - | - | Menu management |
| Modules | modules | ON/critico | 1 | 0 | 0 | Event+Route | addItemsToSection | Toggle commands |
| Newsletter | newsletter | ON | 5 | 1 | 1 | - | addItemsToSection | Blade components, MailjetService |
| Notification | notification | ON | 4 | 2 | 2 | - | mini+sidebar | Observer, scheduled daily+digest, WebSocket |
| Optimize | optimize | ON | 1 | 0 | 0 | - | addItemsToSection | 12 middleware (HTML optimization) |
| Page | page | ON | 21 | 14 | 34 | Route+Event+Schedule | mini+sidebar | 10 cmds, 7 jobs, observer, locking, autosave |
| Pulse | pulse | OFF | 2 | 0 | 1 | Event+Route | ninguno | APM, Livewire dependency |
| Queue | queue | ON | 1 | 0 | 0 | Route | addItemsToSection | 3 commands, Blade components |
| Reviews | reviews | ON | 26 | 21 | 34 | Route | mini+sidebar+settings | 15 cmds, 10 jobs, 7 policies, Google API, webhooks, rate limiters |
| Reverb | reverb | ON | 0 | 0 | 0 | - | - | WebSocket server |
| Role | role | ON/critico | 2 | 1 | 4 | Blade | settings sidebar | 8 cmds, Spatie Permission, Blade directives |
| Seo | seo | ON | 15 | 7 | 16 | - | settings sidebar(16 items) | 6 cmds, 5 middleware, 3 facades, 5 jobs, observer |
| Shortcode | shortcode | ON | 1 | 0 | 0 | Route+Event | ninguno | Facade, 15+ shortcodes, Blade directives |
| Sitemap | sitemap | ON | 1 | 0 | 0 | Route+Event | ninguno | Facade, middleware, scheduled daily 2AM |
| Slug | slug | OFF | 0 | 0 | 0 | - | - | URL slug management |
| Storage | storage | ON | 1 | 0 | 0 | - | addItemsToSection | Custom disks from DB, encryption |
| System | system | ON | 13 | 0 | 0 | - | mini+sidebar(settings) | 3 middleware, SystemInfoService |
| Template | template | ON | 9 | 6 | 9 | Route+Event | settings sidebar(7 items) | 3 cmds, 6 facades, theme engine, dynamic shortcodes |
| Theme | theme | ON/critico | 1 | 0 | 0 | Menu | ninguno (ES el NavService) | NavService provider, theme assets |
| User | user | ON | 1 | 0 | 0 | - | mini+sidebar | UserPolicy, public search route |
| Widget | widget | OFF | 1 | 1 | 1 | Route | ninguno | 2 facades, cache decorators |

## Clasificacion por complejidad

### Tier 1 - Enterprise (50+ migrations, 20+ controllers)
- **Helpdesk** (51 ctrl, 55 models, 60 migrations, 21 events, 15 policies)
- **Mailrelay** (36 ctrl, 34 models, 63 migrations, 8 jobs)

### Tier 2 - Full Feature (15+ migrations, 10+ controllers)
- **Page** (21 ctrl, 14 models, 34 migrations, 10 cmds, 7 jobs)
- **Reviews** (26 ctrl, 21 models, 34 migrations, 15 cmds, 10 jobs)
- **Attention** (13 ctrl, 13 models, 24 migrations, 5 events)
- **Blog** (10 ctrl, 11 models, 22 migrations, observer)
- **Forms** (15 ctrl, 15 models, 22 migrations, shortcodes)
- **Auth** (12 ctrl, 16 migrations, 37 middleware)
- **Seo** (15 ctrl, 7 models, 16 migrations, 3 facades)

### Tier 3 - Medium (5+ migrations or 3+ controllers)
- **Core** (1 ctrl, 5 models, 14 migrations, dashboard)
- **Mailer** (5 ctrl, 10 models, 11 migrations, 4 observers)
- **Template** (9 ctrl, 6 models, 9 migrations, 6 facades)
- **System** (13 ctrl, 0 models, 0 migrations)
- **Analytics** (7 ctrl, 1 model, 4 migrations)

### Tier 4 - Light (1-3 controllers, minimal)
- Activity, Backup, Cache, Captcha, Cookie, Database, Health, Locales, MailsSettings, Media, Menu, Modules, Newsletter, Notification, Optimize, Queue, Role, Shortcode, Sitemap, Storage, User, Widget

## Patrones por tipo de NavService

### registerMiniItem + registerSidebar (icono + menu lateral)
Core (dashboard), Attention, Blog, Forms, Mailer, Mailrelay, Media, Notification, Page, Reviews, System, User

### Solo registerSidebar (settings section)
Activity, Analytics, Backup, Cookie, Database, Health, MailsSettings, Role, Seo, Template

### addItemsToSection (agregar a seccion existente)
Cache, Captcha, Locales, Modules, Newsletter, Optimize, Queue, Storage

### Sin NavService
Auth, Helpdesk, Pulse, Shortcode, Sitemap, Theme (es el provider), Reverb, Menu, Slug, Widget

## Order values usados
- 10: Core (dashboard)
- 30: User
- 45: Attention
- 46: Forms
- 50: Default para nuevos
- 55: Blog

## Sub-Providers pattern

| Patron | Modulos que lo usan |
|--------|-------------------|
| RouteServiceProvider | Attention, Backup, Blog, Cache, Captcha, Database, Health, Helpdesk, Locales, Mailer, Page, Queue, Reviews, Shortcode, Sitemap, Template, Widget |
| EventServiceProvider | Attention, Helpdesk, Locales, Mailrelay, Modules, Page, Shortcode, Sitemap, Template |
| ScheduleServiceProvider | Page |
| BladeDirectivesServiceProvider | Attention |
| PermissionBladeServiceProvider | Role |
| MenuServiceProvider | Theme |

## Middleware custom por modulo
- Auth: 37 middleware (login, 2FA, session, etc.)
- Optimize: 12 middleware (HTML optimization)
- Seo: 5 middleware (redirects, 404, X-Robots, pagination, sitemap cache)
- System: 3 middleware
- Captcha: validator rules (no middleware)
