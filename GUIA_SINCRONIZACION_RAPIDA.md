# Guía Rápida de Sincronización de Documentos

## Estado del Sistema

Todos los endpoints están listos para usar. Aquí está cómo sincronizar tus documentos existentes.

---

## 🚀 Opciones de Sincronización

### Opción 1: Sincronizar TODOS los documentos

**Endpoint:**
```
POST /api/documents/sync/all
```

**cURL:**
```bash
curl -X POST "http://tu-dominio.com/api/documents/sync/all" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

**Respuesta (Éxito):**
```json
{
    "status": "success",
    "message": "Synchronization completed. 1500 documents synced, 12 failed.",
    "data": {
        "synced": 1500,
        "failed": 12,
        "total": 1512,
        "errors": [
            {
                "uid": "doc-123",
                "order_id": 456,
                "reason": "Order not found in Prestashop"
            }
        ]
    }
}
```

---

### Opción 2: Sincronizar Documentos de una Orden Específica

**Endpoint:**
```
POST /api/documents/sync/by-order
```

**cURL:**
```bash
curl -X POST "http://tu-dominio.com/api/documents/sync/by-order" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "order_id": 123
  }'
```

**Respuesta (Éxito):**
```json
{
    "status": "success",
    "message": "Successfully synced 3 document(s) for order 123.",
    "data": {
        "order_id": 123,
        "synced": 3,
        "order_reference": "ABC123",
        "customer_name": "Juan Pérez"
    }
}
```

---

## 🔍 Verificación de Estado

### Antes de Sincronizar: Ver Documentos sin Datos

```bash
php artisan tinker
> use App\Models\Order\Document;
> Document::whereNull('customer_firstname')->count()
```

Esto te dirá cuántos documentos faltan por sincronizar.

### Después de Sincronizar: Verificar Datos

```bash
php artisan tinker
> Document::whereNotNull('customer_firstname')->count()
> Document::whereNotNull('order_reference')->count()
```

---

## 📊 Ejemplos Reales

### Ejemplo 1: Sincronizar TODO (Recomendado)

```bash
# 1. Ver cuántos documentos necesitan sincronización
php artisan tinker
> use App\Models\Order\Document;
> Document::whereNull('customer_firstname')->count()
# Output: 1512

# 2. Ejecutar sincronización (vía API)
curl -X POST "http://localhost/api/documents/sync/all" \
  -H "Content-Type: application/json"

# 3. Esperar respuesta (puede tomar varios segundos con muchos documentos)

# 4. Verificar resultados
php artisan tinker
> Document::whereNull('customer_firstname')->count()
# Output: 0 (todos sincronizados) o número de errores
```

### Ejemplo 2: Sincronizar una Orden Específica

```bash
# 1. Sincronizar documentos de orden 123
curl -X POST "http://localhost/api/documents/sync/by-order" \
  -H "Content-Type: application/json" \
  -d '{"order_id": 123}'

# 2. Verificar datos fueron llenados
php artisan tinker
> Document::where('order_id', 123)->first()
# Deberá mostrar customer_firstname, customer_lastname, order_reference, etc.
```

### Ejemplo 3: Sincronizar Documentos de Múltiples Órdenes

```bash
# Sincronizar órdenes 100-110
for i in {100..110}; do
  curl -X POST "http://localhost/api/documents/sync/by-order" \
    -H "Content-Type: application/json" \
    -d "{\"order_id\": $i}"
  echo "Orden $i sincronizada"
done
```

---

## 🛠️ Solución de Problemas

### Problema: "Order not found in Prestashop"

**Causa:** La orden no existe en la base de datos de Prestashop

**Solución:** Verificar el `order_id` en el documento

```bash
php artisan tinker
> use App\Models\Order\Document;
> $doc = Document::where('uid', 'tu-uid')->first();
> echo $doc->order_id;  // Ver cuál es el ID

# Si el ID es incorrecto, actualizar manualmente
> $doc->update(['order_id' => 123]); // El ID correcto
> exit;

# Luego sincronizar nuevamente
curl -X POST "http://localhost/api/documents/sync/by-order" \
  -H "Content-Type: application/json" \
  -d '{"order_id": 123}'
```

### Problema: "Customer not found"

**Causa:** La orden no tiene cliente asociado en Prestashop

**Solución:** Verificar en Prestashop que la orden tenga cliente

```bash
php artisan tinker
> use App\Models\Prestashop\Order\Order;
> $order = Order::find(123);
> $order->customer  // Debe retornar un cliente
```

### Problema: Sincronización lenta

**Para muchos documentos (10,000+):**

- Usar `/sync/by-order` en lotes de 100-200 órdenes
- O ejecutar `/sync/all` en horarios de bajo tráfico (madrugada)
- Considerar hacer un comando artisan personalizado si es necesario hacer esto regularmente

---

## ⚡ Métodos Avanzados

### Método 1: Comando Artisan Personalizado

Si necesitas sincronizar regularmente, crear un comando:

```bash
php artisan make:command SyncDocuments
```

```php
// app/Console/Commands/SyncDocuments.php
<?php

namespace App\Console\Commands;

use App\Models\Order\Document;
use App\Models\Prestashop\Order\Order as PrestashopOrder;
use Illuminate\Console\Command;

class SyncDocuments extends Command
{
    protected $signature = 'documents:sync {--order-id=}';
    protected $description = 'Sync document data with Prestashop orders';

    public function handle()
    {
        $orderId = $this->option('order-id');

        if ($orderId) {
            $documents = Document::where('order_id', $orderId)->get();
            $this->info("Syncing {$documents->count()} documents for order $orderId");
        } else {
            $documents = Document::whereNull('customer_firstname')->get();
            $this->info("Syncing {$documents->count()} documents without customer data");
        }

        $synced = 0;
        $failed = 0;

        foreach ($documents as $document) {
            try {
                $order = PrestashopOrder::find($document->order_id);

                if (!$order || !$order->customer) {
                    $failed++;
                    continue;
                }

                $customer = $order->customer;

                $document->update([
                    'order_reference' => $order->reference,
                    'order_date' => $order->date_add,
                    'order_total' => $order->total_paid,
                    'customer_firstname' => $customer->firstname,
                    'customer_lastname' => $customer->lastname,
                    'customer_email' => $customer->email,
                    'customer_dni' => $customer->siret,
                    'customer_company' => $customer->company,
                ]);

                $synced++;
            } catch (\Exception $e) {
                $failed++;
                $this->error("Error syncing document {$document->uid}: {$e->getMessage()}");
            }
        }

        $this->info("Completed: $synced synced, $failed failed");
    }
}
```

**Usar:**
```bash
# Sincronizar todos
php artisan documents:sync

# Sincronizar una orden
php artisan documents:sync --order-id=123
```

---

### Método 2: Ejecutar en Tinker Directamente

```bash
php artisan tinker

# Sincronizar todos
> app(App\Http\Controllers\Api\DocumentsController::class)->syncAllDocumentsWithOrders()

# Salir
> exit
```

---

## 📈 Monitoreo Después de Sincronizar

### Ver resultados por origen

```bash
php artisan tinker
> use App\Models\Order\Document;

# Documentos subidos vs no subidos
> Document::whereNotNull('confirmed_at')->count()
> Document::whereNull('confirmed_at')->count()

# Documentos por origen
> Document::where('source', 'api')->count()
> Document::where('source', 'email')->count()
> Document::where('source', 'whatsapp')->count()

# Documentos con datos de cliente
> Document::whereNotNull('customer_firstname')->count()
```

### Ver documentos sin sincronizar

```bash
php artisan tinker
> use App\Models\Order\Document;
> Document::whereNull('customer_firstname')->limit(10)->get()
```

---

## ✅ Checklist de Sincronización

- [ ] Backup de base de datos hecho
- [ ] Migraciones ejecutadas (`php artisan migrate`)
- [ ] Verificar documentos sin sincronizar: `Document::whereNull('customer_firstname')->count()`
- [ ] Ejecutar sincronización: `POST /api/documents/sync/all`
- [ ] Esperar a que termine (revisar respuesta)
- [ ] Verificar datos sincronizados: `Document::whereNull('customer_firstname')->count()` (debe ser 0 o muy bajo)
- [ ] Verificar búsquedas funcionan rápido en admin
- [ ] Verificar que "Origen" se muestre correctamente en documentos subidos por API

---

## 🚨 Advertencias Importantes

1. **No ejecutar mientras hay usuarios activos** - La sincronización puede usar recursos
2. **Hacer backup antes** - Por si algo sale mal
3. **En producción, hacerlo en horarios de bajo tráfico** - De madrugada es ideal
4. **Monitorear logs** - Ver si hay errores durante sincronización

---

## 📞 Soporte

Si encuentras problemas durante la sincronización:

1. Revisar logs: `storage/logs/laravel.log`
2. Verificar que Prestashop esté funcionando
3. Confirmar que las órdenes existan en Prestashop
4. Confirmar que los clientes estén asociados a las órdenes

¡Listo para sincronizar! 🎉
