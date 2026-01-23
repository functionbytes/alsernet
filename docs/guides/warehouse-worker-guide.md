# Guía de Usuario para Workers - Módulo Warehouse

## Introducción

Esta guía está diseñada para **trabajadores de almacén** que realizan operaciones diarias de inventario. Si necesitas configurar almacenes completos o gestionar usuarios, consulta la [Guía de Usuario para Managers](warehouse-manager-guide.md).

---

## Índice

1. [Acceso al Sistema](#acceso-al-sistema)
2. [Dashboard de Worker](#dashboard-de-worker)
3. [Escaneo de Códigos de Barras](#escaneo-de-códigos-de-barras)
4. [Recepción de Mercancía](#recepción-de-mercancía)
5. [Picking (Preparación de Pedidos)](#picking-preparación-de-pedidos)
6. [Transferencias entre Ubicaciones](#transferencias-entre-ubicaciones)
7. [Consulta de Inventario](#consulta-de-inventario)
8. [Ver Historial Propio](#ver-historial-propio)
9. [Preguntas Frecuentes](#preguntas-frecuentes)
10. [Solución de Problemas](#solución-de-problemas)

---

## Acceso al Sistema

### 1. Inicio de Sesión

1. Abre el navegador en tu dispositivo (móvil, tablet o PC)
2. Accede a: `https://tusitio.com`
3. Ingresa tu usuario y contraseña
4. Click en **"Iniciar Sesión"**

### 2. Navegación a Interfaz de Worker

**Opción 1**: Desde el menú principal
- Click en **"Almacén"** en el menú superior

**Opción 2**: URL directa
- Ve a: `/warehouse`

---

## Dashboard de Worker

### Vista Principal

Al acceder verás:

1. **Almacenes Asignados**: Lista de almacenes donde puedes trabajar
2. **Resumen del Día**:
   - Operaciones realizadas hoy
   - Productos recibidos
   - Productos enviados
3. **Accesos Rápidos**:
   - 📦 Recibir Mercancía
   - 📋 Picking / Preparar Pedido
   - 🔄 Transferir Inventario
   - 🔍 Consultar Stock

---

## Escaneo de Códigos de Barras

### Tipos de Códigos de Barras

El sistema usa dos tipos de códigos:

1. **Códigos de Ubicación**: Identifican estanterías y secciones
   - Formato: `LOC-A-01` (ubicación)
   - Formato: `SEC-A-01-F-L1` (sección específica)

2. **Códigos de Producto**: Identifican productos
   - Formato: SKU del producto (ej: `PROD-X-001`)

### Usar el Escáner

#### Con Escáner de Mano (Recomendado)

1. Enciende el escáner
2. Apunta al código de barras
3. Presiona el gatillo
4. Escucharás un "beep" de confirmación
5. El código aparecerá automáticamente en el campo activo

#### Con Cámara del Móvil/Tablet

1. Click en botón **"Escanear con Cámara"**
2. Permite acceso a la cámara
3. Apunta al código de barras
4. Espera reconocimiento automático
5. Confirma el código detectado

---

## Recepción de Mercancía

**Cuándo usar**: Al recibir productos de proveedores o transferencias.

### Flujo Completo de Recepción

#### Paso 1: Acceder a Recepción

1. Desde dashboard, click en **"Recibir Mercancía"**
2. O navega a: `/warehouse/receive`

#### Paso 2: Escanear Ubicación

1. Escanea el código de barras de la **sección** donde guardarás el producto
   - Ejemplo: Escanea `SEC-A-01-F-L1`
2. El sistema validará:
   - ✅ La ubicación existe
   - ✅ La sección está disponible
   - ✅ Hay capacidad disponible
3. Verás información de la sección:
   - Código de ubicación
   - Nivel y cara
   - Capacidad disponible
   - Productos actuales

#### Paso 3: Escanear o Seleccionar Producto

**Opción A: Escanear código del producto**
1. Escanea el código de barras del producto
2. El sistema buscará el producto automáticamente

**Opción B: Buscar manualmente**
1. Click en **"Buscar Producto"**
2. Ingresa nombre o SKU
3. Selecciona del listado

#### Paso 4: Ingresar Cantidad

1. Ingresa la cantidad recibida
   - Ejemplo: `50` unidades
2. Verifica que no exceda la capacidad de la sección

#### Paso 5: Ingresar Razón y Referencia

**Razón** (obligatorio):
- Describe por qué estás agregando inventario
- Ejemplos:
  - "Recepción proveedor - Albarán #12345"
  - "Transferencia de almacén Madrid"
  - "Devolución de cliente"

**Referencia** (opcional):
- Código del documento externo
- Ejemplos:
  - "PO-1234" (orden de compra)
  - "ALB-12345" (albarán)
  - "TRANS-5678" (transferencia)

#### Paso 6: Confirmar

1. Revisa la información:
   - Producto correcto ✓
   - Ubicación correcta ✓
   - Cantidad correcta ✓
2. Click en **"Confirmar Recepción"**
3. Verás mensaje de éxito

#### Paso 7: Continuar o Finalizar

**Para recibir más productos**:
- Click en **"Recibir Otro Producto"**
- Repite desde Paso 2

**Para finalizar**:
- Click en **"Finalizar Recepción"**
- Verás resumen de todos los productos recibidos

---

### Ejemplo Práctico de Recepción

```
Situación: Llegaron 100 unidades del Producto X del proveedor

1. Escaneo ubicación: SEC-A-01-F-L2
   ✓ Validada: Estantería A-01, Frente, Nivel 2

2. Escaneo producto: PROD-X-001
   ✓ Encontrado: Producto X

3. Ingreso cantidad: 100

4. Ingreso razón: "Recepción proveedor - Albarán #12345"
   Ingreso referencia: "ALB-12345"

5. Confirmo
   ✓ Éxito: 100 unidades agregadas a A-01-F-L2
```

---

## Picking (Preparación de Pedidos)

**Cuándo usar**: Al preparar pedidos para envío a clientes o tiendas.

### Flujo de Picking

#### Paso 1: Acceder a Picking

1. Desde dashboard, click en **"Preparar Pedido"**
2. O navega a: `/warehouse/picking`

#### Paso 2: Cargar Lista de Picking

**Opción A: Desde orden de venta**
1. Ingresa número de orden de venta
2. Click en **"Cargar Pedido"**
3. El sistema cargará todos los productos a preparar

**Opción B: Manual**
1. Busca productos uno por uno
2. Agrega a lista de picking

#### Paso 3: Por Cada Producto en la Lista

El sistema te mostrará:
- **Producto**: Nombre y SKU
- **Cantidad requerida**: Cuánto necesitas
- **Ubicación sugerida**: Dónde está el producto (más cercano o con más stock)

**Proceso**:

1. **Ve a la ubicación indicada**
   - Ejemplo: "A-01-F-L2"

2. **Escanea la sección**
   - Valida que estás en la ubicación correcta
   - El sistema te alertará si escaneaste la ubicación incorrecta

3. **Ingresa cantidad a retirar**
   - Puede ser la cantidad total o parcial
   - El sistema valida que haya suficiente stock

4. **Ingresa razón**
   - Ejemplo: "Picking orden de venta SO-5678"

5. **Confirma**
   - La cantidad se resta del inventario
   - Marcas el ítem como "pickeado"

#### Paso 4: Finalizar Picking

1. Una vez completados todos los ítems
2. Click en **"Finalizar Picking"**
3. Imprime etiqueta de envío (si aplica)
4. Marca orden como lista para envío

---

### Ejemplo Práctico de Picking

```
Orden de Venta SO-5678:
- Producto X: 25 unidades
- Producto Y: 10 unidades

PRODUCTO X:
1. Sistema sugiere: A-01-F-L2 (tiene 100 unidades)
2. Voy a ubicación A-01-F-L2
3. Escaneo: SEC-A-01-F-L2 ✓
4. Ingreso cantidad: 25
5. Razón: "Picking SO-5678"
6. Confirmo
   ✓ Quedan 75 unidades en A-01-F-L2

PRODUCTO Y:
1. Sistema sugiere: B-03-F-L1 (tiene 30 unidades)
2. Voy a ubicación B-03-F-L1
3. Escaneo: SEC-B-03-F-L1 ✓
4. Ingreso cantidad: 10
5. Razón: "Picking SO-5678"
6. Confirmo
   ✓ Quedan 20 unidades en B-03-F-L1

Finalizo picking ✓
Orden SO-5678 lista para envío
```

---

## Transferencias entre Ubicaciones

**Cuándo usar**: Para reorganizar el almacén o mover productos a ubicaciones más accesibles.

### Flujo de Transferencia

#### Paso 1: Acceder a Transferencias

1. Desde dashboard, click en **"Transferir Inventario"**
2. O navega a: `/warehouse/transfer`

#### Paso 2: Buscar Producto a Transferir

**Opción A: Escanear producto**
1. Escanea código de barras del producto
2. El sistema mostrará todas las ubicaciones donde hay stock

**Opción B: Buscar manualmente**
1. Ingresa nombre o SKU del producto
2. Selecciona del listado
3. Verás todas las ubicaciones con stock

#### Paso 3: Seleccionar Ubicación Origen

1. De la lista de ubicaciones mostradas, selecciona de dónde quieres mover
   - Ejemplo: "A-01-F-L2" tiene 75 unidades
2. Escanea la sección origen para confirmar
   - Escanea: `SEC-A-01-F-L2`

#### Paso 4: Seleccionar Ubicación Destino

**Opción A: Escanear destino**
1. Escanea la sección donde moverás el producto
   - Ejemplo: `SEC-C-05-F-L1`
2. El sistema validará que la ubicación existe y tiene capacidad

**Opción B: Seleccionar manualmente**
1. Click en **"Seleccionar Ubicación"**
2. Navega por almacén → piso → ubicación → sección
3. Selecciona sección destino

#### Paso 5: Ingresar Cantidad

1. Ingresa cuántas unidades mover
   - Puede ser total o parcial
   - Ejemplo: Mover 30 de 75 unidades
2. Verifica capacidad disponible en destino

#### Paso 6: Ingresar Razón

Ejemplos de razones válidas:
- "Reorganización de almacén"
- "Acercar a zona de picking"
- "Liberar espacio para nueva mercancía"
- "Consolidar stock disperso"

#### Paso 7: Confirmar Transferencia

1. Revisa:
   - Producto: ✓
   - Desde: A-01-F-L2 (quedarán 45)
   - Hacia: C-05-F-L1 (tendrá 30)
   - Cantidad: 30
2. Click en **"Confirmar Transferencia"**
3. El sistema:
   - Resta 30 de A-01-F-L2
   - Agrega 30 a C-05-F-L1
   - Registra movimiento con tipo "move"

---

### Ejemplo Práctico de Transferencia

```
Situación: Producto X está lejos de zona de picking,
           quiero acercarlo

1. Busco producto: "Producto X"
   Sistema muestra:
   - A-01-F-L2: 75 unidades (lejos de picking)
   - D-02-B-L1: 20 unidades (cerca de picking)

2. Selecciono origen: A-01-F-L2
   Escaneo: SEC-A-01-F-L2 ✓

3. Escaneo destino: SEC-D-02-F-L3
   (ubicación vacía cerca de zona de picking)
   ✓ Validada

4. Ingreso cantidad: 50
   (mover 50 de 75)

5. Razón: "Acercar a zona de picking"

6. Confirmo
   ✓ Transferencia exitosa
   - A-01-F-L2: quedan 25 unidades
   - D-02-F-L3: ahora tiene 50 unidades
```

---

## Consulta de Inventario

### Buscar Stock de un Producto

#### Paso 1: Acceder a Consulta

1. Desde dashboard, click en **"Consultar Stock"**
2. O navega a: `/warehouse/inventory/search`

#### Paso 2: Buscar Producto

**Opción A: Escanear**
1. Escanea código de barras del producto

**Opción B: Buscar**
1. Ingresa nombre o SKU
2. Selecciona del listado

#### Paso 3: Ver Información

El sistema mostrará:
- **Nombre del producto**
- **SKU**
- **Stock total en el almacén**
- **Ubicaciones**:
  - Código de ubicación
  - Sección específica
  - Cantidad en esa ubicación
  - Último movimiento

**Ejemplo de Resultado**:
```
Producto: Producto X
SKU: PROD-X-001
Stock Total: 145 unidades

Ubicaciones:
┌─────────────┬──────────┬───────────────────┐
│ Ubicación   │ Cantidad │ Último Movimiento │
├─────────────┼──────────┼───────────────────┤
│ A-01-F-L2   │ 25       │ Hace 2 días       │
│ D-02-F-L3   │ 50       │ Hace 1 hora       │
│ D-02-B-L1   │ 20       │ Hace 1 semana     │
│ B-05-F-L4   │ 50       │ Hace 3 días       │
└─────────────┴──────────┴───────────────────┘
```

---

### Ver Stock de una Ubicación

#### Buscar por Ubicación

1. Accede a consulta de inventario
2. Tab **"Por Ubicación"**
3. **Opción A**: Escanea código de ubicación o sección
4. **Opción B**: Selecciona almacén → piso → ubicación
5. Verás todos los productos en esa ubicación

**Ejemplo de Resultado**:
```
Ubicación: A-01-F-L2
Capacidad: 50 unidades
Ocupación: 25 unidades (50%)

Productos:
┌──────────────┬──────────┬───────────────────┐
│ Producto     │ Cantidad │ Último Movimiento │
├──────────────┼──────────┼───────────────────┤
│ Producto X   │ 25       │ Hace 2 días       │
└──────────────┴──────────┴───────────────────┘
```

---

## Ver Historial Propio

### Ver Tus Operaciones del Día

1. Desde dashboard, sección **"Mis Operaciones Hoy"**
2. Verás listado de todas tus operaciones:
   - Hora
   - Tipo (recepción, picking, transferencia)
   - Producto
   - Cantidad
   - Ubicación

---

### Ver Historial Completo

1. Click en **"Ver Mi Historial Completo"**
2. Filtra por:
   - Rango de fechas
   - Tipo de operación
   - Producto
3. Exporta a Excel si necesitas (botón "Exportar")

---

## Preguntas Frecuentes

### ¿Qué hago si escaneo la ubicación incorrecta?

**Respuesta**: El sistema te alertará automáticamente. Simplemente escanea la ubicación correcta.

---

### ¿Puedo corregir una cantidad mal ingresada?

**Respuesta**:
- Si acabas de confirmar, **contacta a tu supervisor de inmediato**
- El supervisor puede ajustar el inventario con la razón: "Corrección error worker"
- También puedes hacer otra operación para compensar (si agregaste de más, resta la diferencia)

---

### ¿Qué hago si no hay suficiente espacio en una ubicación?

**Respuesta**:
1. **Opción 1**: Usa otra sección de la misma ubicación
2. **Opción 2**: Busca otra ubicación cercana con espacio
3. **Opción 3**: Contacta supervisor para reorganización

---

### ¿Puedo recibir en cualquier ubicación?

**Respuesta**: Depende de la configuración:
- Algunas ubicaciones están reservadas para productos específicos
- Respeta las zonas asignadas (refrigerados, peligrosos, etc.)
- Usa las ubicaciones sugeridas por el sistema cuando sea posible

---

### ¿Qué hago si el producto no aparece en el sistema?

**Respuesta**:
1. Verifica que el código de barras sea correcto
2. Busca manualmente por nombre
3. Si no existe, **contacta al supervisor** para que lo cree

---

### ¿Puedo ver el inventario de otros almacenes?

**Respuesta**: Solo de los almacenes a los que estás asignado. Si necesitas ver otros, contacta al supervisor.

---

## Solución de Problemas

### El escáner no funciona

**Soluciones**:
1. Verifica que esté encendido
2. Revisa la batería
3. Reconecta Bluetooth/USB
4. Prueba con otro escáner
5. Usa entrada manual como alternativa

---

### El código de barras no se reconoce

**Causas y Soluciones**:
- **Etiqueta dañada**: Ingresa código manualmente
- **Código sucio**: Limpia la etiqueta
- **Iluminación**: Mejora iluminación del área
- **Distancia**: Acerca/aleja el escáner

---

### Aparece error "No tienes permiso"

**Causa**: Tu usuario no tiene la capacidad necesaria.

**Solución**:
1. Verifica que estás en el almacén correcto
2. Contacta supervisor para verificar permisos
3. Puede que necesites permiso de "transferencia" o "inventario"

---

### El sistema está lento

**Soluciones**:
1. Refresca la página (F5)
2. Cierra pestañas innecesarias
3. Limpia caché del navegador
4. Verifica conexión a internet
5. Reporta al supervisor si persiste

---

### Hice una operación por error

**Acción Inmediata**:
1. **NO intentes corregirlo tú mismo**
2. **Contacta al supervisor inmediatamente**
3. Proporciona:
   - Qué operación hiciste
   - En qué ubicación
   - Qué producto
   - Qué cantidad
4. El supervisor hará el ajuste correspondiente

---

## Mejores Prácticas

### 1. Siempre Escanea para Validar

❌ **Incorrecto**: Confiar en memoria, ingresar ubicaciones manualmente
✅ **Correcto**: Escanear código de barras para validar ubicación

### 2. Razones Claras y Completas

❌ **Malo**: "recepción", "mover", "ajuste"
✅ **Bueno**:
- "Recepción proveedor - Albarán #12345"
- "Reorganización zona de picking"
- "Corrección por conteo físico - supervisor aprobado"

### 3. Verifica Antes de Confirmar

Siempre revisa:
- ✓ Producto correcto
- ✓ Ubicación correcta
- ✓ Cantidad correcta

### 4. Mantén Orden

- Cierra bien las operaciones (no dejes pantallas a medias)
- Finaliza sesión al terminar tu turno
- Reporta problemas inmediatamente

### 5. Seguridad Primero

- No uses el sistema mientras operas montacargas
- Si usas móvil/tablet, asegúralo bien
- Reporta etiquetas dañadas o ilegibles

---

## Atajos Rápidos

Desde el dashboard:
- **R**: Recibir mercancía
- **P**: Picking
- **T**: Transferir
- **C**: Consultar stock
- **H**: Ver historial

---

## Contacto y Soporte

**Supervisor de Turno**: [Nombre]
**Teléfono Interno**: [Extensión]
**Soporte Técnico**: soporte@tusitio.com

**En caso de emergencia del sistema**:
1. Contacta supervisor inmediatamente
2. Supervisor contactará soporte técnico
3. Mientras tanto, documenta operaciones en papel

---

## Recursos Adicionales

- [Guía de Manager](warehouse-manager-guide.md) - Para supervisores
- [Preguntas Frecuentes Completas](../faq.md)
- [Videos de Capacitación](https://tusitio.com/training)

---

**¡Importante!**: Ante cualquier duda, **pregunta a tu supervisor**. Es mejor preguntar que cometer un error en el inventario.

---

**Última actualización**: Enero 2026
**Versión**: 1.0
