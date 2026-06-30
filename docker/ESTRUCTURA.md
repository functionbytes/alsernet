# 📁 ESTRUCTURA DE ARCHIVOS - DOCKER

**Documentación sobre la organización de archivos Docker en Manager**

---

## 🎯 PRINCIPIO

Todos los archivos relacionados con Docker están dentro de la carpeta `docker/`.
Desde la raíz solo hay scripts wrapper y el archivo `DOCKER.md` para guía rápida.

---

## 📊 ESTRUCTURA COMPLETA

```
manager/
├── DOCKER.md                           📖 Guía rápida (LEER PRIMERO)
├── start-docker.sh                     🚀 Script para iniciar
├── stop-docker.sh                      🛑 Script para detener
├── .dockerignore                       ⚙️ Archivo de exclusión
├── .env                                ⚙️ Configuración de ambiente
│
└── docker/                             🐳 TODO LO DOCKER AQUÍ
    ├── README.md                       📖 Documentación principal
    ├── Dockerfile                      🏗️ Imagen Docker
    ├── docker-compose.yml              🎼 Orquestación de servicios
    │
    ├── docs/                           📚 DOCUMENTACIÓN
    │   ├── SETUP_COMPLETO.md          ← Guía de inicio rápido
    │   ├── DOCKER_SETUP.md            ← Configuración detallada
    │   ├── IMPLEMENTACION_ORACLE_COMPLETA.md
    │   └── ORACLE_CONFIGURATION_SUMMARY.md
    │
    ├── scripts/                        🔧 SCRIPTS AUTOMATIZADOS
    │   ├── docker-start.sh            ← Inicia servicios
    │   ├── docker-stop.sh             ← Detiene servicios
    │   └── test-oracle-from-docker.sh ← Test de Oracle
    │
    ├── config/                         ⚙️ CONFIGURACIÓN
    │   ├── nginx/
    │   │   ├── nginx.conf             (Config principal)
    │   │   └── conf.d/
    │   │       └── default.conf       (Virtual host)
    │   └── php/
    │       └── php.ini                (Configuración PHP)
    │
    └── oracle/                         🗄️ ORACLE (OPCIONAL)
        ├── instantclient-basic-linux.x64-11.2.0.4.0.zip
        └── instantclient-sdk-linux.x64-11.2.0.4.0.zip
```

---

## 📖 ARCHIVOS PRINCIPALES

### EN RAÍZ

#### DOCKER.md
- Guía rápida de inicio
- Comando `bash start-docker.sh` y `bash stop-docker.sh`
- Enlaces a documentación completa
- **Leer esto primero**

#### start-docker.sh
- Script wrapper que ejecuta `docker/scripts/docker-start.sh`
- Se puede ejecutar desde cualquier lugar
- Maneja automáticamente las rutas

#### stop-docker.sh
- Script wrapper que ejecuta `docker/scripts/docker-stop.sh`
- Detiene todos los servicios
- Ejecutable desde cualquier lugar

#### .dockerignore
- Archivos a excluir de la imagen Docker
- Reduce tamaño de imagen
- Mejora build

#### .env
- Configuración del ambiente
- Variables de base de datos, Oracle, etc.
- **NO commitear a Git**

---

### EN docker/

#### README.md
- Documentación principal de Docker
- Explicación de servicios
- Comandos útiles
- Referencias

#### Dockerfile
- Define la imagen Docker
- PHP 8.4-FPM
- OCI8 compilado
- Todas las extensiones necesarias

#### docker-compose.yml
- Define orquestación de servicios
- 5 servicios: PHP, Nginx, MySQL, Redis, Mailhog
- Redes y volúmenes
- Variables de ambiente

---

## 📚 DOCUMENTACIÓN (docker/docs/)

### SETUP_COMPLETO.md ⭐
**Comienza aquí**
- Tabla de contenidos
- Inicio rápido
- Configuración de Docker
- Configuración de Oracle
- Verificación
- Uso real

### DOCKER_SETUP.md
**Configuración detallada de Docker**
- Servicios incluidos
- Variables de entorno
- Pruebas
- Troubleshooting

### IMPLEMENTACION_ORACLE_COMPLETA.md
**Configuración completa de Oracle**
- Cambios realizados
- Componentes verificados
- Test de conectividad
- Cómo usar en controladores

### ORACLE_CONFIGURATION_SUMMARY.md
**Resumen ejecutivo**
- Problemas encontrados
- Recomendaciones
- Checklist

---

## 🔧 SCRIPTS (docker/scripts/)

### docker-start.sh
```bash
# Ejecutable desde docker/scripts/ o desde raíz
bash docker/scripts/docker-start.sh
bash start-docker.sh  # Desde raíz
```

**Hace:**
- Verifica que Docker esté instalado
- Levanta todos los servicios
- Espera a que MySQL esté listo
- Intenta conectar con red de Oracle
- Muestra URLs de acceso

### docker-stop.sh
```bash
bash docker/scripts/docker-stop.sh
bash stop-docker.sh  # Desde raíz
```

**Hace:**
- Detiene todos los servicios
- Preserva volúmenes

### test-oracle-from-docker.sh
```bash
bash docker/scripts/test-oracle-from-docker.sh
```

**Hace:**
- Test de conectividad a Oracle
- Desde dentro del contenedor Docker
- Verifica que funcione correctamente

---

## ⚙️ CONFIGURACIÓN (docker/config/)

### nginx/
- `nginx.conf` - Configuración principal
- `conf.d/default.conf` - Virtual host para Laravel

### php/
- `php.ini` - Configuración de PHP
  - Memory: 512M
  - Upload: 100M
  - Timezone: Europe/Paris
  - OCI8 habilitado

---

## 🗄️ ORACLE (docker/oracle/)

**Opcional - Instant Client 11.2**

Si quieres compilar OCI8 en la imagen:
1. Descarga: `instantclient-basic-linux.x64-11.2.0.4.0.zip`
2. Descarga: `instantclient-sdk-linux.x64-11.2.0.4.0.zip`
3. Copia a `docker/oracle/`
4. Reconstruye: `docker-compose -f docker/docker-compose.yml build --no-cache`

---

## 🚀 FLUJO DE USO

### Desde raíz

```bash
cd /Users/developert/Herd/manager

# 1. Leer guía
cat DOCKER.md

# 2. Iniciar
bash start-docker.sh

# 3. Abrir navegador
open http://localhost:8080

# 4. Para más info
cat docker/docs/SETUP_COMPLETO.md

# 5. Detener
bash stop-docker.sh
```

### Desde docker/

```bash
cd /Users/developert/Herd/manager/docker

# Leer documentación
cat README.md
cat docs/SETUP_COMPLETO.md

# Ejecutar scripts
bash scripts/docker-start.sh
bash scripts/docker-stop.sh
bash scripts/test-oracle-from-docker.sh
```

---

## 📋 VENTAJAS DE ESTA ESTRUCTURA

✅ **Organizado**: Todo Docker en una carpeta
✅ **Limpio**: Raíz sin clutter
✅ **Documentado**: 4 guías en `docs/`
✅ **Escalable**: Fácil de añadir más servicios
✅ **Portátil**: Puedes copiar `docker/` a otro proyecto
✅ **Ejecutables**: Scripts desde cualquier lugar
✅ **Legible**: Estructura clara y lógica

---

## 🔍 REFERENCIAS RÁPIDAS

| Necesito... | Ir a... |
|------------|---------|
| Iniciar Docker | `bash start-docker.sh` |
| Detener Docker | `bash stop-docker.sh` |
| Guía rápida | `cat DOCKER.md` |
| Documentación completa | `cat docker/docs/SETUP_COMPLETO.md` |
| Modificar Dockerfile | `docker/Dockerfile` |
| Cambiar servicios | `docker/docker-compose.yml` |
| Configurar Nginx | `docker/config/nginx/` |
| Configurar PHP | `docker/config/php/php.ini` |
| Ejecutar test | `bash docker/scripts/test-oracle-from-docker.sh` |

---

## ✨ CONCLUSIÓN

**Toda la configuración de Docker está centralizada en `docker/`**

- Scripts wrapper en raíz para comodidad
- Documentación completa en `docker/docs/`
- Configuración en `docker/config/`
- Scripts automáticos en `docker/scripts/`

**Para empezar**: `bash start-docker.sh`

