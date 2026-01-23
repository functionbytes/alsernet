# LiveChat Widget API Documentation

Esta documentación describe los endpoints API para el sistema de LiveChat que permite la comunicación bidireccional entre el widget (clientes) y el panel de gestión (agentes).

## Arquitectura

```
Widget (Cliente) ←→ API Backend ←→ Panel Manager (Agente)
                        ↓
                  Laravel Reverb
                  (Broadcasting)
```

### Flujo de Datos

1. **Cliente envía mensaje** → API crea mensaje → Broadcast a agentes
2. **Agente responde** → API crea mensaje → Broadcast a cliente
3. **Real-time updates** vía Laravel Reverb en canales privados

---

## Endpoints Públicos (Widget - Sin Autenticación)

Estos endpoints son usados por el widget del cliente y NO requieren autenticación.

### 1. Crear Nueva Conversación

Crea una nueva conversación cuando un cliente inicia un chat.

**Endpoint:** `POST /lc/api/conversations`

**Request Body:**
```json
{
  "customer": {
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "phone": "+52 555 1234567" // opcional
  },
  "message": "Hola, necesito ayuda con mi pedido",
  "subject": "Consulta sobre pedido" // opcional
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "conversation": {
      "id": 1,
      "subject": "Consulta sobre pedido",
      "status": "Abierto",
      "created_at": "2025-12-09T15:30:00Z"
    },
    "messages": [
      {
        "id": 1,
        "type": "message",
        "body": "Hola, necesito ayuda con mi pedido",
        "is_from_customer": true,
        "is_from_agent": false,
        "sender_name": "Juan Pérez",
        "sender_avatar": "https://ui-avatars.com/api/?name=Juan+Pérez",
        "created_at": "2025-12-09T15:30:00Z"
      }
    ]
  }
}
```

**Notas:**
- Si no se proporciona `email`, se genera un identificador temporal de invitado
- El cliente es creado automáticamente si no existe
- Se asigna automáticamente el estado "Abierto" por defecto
- Broadcasting: Emite evento `conversation.created` a canal `helpdesk.conversations`

---

### 2. Obtener Conversación

Obtiene los detalles completos de una conversación y sus mensajes.

**Endpoint:** `GET /lc/api/conversations/{id}`

**Query Parameters:**
- `customer_email` (opcional): Email del cliente para verificación de permisos

**Response (200):**
```json
{
  "success": true,
  "data": {
    "conversation": {
      "id": 1,
      "subject": "Consulta sobre pedido",
      "status": {
        "id": 1,
        "name": "Abierto",
        "is_open": true
      },
      "assignee": {
        "id": 5,
        "name": "María López"
      },
      "created_at": "2025-12-09T15:30:00Z"
    },
    "messages": [
      {
        "id": 1,
        "type": "message",
        "body": "Hola, necesito ayuda con mi pedido",
        "html_body": null,
        "is_from_customer": true,
        "is_from_agent": false,
        "is_internal": false,
        "sender_name": "Juan Pérez",
        "sender_avatar": "https://ui-avatars.com/api/?name=Juan+Pérez",
        "created_at": "2025-12-09T15:30:00Z"
      }
    ]
  }
}
```

**Notas:**
- Los mensajes internos (`is_internal: true`) NO son visibles para clientes
- Solo muestra mensajes donde `type = 'message'` y `is_internal = false`

---

### 3. Enviar Mensaje (Cliente → Agente)

Envía un nuevo mensaje desde el cliente en una conversación existente.

**Endpoint:** `POST /lc/api/conversations/{id}/messages`

**Request Body:**
```json
{
  "customer_email": "juan@example.com",
  "message": "Mi número de pedido es #12345"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "message": {
      "id": 2,
      "type": "message",
      "body": "Mi número de pedido es #12345",
      "is_from_customer": true,
      "is_from_agent": false,
      "sender_name": "Juan Pérez",
      "sender_avatar": "https://ui-avatars.com/api/?name=Juan+Pérez",
      "created_at": "2025-12-09T15:31:00Z"
    }
  }
}
```

**Notas:**
- Verifica que el `customer_email` coincida con el dueño de la conversación
- Si la conversación está cerrada, la reabre automáticamente
- Actualiza `last_message_at` y `last_seen_at` del cliente
- Broadcasting: Emite evento `message.received` a canales:
  - `conversation.{id}` - Para esta conversación específica
  - `helpdesk.conversations` - Para todos los agentes

---

### 4. Obtener Mensajes

Obtiene solo los mensajes de una conversación (sin datos de la conversación).

**Endpoint:** `GET /lc/api/conversations/{id}/messages`

**Query Parameters:**
- `customer_email` (opcional): Email del cliente para verificación

**Response (200):**
```json
{
  "success": true,
  "data": {
    "messages": [
      {
        "id": 1,
        "type": "message",
        "body": "Hola, necesito ayuda con mi pedido",
        "html_body": null,
        "is_from_customer": true,
        "is_from_agent": false,
        "sender_name": "Juan Pérez",
        "sender_avatar": "https://ui-avatars.com/api/?name=Juan+Pérez",
        "created_at": "2025-12-09T15:30:00Z"
      },
      {
        "id": 2,
        "type": "message",
        "body": "Hola Juan, ¿en qué puedo ayudarte?",
        "html_body": null,
        "is_from_customer": false,
        "is_from_agent": true,
        "sender_name": "María López",
        "sender_avatar": "https://example.com/avatar/maria.jpg",
        "created_at": "2025-12-09T15:30:30Z"
      }
    ]
  }
}
```

---

## Endpoints Protegidos (Panel Manager - Con Autenticación)

Estos endpoints requieren autenticación vía `auth:sanctum` y permisos de `manager.helpdesk.conversations.index`.

### 5. Responder como Agente

Permite a un agente autenticado responder en una conversación.

**Endpoint:** `POST /api/helpdesk/conversations/{id}/reply`

**Headers:**
```
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "message": "Hola Juan, déjame revisar tu pedido #12345",
  "is_internal": false  // opcional, default: false
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "message": {
      "id": 3,
      "type": "message",
      "body": "Hola Juan, déjame revisar tu pedido #12345",
      "is_from_customer": false,
      "is_from_agent": true,
      "is_internal": false,
      "sender_name": "María López",
      "sender_avatar": "https://example.com/avatar/maria.jpg",
      "created_at": "2025-12-09T15:32:00Z"
    }
  }
}
```

**Notas:**
- Si la conversación no tiene asignado un agente, se asigna automáticamente al que responde
- Si es la primera respuesta de un agente, se registra `first_response_at`
- `is_internal: true` crea una nota interna NO visible para el cliente
- Broadcasting: Emite evento `message.received` a canales privados
- **Permisos requeridos:** `manager.helpdesk.conversations.index`

**Errores:**
```json
// 401 - No autenticado
{
  "success": false,
  "message": "Autenticación requerida."
}

// 403 - Sin permisos
{
  "success": false,
  "message": "No tienes permisos para responder conversaciones."
}
```

---

## Broadcasting (Tiempo Real)

### Canales Privados

#### 1. `helpdesk.conversations`
- **Suscriptores:** Todos los agentes con permisos de helpdesk
- **Eventos:**
  - `conversation.created` - Nueva conversación creada
  - `message.received` - Nuevo mensaje en cualquier conversación

#### 2. `conversation.{id}`
- **Suscriptores:** Clientes y agentes participantes en la conversación específica
- **Eventos:**
  - `message.received` - Nuevo mensaje en esta conversación

### Estructura de Eventos

#### Evento: `conversation.created`
```json
{
  "conversation": {
    "id": 1,
    "subject": "Consulta sobre pedido",
    "priority": "normal",
    "status": {
      "id": 1,
      "name": "Abierto",
      "is_open": true
    },
    "customer": {
      "id": 10,
      "name": "Juan Pérez",
      "email": "juan@example.com",
      "avatar_url": "https://ui-avatars.com/api/?name=Juan+Pérez"
    },
    "assignee": null,
    "message_count": 1,
    "created_at": "2025-12-09T15:30:00Z",
    "last_message_at": "2025-12-09T15:30:00Z"
  }
}
```

#### Evento: `message.received`
```json
{
  "conversation_id": 1,
  "message": {
    "id": 2,
    "type": "message",
    "body": "Mi número de pedido es #12345",
    "html_body": null,
    "is_from_customer": true,
    "is_from_agent": false,
    "is_internal": false,
    "sender_name": "Juan Pérez",
    "sender_avatar": "https://ui-avatars.com/api/?name=Juan+Pérez",
    "created_at": "2025-12-09T15:31:00Z"
  },
  "conversation": {
    "id": 1,
    "subject": "Consulta sobre pedido",
    "last_message_at": "2025-12-09T15:31:00Z"
  }
}
```

---

## Ejemplos de Uso

### Ejemplo 1: Cliente inicia conversación desde Widget

```javascript
// Widget React/TypeScript
const startConversation = async () => {
  const response = await fetch('/lc/api/conversations', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      customer: {
        name: 'Juan Pérez',
        email: 'juan@example.com'
      },
      message: 'Hola, necesito ayuda',
      subject: 'Consulta general'
    })
  });

  const data = await response.json();

  if (data.success) {
    const conversationId = data.data.conversation.id;
    // Guardar conversationId en localStorage o estado
    subscribeToConversation(conversationId);
  }
};
```

### Ejemplo 2: Cliente envía mensaje

```javascript
const sendMessage = async (conversationId, message, customerEmail) => {
  const response = await fetch(`/lc/api/conversations/${conversationId}/messages`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      customer_email: customerEmail,
      message: message
    })
  });

  return await response.json();
};
```

### Ejemplo 3: Agente responde desde panel

```javascript
// Panel Manager - Con Sanctum Token
const replyToCustomer = async (conversationId, message, token) => {
  const response = await fetch(`/api/helpdesk/conversations/${conversationId}/reply`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      message: message,
      is_internal: false
    })
  });

  return await response.json();
};

// Nota interna (no visible para cliente)
const addInternalNote = async (conversationId, note, token) => {
  const response = await fetch(`/api/helpdesk/conversations/${conversationId}/reply`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      message: note,
      is_internal: true
    })
  });

  return await response.json();
};
```

### Ejemplo 4: Escuchar eventos en tiempo real

```javascript
// Usando Laravel Echo + Reverb
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT,
  forceTLS: false,
  enabledTransports: ['ws', 'wss'],
});

// Widget: Escuchar mensajes en una conversación específica
echo.private(`conversation.${conversationId}`)
  .listen('.message.received', (event) => {
    console.log('Nuevo mensaje:', event.message);
    // Actualizar UI del widget
    addMessageToChat(event.message);
  });

// Panel Manager: Escuchar todas las conversaciones
echo.private('helpdesk.conversations')
  .listen('.conversation.created', (event) => {
    console.log('Nueva conversación:', event.conversation);
    // Mostrar notificación y agregar a lista
    showNotification('Nueva conversación');
    addConversationToList(event.conversation);
  })
  .listen('.message.received', (event) => {
    console.log('Mensaje recibido:', event.message);
    // Actualizar contador de mensajes no leídos
    updateUnreadCount(event.conversation_id);
  });
```

---

## Códigos de Error

### Errores Comunes

| Código | Descripción | Causa |
|--------|-------------|-------|
| 401 | Unauthorized | Token de autenticación inválido o faltante |
| 403 | Forbidden | Usuario no tiene permisos necesarios |
| 404 | Not Found | Conversación no encontrada |
| 422 | Validation Error | Datos de entrada inválidos |
| 500 | Server Error | Error interno del servidor |

### Ejemplo de Error de Validación (422)

```json
{
  "success": false,
  "errors": {
    "customer.email": [
      "El campo email debe ser una dirección de correo válida."
    ],
    "message": [
      "El campo message es obligatorio."
    ]
  }
}
```

---

## Notas de Implementación

### Seguridad

1. **Widget (público):**
   - No requiere autenticación
   - Verifica ownership por email del cliente
   - No expone mensajes internos

2. **Panel Manager (protegido):**
   - Requiere token Sanctum
   - Verifica permisos: `manager.helpdesk.conversations.index`
   - Puede crear notas internas con `is_internal: true`

### Base de Datos

- **Tabla:** `helpdesk_conversations`
  - `customer_id` → Identificador del cliente
  - `status_id` → Estado de la conversación
  - `assignee_id` → Agente asignado (nullable)
  - `first_response_at` → Primera respuesta de agente
  - `last_message_at` → Último mensaje enviado

- **Tabla:** `helpdesk_conversation_items`
  - `conversation_id` → Conversación padre
  - `author_id` → Cliente que envió (si es del cliente)
  - `user_id` → Agente que envió (si es del agente)
  - `type` → Tipo: `message`, `status_change`, etc.
  - `is_internal` → Si es nota interna (no visible para cliente)

### Comportamiento Automático

1. **Auto-asignación:** Primera respuesta de agente asigna la conversación automáticamente
2. **Reapertura:** Cliente que responde en conversación cerrada la reabre
3. **Timestamps:** `last_message_at`, `last_seen_at`, `first_response_at` se actualizan automáticamente
4. **Broadcasting:** Todos los mensajes se transmiten en tiempo real vía Reverb

---

## Testing

### Test Manual con cURL

```bash
# 1. Crear conversación
curl -X POST http://alsernet.test/lc/api/conversations \
  -H "Content-Type: application/json" \
  -d '{
    "customer": {
      "name": "Test User",
      "email": "test@example.com"
    },
    "message": "Hola, esto es una prueba"
  }'

# 2. Enviar mensaje como cliente
curl -X POST http://alsernet.test/lc/api/conversations/1/messages \
  -H "Content-Type: application/json" \
  -d '{
    "customer_email": "test@example.com",
    "message": "Mensaje de prueba 2"
  }'

# 3. Responder como agente (requiere token)
curl -X POST http://alsernet.test/api/helpdesk/conversations/1/reply \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Hola, soy un agente respondiendo"
  }'
```

---

## Próximos Pasos

1. ✅ API Backend implementada
2. ⏳ Integrar React widget con API
3. ⏳ Integrar panel manager con API
4. ⏳ Configurar Laravel Reverb para broadcasting
5. ⏳ Implementar autenticación de canales privados
6. ⏳ Agregar tests automatizados

---

**Documentación generada:** 2025-12-09
**Versión de Laravel:** 12.x
**Broadcasting:** Laravel Reverb
**Autenticación:** Laravel Sanctum
