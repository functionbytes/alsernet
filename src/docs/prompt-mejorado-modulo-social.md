# Prompt Mejorado: Módulo de Gestión Social para Helpdesk

## Contexto del Sistema Actual

El proyecto es una plataforma Laravel 12 modular (nwidart/laravel-modules) con los siguientes módulos de atención al cliente:

- **Helpdesk** (Core): Conversaciones multicanal, clientes, canales (FB/IG/WA/Email/Web/API), webhooks, automatizaciones, SLA
- **HelpdeskAgents** (extensión IA): Requiere Helpdesk. Agentes AI con flujos conversacionales, sesiones, knowledge base, herramientas
- **HelpdeskTickets** (extensión): Requiere Helpdesk. Tickets tradicionales con SLA, categorías, estados, macros
- **HelpdeskCampaigns** (extensión): Requiere Helpdesk. Campañas proactivas
- **Attention** (independiente): Sistema PQRSF con sedes, departamentos, reglas de enrutamiento

**Base de datos**: Helpdesk usa conexión dedicada `helpdesk`. Los modelos clave son `Conversation`, `ConversationItem`, `Customer`, `Inbox`, `Channel`.

---

## Objetivo

Diseñar e implementar un módulo Laravel `HelpdeskSocial` que gestione **comentarios en publicaciones** y mensajes directos de redes sociales (Meta principalmente), integrándose nativamente con el módulo `Helpdesk` existente.

---

## Requisitos Funcionales

### 1. Canales Soportados
- **Fase 1**: Facebook (comentarios en posts + Messenger) + Instagram (comentarios en media + DM)
- **Fase 2**: WhatsApp Business API
- **Futuro**: TikTok, X/Twitter, LinkedIn (arquitectura extensible)

### 2. Recepción de Mensajes
- Webhooks unificados Meta (`/webhooks/meta`) para FB + IG
- Verificación HMAC-SHA256 de firmas
- Parser de payloads que distinga: comentarios, menciones, DMs, reacciones
- Procesamiento async vía colas (Jobs)
- Deduplicación por `external_comment_id`

### 3. Clasificación Inteligente de Intenciones
Para cada comentario/mensaje recibido, identificar:
- **Intención**: `consulta`, `queja`, `interes_compra`, `spam`, `positivo`, `neutral`
- **Urgencia**: `baja`, `media`, `alta`, `critica`
- **Palabras clave** detectadas
- **Sentimiento** general

**Implementación**: Motor híbrido
- Reglas predefinidas por keywords (rápido, sin costo)
- OpenAI GPT-4o-mini como fallback para casos ambiguos
- Umbral de confianza configurable

### 4. Automatización de Respuestas
El sistema debe permitir configurar reglas del tipo:
```
SI [intencion = "interes_compra"] Y [plataforma = "instagram"] 
ENTONCES [responder plantilla "precios"] 
Y [detener procesamiento]
```

**Acciones disponibles**:
- Responder con texto personalizado
- Responder con plantilla dinámica (variables: `{{author_name}}`, `{{producto}}`)
- Ocultar comentario
- Marcar como spam
- Escalar a agente humano
- Etiquetar/Taggear

**Modos de respuesta**:
- `automatica`: Responde sin intervención humana
- `sugerida`: Sugiere respuesta al agente pero no envía
- `escalar`: Solo crea conversación y notifica

### 5. Bandeja de Entrada Unificada
- Listado de comentarios/mensajes con filtros (plataforma, estado, intención, urgencia, fecha)
- Vista tipo chat para responder manualmente
- Indicadores visuales de intención y urgencia
- Acciones rápidas: responder, spam, escalar, ver conversación

### 6. Panel Administrativo
- **Cuentas sociales**: Conectar/desconectar páginas de FB/IG, ver estado de tokens, activar/desactivar canales
- **Reglas**: CRUD de reglas con condiciones y acciones, simulador de reglas, orden por prioridad
- **Plantillas**: CRUD de plantillas de respuesta con variables dinámicas
- **Analítica**: Métricas de respuesta, tasa de automatización, distribución de intenciones, rendimiento por agente

### 7. Integración con Helpdesk Core
- Los comentarios deben crear automáticamente:
  - `Customer` en `helpdesk_customers` (vinculado por `facebook_psid` o `instagram_id`)
  - `Conversation` en `helpdesk_conversations` (canal = `facebook`/`instagram`)
  - `ConversationItem` con el contenido del comentario
- Los DMs/Messenger deben reutilizar el flujo existente de `ProcessSocialWebhookJob` de Helpdesk
- Debe emitir los eventos existentes: `ConversationCreated`, `ConversationMessageCreated`
- Debe integrarse con `HelpdeskAgents` para que los agentes AI puedan responder si la regla lo indica

---

## Requisitos Técnicos

### Arquitectura
- Módulo Laravel estándar en `modules/HelpdeskSocial/`
- Depende de `Helpdesk` (declarar en `module.json`)
- Usar conexión de base de datos principal (no necesita conexión propia)
- Tablas con prefijo `helpdesk_social_`

### Seguridad
- Tokens OAuth encriptados con `Crypt::encryptString()`
- Verificación de firmas de webhooks
- Rate limiting en endpoints de webhook
- Control de acceso por permisos Spatie

### Escalabilidad
- Jobs con reintentos y backoff exponencial
- Cache para clasificación de intenciones (evitar llamadas repetidas a OpenAI)
- Procesamiento async de comentarios masivos
- Índices apropiados en tablas (`platform` + `external_id`, `status`, `intent`)

### APIs Oficiales Únicamente
- Facebook Graph API v25.0+
- Instagram Graph API (vía Facebook)
- WhatsApp Business API (Cloud API)
- **NO scraping bajo ninguna circunstancia**

---

## Entregables Esperados

1. **Migraciones**: Tablas `social_accounts`, `social_comments`, `social_rules`, `social_templates`, `social_intents`, `social_metrics`
2. **Modelos Eloquent**: Con relaciones, casts, scopes, accessors
3. **Servicios**:
   - `MetaGraphApiService`: Cliente HTTP para Graph API (comentarios, respuestas, perfiles)
   - `MetaWebhookParser`: Parser de payloads de Meta
   - `IntentClassifier`: Motor de clasificación (reglas + OpenAI)
   - `AutoResponder`: Motor de evaluación y ejecución de reglas
4. **Jobs**: `ProcessSocialCommentJob`, `ClassifyIntentJob`, `SyncSocialCommentsJob`, `CalculateSocialMetricsJob`
5. **Controladores**: Webhook handler, API REST completa, Managers para vistas
6. **Vistas Blade**: Panel admin con Bootstrap 5.3 + jQuery
7. **Rutas**: Webhooks públicos, API autenticada, rutas web del panel
8. **Tests**: PHPUnit para clasificador, API endpoints, reglas
9. **Seeders**: Permisos Spatie
10. **Configuración**: Variables de entorno para Meta, OpenAI, colas

---

## Formato de Respuesta

Por favor genera:
1. **Diagrama de flujo de datos** (en texto o Mermaid) mostrando: webhook → parser → job → clasificación → reglas → respuesta/conversación
2. **Esquema de base de datos** en formato detallado (tablas, columnas, índices, FKs)
3. **Ejemplo de implementación** de:
   - Webhook handler completo
   - Clasificador de intenciones
   - Motor de reglas con 3 ejemplos prácticos
4. **Código completo del módulo** siguiendo la estructura estándar de Laravel Modules

---

## Restricciones
- No modificar código existente de Helpdesk/HelpdeskAgents/HelpdeskTickets
- No crear duplicación de funcionalidad ya existente (reutilizar Conversation, Customer, etc.)
- No usar Livewire ni Inertia (usar Blade + jQuery/AJAX según estándar del proyecto)
- Iconos: Font Awesome 6 exclusivamente
- Idioma: Español para UI, inglés para código
