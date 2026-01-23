# 📑 Índice Completo - Configuración de Deployment

**Proyecto**: Laravel Reverdezcámonos
**Ruta**: `/home2/webadminpruebas/web`
**Fecha**: 12 de Enero 2026

---

## 🎯 Inicio Rápido

### Para administradores que necesitan instalar YA MISMO:

1. **Lee**: [`README.md`](README.md) (5 minutos)
2. **Ejecuta**: `sudo bash QUICK_SETUP.sh` (instalación automática)
3. **Verifica**: `bash VERIFY_INSTALLATION.sh` (validación)

### Para entender qué se está configurando:

- Lee [`ARCHITECTURE.md`](ARCHITECTURE.md) para entender la arquitectura

---

## 📚 Documentación Completa

### Guías Principales

| Archivo | Descripción | Tiempo | Para quién |
|---------|-------------|--------|-----------|
| **[README.md](README.md)** | Introducción y resumen | 5 min | Administrador |
| **[INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)** | Guía paso a paso completa | 20 min | Administrador / DevOps |
| **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** | Comandos más usados | Referencia | Administrador / DevOps |
| **[ARCHITECTURE.md](ARCHITECTURE.md)** | Flujos y diagramas | 15 min | Arquitecto / DevOps |

---

## 🛠️ Scripts Ejecutables

### Instalación & Verificación

```bash
# Instalación automática (RECOMENDADO)
sudo bash ./QUICK_SETUP.sh

# Verificar instalación
bash ./VERIFY_INSTALLATION.sh
```

### Scripts Internos

| Script | Ubicación | Función | Ejecutor |
|--------|-----------|---------|----------|
| **scheduler.sh** | `scripts/` | Ejecuta scheduler cada minuto | systemd / Supervisor |

---

## ⚙️ Archivos de Configuración Systemd

### Servicios Systemd

Ubicación: `/home2/webadminpruebas/web/deployment/systemd/`

Se copian a: `/etc/systemd/system/` durante la instalación

| Archivo | Servicio | Puerto | Función |
|---------|----------|--------|---------|
| **reverb.service** | reverb | 8080 | WebSocket - Comunicación en tiempo real |
| **queue-worker.service** | queue-worker | - | Procesa trabajos en background |
| **scheduler.service** | scheduler | - | Ejecuta tareas programadas cada minuto |

**Ver contenido:**
```bash
cat deployment/systemd/reverb.service
cat deployment/systemd/queue-worker.service
cat deployment/systemd/scheduler.service
```

---

## ⚙️ Archivos de Configuración Supervisor

### Configuración Supervisor

Ubicación: `/home2/webadminpruebas/web/deployment/supervisor/`

Se copian a: `/etc/supervisor/conf.d/` durante la instalación

| Archivo | Proceso | Función |
|---------|---------|---------|
| **laravel-reverb.conf** | laravel-reverb | WebSocket server |
| **laravel-queue-worker.conf** | laravel-queue-worker:* | 4 workers en paralelo |
| **laravel-scheduler.conf** | laravel-scheduler | Scheduler |

**Ver contenido:**
```bash
cat deployment/supervisor/laravel-reverb.conf
cat deployment/supervisor/laravel-queue-worker.conf
cat deployment/supervisor/laravel-scheduler.conf
```

---

## 📋 Estructura de Carpetas

```
/home2/webadminpruebas/web/
│
└── deployment/
    ├── README.md                           ← COMIENZA AQUÍ
    ├── INDEX.md                            ← Este archivo
    ├── INSTALLATION_GUIDE.md               ← Guía completa
    ├── QUICK_REFERENCE.md                  ← Comandos rápidos
    ├── ARCHITECTURE.md                     ← Diagramas
    │
    ├── QUICK_SETUP.sh                      ← Script automático
    ├── VERIFY_INSTALLATION.sh              ← Verificación
    │
    ├── systemd/
    │   ├── reverb.service
    │   ├── queue-worker.service
    │   └── scheduler.service
    │
    ├── supervisor/
    │   ├── laravel-reverb.conf
    │   ├── laravel-queue-worker.conf
    │   └── laravel-scheduler.conf
    │
    └── scripts/
        └── scheduler.sh
```

---

## 🚀 Flujo de Instalación

### Paso 1: Seleccionar método
```
systemd (RECOMENDADO) ← Más ligero, integrado en el SO
     vs
Supervisor          ← Más control, UI web opcional
```

### Paso 2: Preparar el sistema
```bash
# Script automático hace esto:
- Crear directorios de logs
- Hacer scripts ejecutables
- Copiar archivos de configuración
```

### Paso 3: Habilitar servicios
```bash
# systemd
sudo systemctl enable reverb.service
sudo systemctl enable queue-worker.service
sudo systemctl enable scheduler.service

# Supervisor
sudo supervisorctl reread
sudo supervisorctl update
```

### Paso 4: Iniciar servicios
```bash
# systemd
sudo systemctl start reverb.service queue-worker.service scheduler.service

# Supervisor
sudo supervisorctl start laravel-reverb
sudo supervisorctl start laravel-queue-worker:*
sudo supervisorctl start laravel-scheduler
```

### Paso 5: Verificar
```bash
# systemd
sudo systemctl status reverb.service
sudo journalctl -u reverb.service -f

# Supervisor
sudo supervisorctl status
sudo tail -f /var/log/supervisor/laravel-reverb.log
```

---

## 🔍 Checklist de Configuración

### Antes de empezar
- [ ] Tienes acceso root/sudo
- [ ] Lectura de [README.md](README.md)
- [ ] Verificaste archivo `.env` en `/home2/webadminpruebas/web/`

### Instalación
- [ ] Ejecutaste `QUICK_SETUP.sh` O copiaste archivos manualmente
- [ ] Recargaste systemd (`systemctl daemon-reload`)
- [ ] Habilitaste servicios (`systemctl enable`)
- [ ] Iniciaste servicios (`systemctl start`)

### Verificación
- [ ] Apache está corriendo
- [ ] PHP-FPM está corriendo
- [ ] Reverb escucha puerto 8080
- [ ] Queue worker está procesando
- [ ] Scheduler se ejecuta cada minuto
- [ ] No hay errores en logs

### Post-instalación
- [ ] Guardaste `QUICK_REFERENCE.md` para consultas
- [ ] Configuraste rotación de logs (opcional)
- [ ] Configuraste alertas (opcional)
- [ ] Documentaste cambios en README local

---

## 📊 Componentes Instalados

### Componentes Ya Configurados ✅
- Apache2 (Web Server)
- PHP-FPM 8.4 (Application Runtime)
- MySQL/MariaDB (Base de datos)

### Componentes Nuevos 🆕
- **Reverb WebSocket Server** - Comunicación en tiempo real
- **Queue Worker** - Procesos en background
- **Scheduler** - Tareas programadas cada minuto

---

## 🔧 Configuración Requerida en .env

```env
# Broadcasting
BROADCAST_CONNECTION=reverb
BROADCAST_DRIVER=reverb

# Reverb
REVERB_APP_ID=123456
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=manager.test
REVERB_PORT=8080
REVERB_SCHEME=https

# Queue
QUEUE_CONNECTION=database

# Scheduler
# Automático, no requiere cron
```

**Nota**: La mayoría ya está configurada en tu `.env`

---

## 📞 Referencias Rápidas

### Comandos Más Frecuentes

```bash
# Ver estado
sudo systemctl status reverb

# Ver logs
sudo journalctl -u reverb -f

# Reiniciar
sudo systemctl restart reverb

# Para Supervisor
sudo supervisorctl status
sudo tail -f /var/log/supervisor/laravel-reverb.log
```

### Documentación Externa

- **Laravel**: https://laravel.com/docs
- **Reverb**: https://reverb.laravel.com
- **Queue**: https://laravel.com/docs/queues
- **Scheduler**: https://laravel.com/docs/scheduling
- **systemd**: https://systemd.io/

---

## 🆘 Troubleshooting Rápido

### El servicio no inicia
```bash
sudo systemctl status reverb.service -l
sudo journalctl -u reverb.service -n 50 --no-pager
```

### Puerto ya en uso
```bash
sudo lsof -i :8080
# Cambiar REVERB_PORT en .env
```

### Permisos incorrectos
```bash
sudo chown -R www-data:www-data /home2/webadminpruebas/web
sudo chmod -R 755 /home2/webadminpruebas/web
sudo chmod -R 775 /home2/webadminpruebas/web/storage
```

**Para problemas más complejos**: Ver sección Troubleshooting en [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)

---

## 📚 Lecturas Recomendadas por Rol

### Para Administrador del Servidor
1. [README.md](README.md) - Introducción
2. [QUICK_SETUP.sh](QUICK_SETUP.sh) - Instalación
3. [QUICK_REFERENCE.md](QUICK_REFERENCE.md) - Comandos diarios

### Para DevOps / Arquitecto
1. [ARCHITECTURE.md](ARCHITECTURE.md) - Diagramas y flujos
2. [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md) - Configuración completa
3. Archivos `.service` y `.conf` - Detalles técnicos

### Para Desarrollador (mantenimiento)
1. [QUICK_REFERENCE.md](QUICK_REFERENCE.md) - Testing local
2. [ARCHITECTURE.md](ARCHITECTURE.md) - Entender flujos
3. Archivos de configuración - Ajustes

---

## 🔐 Notas de Seguridad

- ✅ Servicios se ejecutan como `www-data` (usuario limitado)
- ✅ Permisos correctos: 755 (lectura) y 775 (escritura en storage)
- ✅ Firewall debe permitir puerto 8080 desde hosts autorizados
- ⚠️ Proteger `.env` - No incluir en repositorio público
- ⚠️ Cambiar claves de Reverb en producción

---

## 📝 Control de Versiones

| Versión | Fecha | Cambios |
|---------|-------|---------|
| 1.0 | 12/01/2026 | Creación inicial - systemd + Supervisor |

---

## 🤝 Soporte

Para preguntas o problemas:
1. Consulta [QUICK_REFERENCE.md](QUICK_REFERENCE.md)
2. Revisa [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md) sección Troubleshooting
3. Contacta al equipo de desarrollo

---

**Última actualización**: 12 de Enero 2026
**Mantener este archivo actualizado con cambios de configuración**
