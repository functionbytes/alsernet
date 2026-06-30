# 🐳 CONFIGURACIÓN DOCKER - MANAGER

**Fecha**: 2026-02-23
**Status**: ✅ Completado

---

## 📋 CONTENIDOS CREADOS

```
/Users/developert/Herd/manager/
├── Dockerfile                              ✅ Imagen PHP 8.4-FPM con OCI8
├── docker-compose-manager.yml              ✅ Orquestación de servicios
├── .dockerignore                           ✅ Archivos a ignorar
├── docker-start.sh                         ✅ Script para iniciar
├── docker-stop.sh                          ✅ Script para detener
├── docker/
│   ├── nginx/
│   │   ├── nginx.conf                      ✅ Config principal Nginx
│   │   └── conf.d/
│   │       └── default.conf                ✅ Virtual host
│   ├── php/
│   │   └── php.ini                         ✅ Configuración PHP
│   └── oracle/                             📁 (Opcional) Oracle Instant Client
└── DOCKER_SETUP.md                         ✅ Este archivo
```

---

## 🚀 INICIO RÁPIDO

### Opción 1: Script Automático (RECOMENDADO)
```bash
cd /Users/developert/Herd/manager
bash docker-start.sh
```

### Opción 2: Manual con docker-compose
```bash
cd /Users/developert/Herd/manager
docker-compose -f docker-compose-manager.yml up -d
```

---

## 🛠️ SERVICIOS INCLUIDOS

### 1️⃣ PHP-FPM (app:9000)
- **Imagen**: `php:8.4-fpm`
- **Características**:
  - PHP 8.4 con todas las extensiones
  - OCI8 para conectar a Oracle
  - Composer instalado
  - Volumen: `./` → `/var/www`
  - Red: `manager-network`

### 2️⃣ Nginx (localhost:8080)
- **Imagen**: `nginx:alpine`
- **Puertos**:
  - HTTP: `8080:80`
  - HTTPS: `8443:443`
- **Volúmenes**:
  - Configuración: `docker/nginx/`
  - Aplicación: `./` → `/var/www`
- **Red**: `manager-network`

### 3️⃣ MySQL (localhost:3307)
- **Imagen**: `mysql:8.0`
- **Credenciales**:
  - Usuario: `webadmin`
  - Contraseña: `Mar.90272618`
  - Base de datos: `managerchat`
- **Volumen**: `mysql-data:/var/lib/mysql`
- **Red**: `manager-network`

### 4️⃣ Redis (localhost:6380)
- **Imagen**: `redis:7-alpine`
- **Configuración**: Sin contraseña
- **Volumen**: `redis-data:/data`
- **Red**: `manager-network`

### 5️⃣ Mailhog (localhost:8026)
- **Imagen**: `mailhog:latest`
- **SMTP**: `localhost:1026`
- **Web UI**: `http://localhost:8026`
- **Red**: `manager-network`

---

## 🔗 CONEXIÓN A ORACLE

### Opción 1: Compartir Red con Integracion (RECOMENDADO)

**Paso 1**: Asegúrate que integracion esté corriendo
```bash
cd /Users/developert/Herd/integracion
docker-compose up -d
```

**Paso 2**: Conecta manager a la red de Oracle
```bash
docker network connect laravel-oracle manager-app
```

**Paso 3**: Actualiza .env en manager
```env
ORACLE_HOST=laravel-oracle9i-db  # Nombre del contenedor en red compartida
ORACLE_PORT=1521
ORACLE_USERNAME=lectura
ORACLE_PASSWORD=alsernet
ORACLE_ENABLED=true
```

### Opción 2: Oracle Instant Client Local

Si tienes Oracle Instant Client instalado localmente:

**Paso 1**: Copia los archivos de instalación
```bash
cp /path/to/instantclient-basic-linux.x64-11.2.0.4.0.zip \
   /Users/developert/Herd/manager/docker/oracle/

cp /path/to/instantclient-sdk-linux.x64-11.2.0.4.0.zip \
   /Users/developert/Herd/manager/docker/oracle/
```

**Paso 2**: Reconstruye la imagen
```bash
docker-compose -f docker-compose-manager.yml build --no-cache app
docker-compose -f docker-compose-manager.yml up -d
```

**Paso 3**: Usa localhost
```env
ORACLE_HOST=127.0.0.1
```

---

## 📊 VARIABLES DE ENTORNO

### Archivo `.env` - Configuración de Docker
```env
# Aplicación
APP_ENV=development
APP_DEBUG=true
DB_HOST=mysql              # Nombre del servicio en docker-compose
DB_PORT=3306
DB_DATABASE=managerchat
DB_USERNAME=webadmin
DB_PASSWORD=Mar.90272618

# Redis
REDIS_HOST=redis           # Nombre del servicio
REDIS_PORT=6379

# Mail
MAIL_HOST=mailhog          # Nombre del servicio
MAIL_PORT=1025

# Oracle (si está en red compartida)
ORACLE_ENABLED=true
ORACLE_HOST=laravel-oracle9i-db  # Nombre del contenedor en integracion
ORACLE_PORT=1521
ORACLE_USERNAME=lectura
ORACLE_PASSWORD=alsernet
```

---

## 🧪 PRUEBAS

### 1. Verificar que los servicios estén corriendo
```bash
docker-compose -f docker-compose-manager.yml ps
```

**Salida esperada:**
```
NAME                    STATUS
manager-app             Up
manager-nginx           Up
manager-mysql           Up
manager-redis           Up
manager-mailhog         Up
```

### 2. Acceder a la aplicación
```
http://localhost:8080
```

### 3. Verificar conectividad a MySQL
```bash
docker-compose -f docker-compose-manager.yml exec app \
  php artisan db:seed
```

### 4. Verificar conectividad a Redis
```bash
docker-compose -f docker-compose-manager.yml exec app \
  php artisan tinker

>>> Redis::ping()
=> "PONG"
```

### 5. Verificar conectividad a Oracle
```bash
bash test-oracle-from-docker.sh
```

---

## 🔧 COMANDOS ÚTILES

### Ver logs
```bash
# Todos los servicios
docker-compose -f docker-compose-manager.yml logs -f

# Solo aplicación
docker-compose -f docker-compose-manager.yml logs -f app

# Solo Nginx
docker-compose -f docker-compose-manager.yml logs -f nginx

# Últimas 100 líneas
docker-compose -f docker-compose-manager.yml logs -n 100 app
```

### Ejecutar comandos dentro del contenedor
```bash
# Bash interactivo
docker-compose -f docker-compose-manager.yml exec app bash

# PHP Artisan
docker-compose -f docker-compose-manager.yml exec app php artisan tinker

# Composer
docker-compose -f docker-compose-manager.yml exec app composer install

# Migraciones
docker-compose -f docker-compose-manager.yml exec app php artisan migrate
```

### Acceder a bases de datos
```bash
# MySQL
docker-compose -f docker-compose-manager.yml exec mysql mysql \
  -u webadmin -p managerchat

# Redis CLI
docker-compose -f docker-compose-manager.yml exec redis redis-cli
```

### Detener y limpiar
```bash
# Solo detener
docker-compose -f docker-compose-manager.yml down

# Detener y borrar volúmenes
docker-compose -f docker-compose-manager.yml down -v

# Detener y limpiar huérfanos
docker-compose -f docker-compose-manager.yml down --remove-orphans
```

---

## 🔄 RECONSTRUIR IMAGEN

Si cambias el Dockerfile o necesitas actualizar dependencias:

```bash
# Opción 1: Reconstruir todo
docker-compose -f docker-compose-manager.yml build --no-cache

# Opción 2: Solo la aplicación
docker-compose -f docker-compose-manager.yml build --no-cache app

# Opción 3: Y luego levantar
docker-compose -f docker-compose-manager.yml up -d --build
```

---

## 🐛 TROUBLESHOOTING

### Error: "Connection refused"
```bash
# Verifica que los servicios estén corriendo
docker-compose -f docker-compose-manager.yml ps

# Espera un momento a que se inicialicen
sleep 5

# Intenta nuevamente
docker-compose -f docker-compose-manager.yml exec app php artisan db:seed
```

### Error: "Bind for 0.0.0.0:8080 failed"
```bash
# Puerto 8080 ya está en uso, libéralo o cambia el puerto en docker-compose.yml
lsof -i :8080
kill -9 <PID>

# O cambia el puerto en docker-compose.yml
# ports:
#   - "8081:80"  ← Usa 8081 en lugar de 8080
```

### Error: "ORA-03113" (Oracle)
```bash
# Asegúrate que la red esté conectada
docker network connect laravel-oracle manager-app

# Verifica que integracion esté corriendo
docker-compose -f ../integracion/docker-compose.yml ps
```

### Volúmenes no sincronizados
```bash
# En Mac/Windows, Docker usa montajes virtuales que pueden ser lentos
# Usa bindfs para mejorar el rendimiento:
docker run --rm -it -v /var/run/docker.sock:/var/run/docker.sock \
  -v /Users/developert/Herd/manager:/mnt/manager \
  mutagen/bindfs mount /mnt/manager /var/www
```

---

## 📈 PRODUCCIÓN

Para environment de producción:

```yaml
services:
  app:
    environment:
      APP_ENV: production
      APP_DEBUG: false
    restart: always

  mysql:
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}  # De .env.production
      MYSQL_PASSWORD: ${MYSQL_PASSWORD}
```

---

## 📞 SOPORTE

### Documentos relacionados:
- `IMPLEMENTACION_ORACLE_COMPLETA.md` - Configuración Oracle
- `ORACLE_CONFIGURATION_SUMMARY.md` - Resumen Oracle
- `docker-compose-manager.yml` - Orquestación completa

### Comandos de ayuda:
```bash
bash docker-start.sh   # Inicia todo
bash docker-stop.sh    # Detiene todo
docker-compose --version  # Verifica versión
docker --version       # Verifica versión de Docker
```

---

## ✨ CHECKLIST FINAL

- [x] Dockerfile creado
- [x] docker-compose.yml configurado
- [x] Nginx configurado
- [x] PHP configurado
- [x] Scripts de inicio/parada
- [x] .dockerignore creado
- [x] Documentación completa
- [ ] Oracle Instant Client (Opcional)
- [ ] Ejecutar `bash docker-start.sh`

