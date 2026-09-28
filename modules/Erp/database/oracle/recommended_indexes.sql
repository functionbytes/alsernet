-- =============================================================================
-- Índices recomendados para la API ERP (/api/erp/*) — esquema DEVELOPER
-- =============================================================================
-- NO se ejecuta desde Laravel: es el Oracle de producción del ERP y lo debe
-- revisar/lanzar el DBA (espacio, ventana, impacto en las escrituras del ERP).
--
-- Diagnóstico (2026-09-28, medido contra GESTCENT desde webadmin):
--   * La causa común de casi todas las esperas: tablas sin estadísticas y
--     sin índice en las columnas por las que filtra la API (ver abajo).
--   * MODELO (188k filas) solo tiene índice por IDMODELO. /products/filter
--     ordena por FCREACION/FMODIFICACION y filtra por ESTADO_PUBLICADO_WEB:
--     la consulta principal tarda ~2,5 s por página (sort de la tabla entera).
--   * W_PERFILES_PROD (230k) se consulta por IDARTICULO (~760 ms por página de
--     /products/filter); los índices existentes son ID_PRODUCTO / ID_MODELO.
--   * W_CARACTERISTICAS_ORDEN (42k) se consulta por IDMODELO (~140 ms); el
--     índice existente es ID_MODELO.
--   * CLIENTE_CENT (1,5 M) solo tiene UPPER(CIF). La búsqueda por email hace
--     UPPER(EMAIL) = :q y el listado filtra UPPER(APELLIDOS) LIKE 'X%'.
--   * PEDIDOCLI_CENTRAL solo tiene índice por la PK; los pedidos de un
--     cliente se filtran por IDCLIENTE.
--
-- ONLINE requiere Enterprise Edition; en Standard quitarlo y lanzar en ventana.
-- Tras crearlos: EXEC DBMS_STATS.GATHER_TABLE_STATS('DEVELOPER', '<TABLA>');
-- =============================================================================

-- /products/filter: filtro web + orden por fecha de creación / modificación
CREATE INDEX DEVELOPER.IDX_MODELO_WEB_FCREACION  ON DEVELOPER.MODELO (ESTADO_PUBLICADO_WEB, FCREACION)  ONLINE;
CREATE INDEX DEVELOPER.IDX_MODELO_FMODIFICACION  ON DEVELOPER.MODELO (FMODIFICACION)                    ONLINE;

-- Características por artículo / modelo (lotes de /products/filter y /detailed)
CREATE INDEX DEVELOPER.IDX_W_PERFILES_PROD_IDART ON DEVELOPER.W_PERFILES_PROD (IDARTICULO)               ONLINE;
CREATE INDEX DEVELOPER.IDX_W_CARACT_ORDEN_IDMOD  ON DEVELOPER.W_CARACTERISTICAS_ORDEN (IDMODELO)         ONLINE;

-- Búsqueda de clientes (/customer/search por email, /customer?surnames=)
CREATE INDEX DEVELOPER.IDX_CLIENTE_CENT_EMAIL_UP ON DEVELOPER.CLIENTE_CENT (UPPER(EMAIL))                ONLINE;
CREATE INDEX DEVELOPER.IDX_CLIENTE_CENT_APELL_UP ON DEVELOPER.CLIENTE_CENT (UPPER(APELLIDOS))            ONLINE;

-- Pedidos de un cliente (/customer/{id}/orders)
CREATE INDEX DEVELOPER.IDX_PEDIDOCLI_CENT_IDCLI  ON DEVELOPER.PEDIDOCLI_CENTRAL (IDCLIENTE)              ONLINE;

-- Búsqueda por teléfono (/customer/search?q=<teléfono>: ~0,85 s, scan de 1,5 M filas)
CREATE INDEX DEVELOPER.IDX_CLIENTETEL_TELEFONO   ON DEVELOPER.CLIENTETELEFONO_CENT (TELEFONO)            ONLINE;

-- Cumpleaños (/customer?birthday=MM-DD y /customer/birthday-stats: 1,1-2,1 s).
-- La consulta filtra por TO_CHAR(FNACIMIENTO, 'MM-DD'); el índice debe usar
-- exactamente esa expresión para que Oracle lo aproveche.
CREATE INDEX DEVELOPER.IDX_CLIENTE_CENT_CUMPLE   ON DEVELOPER.CLIENTE_CENT (TO_CHAR(FNACIMIENTO, 'MM-DD')) ONLINE;

-- -----------------------------------------------------------------------------
-- Estadísticas: PEDIDOCLI_CENTRAL y otras tablas no tienen (NUM_ROWS nulo en
-- ALL_TABLES) y el optimizador elige planes a ciegas — por eso las mismas
-- consultas saltaban de 0,1 s a 20 s según el plan.
-- -----------------------------------------------------------------------------
-- EXEC DBMS_STATS.GATHER_TABLE_STATS('DEVELOPER', 'PEDIDOCLI_CENTRAL', cascade => TRUE);
-- EXEC DBMS_STATS.GATHER_TABLE_STATS('DEVELOPER', 'MODELO',            cascade => TRUE);
-- EXEC DBMS_STATS.GATHER_TABLE_STATS('DEVELOPER', 'ARTICULO',          cascade => TRUE);
-- EXEC DBMS_STATS.GATHER_TABLE_STATS('DEVELOPER', 'ARTIPROV',          cascade => TRUE);
-- EXEC DBMS_STATS.GATHER_TABLE_STATS('DEVELOPER', 'W_PERFILES_PROD',   cascade => TRUE);
-- EXEC DBMS_STATS.GATHER_TABLE_STATS('DEVELOPER', 'CLIENTE_CENT',      cascade => TRUE);

-- -----------------------------------------------------------------------------
-- Permisos del usuario LECTURA (con el que conecta webadmin): sin ellos, 9
-- endpoints de /api/erp/customer/{id}/* devuelven 500 con ORA-00942
-- (facturas, cobros, deudas, saldo, vales, bonos, tarjetas y cuentas).
-- Nombres de tabla a confirmar por el DBA (en DEVELOPER no son visibles).
-- -----------------------------------------------------------------------------
-- GRANT SELECT ON DEVELOPER.FACTURACLI_CENTRAL   TO LECTURA;
-- GRANT SELECT ON DEVELOPER.LFACTURACLI_CENTRAL  TO LECTURA;
-- GRANT SELECT ON DEVELOPER.COBROCLI_CENTRAL     TO LECTURA;
-- GRANT SELECT ON DEVELOPER.DEUDACLI_CENTRAL     TO LECTURA;
-- GRANT SELECT ON DEVELOPER.CLIENTETARJETA_CENT  TO LECTURA;
-- GRANT SELECT ON DEVELOPER.CLIENTECUENTA_CENT   TO LECTURA;
-- GRANT SELECT ON DEVELOPER.VALE                 TO LECTURA;
-- GRANT SELECT ON DEVELOPER.BONO_PROMOCION       TO LECTURA;

-- -----------------------------------------------------------------------------
-- Rollback
-- -----------------------------------------------------------------------------
-- DROP INDEX DEVELOPER.IDX_MODELO_WEB_FCREACION;
-- DROP INDEX DEVELOPER.IDX_MODELO_FMODIFICACION;
-- DROP INDEX DEVELOPER.IDX_W_PERFILES_PROD_IDART;
-- DROP INDEX DEVELOPER.IDX_W_CARACT_ORDEN_IDMOD;
-- DROP INDEX DEVELOPER.IDX_CLIENTE_CENT_EMAIL_UP;
-- DROP INDEX DEVELOPER.IDX_CLIENTE_CENT_APELL_UP;
-- DROP INDEX DEVELOPER.IDX_PEDIDOCLI_CENT_IDCLI;
-- DROP INDEX DEVELOPER.IDX_CLIENTETEL_TELEFONO;
-- DROP INDEX DEVELOPER.IDX_CLIENTE_CENT_CUMPLE;
