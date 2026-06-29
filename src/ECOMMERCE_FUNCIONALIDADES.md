# Plataforma Ecommerce — Inventario de Funcionalidades

**Documento de referencia para presupuesto — Versión 1.0 — Abril 2026**

---

## 1. Resumen ejecutivo

Se propone el desarrollo de una plataforma de comercio electrónico completa y modular, construida sobre Laravel 12 y arquitectura de módulos independientes. La solución cubre todo el ciclo de vida de una venta en línea: desde la navegación del catálogo por parte del cliente hasta la gestión de pagos, logística, facturación y análisis de resultados por parte del administrador. El sistema incluye tres módulos principales: la tienda y panel de administración central (**Ecommerce**), el procesador de pagos multigateway (**EcommercePayment**), y la gestión geográfica jerárquica (**Locations**). La arquitectura de plugins permite incorporar nuevos métodos de pago sin modificar el núcleo del sistema.

---

## 2. Módulos del sistema

| Módulo | Descripción | Funcionalidades principales |
|---|---|---|
| **Ecommerce** | Tienda pública y panel de administración central | Catálogo, ventas, clientes, logística, facturación, reportes, configuración, API REST |
| **EcommercePayment** | Procesamiento de pagos multigateway | Wompi, COD, transferencia bancaria, reembolsos, logs de intentos |
| **Locations** | Gestión geográfica para envíos y direcciones | Países, departamentos, ciudades, importación masiva, API pública en cascada |

---

## 3. Funcionalidades de la tienda (vista del cliente final)

### Catálogo y navegación

- Catálogo con búsqueda por texto, filtros por categoría, marca y rango de precio
- Página de detalle de producto con galería de imágenes múltiples
- Selección de variaciones de producto (talla, color, etc.) con precio y disponibilidad por variación
- Comparación lado a lado de productos
- Búsqueda y localización de tiendas físicas

### Carrito y lista de deseos

- Carrito dinámico: agregar, actualizar cantidad, eliminar ítems y vaciar carrito
- Lista de deseos (wishlist): guardar productos favoritos y compartir la lista

### Checkout y pago

- Proceso de compra en pasos: dirección de envío, método de envío, aplicación de cupón, cálculo de impuestos y pago
- Selección de método de pago (según gateways habilitados)
- Confirmación de orden con código y token único

### Seguimiento post-compra

- Rastreo del estado de la orden mediante código + token (sin necesidad de cuenta)
- Seguimiento del estado del envío
- Descarga de productos digitales una vez confirmado el pago

### Cuenta de cliente

- Registro de cuenta, inicio de sesión y recuperación de contraseña
- Gestión de múltiples direcciones de entrega
- Historial de órdenes y posibilidad de re-ordenar
- Envío de reseñas con calificación de 1 a 5 estrellas y fotos adjuntas

---

## 4. Funcionalidades del panel de administración

### 4.1 Gestión de catálogo

**Productos**
- Creación, edición y eliminación de productos con imágenes múltiples, slug SEO y metadatos
- Gestión de variaciones (talla, color, etc.) con precio, SKU e imágenes propias por variación
- Precio regular y precio de oferta con fechas de inicio y fin
- Control de inventario por SKU: cantidades, stock mínimo, habilitar/deshabilitar compra sin stock
- Etiquetas visuales (badges): Nuevo, Oferta, Destacado, y personalizadas
- Duplicar producto para agilizar la carga de catálogos similares
- Tablas comparativas de especificaciones técnicas

**Organización del catálogo**
- Árbol jerárquico de categorías con imágenes
- Gestión de marcas con logo
- Colecciones y etiquetas de agrupación
- Conjuntos de atributos y opciones globales reutilizables (por ejemplo, paleta de colores, tabla de tallas)

**Importación y exportación masiva**
- Importación desde CSV/Excel: productos, categorías, inventario y precios
- Exportación a Excel: productos, categorías e inventario

---

### 4.2 Gestión de ventas y órdenes

- Listado de órdenes con búsqueda y filtros avanzados
- Visualización del detalle completo de cada orden
- Edición manual de órdenes: ítems, precios y dirección
- Flujo de estados: `pendiente → en proceso → enviado → completado → cancelado`
- Creación manual de órdenes desde el panel (ventas telefónicas o presenciales)
- Aplicación de cupones de descuento desde el panel
- Notas internas (visibles solo para el equipo) y notas visibles al cliente
- Asignación de fecha y hora estimada de entrega
- Re-ordenamiento basado en órdenes anteriores

**Carritos abandonados**
- Listado de carritos no completados
- Envío automatizado de email de recuperación al cliente

---

### 4.3 Gestión de clientes

- Listado, creación manual, edición y eliminación de clientes
- Gestión de múltiples direcciones por cliente
- Historial completo de órdenes del cliente
- Notas privadas del vendedor asociadas al perfil del cliente

---

### 4.4 Descuentos y promociones

| Tipo | Características |
|---|---|
| Cupones de monto fijo | Descuento en valor absoluto sobre el total |
| Cupones de porcentaje | Descuento proporcional al subtotal |
| Cupones de envío gratis | Exoneración del costo de envío |
| Flash Sales | Ofertas de tiempo limitado con cuenta regresiva |

**Reglas aplicables a cupones:**
- Restricción por productos, colecciones o clientes específicos
- Límite de usos global y límite por cliente
- Fechas de validez (inicio y fin)
- Generación automática de códigos únicos

---

### 4.5 Logística y envíos

**Métodos de envío configurables:**
- Tarifa estándar, tarifa plana, envío gratuito, retiro en tienda

**Reglas de envío por:**
- Costo del pedido, peso del paquete y destino geográfico

**Gestión operativa:**
- Registro y seguimiento de shipments con historial de cambios de estado
- Tokens de entrega para confirmación de recepción
- Generación de etiquetas de envío con plantilla personalizable

**Devoluciones:**
- Solicitud de devolución con razón del cliente
- Flujo de estados: solicitud → revisión → aprobada/rechazada → reembolso
- Historial de devoluciones por orden

---

### 4.6 Facturación e impuestos

- Generación automática de factura al confirmar cada orden
- Plantilla de factura personalizable mediante editor de código HTML
- Descarga de factura en formato PDF
- Tasas de impuesto configurables con reglas por país y estado
- Cálculo automático de impuestos durante el proceso de checkout

---

### 4.7 Reseñas de productos

- Listado de reseñas pendientes de moderación
- Acciones: aprobar, rechazar y eliminar reseñas
- Respuestas del vendedor visibles en la tienda
- Publicar o despublicar reseñas sin eliminarlas

---

### 4.8 Reportes y dashboard

- Resumen de ventas del período (ingresos, órdenes, ticket promedio)
- Listado de órdenes recientes con acceso rápido
- Productos más vendidos
- Reportes de ventas por período
- Análisis de comportamiento de clientes

---

### 4.9 Configuración del sistema

El panel de configuración cubre 14 áreas:

| Área | Descripción |
|---|---|
| General | Nombre, moneda, zona horaria, idioma |
| Monedas | Conversión y símbolo de moneda |
| Productos | Comportamiento de stock, reseñas, comparación |
| Checkout | Pasos, campos requeridos, términos |
| Devoluciones | Plazo, política, estados |
| Facturas | Numeración, plantilla, pie de página |
| Impuestos | Inclusión en precio, pantalla de checkout |
| Clientes | Registro, datos requeridos |
| Envío | Opciones de envío activas |
| Flash Sales | Comportamiento del contador |
| Carrito abandonado | Tiempo de espera, asunto del correo |
| Tracking | URL de rastreo externo |
| Webhooks | URLs y eventos configurables |
| Estándares | Unidades de peso y dimensiones |

---

## 5. Módulo de pagos y gateways

### Gateways incluidos

**Wompi (pasarela completa)**
- Métodos aceptados: tarjeta crédito/débito, PSE, Nequi
- Widget de pago embebido en el checkout
- Webhook asíncrono con validación de firma criptográfica
- Reintento automático de pago fallido
- Comisión configurable: fija o porcentaje
- Modo sandbox para pruebas y modo producción

**Pago contra entrega (COD)**
- Cobro al momento de la entrega física
- Confirmación manual por el administrador
- Recargo configurable: fijo o porcentaje

**Transferencia bancaria**
- Datos bancarios configurables (banco, tipo de cuenta, número, titular, documento)
- Email automático al cliente con instrucciones de pago (URL firmada con expiración)
- Confirmación manual por el administrador

---

### Panel de gestión de pagos

- Listado de transacciones con filtros: estado, canal de pago, rango de fechas, búsqueda por cliente/orden
- Vista de detalle con metadata completa y logs de cada intento (IP, request, response)
- Exportación a Excel
- Confirmación manual de pagos (COD y transferencia)
- Reembolsos parciales o totales desde el panel

**Estados de pago:** `pendiente`, `completado`, `fallido`, `en reembolso`, `reembolsado`

---

### Arquitectura extensible de gateways

- Sistema de registro por módulo declarado en `module.json` con tipo `payment-gateway`
- Nuevos métodos de pago instalables como módulos independientes sin modificar el núcleo
- Validación automática de contrato al momento de la instalación

---

## 6. Gestión geográfica (Locations)

Este módulo provee la base de datos geográfica utilizada en los formularios de dirección del checkout y en los módulos de envío.

- CRUD de países con código ISO, código telefónico, moneda asociada y bandera
- CRUD de departamentos/estados vinculados a país
- CRUD de ciudades vinculadas a departamento y país
- Importación y exportación masiva desde CSV/Excel para los tres niveles
- Selects en cascada en el checkout: País → Departamento → Ciudad
- API pública para consulta desde el frontend

---

## 7. API e integraciones

La plataforma expone una API REST versionada (`/api/v1/ecommerce`) para integraciones con aplicaciones móviles, canales de venta externos o sistemas de terceros.

**Endpoints públicos (sin autenticación)**
- Productos: listado, detalle, búsqueda, filtros
- Categorías y marcas
- Carrito de compras (sesión)
- Cupones: validación
- Impuestos: cálculo
- Comparación de productos
- Países, departamentos y ciudades (Locations)

**Endpoints autenticados (Sanctum Bearer Token)**
- Órdenes: crear, listar, detalle
- Perfil del cliente: datos personales y contraseña
- Direcciones: listar, crear, editar, eliminar
- Wishlist: agregar y remover productos
- Descargas de productos digitales

---

## 8. Seguridad y calidad

- Autenticación de API mediante Laravel Sanctum (tokens Bearer)
- URLs firmadas con tiempo de expiración para instrucciones de pago y descargas digitales
- Validación criptográfica de firma en webhooks de Wompi
- Protección contra abuso en webhooks mediante throttle (rate limiting)
- Logs detallados de cada intento de pago: IP, payload enviado y respuesta recibida
- Validación de contratos en plugins de pago al instalarse
- Control de acceso por roles y permisos granulares en el panel de administración

---

## 9. Resumen de funcionalidades por módulo

| Área | Funcionalidades |
|---|---|
| Tienda pública | 17 |
| Catálogo (admin) | 19 |
| Ventas y órdenes (admin) | 11 |
| Clientes (admin) | 7 |
| Descuentos y promociones | 10 |
| Logística y envíos | 10 |
| Facturación e impuestos | 7 |
| Reseñas (admin) | 5 |
| Reportes y dashboard | 5 |
| Configuración del sistema | 14 |
| Módulo de pagos (EcommercePayment) | 16 |
| Gestión geográfica (Locations) | 7 |
| API REST | 15 |
| Seguridad | 7 |
| **Total** | **150** |

---

*Documento generado para uso interno y presentación a cliente. Sujeto a ajustes según el alcance final acordado.*
