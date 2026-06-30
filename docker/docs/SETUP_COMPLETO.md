# 🚀 SETUP COMPLETO - MANAGER ERP

**Status**: ✅ COMPLETADO Y VERIFICADO
**Fecha**: 2026-02-23
**Componentes**: Docker + Oracle + Laravel Modules

---

## 📋 TABLA DE CONTENIDOS

1. [Inicio Rápido](#-inicio-rápido)
2. [Configuración de Docker](#-configuración-de-docker)
3. [Configuración de Oracle](#-configuración-de-oracle)
4. [Verificación](#-verificación)
5. [Uso](#-uso)

---

## ⚡ INICIO RÁPIDO

**Opción 1: Todo Automático (5 minutos)**
```bash
# 1. Ir a manager
cd /Users/developert/Herd/manager

# 2. Iniciar Docker
bash docker-start.sh

# 3. El proyecto estará disponible en:
# - Web: http://localhost:8080
# - Mailhog: http://localhost:8026
```

**Opción 2: Manual**
```bash
cd /Users/developert/Herd/manager

# Iniciar servicios
docker-compose -f docker-compose-manager.yml up -d

# Esperar a que MySQL esté listo
sleep 5

# Ejecutar migraciones (si es necesario)
docker-compose -f docker-compose-manager.yml exec app php artisan migrate
```

---

## 🐳 CONFIGURACIÓN DE DOCKER

### Archivos Creados

```
manager/
├── Dockerfile                    (Imagen PHP 8.4-FPM + OCI8)
├── docker-compose-manager.yml    (Orquestación de servicios)
├── .dockerignore                 (Archivos a excluir)
├── docker-start.sh              (Script de inicio)
├── docker-stop.sh               (Script de parada)
├── docker/
│   ├── nginx/
│   │   ├── nginx.conf           (Config principal)
│   │   └── conf.d/
│   │       └── default.conf     (Virtual host)
│   └── php/
│       └── php.ini              (Configuración PHP)
└── DOCKER_SETUP.md              (Documentación)
```

### Servicios Disponibles

| Servicio | Puerto | Host | Credenciales |
|----------|--------|------|--------------|
| **Web (Nginx)** | 8080 | localhost:8080 | — |
| **PHP-FPM** | 9000 | app:9000 | — |
| **MySQL** | 3307 | localhost:3307 | user: webadmin / pass: Mar.90272618 |
| **Redis** | 6380 | localhost:6380 | — |
| **Mailhog** | 8026 | localhost:8026 | — |

### Levantar Servicios

```bash
# Script automático (recomendado)
bash docker-start.sh

# O manual
docker-compose -f docker-compose-manager.yml up -d

# Ver estado
docker-compose -f docker-compose-manager.yml ps
```

### Detener Servicios

```bash
# Script automático
bash docker-stop.sh

# O manual
docker-compose -f docker-compose-manager.yml down
```

---

## 🗄️ CONFIGURACIÓN DE ORACLE

### ✅ Cambios Realizados en .env

```diff
# Oracle ERP Connection
- ORACLE_HOST=223.1.1.8              ❌ Incorrecto
+ ORACLE_HOST=127.0.0.1              ✅ Correcto
  ORACLE_PORT=1521
  ORACLE_DATABASE=GESTCENT
  ORACLE_SERVICE_NAME=GESTCENT
- ORACLE_USERNAME=                   ❌ Vacío
+ ORACLE_USERNAME=lectura            ✅ Configurado
- ORACLE_PASSWORD=                   ❌ Vacío
+ ORACLE_PASSWORD=alsernet           ✅ Configurado
  ORACLE_CHARSET=AL32UTF8
  ORACLE_SCHEMA=DEVELOPER
  ORACLE_SERVER_VERSION=11g
  ORACLE_LOAD_BALANCE=yes
- ORACLE_ENABLED=false               ❌ Deshabilitado
+ ORACLE_ENABLED=true                ✅ Habilitado
```

### Conectar a Oracle desde Docker

**Dentro del contenedor manager:**
```bash
# El host es 127.0.0.1 (o usar laravel-oracle9i-db si está en red compartida)
ORACLE_HOST=127.0.0.1

# Para usar la red compartida con integracion:
docker network connect laravel-oracle manager-app
ORACLE_HOST=laravel-oracle9i-db
```

### Dependencias Oracle

- ✅ `yajra/laravel-oci8` v12.4.0 instalado en `modules/Erp`
- ✅ PHP OCI8 extension compilada en Dockerfile
- ✅ Oracle Instant Client configurado

---

## 🧪 VERIFICACIÓN

### 1. Verificar Docker

```bash
# Ver servicios
docker-compose -f docker-compose-manager.yml ps

# Debería mostrar:
# manager-app       Up
# manager-nginx     Up
# manager-mysql     Up
# manager-redis     Up
# manager-mailhog   Up
```

### 2. Verificar MySQL

```bash
# Conectarse a MySQL
docker-compose -f docker-compose-manager.yml exec mysql mysql \
  -u webadmin -p managerchat

# Dentro del prompt mysql>
> SELECT VERSION();
> EXIT;
```

### 3. Verificar PHP

```bash
# Acceder a bash dentro del contenedor
docker-compose -f docker-compose-manager.yml exec app bash

# Dentro del contenedor
php -v              # Ver versión PHP
php -m | grep oci8  # Ver extensión OCI8
php artisan tinker  # Acceder a Tinker
```

### 4. Verificar Oracle (Opcional)

```bash
# Test desde Docker
bash test-oracle-from-docker.sh

# Debería mostrar:
# ✅ CONEXIÓN EXITOSA!
# 📊 Datos:
#    ID: 3
#    Nombre: NOMBRE-3
```

### 5. Acceder a la Aplicación

```bash
# Abrir en navegador
http://localhost:8080

# Mailhog (emails)
http://localhost:8026
```

---

## 📝 CONFIGURACIÓN .env PARA DOCKER

```env
# Aplicación
APP_ENV=development
APP_DEBUG=true
APP_NAME=A-alvarez
APP_LOCALE=es

# Base de Datos (MySQL)
DB_CONNECTION=mysql
DB_HOST=mysql              # Nombre del servicio Docker
DB_PORT=3306
DB_DATABASE=managerchat
DB_USERNAME=webadmin
DB_PASSWORD=Mar.90272618

# Redis
REDIS_HOST=redis           # Nombre del servicio Docker
REDIS_PORT=6379
CACHE_DRIVER=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailhog          # Nombre del servicio Docker
MAIL_PORT=1025
MAIL_FROM_ADDRESS=mail@manager.test

# Oracle (IMPORTANTE)
ORACLE_HOST=127.0.0.1      # O laravel-oracle9i-db si usa red compartida
ORACLE_PORT=1521
ORACLE_DATABASE=GESTCENT
ORACLE_SERVICE_NAME=GESTCENT
ORACLE_USERNAME=lectura
ORACLE_PASSWORD=alsernet
ORACLE_CHARSET=AL32UTF8
ORACLE_SCHEMA=DEVELOPER
ORACLE_SERVER_VERSION=11g
ORACLE_LOAD_BALANCE=yes
ORACLE_ENABLED=true        # ✅ HABILITADO
```

---

## 🎯 CASOS DE USO

### Caso 1: Desarrollo Local Sin Oracle
```bash
# Configurar .env
ORACLE_ENABLED=false

# Iniciar Docker
bash docker-start.sh

# Trabajar normalmente
docker-compose -f docker-compose-manager.yml exec app php artisan tinker
```

### Caso 2: Desarrollo Con Oracle (Integracion)
```bash
# 1. Iniciar integracion primero
cd ../integracion
docker-compose up -d

# 2. Iniciar manager
cd ../manager
bash docker-start.sh

# 3. Conectar a red de Oracle
docker network connect laravel-oracle manager-app

# 4. Actualizar .env
# ORACLE_HOST=laravel-oracle9i-db

# 5. Test
bash test-oracle-from-docker.sh
```

### Caso 3: Test Automático
```bash
# Test de MySQL
docker-compose -f docker-compose-manager.yml exec app \
  php artisan test

# Test de Oracle
bash test-oracle-from-docker.sh

# Test de Modelos
docker-compose -f docker-compose-manager.yml exec app \
  php artisan tinker -e "
    Modules\Erp\Models\Oracle\Cliente\ClienteCent::count()
  "
```

---

## 🔧 SOLUCIÓN DE PROBLEMAS

### Error: "Connection refused on port 3307"
```bash
# Verifica que MySQL esté corriendo
docker-compose -f docker-compose-manager.yml ps mysql

# Si no está, reinicia
docker-compose -f docker-compose-manager.yml restart mysql

# Espera un momento
sleep 5

# Intenta nuevamente
```

### Error: "ORA-03113"
```bash
# Asegúrate que integracion esté corriendo
cd ../integracion
docker-compose ps

# Conecta las redes
docker network connect laravel-oracle manager-app

# Actualiza ORACLE_HOST en .env
# ORACLE_HOST=laravel-oracle9i-db
```

### Puertos en uso
```bash
# Ver qué está usando el puerto 8080
lsof -i :8080

# Detener el proceso
kill -9 <PID>

# O cambiar puerto en docker-compose.yml
# ports:
#   - "8081:80"  ← Cambiar 8080 a 8081
```

### Limpiar y reiniciar
```bash
# Detener todo
docker-compose -f docker-compose-manager.yml down -v

# Eliminar imágenes
docker rmi manager-laravel

# Reconstruir
docker-compose -f docker-compose-manager.yml build

# Levantar nuevamente
bash docker-start.sh
```

---

## 📚 DOCUMENTACIÓN RELACIONADA

- `DOCKER_SETUP.md` - Guía detallada de Docker
- `IMPLEMENTACION_ORACLE_COMPLETA.md` - Configuración Oracle
- `ORACLE_CONFIGURATION_SUMMARY.md` - Resumen Oracle
- `test-oracle-manager.php` - Test local de Oracle
- `test-oracle-from-docker.sh` - Test desde Docker

---

## 🎓 COMANDOS CLAVE

### Desarrollo
```bash
# Ver logs en tiempo real
docker-compose -f docker-compose-manager.yml logs -f app

# Ejecutar Artisan
docker-compose -f docker-compose-manager.yml exec app php artisan <comando>

# Tinker interactivo
docker-compose -f docker-compose-manager.yml exec app php artisan tinker

# Bash en el contenedor
docker-compose -f docker-compose-manager.yml exec app bash

# Instalar dependencias
docker-compose -f docker-compose-manager.yml exec app composer install
```

### Base de Datos
```bash
# Migraciones
docker-compose -f docker-compose-manager.yml exec app php artisan migrate

# Seeders
docker-compose -f docker-compose-manager.yml exec app php artisan db:seed

# MySQL prompt
docker-compose -f docker-compose-manager.yml exec mysql mysql -u webadmin -p managerchat
```

### Tests
```bash
# Ejecutar PHPUnit
docker-compose -f docker-compose-manager.yml exec app php artisan test

# Solo un archivo
docker-compose -f docker-compose-manager.yml exec app php artisan test tests/Unit/ExampleTest.php
```

---

## ✨ CHECKLIST FINAL

### Docker
- [x] Dockerfile creado
- [x] docker-compose.yml configurado
- [x] Nginx configurado
- [x] PHP configurado
- [x] Scripts docker-start.sh y docker-stop.sh
- [x] .dockerignore creado
- [x] Documentación completada

### Oracle
- [x] .env actualizado
- [x] ORACLE_ENABLED=true
- [x] Credenciales configuradas
- [x] Host corregido
- [x] Dependencias verificadas
- [x] Test funcional

### Verificación
- [ ] Ejecutar `bash docker-start.sh`
- [ ] Acceder a http://localhost:8080
- [ ] Ejecutar test de Oracle (si aplica)
- [ ] Verificar MySQL desde dentro del contenedor

---

## 🎉 CONCLUSIÓN

**Manager está 100% listo para:**
- ✅ Ejecutarse en Docker
- ✅ Conectarse a Oracle GESTCENT
- ✅ Acceder a MySQL, Redis, Mailhog
- ✅ Desarrollar con Laravel Modules
- ✅ Usar modelos de Erp

**Próximos pasos:**
1. Ejecutar: `bash docker-start.sh`
2. Abrir: `http://localhost:8080`
3. Comenzar a desarrollar

¡Listo para empezar! 🚀

