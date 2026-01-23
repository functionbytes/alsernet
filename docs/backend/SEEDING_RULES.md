# Database Seeding Rules

**Regla Fundamental**: Todos los seeders NUNCA deben eliminar o truncar datos existentes.

## Principios

### ✅ Lo que SÍ se debe hacer

1. **Usar `firstOrCreate()` o `updateOrCreate()`**
   ```php
   $role = Role::firstOrCreate(
       ['name' => 'super-admin'],
       ['guard_name' => 'web']
   );
   ```

2. **Usar `updateOrInsert()`**
   ```php
   DB::table('roles')->updateOrInsert(
       ['name' => 'admin'],
       ['guard_name' => 'web', 'updated_at' => now()]
   );
   ```

3. **Verificar existencia antes de insertar**
   ```php
   if (!User::where('email', 'admin@example.com')->exists()) {
       User::create([...]);
   }
   ```

4. **Solo crear datos, nunca eliminar**
   - No usar `truncate()`
   - No usar `delete()`
   - No usar `destroy()`
   - No usar `forceDelete()`

### ❌ Lo que NO se debe hacer

1. **Truncar tablas**
   ```php
   // PROHIBIDO
   DB::table('users')->truncate();
   ```

2. **Eliminar registros existentes**
   ```php
   // PROHIBIDO
   User::truncate();
   User::delete();
   Role::destroy($ids);
   ```

3. **Sobrescribir datos sin verificar**
   ```php
   // PROHIBIDO - sobrescribe sin comprobar existencia
   User::create(['email' => 'admin@example.com', ...]);
   ```

## Regla para Tests

Igual que los seeders: **Los tests NUNCA pueden eliminar datos de la BD en producción o desarrollo**.

- Los tests deben usar transacciones o fixtures aislados
- Nunca ejecutar `truncate()` en tests
- Usar `->each(fn ($item) => $item->forceDelete())` solo en fixtures de test aisladas
- Preferir factories + fresh data en lugar de borrar todo

## Implementación Actual

El seeder `RolesAndUsersSeeder` en `database/seeders/Permissions/RolesAndUsersSeeder.php` ya sigue esta regla usando `firstOrCreate()` en cada rol y usuario.

### Usuarios creados (no destructivos):

```
super-admin@alsernet.test    (password: secret)
administrative@alsernet.test (password: secret)
return@alsernet.test         (password: secret)
shop@alsernet.test           (password: secret)
license@alsernet.test        (password: secret)
accounting@alsernet.test     (password: secret)
warehouse@alsernet.test      (password: secret)
callcenter@alsernet.test     (password: secret)
```

## Verificación

Para verificar que un seeder es seguro:

```bash
# Contar registros antes de ejecutar
php artisan tinker
> User::count()  // Resultado: X

# Ejecutar seeder
php artisan db:seed --class="YourSeeder"

# Contar registros después
> User::count()  // Resultado: ≥ X (nunca < X)
```

---

**Última actualización**: 2025-12-29
