# 🛍️ Alsernet - E-Commerce Platform

**Alsernet** es una plataforma de e-commerce moderna construida con Laravel 12, PostgreSQL y un **sistema de agentes inteligentes** para desarrollo ágil y eficiente.

---

## 📋 Tabla de Contenidos

- [Descripción](#descripción)
- [Stack Tecnológico](#stack-tecnológico)
- [Sistema de Agentes](#sistema-de-agentes)
- [Cómo Solicitar Cambios](#cómo-solicitar-cambios)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Setup Local](#setup-local)
- [Documentación](#documentación)

---

## Descripción

Alsernet es un sistema de e-commerce completo con:

✅ **Gestión de Productos** - Catálogo, inventario, precios dinámicos
✅ **Sistema de Roles** - RBAC con permisos granulares
✅ **Panel de Admin** - Basado en Modernize template
✅ **API RESTful** - Endpoints autenticados con Laravel Sanctum
✅ **Real-time Features** - WebSockets con Laravel Reverb
✅ **Auditoría Completa** - Activity logs de todas las acciones
✅ **Sistema de Queues** - Background jobs con Supervisor

---

## Stack Tecnológico

### Backend
- **Laravel 12.x** - Framework web PHP
- **PostgreSQL 14+** - Base de datos relacional
- **PHP 8.3+** - Lenguaje backend
- **Redis 6+** - Cache, sessions, queues
- **Laravel Sanctum** - Autenticación API
- **Laravel Reverb** - WebSockets para real-time

### Frontend
- **Bootstrap 5.3+** - Framework CSS responsive
- **jQuery 3.6+** - Manipulación DOM
- **Vite** - Build tool moderno
- **Laravel Echo** - Cliente WebSocket
- **DevExpress jQuery** - Widgets avanzados

### Herramientas
- **Laravel Telescope** - Debugging
- **Laravel Horizon** - Queue management
- **Laravel Pulse** - Performance monitoring
- **Spatie Permissions** - RBAC management

---

## Sistema de Agentes

Alsernet utiliza **3 agentes independientes** para acelerar el desarrollo:

### 🎯 Plan Agent (Planificación)
Planifica la implementación, descompone features y evalúa riesgos.

**Cuándo usar:**
- Iniciar una nueva feature
- Diseñar arquitectura
- Planificar tareas secuenciales
- Evaluar riesgos

**Capacidades:** 35 (Feature analysis, Architecture planning, Task breakdown, Risk assessment)

📚 [Guías de Plan Agent](./.claude/guides/plan/)

---

### 🎨 Frontend Agent (Interfaz)
Desarrolla componentes UI con jQuery, Bootstrap y validación.

**Cuándo usar:**
- Crear formularios
- Construir tablas interactivas
- Implementar modales
- Integrar real-time updates

**Capacidades:** 45 (DOM, Forms, Bootstrap components, DataTables, WebSockets, Storage)

📚 [Guías de Frontend Agent](./.claude/agents/frontend/)

---

### 🔧 Backend Agent (API & Lógica)
Crea modelos, APIs, servicios y lógica de negocio.

**Cuándo usar:**
- Crear modelos y migrations
- Construir endpoints API
- Implementar servicios
- Configurar eventos

**Capacidades:** 41 (Models, Controllers, Services, Real-time, Data management)

📚 [Guías de Backend Agent](./.claude/agents/backend/)

---

## Cómo Solicitar Cambios

El sistema utiliza **Modalidad Inteligente**: automáticamente elige entre Quick Mode (tareas simples) y Structured Mode (tareas complejas).

### Ejemplo 1: Solicitud Simple (Quick Mode ⚡)
```
"Agrega un campo de teléfono al modelo Customer"

→ Respuesta automática:
⚡ QUICK MODE - 2 horas, 1 fase
¿Testing? ☐ SÍ ☐ NO
→ Ejecutando...
```

### Ejemplo 2: Solicitud Compleja (Structured Mode 📋)
```
"Crea un sistema de devoluciones con:
- Solicitud desde el cliente
- Validación en almacén
- Generación de etiqueta de envío
- Procesamiento de reembolso
- Seguimiento en tiempo real
- Dashboard de análisis"

→ Respuesta con cronograma:
📋 STRUCTURED MODE
Fase 1: Planning (2h)
Fase 2: Backend (10h)
Fase 3: Frontend (6h)
Fase 4: Testing (3h)

¿Usar Plan Agent? ✓ YES ✗ NO
¿Testing? ☐ SÍ ☐ NO
¿Estilo? ☐ Por Fases ☐ Todo de una ☐ Híbrido
```

### Más Información
Ver [how-to-request-changes.md](./.claude/guides/plan/how-to-request-changes.md) para ejemplos detallados.

---

## Estructura del Proyecto

```
Alsernet/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Controladores API
│   │   └── Requests/        # Form requests con validación
│   ├── Models/              # Modelos Eloquent
│   ├── Services/            # Lógica de negocio
│   ├── Events/              # Eventos para broadcasting
│   └── Listeners/           # Handlers de eventos
│
├── database/
│   ├── migrations/          # Migraciones de BD
│   └── factories/           # Factories para testing
│
├── routes/
│   ├── api.php              # Rutas API
│   └── web.php              # Rutas web
│
├── resources/
│   ├── views/               # Vistas Blade
│   └── js/                  # JavaScript/jQuery
│
├── .claude/                 # Sistema de agentes inteligentes
│   ├── agents/
│   │   ├── plan/            # Plan Agent spec & capabilities
│   │   ├── frontend/        # Frontend Agent spec & capabilities
│   │   └── backend/         # Backend Agent spec & capabilities
│   │
│   ├── guides/
│   │   ├── plan/            # 5 guías de planificación
│   │   ├── frontend/        # Guías de componentes & patrones
│   │   ├── backend/         # Guías de módulos & endpoints
│   │   └── thematic/        # Guías temáticas (API, DB, Security, Testing)
│   │
│   ├── reference/
│   │   ├── frontend/        # Componentes, layouts, jQuery, Modernize
│   │   └── project/         # Documentación del proyecto (API, Backend, DevOps, Setup)
│   │
│   ├── database-optimization/  # Guías de optimización DB
│   ├── setup/                  # Git hooks y configuración
│   ├── agents.md               # Registry central de agentes
│   ├── index.md                # Índice del sistema .claude/
│   ├── md_saving_conventions.md # Convenciones de archivos
│   └── agents-config.json      # Configuración centralizada
│
├── integrations/                # Integraciones externas
│   └── prestashop/              # PrestaShop + Alsernet
│       ├── content/             # Código PrestaShop (7600+ files, 84MB)
│       │   ├── modules/         # 6 módulos personalizados ✨
│       │   ├── override/        # Overrides de clases
│       │   └── ...              # Estructura estándar PrestaShop
│       │
│       └── docs/                # Documentación integración
│           ├── api-connection.md
│           ├── modules-guide.md
│           └── setup.md
│
└── README.md                # Este archivo
```

---

## Setup Local

### Requisitos
- PHP 8.3+
- PostgreSQL 14+
- Redis 6+
- Node.js 18+
- Composer
- Supervisor (para jobs)

### Instalación

1. **Clonar repositorio**
   ```bash
   git clone <repository> Alsernet
   cd Alsernet
   ```

2. **Instalar dependencias**
   ```bash
   composer install
   npm install
   ```

3. **Configurar ambiente**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configurar base de datos**
   ```bash
   php artisan migrate
   php artisan seed
   ```

5. **Compilar assets**
   ```bash
   npm run dev    # Desarrollo
   npm run build  # Producción
   ```

6. **Iniciar servidor**
   ```bash
   php artisan serve
   ```

Acceder a: `http://localhost:8000`

---

## Documentación

Toda la documentación está centralizada en **`.claude/`** para máximo acceso por los agentes inteligentes.

### 📖 Guía Rápida
- **[.claude/index.md](./.claude/index.md)** - Navegación completa del sistema
- **[agents.md](./.claude/agents.md)** - Registry central de los 3 agentes (121 capacidades)

### 🎯 Plan Agent (35 capacidades)
- **[plan-design.md](./.claude/agents/plan/plan-design.md)** - Especificación
- **[plan-agent-quick-start.md](./.claude/guides/plan/plan-agent-quick-start.md)** - Quick start (5 min)
- **[how-to-request-changes.md](./.claude/guides/plan/how-to-request-changes.md)** - Solicitar cambios
- [+ 4 guías más](./.claude/guides/plan/) - Feature planning, architecture, task breakdown, risk assessment

### 🎨 Frontend Agent (45 capacidades)
- **[frontend-design.md](./.claude/agents/frontend/frontend-design.md)** - Especificación
- [Patrones jQuery](./.claude/guides/frontend/) - Componentes, forms, real-time
- [Referencias](./.claude/reference/frontend/) - Modernize, layouts, librerías jQuery

### 🔧 Backend Agent (41 capacidades)
- **[backend-design.md](./.claude/agents/backend/backend-design.md)** - Especificación
- [Guías backend](./.claude/guides/backend/) - Módulos, endpoints, logging
- [Documentación del proyecto](./.claude/reference/project/backend/) - Roles, rutas, permisos

### 📚 Guías Temáticas (Reutilizables)
- **[database-patterns.md](./.claude/guides/thematic/database-patterns.md)** - Patrones PostgreSQL
- **[api-standards.md](./.claude/guides/thematic/api-standards.md)** - Estándares REST API
- **[security-patterns.md](./.claude/guides/thematic/security-patterns.md)** - Patrones de seguridad
- **[testing-standards.md](./.claude/guides/thematic/testing-standards.md)** - Estándares de testing

### 📝 Configuración y Referencias
- **[CLAUDE.md](./CLAUDE.md)** - Instrucciones para Claude Code
- **[md_saving_conventions.md](./.claude/md_saving_conventions.md)** - Cómo guardar archivos .md
- **[agents-config.json](./.claude/agents-config.json)** - Configuración JSON de agentes

---

## Flujo de Trabajo

```
1. Solicitar Cambio
   ↓
2. Modalidad Inteligente Decide
   ├─ QUICK MODE (< 5h)
   │  └─ Resumen rápido → Testing? → Ejecutar
   │
   └─ STRUCTURED MODE (> 5h)
      └─ Cronograma → Agentes? → Testing? → Estilo? → Ejecutar
   ↓
3. Agentes Implementan
   ├─ Plan Agent: Analiza y planifica
   ├─ Frontend Agent: Crea UI
   └─ Backend Agent: Crea API
   ↓
4. Tests (si aplica)
   ├─ Unit tests
   ├─ Integration tests
   └─ E2E tests
   ↓
5. Commit & Listo
   └─ Cambio en producción
```

---

## Contribución

### Desarrollo de Nueva Feature

1. **Solicita el cambio** (en español o inglés)
2. **Autoriza agentes** si es necesario
3. **Decide testing** (SÍ/NO)
4. **Elige estilo** (por fases, todo de una, híbrido)
5. **Sistema implementa automáticamente**

### Commits

Los commits se hacen automáticamente al final de cada fase/tarea con mensajes descriptivos.

---

## Arquitectura de Agentes

### Independencia Completa
Los 3 agentes son **completamente independientes**:
- ✅ Especificaciones separadas
- ✅ Capacidades separadas
- ✅ Guías separadas
- ✅ Tecnologías diferentes
- ✅ Responsabilidades distintas

### Integración
Se integran automáticamente en el flujo de trabajo:
```
Plan Agent (Diseña)
    ↓
Frontend Agent (UI) + Backend Agent (API) [en paralelo]
    ↓
Testing [opcional]
    ↓
Commit & Deploy
```

---

## Métricas del Sistema

| Aspecto | Valor |
|---------|-------|
| **Agentes** | 3 (Plan, Frontend, Backend) |
| **Capacidades Totales** | 121 |
| **Guías** | 11 |
| **Stack Tecnológico** | Laravel, PostgreSQL, Redis, Bootstrap, jQuery |
| **Modelado** | Haiku (Frontend/Backend), Inherit (Plan) |

---

## Soporte y Recursos

- 📖 **Documentación:** Ver carpeta `docs/` y `.claude/guides/`
- 🤖 **Agentes:** Descriptos en `.claude/agents/`
- 💬 **Configuración:** `.claude/agents-config.json`
- 📝 **Instrucciones Claude Code:** `CLAUDE.md`

---

## Licencia

MIT License

---

**Última actualización:** Noviembre 30, 2025
**Versión:** 3.0 - Sistema de Agentes Completo
