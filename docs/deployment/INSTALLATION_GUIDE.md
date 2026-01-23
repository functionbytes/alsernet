# Guía de Instalación - Configuración Productiva de Laravel "Reverdezcámonos"

**Fecha**: 12 de Enero 2026
**Proyecto**: Reverdezcámonos (Manager)
**Ruta**: `/home2/webadminpruebas/web`
**Usuario de Sistema**: `www-data`
**Grupo de Sistema**: `www-data`

## 📋 Resumen General

Esta aplicación Laravel requiere ejecutar varios procesos en paralelo de forma permanente:

1. **Web Server** (Apache + PHP-FPM) - ✅ YA CONFIGURADO
2. **Reverb WebSocket Server** - Comunicación en tiempo real (NUEVO)
3. **Queue Worker** - Procesos en segundo plano (NUEVO)
4. **Scheduler** - Tareas programadas cada minuto (NUEVO)

Los procesos se pueden gestionar con:
- **Opción A**: systemd (RECOMENDADO para producción)
- **Opción B**: Supervisor (Alternativa)

---

## 🔧 Opción A: Instalación con systemd (RECOMENDADO)

### Paso 1: Hacer el script del scheduler ejecutable

```bash
sudo chmod +x /home2/webadminpruebas/web/deployment/scripts/scheduler.sh
```

### Paso 2: Crear directorio de logs

```bash
sudo mkdir -p /var/log/laravel
sudo chown www-data:www-data /var/log/laravel
sudo chmod 755 /var/log/laravel
```

### Paso 3: Copiar archivos de servicio systemd

```bash
# Copiar los archivos .service a systemd
sudo cp /home2/webadminpruebas/web/deployment/systemd/*.service /etc/systemd/system/

# Recargar la configuración de systemd
sudo systemctl daemon-reload
```

### Paso 4: Habilitar e iniciar los servicios

```bash
# Habilitar servicios para que se inicien al reiniciar
sudo systemctl enable reverb.service
sudo systemctl enable queue-worker.service
sudo systemctl enable scheduler.service

# Iniciar los servicios
sudo systemctl start reverb.service
sudo systemctl start queue-worker.service
sudo systemctl start scheduler.service
```

### Paso 5: Verificar que los servicios estén ejecutándose

```bash
# Ver estado de todos los servicios
sudo systemctl status reverb.service
sudo systemctl status queue-worker.service
sudo systemctl status scheduler.service

# Ver todos los servicios de Laravel juntos
sudo systemctl status 'reverb.service' 'queue-worker.service' 'scheduler.service'
```

### Paso 6: Ver logs en tiempo real

```bash
# Reverb WebSocket
sudo journalctl -u reverb.service -f

# Queue Worker
sudo journalctl -u queue-worker.service -f

# Scheduler
sudo journalctl -u scheduler.service -f
```

### 🛑 Comandos útiles de systemd

```bash
# Detener un servicio
sudo systemctl stop reverb.service

# Reiniciar un servicio
sudo systemctl restart queue-worker.service

# Deshabilitar al inicio (sin parar)
sudo systemctl disable scheduler.service

# Recargar configuración después de cambios
sudo systemctl daemon-reload
sudo systemctl restart reverb.service

# Ver logs de hoy
sudo journalctl -u reverb.service --since today

# Ver últimas 50 líneas
sudo journalctl -u queue-worker.service -n 50
```

---

## 🔧 Opción B: Instalación con Supervisor (Alternativa)

### Paso 1: Instalar Supervisor

```bash
sudo apt-get update
sudo apt-get install supervisor
```

### Paso 2: Copiar archivos de configuración

```bash
# Copiar configuraciones de Supervisor
sudo cp /home2/webadminpruebas/web/deployment/supervisor/*.conf /etc/supervisor/conf.d/

# Actualizar la configuración de Supervisor
sudo supervisorctl reread
sudo supervisorctl update
```

### Paso 3: Iniciar los procesos

```bash
# Iniciar todos los procesos
sudo supervisorctl start laravel-reverb
sudo supervisorctl start laravel-queue-worker:*
sudo supervisorctl start laravel-scheduler

# Ver estado
sudo supervisorctl status
```

### Paso 4: Habilitar Supervisor en el inicio

```bash
sudo systemctl enable supervisor.service
sudo systemctl start supervisor.service
```

### 🛑 Comandos útiles de Supervisor

```bash
# Ver estado de todos los procesos
sudo supervisorctl status

# Reiniciar un proceso
sudo supervisorctl restart laravel-queue-worker:00

# Reiniciar todo
sudo supervisorctl restart all

# Ver logs
sudo tail -f /var/log/supervisor/laravel-reverb.log
sudo tail -f /var/log/supervisor/laravel-queue-worker.log
sudo tail -f /var/log/supervisor/laravel-scheduler.log

# Recargar configuración
sudo supervisorctl reread
sudo supervisorctl update
```

---

## 📊 Configuración Recomendada por Entorno

### Desarrollo (Local)
```bash
# Con systemd
sudo systemctl start reverb.service
sudo systemctl start queue-worker.service
sudo systemctl start scheduler.service

# O ejecutar manualmente en terminales separadas:
php artisan reverb:start --host=0.0.0.0 --port=8080
php artisan queue:work
php /path/to/deployment/scripts/scheduler.sh
```

### Producción (Servidor)
- **Usar systemd** (más seguro y ligero)
- **Configurar límites de recursos**
- **Monitorear con tools como Monit o Netdata**

---

## 🔍 Monitoreo y Diagnóstico

### Verificar que los procesos están corriendo

```bash
# Listar procesos de PHP relacionados
ps aux | grep -E 'reverb|queue|scheduler'

# Verificar puertos ocupados
sudo netstat -tlnp | grep -E '8080|:80|:443'
sudo ss -tlnp | grep -E '8080'

# Verificar procesos de Apache/PHP-FPM
ps aux | grep apache2
ps aux | grep php-fpm
```

### Revisar variables de entorno

```bash
# Verificar configuración de Reverb en .env
grep REVERB /home2/webadminpruebas/web/.env

# Verificar que QUEUE_CONNECTION está correctamente configurado
grep QUEUE_CONNECTION /home2/webadminpruebas/web/.env
```

### Verificar logs de Apache

```bash
# Logs de acceso
sudo tail -f /var/log/apache2/access.log

# Logs de errores
sudo tail -f /var/log/apache2/error.log

# Error específico del sitio
sudo tail -f /var/log/apache2/webadminpruebas.a-alvarez.com-error.log
```

---

## 🧪 Testeo Manual de Procesos

### 1. Testear Reverb WebSocket

```bash
# En el servidor
php artisan reverb:start --host=0.0.0.0 --port=8080

# En otra terminal, verificar que escucha
curl -v http://localhost:8080/app/local-key

# Resultado esperado: Conexión WebSocket aceptada
```

### 2. Testear Queue Worker

```bash
# Ejecutar manualmente
php artisan queue:work --queue=default,emails,notifications

# En otra terminal, enviar un job de prueba
php artisan tinker
> \App\Jobs\TestJob::dispatch();
> exit

# Debería procesar el job en el worker
```

### 3. Testear Scheduler

```bash
# Ejecutar manualmente
php artisan schedule:run

# O usar el script
bash /home2/webadminpruebas/web/deployment/scripts/scheduler.sh
```

---

## 📋 Archivos de Configuración Referencia

### Ubicaciones de archivos creados

```
/home2/webadminpruebas/web/
├── deployment/
│   ├── systemd/
│   │   ├── reverb.service
│   │   ├── queue-worker.service
│   │   └── scheduler.service
│   ├── supervisor/
│   │   ├── laravel-reverb.conf
│   │   ├── laravel-queue-worker.conf
│   │   └── laravel-scheduler.conf
│   └── scripts/
│       └── scheduler.sh
└── .env (configuración existente)
```

### Configuración del .env requerida

```env
# Broadcasting (WebSocket)
BROADCAST_CONNECTION=reverb
BROADCAST_DRIVER=reverb
REVERB_APP_ID=123456
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=manager.test
REVERB_PORT=8080
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# Queue
QUEUE_CONNECTION=database

# Scheduler
# Se ejecuta automáticamente cada minuto
```

---

## ⚠️ Troubleshooting

### Problema: El servicio reverb.service no inicia

**Solución 1**: Verificar el estado del servicio
```bash
sudo systemctl status reverb.service
sudo journalctl -u reverb.service -n 50
```

**Solución 2**: Verificar permisos
```bash
sudo chown www-data:www-data /home2/webadminpruebas/web -R
sudo chmod -R 775 /home2/webadminpruebas/web/storage
```

**Solución 3**: Verificar puerto 8080 disponible
```bash
sudo netstat -tlnp | grep 8080
# Si está en uso, cambiar REVERB_PORT en .env
```

### Problema: Queue no procesa jobs

**Solución 1**: Verificar conexión a DB
```bash
php artisan tinker
> DB::connection()->getPdo();
> exit
```

**Solución 2**: Verificar tabla de colas
```bash
php artisan queue:failed  # Ver jobs fallidos
php artisan queue:clear
```

**Solución 3**: Revisar logs
```bash
sudo journalctl -u queue-worker.service -f
tail -f /var/log/laravel/laravel.log
```

### Problema: Scheduler no ejecuta tareas

**Solución 1**: Verificar script tiene permisos ejecutables
```bash
ls -la /home2/webadminpruebas/web/deployment/scripts/scheduler.sh
# Debería empezar con -rwx
```

**Solución 2**: Verificar logs del scheduler
```bash
sudo journalctl -u scheduler.service -f
tail -f /var/log/laravel/scheduler.log
```

**Solución 3**: Ejecutar manualmente para testear
```bash
php artisan schedule:run
```

---

## 🔐 Consideraciones de Seguridad

### 1. Proteger puerto 8080 (Reverb)

```bash
# Permitir solo desde localhost (desarrollo)
sudo ufw allow from 127.0.0.1 to any port 8080

# O solo desde IPs específicas (producción)
sudo ufw allow from 192.168.1.0/24 to any port 8080

# Usar proxy inverso (nginx) en producción
```

### 2. Configurar firewall

```bash
# Ver puertos abiertos
sudo netstat -tlnp | grep LISTEN

# Configurar ufw (Ubuntu/Debian)
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp
sudo ufw enable
```

### 3. Permisos correctos

```bash
# Asegurar que www-data es propietario
sudo chown -R www-data:www-data /home2/webadminpruebas/web

# Permisos correctos
sudo chmod -R 755 /home2/webadminpruebas/web
sudo chmod -R 775 /home2/webadminpruebas/web/storage
sudo chmod -R 775 /home2/webadminpruebas/web/bootstrap/cache
```

---

## 📈 Monitoreo Avanzado

### Instalación de Monit (Opcional)

```bash
sudo apt-get install monit

# Crear archivo de configuración
sudo nano /etc/monit/conf.d/laravel.conf
```

Contenido:
```
check process reverb with pidfile /var/run/reverb.pid
    start program = "/bin/systemctl start reverb.service"
    stop program = "/bin/systemctl stop reverb.service"
    if failed host localhost port 8080 then alert

check process queue-worker with pidfile /var/run/queue-worker.pid
    start program = "/bin/systemctl start queue-worker.service"
    stop program = "/bin/systemctl stop queue-worker.service"
```

### Monitoreo con New Relic o Datadog

Estos servicios pueden monitorear los procesos PHP automáticamente.

---

## 🚀 Checklist de Configuración Productiva

- [ ] Apache + PHP-FPM configurado ✅
- [ ] Reversión de WebSocket en producción:
  - [ ] Puerto 8080 abierto (o ajustar en .env)
  - [ ] HTTPS habilitado para WSS (WebSocket Secure)
  - [ ] DNS resuelve correctamente manager.test
- [ ] Queue Worker:
  - [ ] Base de datos con tabla de colas
  - [ ] QUEUE_CONNECTION=database en .env
  - [ ] Permisos de escritura en storage/logs
- [ ] Scheduler:
  - [ ] Script scheduler.sh tiene permisos ejecutables
  - [ ] Directorio /var/log/laravel existe
  - [ ] cronjob alternativa deshabilitada (si existe)
- [ ] Logging:
  - [ ] Logs se escriben en /var/log/laravel
  - [ ] Rotación de logs configurada (logrotate)
- [ ] Seguridad:
  - [ ] Firewall configurado correctamente
  - [ ] Permisos de carpetas correctos
  - [ ] .env protegido de lectura pública
- [ ] Monitoreo:
  - [ ] Alertas configuradas para procesos caídos
  - [ ] Logs monitoreados regularmente

---

## 📞 Soporte

Para más información sobre Laravel, consulta:
- Documentación Oficial: https://laravel.com/docs
- Reverb: https://reverb.laravel.com
- Queue: https://laravel.com/docs/queues
- Scheduler: https://laravel.com/docs/scheduling

Para soporte específico del proyecto, contactar al equipo de desarrollo.

---

**Última actualización**: 12 de Enero 2026
**Versión**: 1.0
