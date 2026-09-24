# Solicitud al DBA: permisos de lectura e índices en Oracle (Gestión)

**Asunto:** GRANT SELECT e índices en el esquema DEVELOPER para la consulta de clientes desde el helpdesk

Hola:

Desde el helpdesk de atención al cliente consultamos Gestión en **solo lectura**. Lo hacemos a través de la API del proyecto *manager*, con el usuario **`LECTURA`** sobre el esquema **`DEVELOPER`**. Nunca escribimos: no usamos DDL ni DML.

Para que el agente vea toda la ficha del cliente mientras le atiende, necesitamos dos cosas:

1. Permiso de lectura sobre algunas tablas.
2. Varios índices para las consultas que hoy recorren tablas enteras.

Os dejamos abajo las sentencias exactas y para qué sirve cada una.

---

## 1. Permisos de lectura (GRANT SELECT)

Hoy estas tablas devuelven `ORA-00942: table or view does not exist` para el usuario `LECTURA`. Lo comprobamos el 24-sep-2026 con un `SELECT 1 … WHERE ROWNUM <= 1` sobre cada una.

```sql
-- Ejecutar como DBA (SYS/SYSTEM o el propietario del esquema DEVELOPER)
GRANT SELECT ON DEVELOPER.FACTURACLI_CENTRAL   TO LECTURA;
GRANT SELECT ON DEVELOPER.LFACTURACLI_CENTRAL  TO LECTURA;
GRANT SELECT ON DEVELOPER.COBROCLI_CENTRAL     TO LECTURA;
GRANT SELECT ON DEVELOPER.DEUDACLI_CENTRAL     TO LECTURA;
GRANT SELECT ON DEVELOPER.VALE                 TO LECTURA;
GRANT SELECT ON DEVELOPER.BONO_PROMOCION       TO LECTURA;
GRANT SELECT ON DEVELOPER.CLIENTETARJETA_CENT  TO LECTURA;
GRANT SELECT ON DEVELOPER.CLIENTECUENTA_CENT   TO LECTURA;
```

| Tabla | Para qué la usa el helpdesk |
|---|---|
| `FACTURACLI_CENTRAL` | Lista de facturas del cliente: serie, número, fecha, forma de pago y estado. El agente puede responder "¿me mandáis la factura del pedido X?" sin salir del chat |
| `LFACTURACLI_CENTRAL` | Líneas de la factura, para el detalle y la copia informativa en PDF: artículos, unidades, base imponible, IVA y total |
| `COBROCLI_CENTRAL` | Cobros del cliente: método, importe, fecha y estado. Sirve para confirmar si un pago se ha recibido |
| `DEUDACLI_CENTRAL` | Deudas pendientes por albarán, y el riesgo actual frente al permitido. Con ellas se calculan el saldo y los avisos "Deuda pendiente" y "Riesgo superado", que evitan prometer envíos o financiación a quien tiene impagos |
| `VALE` | Vales del cliente: importe, caducidad y si están usados. Genera el aviso "Vale que caduca pronto" |
| `BONO_PROMOCION` | Bonos promocionales: importe, compra mínima, vigencia y si se han consumido o enviado |
| `CLIENTETARJETA_CENT` | Tarjetas del cliente. **Solo las ven responsables y administradores.** El número se enmascara (solo los 4 últimos dígitos) antes de guardarse o mostrarse |
| `CLIENTECUENTA_CENT` | Cuentas bancarias para domiciliaciones. **Solo las ven responsables y administradores.** El IBAN se enmascara (país + control + 4 últimos) |

### Tablas que ya tienen permiso de lectura

No hace falta hacer nada con ellas; lo comprobamos el 24-sep-2026:

- `PUNTOFIDELIZACION`: movimientos y saldo de puntos. Además ya tiene el índice `IDX_PUNTOFID_IDCLIENTE`.
- `TRANSPORTISTA`: nombre del transportista y `URL_TRACKING`.
- `ALBARANCLI_CENTRAL`, `LALBARANCLI_CENTRAL`, `PEDIDOCLI_CENTRAL`, `LPEDIDOCLI_CENTRAL`, `CLIENTE_LOPD_HIST`.
- Las tablas de códigos: `ALMACEN`, `ORIGENPEDIDOCLI`, `CATALOGO`, `PEDIDOCLIESTADO`, `TIPOALBARANCLI`, `IDIOMA`, `PAIS`, `REGFISCAL`, `REGPAIS`, `CATEGORIA_CLIENTE` y `TIPOCLIENTE`.

### Tabla de envíos: necesitamos que nos confirméis el nombre

`PEDIDOCLI_CENTRAL.IDENVIO` y `ALBARANCLI_CENTRAL.IDENVIO` apuntan a la tabla de envíos, pero no podemos saber cuál es:

- El usuario `LECTURA` no la ve: no aparece en `ALL_OBJECTS` ni en `ALL_TAB_COLUMNS`. Probamos `ENVIO`, `ENVIO_CENTRAL`, `ENVIOCLI`, `ENVIOCLI_CENTRAL`, `EXPEDICION`… y todas dan ORA-00942.
- Esas dos tablas no tienen claves foráneas declaradas. `ALL_CONSTRAINTS` solo muestra la PK y restricciones CHECK.

Esta consulta, lanzada con un usuario con visibilidad total, debería localizarla:

```sql
SELECT owner, table_name
  FROM dba_tab_columns
 WHERE column_name = 'IDENVIO'
   AND owner = 'DEVELOPER'
   AND table_name NOT LIKE 'PEDIDOCLI%'
   AND table_name NOT LIKE 'ALBARANCLI%'
   AND table_name NOT LIKE 'MLOG$%'
   AND table_name NOT LIKE 'RUPD$%';
```

Cuando la tengáis, os pedimos el permiso de lectura sobre ella. Si el número de expedición o de bulto vive en otra tabla hija, también sobre esa:

```sql
GRANT SELECT ON DEVELOPER.<TABLA_DE_ENVIOS> TO LECTURA;
-- y, si aplica:
GRANT SELECT ON DEVELOPER.<TABLA_DE_BULTOS_O_EXPEDICIONES> TO LECTURA;
```

**Para qué sirve:** es lo único que falta para mostrar al agente el transportista y el número de seguimiento de cada pedido, y darle al cliente el enlace de seguimiento directamente en el chat. Es la pregunta más habitual: "¿dónde está mi pedido?".

- `TRANSPORTISTA` ya es legible.
- El helpdesk ya tiene preparadas las plantillas de enlace de SEUR, MRW, Correos, Correos Express, GLS, CTT, DHL, UPS, Nacex, InPost y Schenker.

---

## 2. Índices

Todos son índices normales, sin cambios en los datos. Van por orden de impacto en el helpdesk.

### 2.1 `PEDIDOCLI_CENTRAL (IDCLIENTE, FBAJA)`: el más urgente

```sql
CREATE INDEX DEVELOPER.IDX_PEDIDOCLI_IDCLIENTE
    ON DEVELOPER.PEDIDOCLI_CENTRAL (IDCLIENTE, FBAJA);
```

Hoy la tabla solo tiene la PK (`PK_PEDIDOCLI_CCENTRAL`). Cada consulta de "pedidos de este cliente" recorre la tabla entera: entre 28 y 35 s en frío, 14 s en caliente y 87 s con `PARALLEL(4)`.

Para no dejar al agente esperando, hoy se lanza la consulta en segundo plano y se cachea una hora. Eso tiene dos consecuencias:

- La primera vez que se abre un cliente, el agente ve "Buscando pedidos en Gestión…" durante unos 35 s.
- Un pedido recién hecho puede tardar hasta una hora en aparecer.

Con el índice, la consulta baja a menos de 1 s. Podríamos quitar la carga diferida y reducir esa caché a unos minutos.

### 2.2 `CLIENTE_CENT (UPPER(EMAIL))`

```sql
CREATE INDEX DEVELOPER.IDX_CLIENTE_UPPER_EMAIL
    ON DEVELOPER.CLIENTE_CENT (UPPER(EMAIL));
```

Cuando entra un correo o un chat, el helpdesk busca al cliente de Gestión por su email. Sin índice, esa búsqueda recorre `CLIENTE_CENT` entera: más de 20 s y acaba en timeout cuando el email no existe. Con índice tarda menos de 0,5 s.

Hoy `CLIENTE_CENT` tiene índices por `CIF`, `UPPER(CIF)`, `CODIGO_INTERNET` e `IDTARJETA`, pero ninguno por email.

### 2.3 `CLIENTETELEFONO_CENT (TELEFONO)`

```sql
CREATE INDEX DEVELOPER.IDX_CLIENTETELEFONO_TELEFONO
    ON DEVELOPER.CLIENTETELEFONO_CENT (TELEFONO);
```

Las conversaciones de WhatsApp solo traen el teléfono, y así es como se vincula al cliente. Hoy la tabla solo tiene índice por `IDCLIENTE`: la búsqueda por número tarda más de 20 s y da timeout. Con índice, menos de 1 s.

### 2.4 `CLIENTE_LOPD_HIST (IDCLIENTE, FACEPTACION_LOPD)`

```sql
CREATE INDEX DEVELOPER.IDX_LOPDH_IDCLIENTE
    ON DEVELOPER.CLIENTE_LOPD_HIST (IDCLIENTE, FACEPTACION_LOPD);
```

Es el historial de aceptaciones LOPD del cliente: unas 359.000 filas, y hoy solo tiene la PK. El agente lo necesita para saber si puede ofrecer promociones. Sin índice, el historial se devuelve vacío para no bloquear la ficha.

### 2.5 `CLIENTE_CENT (IDCATEGORIA_CLIENTE, FBAJA)`

```sql
CREATE INDEX DEVELOPER.IDX_CLIENTE_IDCATEGORIA
    ON DEVELOPER.CLIENTE_CENT (IDCATEGORIA_CLIENTE, FBAJA);
```

Lo usan la segmentación por categoría de cliente (campañas de cumpleaños del helpdesk) y el listado de clientes por categoría. Hoy esas consultas recorren la tabla entera.

### 2.6 `CLIENTE_CENT`: búsqueda por apellidos y nombre (prioridad baja)

```sql
CREATE INDEX DEVELOPER.IDX_CLIENTE_UPPER_APELL
    ON DEVELOPER.CLIENTE_CENT (UPPER(APELLIDOS), FBAJA);

CREATE INDEX DEVELOPER.IDX_CLIENTE_UPPER_NOMBRE
    ON DEVELOPER.CLIENTE_CENT (UPPER(NOMBRE), FBAJA);
```

Es el buscador manual de clientes del agente. Hoy tarda entre 3 y 20 s cuando no hay coincidencia exacta; con estos índices, menos de 1 s.

### 2.7 Índices de acceso en las tablas del apartado 1

Como hoy no vemos esas tablas, no sabemos qué índices tienen. Todas las consultas del helpdesk sobre ellas filtran por cliente:

- Por `IDCLIENTE` en facturas, cobros, vales y bonos.
- Por `IDALBARANCLI_CENTRAL` en deudas: `DEUDACLI_CENTRAL` no tiene `IDCLIENTE` y se une con `ALBARANCLI_CENTRAL`.
- Por `IDFACTURACLI` en las líneas de factura.

Si falta alguno de estos índices, os pedimos crearlo para no cambiar un ORA-00942 por un timeout:

```sql
-- Solo los que falten (comprobar antes en DBA_IND_COLUMNS):
CREATE INDEX DEVELOPER.IDX_FACTURACLI_IDCLIENTE  ON DEVELOPER.FACTURACLI_CENTRAL  (IDCLIENTE);
CREATE INDEX DEVELOPER.IDX_LFACTURACLI_IDFACT    ON DEVELOPER.LFACTURACLI_CENTRAL (IDFACTURACLI);
CREATE INDEX DEVELOPER.IDX_COBROCLI_IDCLIENTE    ON DEVELOPER.COBROCLI_CENTRAL    (IDCLIENTE);
CREATE INDEX DEVELOPER.IDX_DEUDACLI_IDALBCENT    ON DEVELOPER.DEUDACLI_CENTRAL    (IDALBARANCLI_CENTRAL);
CREATE INDEX DEVELOPER.IDX_VALE_IDCLIENTE        ON DEVELOPER.VALE                (IDCLIENTE);
CREATE INDEX DEVELOPER.IDX_BONOPROMO_IDCLIENTE   ON DEVELOPER.BONO_PROMOCION      (IDCLIENTE);
```

Para comprobarlo:

```sql
SELECT table_name, index_name, column_name, column_position
  FROM dba_ind_columns
 WHERE table_owner = 'DEVELOPER'
   AND table_name IN ('FACTURACLI_CENTRAL','COBROCLI_CENTRAL','DEUDACLI_CENTRAL','VALE','BONO_PROMOCION',
                      'CLIENTETARJETA_CENT','CLIENTECUENTA_CENT','LFACTURACLI_CENTRAL')
 ORDER BY table_name, index_name, column_position;
```

---

## 3. Resumen

| Prioridad | Acción | Qué desbloquea en el helpdesk |
|---|---|---|
| Alta | `IDX_PEDIDOCLI_IDCLIENTE` | Pedidos al instante, sin la espera de 35 s ni el retraso de hasta 1 h en ver pedidos nuevos |
| Alta | GRANT sobre `DEUDACLI_CENTRAL`, `FACTURACLI_CENTRAL`, `LFACTURACLI_CENTRAL`, `COBROCLI_CENTRAL` | Saldo, deudas, riesgo, facturas y cobros en el chat |
| Alta | Nombre y GRANT de la tabla de envíos (IDENVIO) | Transportista y enlace de seguimiento de cada pedido |
| Alta | `IDX_CLIENTE_UPPER_EMAIL`, `IDX_CLIENTETELEFONO_TELEFONO` | Vincular automáticamente correos y WhatsApp con el cliente de Gestión |
| Media | GRANT sobre `VALE`, `BONO_PROMOCION` | Vales y bonos vigentes, con aviso de caducidad |
| Media | `IDX_LOPDH_IDCLIENTE` | Historial LOPD (si se pueden ofrecer promociones) |
| Media | Índices por `IDCLIENTE` del apartado 2.7 (los que falten) | Que las tablas nuevas no sean lentas |
| Baja | GRANT sobre `CLIENTETARJETA_CENT`, `CLIENTECUENTA_CENT` | Tarjetas y cuentas (enmascaradas, solo responsables) |
| Baja | `IDX_CLIENTE_IDCATEGORIA`, `IDX_CLIENTE_UPPER_APELL`, `IDX_CLIENTE_UPPER_NOMBRE` | Segmentación por categoría y buscador por nombre |

Cuando esté aplicado, basta con que nos aviséis: la aplicación lo detecta sola. Las secciones que hoy aparecen como "Pendiente de permiso en Oracle" pasan a mostrar datos en unos 10 minutos, lo que dura la caché de ese estado, sin necesidad de desplegar nada.

Gracias.
