# 🐳 DOCKER - MANAGER ERP

**Configuración completa de Docker para Manager con Oracle, MySQL, Redis y más.**

---

## 📁 ESTRUCTURA

```
docker/
├── README.md                           ← Estás aquí
├── Dockerfile                          ✅ Imagen PHP 8.4-FPM con OCI8
├── docker-compose.yml                  ✅ Orquestación de servicios
├── .dockerignore                       ✅ (en raíz)
├── docs/
│   ├── SETUP_COMPLETO.md              📚 Guía de inicio rápido
│   ├── DOCKER_SETUP.md                📚 Configuración detallada de Docker
│   ├── IMPLEMENTACION_ORACLE_COMPLETA.md  📚 Oracle detallado
│   └── ORACLE_CONFIGURATION_SUMMARY.md    📚 Resumen Oracle
├── scripts/
│   ├── docker-start.sh                 🚀 Script para iniciar
│   ├── docker-stop.sh                  🛑 Script para detener
│   └── test-oracle-from-docker.sh      🧪 Test de Oracle
├── config/
│   ├── nginx/
│   │   ├── nginx.conf                  ⚙️ Configuración Nginx
│   │   └── conf.d/
│   │       └── default.conf            ⚙️ Virtual host
│   └── php/
│       └── php.ini                     ⚙️ Configuración PHP
├── oracle/
│   ├── instantclient-basic-linux.x64-11.2.0.4.0.zip    (Opcional)
│   └── instantclient-sdk-linux.x64-11.2.0.4.0.zip      (Opcional)
└── test/
    └── (archivos de prueba antiguos)
```

---

## 🚀 INICIO RÁPIDO

### Opción 1: Script Automático (RECOMENDADA)
```bash
cd /Users/developert/Herd/manager
bash docker/scripts/docker-start.sh
```

### Opción 2: Manual
```bash
cd /Users/developert/Herd/manager
docker-compose -f docker/docker-compose.yml up -d
```

---

## 📚 DOCUMENTACIÓN

Todos los documentos están en `docker/docs/`:

### 1. **SETUP_COMPLETO.md** ⭐
**Guía de inicio rápido + referencia completa**
- Estructura del proyecto
- Servicios incluidos
- Inicio rápido
- Configuración de Oracle
- Verificación
- Solución de problemas
- Comandos útiles

👉 **Lee esto primero si estás empezando**

### 2. **DOCKER_SETUP.md**
**Configuración detallada de Docker**
- Contenidos creados
- Servicios incluidos
- Variables de entorno
- Pruebas
- Comandos útiles
- Troubleshooting

### 3. **IMPLEMENTACION_ORACLE_COMPLETA.md**
**Configuración completa de Oracle**
- Cambios realizados
- Componentes verificados
- Test de conectividad
- Cómo usar
- Integración con otros módulos

### 4. **ORACLE_CONFIGURATION_SUMMARY.md**
**Resumen ejecutivo de Oracle**
- Resumen de cambios
- Configuración paso a paso
- Comparativa integracion vs manager
- Checklist

---

## 🔧 SCRIPTS

Todos los scripts están en `docker/scripts/`:

### docker-start.sh
```bash
bash docker/scripts/docker-start.sh
```
- Inicia todos los servicios
- Conecta con red de Oracle (si está disponible)
- Muestra URLs de acceso

### docker-stop.sh
```bash
bash docker/scripts/docker-stop.sh
```
- Detiene todos los servicios

### test-oracle-from-docker.sh
```bash
bash docker/scripts/test-oracle-from-docker.sh
```
- Test de conectividad a Oracle desde Docker

---

## 🐳 SERVICIOS

| Servicio | Puerto | Host | Credenciales |
|----------|--------|------|--------------|
| **Web (Nginx)** | 8080 | localhost:8080 | — |
| **PHP-FPM** | 9000 | app:9000 | — |
| **MySQL** | 3307 | localhost:3307 | webadmin / Mar.90272618 |
| **Redis** | 6380 | localhost:6380 | — |
| **Mailhog** | 8026 | localhost:8026 | — |

---

## 🗄️ CONFIGURACIÓN DE ARCHIVOS

### config/nginx/nginx.conf
- Configuración principal de Nginx
- Compresión gzip
- Manejo de errores

### config/nginx/conf.d/default.conf
- Virtual host para Laravel
- Rutas estáticas
- PHP handling
- Headers de seguridad

### config/php/php.ini
- Memory limit: 512M
- Upload max: 100M
- Timezone: Europe/Paris
- OPCache habilitado
- OCI8 habilitado

---

## 🔗 CONECTAR CON ORACLE

### Desde Integracion (RECOMENDADO)

1. **Asegúrate que integracion esté corriendo**
   ```bash
   cd ../integracion
   docker-compose up -d
   ```

2. **Conecta la red**
   ```bash
   docker network connect laravel-oracle manager-app
   ```

3. **Actualiza .env**
   ```env
   ORACLE_HOST=laravel-oracle9i-db
   ```

4. **Test**
   ```bash
   bash docker/scripts/test-oracle-from-docker.sh
   ```

---

## 💡 COMANDOS ÚTILES

### Ver logs
```bash
docker-compose -f docker/docker-compose.yml logs -f app
```

### Ejecutar Artisan
```bash
docker-compose -f docker/docker-compose.yml exec app php artisan <comando>
```

### Tinker
```bash
docker-compose -f docker/docker-compose.yml exec app php artisan tinker
```

### Bash dentro del contenedor
```bash
docker-compose -f docker/docker-compose.yml exec app bash
```

### Acceder a MySQL
```bash
docker-compose -f docker/docker-compose.yml exec mysql mysql -u webadmin -p managerchat
```

### Detener y limpiar
```bash
docker-compose -f docker/docker-compose.yml down -v
```

---

## 🔄 RECONSTRUIR IMAGEN

Si cambias el Dockerfile o necesitas actualizar:

```bash
# Reconstruir todo
docker-compose -f docker/docker-compose.yml build --no-cache

# Solo aplicación
docker-compose -f docker/docker-compose.yml build --no-cache app

# Y luego levantar
docker-compose -f docker/docker-compose.yml up -d --build
```

---

## 📊 ESTADO DE SERVICIOS

```bash
docker-compose -f docker/docker-compose.yml ps
```

Debería mostrar:
```
NAME               STATUS
manager-app        Up
manager-nginx      Up
manager-mysql      Up
manager-redis      Up
manager-mailhog    Up
```

---

## 🎯 PRÓXIMOS PASOS

1. **Leer guía completa**
   ```bash
   cat docker/docs/SETUP_COMPLETO.md
   ```

2. **Iniciar servicios**
   ```bash
   bash docker/scripts/docker-start.sh
   ```

3. **Abrir en navegador**
   ```
   http://localhost:8080
   ```

4. **Verificar (opcional)**
   ```bash
   bash docker/scripts/test-oracle-from-docker.sh
   ```

---

## ⚠️ TROUBLESHOOTING

### Puerto 8080 en uso
```bash
lsof -i :8080
kill -9 <PID>
```

### MySQL no inicia
```bash
docker-compose -f docker/docker-compose.yml restart mysql
sleep 5
```

### ORA-03113 (Oracle)
```bash
docker network connect laravel-oracle manager-app
```

### Limpiar todo
```bash
docker-compose -f docker/docker-compose.yml down -v
docker rmi manager-laravel
docker-compose -f docker/docker-compose.yml build
bash docker/scripts/docker-start.sh
```

---

## 📞 REFERENCIAS RÁPIDAS

- **Documentación**: `docker/docs/`
- **Scripts**: `docker/scripts/`
- **Config**: `docker/config/`
- **Oracle Instant Client**: `docker/oracle/` (opcional)

---

## ✨ RESUMEN

✅ Docker completo (PHP, Nginx, MySQL, Redis, Mailhog)
✅ Oracle configurado y documentado
✅ Scripts de inicio/parada automatizados
✅ Documentación completa
✅ Listo para desarrollo

**Para comenzar**: `bash docker/scripts/docker-start.sh`

