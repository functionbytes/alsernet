# 📋 Modales de Carrito Abandonado

## 📁 Estructura Organizada

Esta carpeta contiene los modales separados por tipo para una mejor organización y mantenimiento:

### 🎯 **Archivos de Modal Individual**

| Archivo | Tipo de Modal | Propósito | Cuándo se muestra |
|---------|---------------|-----------|-------------------|
| `simple_reminder.tpl` | Recordatorio básico | Notificación amigable | Tiempo de inactividad |
| `discount_offer.tpl` | Oferta con descuento | Incentivo de compra | Intento de salida |
| `urgency_alert.tpl` | Alerta de urgencia | Presión por stock | Stock bajo detectado |
| `related_products.tpl` | Productos relacionados | Upselling/Cross-selling | Carrito con productos específicos |
| `session_recovery.tpl` | Recuperación de sesión | Bienvenida de regreso | Cliente que regresa |

### 🔧 **Archivos de Soporte**

| Archivo | Propósito |
|---------|-----------|
| `index.tpl` | Incluye todos los modales en una sola llamada |
| `common_templates.tpl` | Templates JavaScript reutilizables |
| `README.md` | Documentación de la estructura |

### 🎨 **CSS**

- **Ubicación**: `views/css/front/abandonment/modals.css`
- **Características**: Responsive, animaciones, estados de carga

---

## 🔧 **Uso en el Código**

### **Opción 1: Incluir todos los modales**
```smarty
{include file='module:alsernetshopping/views/templates/front/abandonment/modals/index.tpl'}
```

### **Opción 2: Incluir modales específicos**
```smarty
{* Solo modal de descuento *}
{include file='module:alsernetshopping/views/templates/front/abandonment/modals/discount_offer.tpl'}

{* Solo modal de urgencia *}
{include file='module:alsernetshopping/views/templates/front/abandonment/modals/urgency_alert.tpl'}
```

### **Opción 3: Incluir CSS por separado**
```smarty
<link rel="stylesheet" href="{$module_dir}views/css/front/abandonment/modals.css">
```

---

## 🎯 **JavaScript para Gestión de Modales**

Cada modal tiene atributos data específicos:

```javascript
// Mostrar modal específico
function showAbandonmentModal(type, data) {
    const modal = document.querySelector(`[data-modal-type="${type}"]`);
    if (modal) {
        // Rellenar datos específicos
        populateModalData(modal, data);
        // Mostrar modal
        modal.classList.add('show');
    }
}

// Tipos disponibles
const MODAL_TYPES = {
    SIMPLE_REMINDER: 'simple_reminder',
    DISCOUNT_OFFER: 'discount_offer',
    URGENCY_ALERT: 'urgency_alert',
    RELATED_PRODUCTS: 'related_products',
    SESSION_RECOVERY: 'session_recovery'
};
```

---

## 🎨 **Personalización**

### **Modificar un modal específico**
1. Editar el archivo `.tpl` correspondiente
2. Los cambios se reflejan solo en ese tipo de modal
3. No afecta a otros modales

### **Agregar nuevo tipo de modal**
1. Crear nuevo archivo `.tpl` en esta carpeta
2. Agregar inclusión en `index.tpl`
3. Agregar CSS específico si es necesario
4. Actualizar JavaScript para manejo

### **Modificar estilos**
- **Global**: Editar `modals.css`
- **Específico**: Agregar CSS inline en el archivo `.tpl`

---

## 📊 **Beneficios de esta Estructura**

### ✅ **Mantenibilidad**
- Cada modal es independiente
- Cambios aislados por tipo
- Fácil debugging y testing

### ✅ **Performance**
- Carga condicional de modales
- CSS optimizado y compartido
- Templates JavaScript reutilizables

### ✅ **Escalabilidad**
- Fácil agregar nuevos tipos
- Estructura consistente
- Documentación clara

### ✅ **Desarrollo**
- Separación de responsabilidades
- Código más limpio
- Reutilización de componentes

---

## 🚀 **Migración desde archivo único**

Si vienes del archivo `abandoned_cart_modals.tpl` monolítico:

1. **Incluir index**: Reemplaza la inclusión anterior por `index.tpl`
2. **Mantener JavaScript**: Los selectores y lógica siguen funcionando
3. **CSS migrado**: Todos los estilos están en el archivo CSS separado
4. **Data attributes**: Mantienen la misma estructura

---

*💡 **Consejo**: Esta estructura modular facilita el A/B testing, permite habilitar/deshabilitar modales específicos, y mejora significativamente el mantenimiento del código.*