# Guía de Troubleshooting - Módulo Supplier

## Descripción General

Esta guía cubre los problemas más comunes en el módulo Supplier y sus soluciones. Organizada por categorías para facilitar la búsqueda.

---

## Tabla de Contenidos

1. [Problemas de Sincronización ERP](#problemas-de-sincronización-erp)
2. [Problemas de Queue Workers](#problemas-de-queue-workers)
3. [Problemas de Cache](#problemas-de-cache)
4. [Problemas de Base de Datos](#problemas-de-base-de-datos)
5. [Problemas de Dashboard](#problemas-de-dashboard)
6. [Problemas de Performance](#problemas-de-performance)
7. [Problemas de Observers/Events](#problemas-de-observersevents)
8. [Problemas de Deployment](#problemas-de-deployment)

---

## Problemas de Sincronización ERP

### Problema: Connection Timeout al Conectar con ERP

**Síntomas**:
- Error: `SQLSTATE[HY000]: Connection timeout`
- Logs: `ERP sync failed: Connection timed out after 30 seconds`
- Tabla `supplier_sync_failures` llena de timeouts

**Diagnóstico**:
```bash
# Probar conexión con ERP
php artisan tinker
>>> $dsn = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=".env('SUPPLIER_ERP_HOST').")(PORT=".env('SUPPLIER_ERP_PORT')."))(CONNECT_DATA=(SID=".env('SUPPLIER_ERP_DATABASE').")))";
>>> $conn = @oci_connect(env('SUPPLIER_ERP_USERNAME'), env('SUPPLIER_ERP_PASSWORD'), $dsn);
>>> var_dump($conn); // Debe retornar resource, no false
```

```bash
# Verificar conectividad de red
ping $(php artisan tinker --execute="echo env('SUPPLIER_ERP_HOST');")

# Verificar puerto abierto
nc -zv $(php artisan tinker --execute="echo env('SUPPLIER_ERP_HOST');") 1521
```

**Soluciones**:

1. **Aumentar timeout**:
   ```env
   SUPPLIER_ERP_TIMEOUT=60
   ```

2. **Verificar firewall**:
   ```bash
   # Permitir puerto 1521 outbound
   sudo ufw allow out 1521/tcp
   ```

3. **Verificar VPN/Network**:
   - Si ERP está en VPN privada, verificar que la app tiene acceso
   - Contactar a IT para revisar reglas de firewall

4. **Usar IP en lugar de hostname**:
   ```env
   # Si el DNS no resuelve correctamente
   SUPPLIER_ERP_HOST=192.168.1.100
   ```

---

### Problema: Invalid Username/Password

**Síntomas**:
- Error: `ORA-01017: invalid username/password; logon denied`
- No puede autenticarse con ERP

**Diagnóstico**:
```bash
php artisan tinker
>>> echo "Username: " . env('SUPPLIER_ERP_USERNAME');
>>> echo "Password length: " . strlen(env('SUPPLIER_ERP_PASSWORD'));
```

**Soluciones**:

1. **Verificar credenciales en .env**:
   ```env
   SUPPLIER_ERP_USERNAME=supplier_sync
   SUPPLIER_ERP_PASSWORD=correct_password
   ```

2. **Verificar espacios en blanco**:
   ```bash
   # Remover espacios al inicio/final
   sed -i 's/^SUPPLIER_ERP_PASSWORD=  */SUPPLIER_ERP_PASSWORD=/' .env
   sed -i 's/SUPPLIER_ERP_PASSWORD=.*  *$//' .env
   ```

3. **Verificar caracteres especiales**:
   ```env
   # Si la contraseña tiene caracteres especiales, usar comillas
   SUPPLIER_ERP_PASSWORD="p@ssw0rd!"
   ```

4. **Solicitar reset de contraseña**:
   - Contactar a DBA de Oracle
   - Solicitar nueva contraseña para usuario `supplier_sync`

---

### Problema: Loops Infinitos de Sincronización

**Síntomas**:
- Logs muestran: `Sync already in progress` repetido
- Cola `erp-sync` se llena indefinidamente
- Alto consumo de CPU y memoria
- Mismo registro se sincroniza cada 5 segundos

**Diagnóstico**:
```bash
# Ver jobs en cola
php artisan queue:monitor erp-sync

# Ver cache flags activos
php artisan tinker
>>> use Illuminate\Support\Facades\Cache;
>>> $keys = Cache::getRedis()->keys('*sync_in_progress*');
>>> foreach ($keys as $key) {
>>>     echo $key . " => " . Cache::get(str_replace(Cache::getPrefix(), '', $key)) . "\n";
>>> }
```

**Soluciones**:

1. **Cleanup manual de cache flags**:
   ```bash
   php artisan supplier:cleanup-sync-cache
   ```

2. **Cleanup agresivo (CUIDADO)**:
   ```bash
   php artisan tinker
   >>> Cache::getRedis()->del(Cache::getRedis()->keys('*sync_in_progress*'));
   ```

3. **Verificar TTL de cache flags**:
   ```env
   # Aumentar TTL si sincronizaciones son lentas
   SUPPLIER_SYNC_CACHE_TTL=600  # 10 minutos
   ```

4. **Verificar que flags se liberan en `finally`**:
   ```php
   // En ErpSyncService.php
   try {
       // ...sync logic...
   } finally {
       $this->clearSyncInProgress($type, $id);  // DEBE estar aquí
   }
   ```

5. **Configurar scheduler para cleanup automático**:
   ```bash
   # Verificar que cron está activo
   crontab -l | grep schedule:run

   # Si no existe, agregar:
   * * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
   ```

---

### Problema: Conflictos No Se Resuelven Automáticamente

**Síntomas**:
- Tabla `supplier_sync_conflicts` llena de registros con `resolved_at = NULL`
- Datos locales difieren de datos ERP permanentemente
- Estrategia "ERP Wins" no se aplica

**Diagnóstico**:
```bash
php artisan tinker
>>> use Modules\Supplier\Models\SupplierSyncConflict;
>>> $unresolved = SupplierSyncConflict::unresolved()->get();
>>> foreach ($unresolved as $conflict) {
>>>     echo "ID: {$conflict->id}, Type: {$conflict->entity_type}, Strategy: {$conflict->resolution_strategy}\n";
>>> }
```

**Soluciones**:

1. **Verificar estrategia configurada**:
   ```php
   // config/supplier.php
   'sync' => [
       'strategy' => 'erp_wins',  // DEBE ser 'erp_wins'
   ],
   ```

2. **Verificar que registerConflict() sobrescribe datos**:
   ```php
   // En ErpSyncService.php
   protected function registerConflict(...) {
       // ...
       // Después de registrar, DEBE sobrescribir local con ERP
       $localEntity->update([
           // ...datos del ERP...
           'last_synced_at' => now(),
       ]);
   }
   ```

3. **Re-sincronizar manualmente**:
   ```bash
   php artisan tinker
   >>> $conflict = SupplierSyncConflict::find(1);
   >>> $price = SupplierProductPrice::find($conflict->entity_id);
   >>> $erpData = json_decode($conflict->erp_data, true);
   >>> $price->update($erpData);
   >>> $conflict->update(['resolved_at' => now()]);
   ```

---

## Problemas de Queue Workers

### Problema: Jobs No Se Procesan

**Síntomas**:
- Tabla `jobs` llena de registros pendientes
- Cola `erp-sync` no se vacía
- Sincronizaciones no se ejecutan

**Diagnóstico**:
```bash
# Ver cantidad de jobs pendientes
php artisan tinker
>>> DB::table('jobs')->where('queue', 'erp-sync')->count();

# Ver estado de workers
sudo supervisorctl status supplier-erp-sync-worker:*
```

**Soluciones**:

1. **Iniciar workers si están detenidos**:
   ```bash
   sudo supervisorctl start supplier-erp-sync-worker:*
   ```

2. **Verificar configuración de Supervisor**:
   ```bash
   cat /etc/supervisor/conf.d/supplier-erp-sync-worker.conf

   # Verificar que tiene:
   # - command apunta a artisan correcto
   # - user tiene permisos
   # - autostart=true
   # - autorestart=true
   ```

3. **Reiniciar workers después de deploy**:
   ```bash
   # IMPORTANTE: Siempre después de deploy
   sudo supervisorctl restart supplier-erp-sync-worker:*

   # O si prefieres restart graceful
   php artisan queue:restart
   ```

4. **Verificar que cola está configurada**:
   ```env
   # .env
   QUEUE_CONNECTION=redis  # o database
   ```

5. **Limpiar failed jobs antiguos**:
   ```bash
   # Ver failed jobs
   php artisan queue:failed

   # Eliminar todos los failed jobs
   php artisan queue:flush

   # O reintentar todos
   php artisan queue:retry all
   ```

---

### Problema: Worker Muere con "Out of Memory"

**Síntomas**:
- Logs: `PHP Fatal error: Allowed memory size...`
- Workers se detienen abruptamente
- Supervisor logs muestran: `exited with code 255`

**Diagnóstico**:
```bash
# Ver memoria asignada a PHP
php -i | grep memory_limit

# Ver uso de memoria de workers
ps aux | grep "queue:work"
```

**Soluciones**:

1. **Aumentar límite de memoria PHP**:
   ```ini
   # /etc/php/8.2/cli/php.ini
   memory_limit = 512M
   ```

2. **Configurar worker para reiniciar después de X jobs**:
   ```ini
   # /etc/supervisor/conf.d/supplier-erp-sync-worker.conf
   command=php /var/www/html/artisan queue:work --queue=erp-sync --tries=3 --max-jobs=100
   ```

3. **Configurar worker para reiniciar después de X tiempo**:
   ```ini
   command=php /var/www/html/artisan queue:work --queue=erp-sync --tries=3 --max-time=3600
   ```

4. **Optimizar jobs para liberar memoria**:
   ```php
   // En listeners
   public function handle(SupplierProductPriceChanged $event): void
   {
       $this->erpSyncService->syncPriceToErp($event->price);

       // Liberar memoria
       unset($event->price);
       gc_collect_cycles();
   }
   ```

---

## Problemas de Cache

### Problema: Cache Flags No Expiran

**Síntomas**:
- Registros quedan permanentemente bloqueados
- Logs: `Sync already in progress` pero no hay sincronización activa
- Flags persisten > 1 hora

**Diagnóstico**:
```bash
php artisan tinker
>>> use Illuminate\Support\Facades\Cache;
>>> $key = 'sync_in_progress_price_123';
>>> Cache::has($key);  # true o false
>>> Cache::get($key);  # valor almacenado
```

**Soluciones**:

1. **Ejecutar cleanup command**:
   ```bash
   php artisan supplier:cleanup-sync-cache --ttl=60
   ```

2. **Ejecutar cleanup agresivo**:
   ```bash
   php artisan supplier:cleanup-sync-cache --ttl=5
   ```

3. **Verificar driver de cache**:
   ```env
   # .env - Debe ser Redis o Memcached
   CACHE_DRIVER=redis
   ```

4. **Verificar TTL configurado**:
   ```php
   // config/supplier.php
   'sync' => [
       'cache_ttl' => 300,  // 5 minutos
   ],
   ```

5. **Flush completo de cache (ÚLTIMO RECURSO)**:
   ```bash
   php artisan cache:clear
   ```

---

### Problema: Redis Connection Refused

**Síntomas**:
- Error: `Connection refused [tcp://127.0.0.1:6379]`
- Cache no funciona
- Queue no funciona

**Diagnóstico**:
```bash
# Verificar que Redis está corriendo
redis-cli ping  # Debe responder: PONG

# Verificar puerto
netstat -tulpn | grep 6379
```

**Soluciones**:

1. **Iniciar Redis si está detenido**:
   ```bash
   sudo service redis-server start

   # O con systemd
   sudo systemctl start redis-server
   ```

2. **Verificar configuración de Laravel**:
   ```env
   # .env
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

3. **Verificar que Redis acepta conexiones**:
   ```bash
   # /etc/redis/redis.conf
   bind 127.0.0.1
   protected-mode yes
   ```

4. **Cambiar a database driver temporalmente**:
   ```env
   # .env
   CACHE_DRIVER=database
   QUEUE_CONNECTION=database

   # Luego ejecutar migraciones de queue
   php artisan queue:table
   php artisan migrate
   ```

---

## Problemas de Base de Datos

### Problema: Tabla No Existe

**Síntomas**:
- Error: `SQLSTATE[42S02]: Base table or view not found`
- Queries fallan con "table doesn't exist"

**Diagnóstico**:
```bash
# Ver tablas del módulo Supplier
php artisan tinker
>>> Schema::hasTable('supplier_products');
>>> Schema::hasTable('supplier_sync_failures');
>>> Schema::hasTable('supplier_sync_conflicts');
```

**Soluciones**:

1. **Ejecutar migraciones**:
   ```bash
   php artisan migrate --path=modules/Supplier/database/migrations
   ```

2. **Verificar estado de migraciones**:
   ```bash
   php artisan migrate:status | grep supplier
   ```

3. **Re-ejecutar migración específica**:
   ```bash
   php artisan migrate:refresh --path=modules/Supplier/database/migrations/2026_01_16_102755_create_supplier_sync_conflicts_table.php
   ```

---

### Problema: Index Duplicado

**Síntomas**:
- Error: `SQLSTATE[42000]: Duplicate key name 'idx_products_erp_active'`
- Migración de índices falla

**Diagnóstico**:
```bash
php artisan tinker
>>> $indexes = DB::select('SHOW INDEX FROM supplier_products');
>>> foreach ($indexes as $index) {
>>>     echo $index->Key_name . "\n";
>>> }
```

**Soluciones**:

1. **Rollback y re-ejecutar migración**:
   ```bash
   php artisan migrate:rollback --path=modules/Supplier/database/migrations/2026_01_16_104947_add_performance_indexes_to_supplier_tables.php
   php artisan migrate --path=modules/Supplier/database/migrations/2026_01_16_104947_add_performance_indexes_to_supplier_tables.php
   ```

2. **Eliminar índice manualmente**:
   ```bash
   php artisan tinker
   >>> DB::statement('DROP INDEX idx_products_erp_active ON supplier_products');
   ```

3. **Verificar migración usa verificación idempotente**:
   ```php
   // En migración
   if (! $indexExists('supplier_products', 'idx_products_erp_active')) {
       $table->index(['erp_product_id', 'is_active'], 'idx_products_erp_active');
   }
   ```

---

### Problema: Slow Query Performance

**Síntomas**:
- Dashboard tarda > 5 segundos en cargar
- Logs: `Query took 3452ms`
- Queries de estadísticas son lentas

**Diagnóstico**:
```bash
# Habilitar query logging
php artisan tinker
>>> DB::enableQueryLog();
>>> // ejecutar query problemática
>>> DB::getQueryLog();
```

```sql
-- Ver queries lentas en MySQL
SHOW PROCESSLIST;

-- Ver queries con EXPLAIN
EXPLAIN SELECT * FROM supplier_sync_failures WHERE retry_count < max_retries;
```

**Soluciones**:

1. **Verificar que índices existen**:
   ```bash
   php artisan migrate:status | grep indexes

   # Si no existen, crearlos
   php artisan migrate --path=modules/Supplier/database/migrations/2026_01_16_104947_add_performance_indexes_to_supplier_tables.php
   ```

2. **Agregar índices faltantes**:
   ```php
   Schema::table('supplier_sync_failures', function (Blueprint $table) {
       $table->index('resolved_at');
       $table->index(['sync_type', 'created_at']);
   });
   ```

3. **Optimizar queries con eager loading**:
   ```php
   // Antes (N+1)
   $failures = SupplierSyncFailure::all();
   foreach ($failures as $failure) {
       echo $failure->supplier->name;  // Query por cada iteración
   }

   // Después
   $failures = SupplierSyncFailure::with('supplier')->get();
   ```

4. **Cachear estadísticas**:
   ```php
   $stats = Cache::remember('supplier_sync_stats', 300, function() {
       return [
           'total_failures' => SupplierSyncFailure::count(),
           'retryable_failures' => SupplierSyncFailure::retryable()->count(),
           // ...
       ];
   });
   ```

---

## Problemas de Dashboard

### Problema: 404 Not Found al Acceder Dashboard

**Síntomas**:
- URL: `/settings/suppliers/sync-failures` retorna 404
- Página no existe

**Diagnóstico**:
```bash
# Verificar que ruta existe
php artisan route:list | grep sync-failures
```

**Soluciones**:

1. **Limpiar cache de rutas**:
   ```bash
   php artisan route:clear
   php artisan route:cache
   ```

2. **Verificar rutas en web.php**:
   ```php
   // modules/Supplier/routes/web.php
   Route::get('/settings/suppliers/sync-failures', [
       SupplierSyncFailuresController::class, 'index'
   ])->name('settings.suppliers.sync-failures.index');
   ```

3. **Verificar middleware**:
   ```php
   Route::middleware(['auth', 'role:super-admin'])->group(function() {
       // rutas...
   });
   ```

---

### Problema: Botones de Acción No Funcionan

**Síntomas**:
- Click en "Retry" o "Delete" no hace nada
- No hay feedback visual
- Consola JavaScript sin errores

**Diagnóstico**:
```javascript
// En consola del navegador (F12)
console.log('jQuery loaded:', typeof jQuery !== 'undefined');
console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');

// Verificar event listeners
jQuery._data(document.querySelector('[data-action="retry"]'), 'events');
```

**Soluciones**:

1. **Verificar que jQuery está cargado antes del script**:
   ```blade
   <!-- En layout -->
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <script src="{{ asset('theme/js/sync-failures-dashboard.js') }}"></script>
   ```

2. **Usar delegated events**:
   ```javascript
   // En lugar de
   document.querySelector('[data-action="retry"]').addEventListener('click', ...);

   // Usar
   document.addEventListener('click', function(e) {
       if (e.target.matches('[data-action="retry"]')) {
           // ...
       }
   });
   ```

3. **Verificar CSRF token**:
   ```javascript
   fetch(url, {
       method: 'POST',
       headers: {
           'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
           'Content-Type': 'application/json',
       },
       body: JSON.stringify(data),
   });
   ```

---

### Problema: Modal de Conflicto No Se Abre

**Síntomas**:
- Click en "View Details" no hace nada
- Modal no aparece
- Consola JavaScript: `Cannot read property 'show' of undefined`

**Diagnóstico**:
```javascript
// En consola
console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');
console.log('Modal element exists:', !!document.getElementById('conflictDetailModal'));
```

**Soluciones**:

1. **Verificar que Bootstrap JS está cargado**:
   ```blade
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   ```

2. **Verificar estructura del modal**:
   ```html
   <!-- Debe existir en el HTML -->
   <div class="modal fade" id="conflictDetailModal" tabindex="-1">
       <div class="modal-dialog">
           <div class="modal-content">
               <!-- contenido -->
           </div>
       </div>
   </div>
   ```

3. **Usar método correcto para abrir modal**:
   ```javascript
   // Bootstrap 5
   const modal = new bootstrap.Modal(document.getElementById('conflictDetailModal'));
   modal.show();

   // No usar jQuery si usas Bootstrap 5
   ```

---

## Problemas de Performance

### Problema: Dashboard Carga Lento

**Síntomas**:
- Página tarda > 5 segundos en cargar
- Alto uso de CPU al cargar
- Network tab muestra request lento

**Diagnóstico**:
```bash
# Habilitar debugging
php artisan tinker
>>> config(['app.debug' => true]);

# Instalar Laravel Debugbar
composer require barryvdh/laravel-debugbar --dev
```

**Soluciones**:

1. **Cachear estadísticas**:
   ```php
   // En SupplierSyncFailuresController
   $stats = Cache::remember('supplier_sync_stats', 300, function() {
       return [
           'total_failures' => SupplierSyncFailure::count(),
           // ...
       ];
   });
   ```

2. **Usar paginación**:
   ```php
   // Ya implementado
   $failures = $failuresQuery->paginate(15);
   ```

3. **Agregar índices** (ver sección "Slow Query Performance")

4. **Lazy load de tabs**:
   ```javascript
   // Solo cargar datos del tab cuando se activa
   document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
       tab.addEventListener('shown.bs.tab', function(e) {
           if (e.target.id === 'conflicts-tab') {
               loadConflictsData();
           }
       });
   });
   ```

---

### Problema: Alto Uso de Memoria de Workers

**Síntomas**:
- Workers consumen > 500MB RAM
- Sistema se queda sin memoria
- Logs: `Out of memory` repetido

**Diagnóstico**:
```bash
# Ver uso de memoria
ps aux --sort=-%mem | grep queue:work | head -5
```

**Soluciones**:

1. **Configurar max-jobs**:
   ```ini
   # Supervisor config
   command=php artisan queue:work --queue=erp-sync --max-jobs=50
   ```

2. **Configurar max-time**:
   ```ini
   command=php artisan queue:work --queue=erp-sync --max-time=1800
   ```

3. **Liberar memoria en jobs**:
   ```php
   public function handle(): void
   {
       // ...processing...

       // Liberar memoria
       unset($this->largeData);
       gc_collect_cycles();
   }
   ```

4. **Usar database queue en lugar de Redis** (si tienes muchos jobs grandes):
   ```env
   QUEUE_CONNECTION=database
   ```

---

## Problemas de Observers/Events

### Problema: Observer No Se Dispara

**Síntomas**:
- Cambios en modelo no generan sincronización
- Eventos no se disparan
- Logs: No hay registro de eventos

**Diagnóstico**:
```bash
php artisan tinker
>>> use Modules\Supplier\Models\SupplierProductPrice;
>>> $price = SupplierProductPrice::first();
>>> $price->price = 123.45;
>>> $price->save();  # Observer debería dispararse aquí
>>> # Revisar logs: tail -f storage/logs/laravel.log
```

**Soluciones**:

1. **Verificar que observer está registrado**:
   ```php
   // En SupplierServiceProvider.php
   public function boot(): void
   {
       SupplierProductPrice::observe(SupplierProductPriceObserver::class);
   }
   ```

2. **Verificar imports del observer**:
   ```php
   // En SupplierServiceProvider.php
   use Modules\Supplier\Models\SupplierProductPrice;
   use Modules\Supplier\Observers\SupplierProductPriceObserver;
   ```

3. **Verificar que observer tiene método correcto**:
   ```php
   // En SupplierProductPriceObserver.php
   public function updated(SupplierProductPrice $price): void
   {
       // ...
   }
   ```

4. **Limpiar cache de config**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

---

### Problema: Event Listener No Encola Job

**Síntomas**:
- Evento se dispara pero listener no ejecuta
- Job no aparece en cola
- Sincronización no ocurre

**Diagnóstico**:
```bash
php artisan tinker
>>> use Modules\Supplier\Events\SupplierProductPriceChanged;
>>> use Modules\Supplier\Models\SupplierProductPrice;
>>> $price = SupplierProductPrice::first();
>>> event(new SupplierProductPriceChanged($price));
>>> # Verificar tabla jobs
>>> DB::table('jobs')->where('queue', 'erp-sync')->count();
```

**Soluciones**:

1. **Verificar que listener está registrado**:
   ```php
   // En SupplierServiceProvider.php
   public function boot(): void
   {
       Event::listen(
           SupplierProductPriceChanged::class,
           SyncPriceToErpListener::class
       );
   }
   ```

2. **Verificar que listener implementa ShouldQueue**:
   ```php
   class SyncPriceToErpListener implements ShouldQueue
   {
       public $queue = 'erp-sync';
       // ...
   }
   ```

3. **Verificar imports**:
   ```php
   use Illuminate\Contracts\Queue\ShouldQueue;
   ```

4. **Verificar configuración de queue**:
   ```env
   QUEUE_CONNECTION=redis  # o database
   ```

---

## Problemas de Deployment

### Problema: After Deploy, Sync Stops Working

**Síntomas**:
- Después de deploy, sincronización deja de funcionar
- Workers procesan código antiguo
- Cambios no se reflejan

**Diagnóstico**:
```bash
# Ver versión del código que workers están ejecutando
sudo supervisorctl tail supplier-erp-sync-worker:supplier-erp-sync-worker_00 stdout
```

**Soluciones**:

1. **Reiniciar workers después de deploy**:
   ```bash
   # En script de deploy, agregar:
   sudo supervisorctl restart supplier-erp-sync-worker:*

   # O usar queue:restart (graceful)
   php artisan queue:restart
   ```

2. **Limpiar caches**:
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan cache:clear
   ```

3. **Re-compilar assets si cambiaron**:
   ```bash
   npm run build
   ```

4. **Agregar a deploy script**:
   ```bash
   #!/bin/bash
   git pull
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   npm run build
   sudo supervisorctl restart supplier-erp-sync-worker:*
   ```

---

### Problema: Migrations Fail in Production

**Síntomas**:
- Error: `SQLSTATE[42S02]: Base table or view not found`
- Migraciones fallan durante deploy

**Diagnóstico**:
```bash
# Ver estado de migraciones
php artisan migrate:status

# Intentar migración específica
php artisan migrate --path=modules/Supplier/database/migrations/2026_01_16_104947_add_performance_indexes_to_supplier_tables.php --pretend
```

**Soluciones**:

1. **Ejecutar migraciones en orden**:
   ```bash
   # Primero, tablas base
   php artisan migrate --path=modules/Supplier/database/migrations --step

   # Luego, índices
   php artisan migrate --path=modules/Supplier/database/migrations/2026_01_16_104947_add_performance_indexes_to_supplier_tables.php
   ```

2. **Verificar que migración es idempotente**:
   ```php
   // Debe tener verificaciones
   if (Schema::hasTable('supplier_products')) {
       // ...
   }

   if (! $indexExists('supplier_products', 'idx_products_erp_active')) {
       // ...
   }
   ```

3. **Backup antes de migrar**:
   ```bash
   php artisan backup:run --only-db
   php artisan migrate --force
   ```

---

## Herramientas de Diagnóstico

### Comandos Útiles

```bash
# Ver estado general del sistema
php artisan about

# Ver estado de workers
sudo supervisorctl status

# Ver jobs en cola
php artisan queue:monitor erp-sync

# Ver failed jobs
php artisan queue:failed

# Ver logs en tiempo real
tail -f storage/logs/laravel.log | grep -i "erp sync"

# Ver uso de recursos
top -p $(pgrep -d',' php)

# Test de conexión ERP
php artisan tinker --execute="
\$conn = oci_connect(env('SUPPLIER_ERP_USERNAME'), env('SUPPLIER_ERP_PASSWORD'), env('SUPPLIER_ERP_HOST').':'.env('SUPPLIER_ERP_PORT').'/'.env('SUPPLIER_ERP_DATABASE'));
var_dump(\$conn);
"
```

### Scripts de Verificación

```bash
#!/bin/bash
# verify-supplier-health.sh

echo "=== Supplier Module Health Check ==="

# 1. Verificar tablas
echo "\n1. Verificando tablas..."
php artisan tinker --execute="
echo 'supplier_products: ' . (Schema::hasTable('supplier_products') ? 'OK' : 'MISSING') . PHP_EOL;
echo 'supplier_sync_failures: ' . (Schema::hasTable('supplier_sync_failures') ? 'OK' : 'MISSING') . PHP_EOL;
echo 'supplier_sync_conflicts: ' . (Schema::hasTable('supplier_sync_conflicts') ? 'OK' : 'MISSING') . PHP_EOL;
"

# 2. Verificar workers
echo "\n2. Verificando workers..."
sudo supervisorctl status supplier-erp-sync-worker:*

# 3. Verificar jobs pendientes
echo "\n3. Verificando jobs pendientes..."
php artisan tinker --execute="
echo 'Jobs en cola erp-sync: ' . DB::table('jobs')->where('queue', 'erp-sync')->count() . PHP_EOL;
echo 'Failed jobs: ' . DB::table('failed_jobs')->count() . PHP_EOL;
"

# 4. Verificar cache flags
echo "\n4. Verificando cache flags..."
php artisan tinker --execute="
\$count = count(Cache::getRedis()->keys('*sync_in_progress*'));
echo 'Cache flags activos: ' . \$count . PHP_EOL;
"

# 5. Verificar estadísticas
echo "\n5. Verificando estadísticas..."
php artisan tinker --execute="
echo 'Total failures: ' . Modules\\Supplier\\Models\\SupplierSyncFailure::count() . PHP_EOL;
echo 'Retryable: ' . Modules\\Supplier\\Models\\SupplierSyncFailure::where('retry_count', '<', DB::raw('max_retries'))->count() . PHP_EOL;
echo 'Conflictos no resueltos: ' . Modules\\Supplier\\Models\\SupplierSyncConflict::whereNull('resolved_at')->count() . PHP_EOL;
"

echo "\n=== Health Check Completo ==="
```

---

## Checklist de Troubleshooting

Cuando enfrentes un problema, seguir este orden:

1. **Revisar logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Verificar workers**:
   ```bash
   sudo supervisorctl status
   ```

3. **Verificar queue**:
   ```bash
   php artisan queue:monitor erp-sync
   ```

4. **Verificar cache**:
   ```bash
   php artisan supplier:cleanup-sync-cache --dry-run
   ```

5. **Verificar base de datos**:
   ```bash
   php artisan tinker
   >>> Schema::hasTable('supplier_products');
   ```

6. **Verificar ERP connection**:
   ```bash
   php artisan tinker
   >>> oci_connect(...);
   ```

7. **Limpiar caches**:
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan cache:clear
   ```

8. **Reiniciar workers**:
   ```bash
   sudo supervisorctl restart supplier-erp-sync-worker:*
   ```

---

## Contacto y Soporte

**Equipo de Backend**: backend@alsernet.com
**IT Support**: it@alsernet.com
**DBA Oracle**: dba@alsernet.com

**Horario de soporte**: Lunes a Viernes, 9:00 - 18:00 CET

---

**Última actualización**: 2026-01-16
**Autor**: Equipo de Backend Alsernet
