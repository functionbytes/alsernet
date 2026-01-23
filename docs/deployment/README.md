# 🚀 Guía de Deployment - Laravel Reverdezcámonos

Bienvenido a la guía de configuración productiva de la aplicación Laravel "Reverdezcámonos".

## 📚 Documentación

Esta carpeta contiene toda la configuración necesaria para ejecutar la aplicación de forma permanente en producción.

### 📖 Documentos Principales

1. **[INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)** ← **COMIENZA AQUÍ**
   - Guía completa paso a paso
   - Configuración con systemd (RECOMENDADO)
   - Configuración con Supervisor (alternativa)
   - Troubleshooting completo

2. **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** ← **REFERENCIA RÁPIDA**
   - Comandos más usados
   - Atajos y tips
   - Comparación systemd vs Supervisor
   - Alertas importantes

---

## ⚡ Instalación Rápida (30 segundos)

### Opción 1: Script Automático (RECOMENDADO)

```bash
sudo bash /home2/webadminpruebas/web/deployment/QUICK_SETUP.sh
```

El script hará todo automáticamente y te pedirá elegir entre systemd o Supervisor.

### Opción 2: Manual con systemd

```bash
# 1. Copiar archivos de servicio
sudo cp /home2/webadminpruebas/web/deployment/systemd/*.service /etc/systemd/system/

# 2. Recargar systemd
sudo systemctl daemon-reload

# 3. Habilitar servicios
sudo systemctl enable reverb.service queue-worker.service scheduler.service

# 4. Iniciar servicios
sudo systemctl start reverb.service queue-worker.service scheduler.service

# 5. Verificar
sudo systemctl status reverb queue-worker scheduler
```

---

## 📂 Estructura de Archivos

```
deployment/
├── README.md (este archivo)
├── INSTALLATION_GUIDE.md (guía completa)
├── QUICK_REFERENCE.md (comandos rápidos)
├── QUICK_SETUP.sh (instalación automática)
├── VERIFY_INSTALLATION.sh (script de verificación)
├── systemd/
│   ├── reverb.service (WebSocket server)
│   ├── queue-worker.service (Queue processor)
│   └── scheduler.service (Scheduler)
├── supervisor/
│   ├── laravel-reverb.conf
│   ├── laravel-queue-worker.conf
│   └── laravel-scheduler.conf
└── scripts/
    └── scheduler.sh (script que ejecuta el scheduler)
```

---

## 🔍 Archivos de Configuración

### systemd Services
Los archivos `.service` se copian a `/etc/systemd/system/` y son gestionados por systemd.
- **reverb.service**: Servidor WebSocket para comunicación en tiempo real
- **queue-worker.service**: Procesa trabajos en background (emails, notificaciones)
- **scheduler.service**: Ejecuta tareas programadas cada minuto

### Supervisor Config
Los archivos `.conf` se copian a `/etc/supervisor/conf.d/` y son gestionados por Supervisor.
- Alternativa a systemd
- Incluye UI web opcional
- Mejor para desarrollo

### Scripts
- **scheduler.sh**: Script bash que ejecuta `php artisan schedule:run` cada minuto
  - Usado tanto por systemd como por Supervisor
  - Requiere permisos ejecutables (chmod +x)

---

## ✅ Verificación de Instalación

Después de instalar, verifica que todo funciona:

```bash
# Script automático de verificación
bash /home2/webadminpruebas/web/deployment/VERIFY_INSTALLATION.sh

# O manualmente:
sudo systemctl status reverb.service
sudo systemctl status queue-worker.service
sudo systemctl status scheduler.service

# Ver logs
sudo journalctl -u reverb.service -f
```

---

## 🔧 Configuración Requerida en .env

**La mayoría ya está configurada. Verifica estos valores:**

```env
# Broadcasting (WebSocket)
BROADCAST_CONNECTION=reverb
BROADCAST_DRIVER=reverb

# Reverb Configuration
REVERB_APP_ID=123456
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=manager.test
REVERB_PORT=8080
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# Queue Configuration
QUEUE_CONNECTION=database

# Scheduler
# Se ejecuta automáticamente cada minuto (no requiere cronjob)
```

---

## 🚀 Próximos Pasos

1. **Lee** `INSTALLATION_GUIDE.md` para entender el proceso completo
2. **Ejecuta** `QUICK_SETUP.sh` para instalación automática
3. **Verifica** con `VERIFY_INSTALLATION.sh` que todo funciona
4. **Guarda** `QUICK_REFERENCE.md` para consultas rápidas

---

## 📞 Comandos Más Usados

```bash
# Ver estado
sudo systemctl status reverb.service

# Ver logs en tiempo real
sudo journalctl -u reverb.service -f

# Reiniciar servicio
sudo systemctl restart queue-worker.service

# Detener servicio
sudo systemctl stop scheduler.service

# Recargar tras cambios en .service
sudo systemctl daemon-reload
sudo systemctl restart reverb.service
```

**Para más comandos:** Ver `QUICK_REFERENCE.md`

---

## 🛠️ Soporte

- **Documentación oficial de Laravel**: https://laravel.com/docs
- **Reverb (WebSocket)**: https://reverb.laravel.com
- **Queue**: https://laravel.com/docs/queues
- **Scheduler**: https://laravel.com/docs/scheduling

---

## 🔐 Seguridad

- Los servicios se ejecutan como `www-data` (usuario web)
- Directorio `/var/log/laravel` protegido con permisos 755
- Archivos de .env protegidos (no readable por web)
- Firewall debe permitir puerto 8080 (Reverb) desde hosts autorizados

---

## 📝 Notas Importantes

- ✅ Apache + PHP-FPM **YA ESTÁ CONFIGURADO**
- 🆕 Este deployment añade Reverb, Queue, y Scheduler
- 🔄 Los servicios se reinician automáticamente ante fallos
- 🚀 Se habilitan automáticamente al reiniciar el servidor
- 📊 Logs disponibles en `/var/log/laravel/` y journalctl

---

**Última actualización**: 12 de Enero 2026
**Versión**: 1.0
**Mantenedor**: Equipo de Desarrollo
