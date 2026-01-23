# Guía de Usuario para Managers - Módulo Warehouse

## Introducción

Esta guía está diseñada para **managers y administradores** que necesitan configurar y gestionar almacenes completos. Si eres un trabajador de almacén que solo necesita realizar operaciones diarias, consulta la [Guía de Usuario para Workers](warehouse-worker-guide.md).

---

## Índice

1. [Acceso al Sistema](#acceso-al-sistema)
2. [Configuración Inicial](#configuración-inicial)
3. [Gestión de Almacenes](#gestión-de-almacenes)
4. [Gestión de Pisos](#gestión-de-pisos)
5. [Gestión de Ubicaciones](#gestión-de-ubicaciones)
6. [Gestión de Secciones](#gestión-de-secciones)
7. [Gestión de Inventario](#gestión-de-inventario)
8. [Mapa Visual del Almacén](#mapa-visual-del-almacén)
9. [Reportes y Analíticas](#reportes-y-analíticas)
10. [Gestión de Usuarios y Permisos](#gestión-de-usuarios-y-permisos)
11. [Solución de Problemas](#solución-de-problemas)

---

## Acceso al Sistema

### 1. Login

1. Accede a la URL de tu instalación: `https://tusitio.com`
2. Inicia sesión con tus credenciales
3. Asegúrate de tener el rol **manager** o **super-admin**
4. Verifica que tengas el permiso **warehouse.manage**

### 2. Navegación al Módulo

Desde el menú principal:
- **Opción 1**: Click en el ícono de almacén en la barra superior
- **Opción 2**: Menú lateral → "Configuración" → "Almacenes"
- **URL Directa**: `/settings/warehouse`

---

## Configuración Inicial

### Flujo Completo de Configuración

```
1. Crear Almacén
   ↓
2. Definir Pisos/Niveles
   ↓
3. Crear Estilos de Ubicación (opcional)
   ↓
4. Crear Ubicaciones/Estanterías
   ↓
5. Definir Secciones
   ↓
6. Generar Slots de Inventario (automático)
   ↓
7. Asignar Usuarios
   ↓
8. Vincular Tiendas
```

---

## Gestión de Almacenes

### Crear Nuevo Almacén

#### Paso 1: Acceder al Formulario

1. Navega a `/settings/warehouse`
2. Click en botón **"Crear Almacén"** (esquina superior derecha)

#### Paso 2: Completar Información

**Campos Obligatorios:**
- **Código**: Identificador único (ej: `ALM-COR-01`)
- **Nombre**: Nombre descriptivo (ej: "Almacén Central Coruña")

**Campos Opcionales:**
- **Descripción**: Descripción detallada del almacén
- **Disponible**: Marcar si el almacén está activo (checked por defecto)

**Ejemplo de Datos:**
```
Código: ALM-MAD-01
Nombre: Almacén Madrid Norte
Descripción: Almacén principal para zona norte de Madrid
Disponible: ✓
```

#### Paso 3: Guardar

1. Click en botón **"Guardar"**
2. Serás redirigido a la vista de detalles del almacén

---

### Editar Almacén Existente

1. En la lista de almacenes, localiza el almacén a editar
2. Click en el botón **"Editar"** (ícono de lápiz)
3. Modifica los campos necesarios
4. Click en **"Actualizar"**

**Nota**: Solo super-admin puede cambiar el código del almacén.

---

### Ver Detalles del Almacén

1. Click en el nombre del almacén o botón **"Ver"**
2. Verás un dashboard con:
   - **Información General**: Código, nombre, descripción
   - **Estadísticas**:
     - Total de pisos
     - Total de ubicaciones
     - Total de slots
     - Slots ocupados
     - Porcentaje de ocupación
   - **Pisos**: Lista de pisos con estadísticas
   - **Acciones Rápidas**: Editar, ver mapa, generar reportes

---

### Eliminar Almacén

⚠️ **Solo super-admin puede eliminar almacenes**

1. Accede a la vista de detalles del almacén
2. Click en botón **"Eliminar"** (rojo)
3. Confirma la eliminación

**Importante**:
- La eliminación es "soft delete" (se marca como eliminado pero no se borra de la BD)
- Todos los datos relacionados (pisos, ubicaciones, slots) quedan inaccesibles
- Los movimientos de inventario se conservan para auditoría

---

## Gestión de Pisos

### Crear Piso

#### Paso 1: Acceder

1. Ve a detalles del almacén
2. Tab **"Pisos"**
3. Click en **"Crear Piso"**

#### Paso 2: Completar Datos

**Campos Obligatorios:**
- **Código**: Identificador del piso (ej: `P0`, `P1`, `P2`)
- **Nombre**: Nombre descriptivo (ej: "Planta Baja", "Primer Piso")
- **Nivel**: Número de nivel (0 = planta baja, 1 = primer piso, etc.)

**Ejemplo:**
```
Código: P0
Nombre: Planta Baja
Nivel: 0
Disponible: ✓
```

#### Paso 3: Guardar

Click en **"Guardar Piso"**

---

### Ver Estadísticas de Piso

Cada piso muestra:
- **Total de ubicaciones**
- **Total de slots**
- **Slots ocupados**
- **Porcentaje de ocupación**
- **Productos almacenados**

---

### Editar o Eliminar Piso

- **Editar**: Click en ícono de lápiz
- **Eliminar**: Click en ícono de papelera (confirmación requerida)

**Advertencia**: Eliminar un piso eliminará todas sus ubicaciones y slots.

---

## Gestión de Ubicaciones

Las ubicaciones representan las estanterías, estands, o zonas físicas donde se almacenan productos.

### Crear Ubicación

#### Paso 1: Acceder

1. Detalles del almacén → Tab **"Pisos"**
2. Selecciona el piso deseado
3. Tab **"Ubicaciones"**
4. Click en **"Crear Ubicación"**

#### Paso 2: Información Básica

**Campos Obligatorios:**
- **Código**: Identificador único (ej: `A-01`, `B-12`)
- **Piso**: Seleccionar piso del listado

**Campos Opcionales:**
- **Estilo**: Seleccionar estilo visual (define color, tamaño, ícono)
- **Código de Barras**: Generar automáticamente o ingresar manualmente
- **Total de Niveles**: Número de niveles/alturas (default: 1)

**Ejemplo:**
```
Código: A-01
Piso: Planta Baja (P0)
Estilo: Estantería Grande
Código de Barras: LOC-A-01 (auto-generado)
Total de Niveles: 5
```

#### Paso 3: Posicionamiento Visual (Opcional)

Si usas el mapa visual:
- **Posición X**: Coordenada horizontal (píxeles)
- **Posición Y**: Coordenada vertical (píxeles)
- **Ancho**: Ancho personalizado (sobreescribe estilo)
- **Alto**: Alto personalizado (sobreescribe estilo)

**Recomendación**: Usa el [Mapa Visual](#mapa-visual-del-almacén) para posicionar ubicaciones con arrastrar y soltar.

#### Paso 4: Guardar

Click en **"Crear Ubicación"**

---

### Editar Ubicación

1. Localiza la ubicación en la lista
2. Click en **"Editar"**
3. Modifica campos necesarios
4. Click en **"Actualizar"**

---

### Transferir Inventario de Ubicación

Si necesitas mover todo el inventario de una ubicación a otra:

1. Accede a la ubicación origen
2. Click en **"Transferir Inventario"**
3. Selecciona ubicación destino
4. Ingresa razón de transferencia
5. Confirma la transferencia

**Nota**: Esta operación mueve todos los productos de la ubicación origen.

---

### Imprimir Códigos de Barras

Para imprimir etiquetas con códigos de barras:

1. En la lista de ubicaciones, marca las casillas de las ubicaciones deseadas
2. Click en botón **"Imprimir Códigos de Barras"**
3. Selecciona formato:
   - **PDF**: Para impresión en impresora normal
   - **PNG**: Para guardar imágenes individuales
4. Descarga el archivo generado

**Uso**: Imprime y pega las etiquetas en las estanterías físicas para facilitar el escaneo por parte de los workers.

---

## Gestión de Secciones

Las secciones representan las caras o subdivisiones de una ubicación (ej: frente, trasera, niveles).

### Crear Sección

#### Paso 1: Acceder

1. Detalles del almacén → Pisos → Ubicaciones
2. Selecciona una ubicación
3. Tab **"Secciones"**
4. Click en **"Crear Sección"**

#### Paso 2: Completar Información

**Campos Obligatorios:**
- **Código**: Identificador (ej: `A-01-F-L1` = Ubicación A-01, Frente, Nivel 1)
- **Nivel**: Altura/nivel de la sección (1, 2, 3, ...)
- **Cara**: Seleccionar:
  - `front` - Frente
  - `back` - Trasera
  - `left` - Izquierda
  - `right` - Derecha

**Campos Opcionales:**
- **Código de Barras**: Generar automáticamente
- **Peso Máximo (kg)**: Capacidad de peso
- **Cantidad Máxima**: Número máximo de productos

**Ejemplo:**
```
Código: A-01-F-L1
Nivel: 1
Cara: front (Frente)
Código de Barras: SEC-A-01-F-L1
Peso Máximo: 100.00 kg
Cantidad Máxima: 50 unidades
```

#### Paso 3: Guardar

Click en **"Crear Sección"**

---

### Generación Automática de Secciones

Para crear múltiples secciones a la vez:

1. Accede a una ubicación
2. Click en **"Generar Secciones Automáticamente"**
3. Configura:
   - **Número de niveles**: Cuántos niveles/alturas (ej: 5)
   - **Caras**: Selecciona qué caras crear (frente, trasera, etc.)
   - **Peso máximo por sección**: Aplicar a todas
   - **Cantidad máxima por sección**: Aplicar a todas
4. Click en **"Generar"**

**Resultado**: Se crearán todas las combinaciones de nivel x cara automáticamente.

**Ejemplo**: Si configuras 5 niveles y 2 caras (frente, trasera), se crearán 10 secciones:
- A-01-F-L1, A-01-F-L2, ..., A-01-F-L5
- A-01-B-L1, A-01-B-L2, ..., A-01-B-L5

---

## Gestión de Inventario

### Ver Inventario de un Almacén

1. Accede a detalles del almacén
2. Tab **"Inventario"**
3. Verás listado de todos los slots ocupados con:
   - Producto
   - Ubicación completa
   - Cantidad actual
   - Último movimiento

---

### Agregar Inventario

#### Opción 1: Desde Slot Específico

1. Navega a: Almacén → Piso → Ubicación → Sección → Slot
2. Click en **"Agregar Cantidad"**
3. Completa el formulario:
   - **Cantidad**: Número de unidades a agregar
   - **Razón**: Motivo (ej: "Recepción orden de compra PO-1234")
   - **Referencia**: Código externo (opcional, ej: "PO-1234")
   - **Notas**: Información adicional (opcional)
4. Click en **"Agregar"**

**Registro Automático**: El sistema registra automáticamente:
- Usuario que realizó la operación
- Fecha y hora exacta
- Cantidad antes y después
- Razón proporcionada

---

#### Opción 2: Desde Producto

1. Busca el producto en el sistema
2. Click en **"Gestionar Inventario"**
3. Selecciona almacén, ubicación y sección
4. Ingresa cantidad y razón
5. Confirma

---

### Restar Inventario

Proceso similar a agregar:

1. Accede al slot
2. Click en **"Restar Cantidad"**
3. Ingresa:
   - Cantidad a restar
   - Razón (ej: "Picking orden de venta SO-5678")
   - Referencia (opcional)
4. Click en **"Restar"**

**Validación**: El sistema no permite restar más cantidad de la disponible.

---

### Mover Inventario entre Secciones

1. Accede al slot origen
2. Click en **"Mover a Otra Sección"**
3. Selecciona:
   - **Sección destino**: Escanea código de barras o selecciona del listado
   - **Cantidad**: Cantidad a mover (puede ser parcial)
   - **Razón**: Motivo de la transferencia
4. Click en **"Mover"**

**Resultado**:
- Si mueves toda la cantidad, el slot origen queda vacío
- Si mueves parcial, se mantienen dos slots (origen y destino)
- Se registra el movimiento con tipo `move`

---

### Limpiar Slot (Vaciar Completamente)

Para eliminar todo el inventario de un slot:

1. Accede al slot
2. Click en **"Limpiar Slot"**
3. Ingresa razón (ej: "Producto dañado - retiro completo")
4. Confirma la acción

**Advertencia**: Esta acción elimina todo el inventario. Úsala solo para casos especiales (productos dañados, vencidos, etc.).

---

### Ver Historial de Movimientos

#### Ver Historial de un Slot Específico

1. Accede al slot
2. Tab **"Historial"**
3. Verás todos los movimientos con:
   - Fecha y hora
   - Tipo de movimiento (add, subtract, move, clear, count)
   - Cantidad antes/después
   - Usuario que realizó la acción
   - Razón

---

#### Ver Historial General del Almacén

1. Detalles del almacén
2. Tab **"Historial de Movimientos"**
3. Usa los filtros:
   - **Rango de fechas**: Desde - Hasta
   - **Tipo de movimiento**: add, subtract, move, clear, count
   - **Usuario**: Seleccionar usuario específico
   - **Producto**: Buscar por producto
4. Click en **"Filtrar"**

**Exportar Historial**:
- Click en botón **"Exportar a Excel"**
- El historial filtrado se descargará en formato Excel

---

## Mapa Visual del Almacén

El mapa visual permite diseñar el layout físico del almacén usando una interfaz interactiva.

### Acceder al Mapa

1. Detalles del almacén
2. Tab **"Mapa Visual"**

### Funcionalidades del Mapa

#### 1. Crear Ubicaciones con Arrastrar y Soltar

1. Selecciona un estilo de ubicación del panel derecho
2. Arrastra el estilo al área del mapa
3. Suelta para crear la ubicación
4. Completa el código cuando se solicite

#### 2. Posicionar Ubicaciones

1. Click y arrastra una ubicación existente
2. Suelta en la nueva posición
3. La posición se guarda automáticamente

#### 3. Editar Dimensiones

1. Click en una ubicación
2. Arrastra los bordes para cambiar tamaño
3. Las nuevas dimensiones se guardan

#### 4. Cambiar Estilo

1. Click derecho en una ubicación
2. **"Cambiar Estilo"**
3. Selecciona nuevo estilo

#### 5. Ver Información de Ocupación

- **Verde**: Menos del 50% ocupado
- **Amarillo**: Entre 50% y 80% ocupado
- **Rojo**: Más del 80% ocupado
- **Gris**: Vacío

#### 6. Guardar Layout

1. Realiza todos los cambios necesarios
2. Click en **"Guardar Layout"**
3. Confirma

---

### Importar Layout desde JSON

Para configuraciones complejas:

1. Click en **"Importar Layout"**
2. Selecciona archivo JSON con especificación
3. Click en **"Importar"**
4. El sistema validará y creará todas las ubicaciones

**Formato JSON** (ejemplo):
```json
{
  "floors": [
    {
      "code": "P0",
      "name": "Planta Baja",
      "level": 0,
      "locations": [
        {
          "code": "A-01",
          "style_code": "SHELF-LG",
          "position": {"x": 100, "y": 100},
          "total_levels": 5
        }
      ]
    }
  ]
}
```

---

## Reportes y Analíticas

### Tipos de Reportes Disponibles

1. **Reporte de Inventario**: Estado actual del inventario
2. **Reporte de Movimientos**: Historial de operaciones
3. **Reporte de Ocupación**: Utilización de espacio
4. **Reporte de Capacidad**: Análisis de capacidad disponible

---

### Generar Reporte de Inventario

#### Paso 1: Acceder

1. Detalles del almacén
2. Tab **"Reportes"**
3. Sección **"Reporte de Inventario"**

#### Paso 2: Configurar Opciones

**Filtros:**
- **Incluir slots vacíos**: Marcar para incluir posiciones vacías
- **Pisos**: Seleccionar pisos específicos o "Todos"
- **Productos**: Filtrar por productos específicos
- **Formato**: PDF o Excel

**Agrupación:**
- Por piso
- Por ubicación
- Por producto

#### Paso 3: Generar

1. Click en **"Generar Reporte"**
2. Espera procesamiento (puede tardar si es almacén grande)
3. Descarga el archivo generado

**Contenido del Reporte**:
- Listado completo de inventario
- Cantidades por producto y ubicación
- Totales y subtotales
- Gráficos de ocupación (si PDF)

---

### Generar Reporte de Movimientos

#### Configuración

**Filtros:**
- **Rango de fechas**: Obligatorio (máximo 3 meses)
- **Tipos de movimiento**: add, subtract, move, clear, count
- **Usuarios**: Filtrar por usuario específico
- **Productos**: Filtrar por producto
- **Formato**: PDF o Excel

**Agrupación:**
- Por día
- Por semana
- Por mes
- Por usuario
- Por tipo de movimiento

#### Resultado

El reporte incluye:
- Listado de todos los movimientos filtrados
- Resumen de cantidades agregadas/restadas
- Gráfico de tendencias (si PDF)
- Estadísticas por usuario

---

### Reporte de Ocupación

Muestra la utilización del espacio en el almacén:

**Contenido**:
- Porcentaje de ocupación total
- Ocupación por piso
- Ocupación por ubicación
- Identificación de áreas con baja utilización
- Gráficos de distribución

**Uso**: Identificar áreas subutilizadas para optimizar el espacio.

---

### Reporte de Capacidad

Análisis de capacidad disponible:

**Contenido**:
- Slots totales vs. ocupados
- Capacidad disponible por piso
- Proyecciones de llenado
- Recomendaciones de expansión

---

## Gestión de Usuarios y Permisos

### Asignar Usuario a Almacén

#### Paso 1: Acceder

1. Detalles del almacén
2. Tab **"Usuarios Asignados"**
3. Click en **"Asignar Usuario"**

#### Paso 2: Seleccionar Usuario

1. Busca el usuario por nombre o email
2. Selecciona del listado

#### Paso 3: Configurar Capacidades

**Opciones**:
- ☑️ **Almacén predeterminado**: Marcar si es el almacén principal del usuario
- ☑️ **Puede transferir**: Permitir transferencias de inventario
- ☑️ **Puede inventariar**: Permitir agregar/restar inventario

**Ejemplo de Configuración**:

**Worker de Recepción**:
```
Almacén predeterminado: ✓
Puede transferir: ✗
Puede inventariar: ✓
```

**Worker de Picking**:
```
Almacén predeterminado: ✓
Puede transferir: ✓
Puede inventariar: ✓
```

**Manager de Almacén**:
```
Almacén predeterminado: ✓
Puede transferir: ✓
Puede inventariar: ✓
+ Permiso: warehouse.manage
```

#### Paso 4: Guardar

Click en **"Asignar Usuario"**

---

### Modificar Capacidades de Usuario

1. Tab **"Usuarios Asignados"**
2. Localiza el usuario
3. Click en **"Editar Capacidades"**
4. Modifica las opciones
5. Click en **"Actualizar"**

---

### Remover Usuario de Almacén

1. Tab **"Usuarios Asignados"**
2. Localiza el usuario
3. Click en **"Remover"**
4. Confirma la acción

**Nota**: El usuario perderá acceso al almacén inmediatamente.

---

## Gestión de Estilos de Ubicación

Los estilos definen la apariencia visual de las ubicaciones en el mapa.

### Crear Estilo

1. Navega a `/settings/warehouse/styles`
2. Click en **"Crear Estilo"**
3. Completa:
   - **Nombre**: Nombre descriptivo (ej: "Estantería Grande")
   - **Código**: Código único (ej: "SHELF-LG")
   - **Color**: Color hexadecimal (ej: #3498db)
   - **Ícono**: Ícono Font Awesome (ej: fa-warehouse)
   - **Ancho**: Ancho en píxeles (ej: 120)
   - **Alto**: Alto en píxeles (ej: 100)
   - **Descripción**: Descripción del estilo
4. Click en **"Crear Estilo"**

### Estilos Predefinidos

El sistema incluye estilos básicos:
- **Estantería Grande**: 120x100px, azul
- **Estantería Mediana**: 100x80px, verde
- **Estantería Pequeña**: 80x60px, púrpura
- **Pallet**: 80x80px, rojo
- **Caja Grande**: 60x60px, naranja
- **Zona Fría**: 100x80px, cian

---

## Solución de Problemas

### Problema: No Puedo Ver un Almacén

**Causa**: No estás asignado al almacén.

**Solución**:
1. Contacta a super-admin
2. Solicita asignación al almacén
3. Verifica tener permiso `warehouse.manage`

---

### Problema: No Puedo Crear Almacenes

**Causa**: Solo super-admin puede crear almacenes.

**Solución**:
- Si eres manager, contacta a super-admin
- Si eres super-admin, verifica tu sesión

---

### Problema: El Mapa Visual No Carga

**Soluciones**:
1. Refresca la página (F5)
2. Limpia caché del navegador
3. Verifica que el almacén tenga pisos creados
4. Contacta soporte técnico si persiste

---

### Problema: Los Reportes No Se Generan

**Causas Posibles**:
- Rango de fechas muy amplio (más de 3 meses)
- Demasiados registros (más de 100,000)

**Soluciones**:
1. Reduce el rango de fechas
2. Aplica más filtros (piso específico, producto específico)
3. Usa formato Excel en lugar de PDF (más rápido)

---

### Problema: No Puedo Eliminar una Ubicación

**Causa**: La ubicación tiene inventario.

**Solución**:
1. Primero mueve o elimina todo el inventario de la ubicación
2. Luego podrás eliminar la ubicación
3. O marca la ubicación como "No disponible" sin eliminarla

---

## Mejores Prácticas

### 1. Nomenclatura Consistente

Usa un esquema consistente para códigos:
- **Almacenes**: `ALM-[CIUDAD]-[NUMERO]` (ej: ALM-COR-01)
- **Pisos**: `P[NUMERO]` (ej: P0, P1, P2)
- **Ubicaciones**: `[LETRA]-[NUMERO]` (ej: A-01, B-12)
- **Secciones**: `[UBICACION]-[CARA]-L[NIVEL]` (ej: A-01-F-L1)

### 2. Documentación de Razones

Siempre ingresa razones claras en los movimientos:
- ❌ **Malo**: "ajuste"
- ✅ **Bueno**: "Recepción orden de compra PO-1234"
- ✅ **Bueno**: "Picking orden de venta SO-5678"
- ✅ **Bueno**: "Ajuste inventario físico - conteo mensual"

### 3. Revisiones Periódicas

- **Semanal**: Revisa ocupación de ubicaciones
- **Mensual**: Genera reporte de movimientos
- **Trimestral**: Analiza capacidad y planifica expansión

### 4. Capacitación de Usuarios

- Asigna capacidades según rol real
- Capacita a workers en uso de escáneres
- Documenta procesos internos

### 5. Backup de Layouts

- Exporta el layout del mapa visual mensualmente
- Guarda archivo JSON en lugar seguro
- Facilita recuperación ante errores

---

## Atajos de Teclado

- `Ctrl + F`: Buscar en listados
- `Esc`: Cerrar modales
- `Ctrl + S`: Guardar formularios (donde aplique)

---

## Recursos Adicionales

- [Guía de Worker](warehouse-worker-guide.md)
- [Documentación Técnica](../backend/warehouse-module.md)
- [API y Endpoints](../api/warehouse-endpoints.md)
- [Sistema de Permisos](../backend/warehouse-permissions.md)

---

## Soporte

Para soporte técnico:
- **Email**: soporte@tusitio.com
- **Teléfono**: +34 XXX XXX XXX
- **Documentación**: https://tusitio.com/docs

---

**Última actualización**: Enero 2026
