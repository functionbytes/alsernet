# Referencia Rápida de Comandos

## 🚀 Instalación Rápida (PRIMERA VEZ)

```bash
# Opción A: Con systemd (RECOMENDADO)
sudo bash /home2/webadminpruebas/web/deployment/QUICK_SETUP.sh

# Opción B: Manual systemd
sudo chmod +x /home2/webadminpruebas/web/deployment/scripts/scheduler.sh
sudo mkdir -p /var/log/laravel && sudo chown www-data:www-data /var/log/laravel
sudo cp /home2/webadminpruebas/web/deployment/systemd/*.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable reverb queue-worker scheduler
sudo systemctl start reverb queue-worker scheduler
```

---

## 📊 systemd - Comandos Principales

### Ver Estado
```bash
sudo systemctl status reverb              # Estado de un servicio
sudo systemctl status reverb queue-worker scheduler  # Estado de varios
```

### Iniciar/Parar
```bash
sudo systemctl start reverb               # Iniciar servicio
sudo systemctl stop reverb                # Detener servicio
sudo systemctl restart reverb             # Reiniciar servicio
sudo systemctl reload reverb              # Recargar (sin parar)
```

### Habilitar/Deshabilitar
```bash
sudo systemctl enable reverb              # Habilitar en boot
sudo systemctl disable reverb             # Deshabilitar en boot
sudo systemctl is-enabled reverb          # Verificar si está habilitado
```

### Logs
```bash
# Logs en tiempo real
sudo journalctl -u reverb -f              # Seguir logs en vivo (-f = follow)
sudo journalctl -u queue-worker -f

# Últimas líneas
sudo journalctl -u reverb -n 50           # Últimas 50 líneas
sudo journalctl -u scheduler -n 100

# Con timestamp
sudo journalctl -u reverb -f -o short-iso

# Filtrar por nivel
sudo journalctl -u reverb -p err          # Solo errores
sudo journalctl -u reverb -p warn         # Warnings y errores

# Por fecha
sudo journalctl -u reverb --since today
sudo journalctl -u reverb --since "2 hours ago"
sudo journalctl -u reverb --since "2026-01-12 10:00:00" --until "2026-01-12 11:00:00"
```

### Daemon Reload
```bash
sudo systemctl daemon-reload              # SIEMPRE después de editar .service
```

---

## 👔 Supervisor - Comandos Principales

### Estado
```bash
sudo supervisorctl status                 # Ver todos los procesos
sudo supervisorctl status laravel-reverb  # Ver proceso específico
```

### Control
```bash
sudo supervisorctl start laravel-reverb
sudo supervisorctl stop laravel-reverb
sudo supervisorctl restart laravel-reverb
sudo supervisorctl restart all            # Reiniciar todo

# Para queue worker con múltiples procesos
sudo supervisorctl start laravel-queue-worker:*
sudo supervisorctl restart laravel-queue-worker:00
```

### Recargar Configuración
```bash
sudo supervisorctl reread                 # Leer nuevos archivos
sudo supervisorctl update                 # Actualizar procesos
```

### Logs
```bash
sudo tail -f /var/log/supervisor/laravel-reverb.log
sudo tail -f /var/log/supervisor/laravel-queue-worker.log
sudo tail -f /var/log/supervisor/laravel-scheduler.log

# Últimas 100 líneas
sudo tail -100 /var/log/supervisor/laravel-reverb.log
```

---

## 🔍 Diagnóstico

### Verificar Procesos Ejecutándose
```bash
# Procesos PHP relacionados
ps aux | grep -E 'reverb|queue|scheduler'

# Procesos de Reverb específicamente
ps aux | grep 'artisan reverb'

# Procesos de Queue
ps aux | grep 'queue:work'

# Procesos de Scheduler
ps aux | grep 'scheduler'
```

### Verificar Puertos
```bash
# Ver puerto 8080 (Reverb)
sudo netstat -tlnp | grep 8080
sudo ss -tlnp | grep 8080
sudo lsof -i :8080

# Ver todos los puertos escuchando
sudo netstat -tlnp | grep LISTEN
```

### Verificar Configuración
```bash
# Variables de Reverb en .env
grep REVERB /home2/webadminpruebas/web/.env

# Variables de Queue
grep QUEUE /home2/webadminpruebas/web/.env

# Broadcast Connection
grep BROADCAST /home2/webadminpruebas/web/.env
```

### Testear Conexión
```bash
# Testear base de datos
cd /home2/webadminpruebas/web
php artisan tinker
# En tinker:
> DB::connection()->getPdo()
> exit

# Testear cache
php artisan tinker
> Cache::put('test', 'value', 1)
> Cache::get('test')
> exit

# Listar jobs en cola
php artisan queue:failed

# Contar jobs
php artisan queue:count
```

---

## 🧪 Testing Manual

### Testear Reverb
```bash
# En terminal 1
php artisan reverb:start --host=0.0.0.0 --port=8080

# En terminal 2
curl -v http://localhost:8080/app/local-key
```

### Testear Queue
```bash
# En terminal 1
cd /home2/webadminpruebas/web
php artisan queue:work --queue=default,emails,notifications

# En terminal 2
php artisan tinker
> \App\Jobs\TestJob::dispatch()
> exit

# Debe procesar en terminal 1
```

### Testear Scheduler
```bash
# Ejecutar una vez
php artisan schedule:run

# O el script
bash /home2/webadminpruebas/web/deployment/scripts/scheduler.sh
```

---

## 🔧 Tareas Comunes

### Cambiar Puerto de Reverb
```bash
# Editar .env
nano /home2/webadminpruebas/web/.env

# Cambiar REVERB_PORT de 8080 a otro puerto (ej: 9000)
# REVERB_PORT=9000

# Reiniciar Reverb
sudo systemctl restart reverb

# O con Supervisor
sudo supervisorctl restart laravel-reverb
```

### Reiniciar Todo
```bash
# systemd
sudo systemctl restart reverb queue-worker scheduler

# Supervisor
sudo supervisorctl restart all
```

### Limpiar Colas
```bash
cd /home2/webadminpruebas/web

# Ver jobs fallidos
php artisan queue:failed

# Eliminar jobs fallidos
php artisan queue:clear

# Eliminar tabla de jobs
php artisan queue:prune-failed
```

### Ver Logs de Aplicación
```bash
# Logs principales
tail -f /home2/webadminpruebas/web/storage/logs/laravel.log

# Logs de hoy
tail -f /home2/webadminpruebas/web/storage/logs/laravel-$(date +%Y-%m-%d).log

# Con grep para filtrar errores
grep ERROR /home2/webadminpruebas/web/storage/logs/laravel.log
grep ERROR /home2/webadminpruebas/web/storage/logs/laravel.log | tail -20
```

---

## 🛑 Troubleshooting

### Servicio no inicia

```bash
# Ver error detallado
sudo systemctl status reverb.service -l
sudo journalctl -u reverb.service -n 50 --no-pager

# Testear comando manualmente
sudo -u www-data php /home2/webadminpruebas/web/artisan reverb:start --host=0.0.0.0 --port=8080
```

### Puerto ya en uso

```bash
# Ver qué está usando el puerto 8080
sudo lsof -i :8080
sudo netstat -tlnp | grep 8080

# Matar el proceso
sudo kill -9 PID

# O cambiar puerto en .env
```

### Permisos de archivo

```bash
# Fijar propietario
sudo chown -R www-data:www-data /home2/webadminpruebas/web

# Permisos correctos
sudo chmod -R 755 /home2/webadminpruebas/web
sudo chmod -R 775 /home2/webadminpruebas/web/storage
sudo chmod -R 775 /home2/webadminpruebas/web/bootstrap/cache
```

### Database connection error

```bash
# Verificar conexión
php artisan tinker
> DB::connection()->getPdo()
> exit

# Comprobar credenciales en .env
cat /home2/webadminpruebas/web/.env | grep DB_

# Verificar servicio MySQL
sudo systemctl status mysql
sudo systemctl status mariadb
```

---

## 📋 Comparación: systemd vs Supervisor

| Aspecto | systemd | Supervisor |
|---------|---------|-----------|
| Complejidad | Simple | Medio |
| Instalación | Sistema operativo | Necesita instalar |
| Performance | Muy ligero | Ligero |
| Logs | journalctl | Archivos |
| Recomendado para | Producción | Desarrollo/Testing |
| Reinicio automático | ✅ Integrado | ✅ Integrado |
| Monitoreo | systemd integrado | UI web opcional |

---

## 🚨 Alertas Importantes

### ❌ NO hacer
```bash
# NO detener Apache directamente si tienes request en curso
sudo systemctl stop apache2

# NO cambiar permisos a 777 (INSEGURO)
chmod 777 /home2/webadminpruebas/web

# NO ejecutar como root (usar www-data)
php artisan queue:work  # MAL
sudo -u www-data php artisan queue:work  # BIEN

# NO confundir las colas
sudo systemctl restart queue-worker  # systemd
sudo supervisorctl restart laravel-queue-worker  # Supervisor
```

### ✅ SI hacer
```bash
# Usar daemon-reload después de cambios
sudo systemctl daemon-reload
sudo systemctl restart reverb

# Revisar logs regularmente
sudo journalctl -u reverb -f

# Monitorear procesos
ps aux | grep queue

# Hacer backup de configuración
cp -r /home2/webadminpruebas/web/deployment /home2/webadminpruebas/web/deployment.backup
```

---

## 📞 Links Útiles

- Laravel Docs: https://laravel.com/docs
- Reverb: https://reverb.laravel.com
- Queue: https://laravel.com/docs/queues
- Scheduler: https://laravel.com/docs/scheduling
- systemd: https://systemd.io/
- Supervisor: http://supervisord.org/

---

**Última actualización**: 12 de Enero 2026
**Mantener actualizado con cambios de configuración**
