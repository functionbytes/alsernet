# Configuración de Tests - Guía Paso a Paso

Esta guía te ayudará a configurar el entorno de tests para el módulo Attention desde cero.

## Requisitos Previos

Antes de comenzar, asegúrate de tener:

- ✅ PHP >= 8.1
- ✅ Composer instalado
- ✅ Laravel instalado y configurado
- ✅ Base de datos configurada (MySQL, PostgreSQL o SQLite)

## Paso 1: Instalar Dependencias

```bash
# Desde la raíz del proyecto
composer install

# Si usas Spatie Media Library (para archivos)
composer require spatie/laravel-medialibrary

# Si necesitas faker en español
composer require fakerphp/faker --dev
```

## Paso 2: Configurar Base de Datos de Tests

### Opción A: SQLite (Recomendado para tests)

La configuración ya está lista en `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

No requiere configuración adicional. SQLite en memoria es rápido y se limpia automáticamente.

### Opción B: MySQL/PostgreSQL

Si prefieres usar una base de datos real:

1. Crear base de datos de tests:

```sql
CREATE DATABASE inoqualab_testing;
```

2. Configurar en `.env.testing`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inoqualab_testing
DB_USERNAME=root
DB_PASSWORD=
```

3. Actualizar `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="inoqualab_testing"/>
```

## Paso 3: Ejecutar Migraciones

```bash
# Ejecutar migraciones en ambiente de testing
php artisan migrate --env=testing

# O si usas SQLite en memoria, las migraciones se ejecutan automáticamente
```

## Paso 4: Publicar Configuraciones (si es necesario)

```bash
# Publicar configuración de Media Library
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="config"

# Publicar configuración del módulo Attention
php artisan vendor:publish --tag="attention-config"
```

## Paso 5: Verificar Configuración del Módulo

Asegúrate de que existe el archivo `/modules/Attention/config/attention.php`:

```php
<?php

return [
    // Prefijo para números de radicado
    'radicado_prefix' => env('ATTENTION_RADICADO_PREFIX', 'peticiones'),

    // Configuración de archivos adjuntos
    'attachments' => [
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_mimes' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/jpg',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
    ],

    // Configuración de emails
    'emails' => [
        'from_address' => env('ATTENTION_FROM_ADDRESS', 'noreply@example.com'),
        'from_name' => env('ATTENTION_FROM_NAME', 'Sistema peticiones'),
    ],
];
```

## Paso 6: Configurar Factories

Asegúrate de que los modelos usan el trait `HasFactory`:

```php
// En Attention.php, AttentionType.php, etc.
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attention extends Model
{
    use HasFactory;

    // Especificar la ubicación de la factory
    protected static function newFactory()
    {
        return \Modules\Attention\Database\Factories\AttentionFactory::new();
    }
}
```

## Paso 7: Verificar Rutas y Service Provider

### Verificar Service Provider

En `modules/Attention/app/Providers/AttentionServiceProvider.php`:

```php
public function boot()
{
    // Cargar migraciones
    $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

    // Cargar rutas
    $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');

    // Cargar configuración
    $this->publishes([
        __DIR__.'/../../config/attention.php' => config_path('attention.php'),
    ], 'attention-config');

    $this->mergeConfigFrom(
        __DIR__.'/../../config/attention.php', 'attention'
    );
}
```

### Registrar Service Provider

En `config/app.php`:

```php
'providers' => [
    // ...
    Modules\Attention\Providers\AttentionServiceProvider::class,
],
```

## Paso 8: Dar Permisos al Script de Tests

```bash
cd modules/Attention
chmod +x run-tests.sh
```

## Paso 9: Ejecutar Tests por Primera Vez

```bash
# Desde el directorio del módulo
./run-tests.sh

# O desde cualquier lugar
vendor/bin/phpunit modules/Attention/tests
```

## Paso 10: Verificar Coverage (Opcional)

```bash
# Generar reporte de cobertura
./run-tests.sh coverage

# Abrir reporte en navegador
open build/coverage/index.html
```

## Troubleshooting

### Error: "Class 'Database\Factories\UserFactory' not found"

**Solución:**

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Error: "Base table or view not found"

**Solución:**

```bash
# Eliminar y recrear migraciones
php artisan migrate:fresh --env=testing

# O verificar que RefreshDatabase está en la clase de test
use Illuminate\Foundation\Testing\RefreshDatabase;
use RefreshDatabase;
```

### Error: "Driver [sqlite] not supported"

**Solución:**

```bash
# Ubuntu/Debian
sudo apt-get install php-sqlite3

# MacOS (Homebrew)
brew install php
```

### Error: "No such file or directory" al usar Storage fake

**Solución:**

```bash
# Crear directorios de storage
php artisan storage:link
chmod -R 775 storage/
```

### Tests muy lentos

**Causas comunes:**

1. Usando MySQL/PostgreSQL en lugar de SQLite
2. Tests con throttling (límites de rate)
3. Muchos archivos adjuntos en tests

**Soluciones:**

```bash
# Usar SQLite en memoria (más rápido)
# Actualizar phpunit.xml con sqlite

# Excluir tests lentos
vendor/bin/phpunit --exclude-group throttle
```

### Error: "Too many open files"

**Solución (Mac):**

```bash
ulimit -n 10240
```

## Configuración para IDE

### PHPStorm

1. Ir a `Settings` > `PHP` > `Test Frameworks`
2. Agregar PHPUnit
3. Path to phpunit.phar: `vendor/autoload.php`
4. Default configuration file: `modules/Attention/phpunit.xml`

### VS Code

Instalar extensión "PHP Unit Test Explorer":

```json
{
    "phpunit.php": "/usr/bin/php",
    "phpunit.phpunit": "vendor/bin/phpunit",
    "phpunit.args": [
        "-c",
        "modules/Attention/phpunit.xml"
    ]
}
```

## CI/CD Setup

### GitHub Actions

Crear `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.1
          extensions: sqlite3, pdo_sqlite

      - name: Install Dependencies
        run: composer install

      - name: Run Tests
        run: |
          php artisan migrate --env=testing
          vendor/bin/phpunit modules/Attention/tests

      - name: Upload Coverage
        uses: codecov/codecov-action@v2
        with:
          file: ./build/logs/clover.xml
```

### GitLab CI

Crear `.gitlab-ci.yml`:

```yaml
test:
  image: php:8.1
  services:
    - mysql:8.0
  before_script:
    - apt-get update && apt-get install -y git
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install
  script:
    - php artisan migrate --env=testing
    - vendor/bin/phpunit modules/Attention/tests
```

## Comandos Útiles

```bash
# Limpiar caché antes de tests
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Regenerar autoload
composer dump-autoload

# Ver lista de tests
./run-tests.sh help
vendor/bin/phpunit --list-tests

# Ejecutar test específico
vendor/bin/phpunit --filter test_nombre_del_test

# Ver progreso detallado
vendor/bin/phpunit --testdox

# Debugear test fallido
vendor/bin/phpunit --stop-on-failure --verbose
```

## Estructura Final del Proyecto

```
modules/Attention/
├── app/
├── config/
│   └── attention.php
├── database/
│   ├── factories/
│   │   ├── AttentionFactory.php
│   │   ├── AttentionTypeFactory.php
│   │   ├── AttentionCategoryFactory.php
│   │   ├── SedeFactory.php
│   │   └── DepartmentFactory.php
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
├── tests/
│   ├── Feature/
│   │   ├── AttentionSubmissionTest.php
│   │   ├── AttentionTrackingTest.php
│   │   ├── AttentionFileUploadTest.php
│   │   └── AttentionAdminTest.php
│   ├── Unit/
│   │   ├── AttentionModelTest.php
│   │   └── HasAttachmentsTraitTest.php
│   ├── TestCase.php
│   ├── .gitignore
│   ├── README.md
│   ├── EXAMPLES.md
│   └── SETUP.md
├── phpunit.xml
└── run-tests.sh
```

## Checklist de Configuración

- [ ] Dependencias instaladas
- [ ] Base de datos configurada
- [ ] Migraciones ejecutadas
- [ ] Service Provider registrado
- [ ] Factories creadas
- [ ] TestCase configurado
- [ ] Storage configurado
- [ ] Tests ejecutándose correctamente
- [ ] Coverage funcionando (opcional)
- [ ] CI/CD configurado (opcional)

## Siguientes Pasos

1. Leer `README.md` para entender la estructura de tests
2. Revisar `EXAMPLES.md` para ver ejemplos prácticos
3. Ejecutar `./run-tests.sh` para verificar que todo funciona
4. Comenzar a escribir tus propios tests

## Soporte

Si encuentras problemas durante la configuración:

1. Verifica que todas las dependencias estén instaladas
2. Revisa los logs en `storage/logs/laravel.log`
3. Ejecuta `php artisan config:clear && php artisan cache:clear`
4. Consulta la documentación de Laravel Testing

## Referencias

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Spatie Media Library](https://spatie.be/docs/laravel-medialibrary)
- [Laravel Factories](https://laravel.com/docs/database-testing#defining-model-factories)
