# 🌉 Docker + Herd Integration

Esta configuración permite que **Docker funcione con Herd**, usando la configuración de DNS `.test` y redes de Herd.

## 🚀 Quick Start

### Opción 1: Script Automático (RECOMENDADA)

```bash
cd /Users/developert/Herd/manager
bash docker/scripts/herd-docker-bridge.sh
```

Este script:
- ✅ Inicia todos los servicios Docker
- ✅ Espera a que estén listos
- ✅ Muestra las URLs de acceso
- ✅ Integra automáticamente con Herd

### Opción 2: Manual

```bash
cd /Users/developert/Herd/manager/docker
docker-compose up -d
```

## 📍 Acceso a la Aplicación

### Local (Sin Herd)
```
http://localhost
```

### Via Herd (RECOMENDADO)
```
http://managers.test
```

**Cómo configurar:**

1. **Edita tu archivo hosts:**
   ```bash
   sudo nano /etc/hosts
   ```

2. **Agrega esta línea:**
   ```
   127.0.0.1    managers.test
   ```

3. **Guarda (Ctrl+O, Enter, Ctrl+X)**

4. **Accede a:** `http://managers.test`

---

## 🔧 Configuración Actualizada

### docker-compose.yml

✅ **DNS configurado:**
- Resuelve dominios `.test` via Herd
- Fallback a 8.8.8.8

✅ **Puertos:**
- HTTP: 80 (localhost)
- HTTPS: 443 (localhost, si lo necesitas)

✅ **Dominio:**
- `APP_URL=http://managers.test`
- `SERVER_NAME=managers.test`

### nginx/conf.d/default.conf

✅ **Server names:**
```nginx
server_name managers.test localhost 127.0.0.1;
```

✅ **FastCGI:**
```nginx
fastcgi_pass app:9000;
```

---

## 💡 Diferencias con Herd Nativo

| Aspecto | Herd | Docker |
|--------|------|--------|
| **Performance** | ⚡ Nativo (rápido) | 🐳 Virtualizado |
| **Consistencia** | Local | Linux (linux/amd64) |
| **Aislamiento** | Opcional | Completo |
| **Oracle DB** | ✅ Fácil | ⏳ Requiere config |
| **MySQL** | ✅ Integrado | ✅ Contenedor |

---

## 🎯 Cuando Usar Docker

✅ **Usar Docker cuando:**
- Necesitas entorno completamente aislado
- Trabajas en equipo con diferentes configuraciones
- Quieres que sea reproducible en producción
- Necesitas Oracle DB configurado

✅ **Usar Herd cuando:**
- Desarrollo local rápido
- No necesitas aislamiento completo
- Prefieres performance nativa

---

## 📚 Comandos Útiles

### Iniciar
```bash
bash docker/scripts/herd-docker-bridge.sh
```

### Ver Logs
```bash
docker-compose -f docker/docker-compose.yml logs -f app
```

### Acceder a Bash
```bash
docker-compose -f docker/docker-compose.yml exec app bash
```

### Ejecutar Artisan
```bash
docker-compose -f docker/docker-compose.yml exec app php artisan tinker
```

### Parar Servicios
```bash
docker-compose -f docker/docker-compose.yml down
```

### Ver Estado
```bash
docker-compose -f docker/docker-compose.yml ps
```

---

## 🔍 Troubleshooting

### Problema: `managers.test` no se resuelve

**Solución:**
```bash
# 1. Verifica que el host está configurado
cat /etc/hosts | grep managers.test

# 2. Limpia caché de DNS (Mac)
sudo dscacheutil -flushcache

# 3. Prueba conectividad
ping managers.test
```

### Problema: Puerto 80 ya está en uso

**Solución:**
```bash
# Cambia el puerto en docker-compose.yml:
# De:   - "80:80"
# A:    - "8080:80"

# Luego accede a: http://localhost:8080
```

### Problema: Docker no conecta con Herd

**Solución:**
El DNS de Docker está configurado para resolver a 127.0.0.1. Asegúrate que:
- Herd está corriendo
- Tu `/etc/hosts` tiene la entrada correcta
- El contenedor pode hace ping a managers.test

```bash
docker-compose -f docker/docker-compose.yml exec app ping managers.test
```

---

## 🌐 Arquitectura

```
┌─ Tu Máquina (127.0.0.1) ─┐
│                           │
│  ┌─ Herd ──────────────┐  │
│  │ • DNS               │  │
│  │ • managers.test → :80│  │
│  └─────────────────────┘  │
│                           │
│  ┌─ Docker ────────────┐  │
│  │ • Nginx :80         │  │
│  │ • PHP-FPM :9000     │  │
│  │ • MySQL :3307       │  │
│  └─────────────────────┘  │
│                           │
└───────────────────────────┘

Flujo: managers.test → Herd DNS → 127.0.0.1:80 → Nginx (Docker)
```

---

## ✅ Checklist de Configuración

- [ ] Docker está corriendo (`docker ps`)
- [ ] `/etc/hosts` tiene `127.0.0.1 managers.test`
- [ ] Puedes hacer ping a `managers.test`
- [ ] `http://managers.test` carga en el navegador
- [ ] Puedes ver logs con `docker-compose logs -f app`
- [ ] Artisan funciona: `docker-compose exec app php artisan --version`

---

## 📝 Próximos Pasos

1. ✅ Ejecuta el script de inicio
2. ✅ Configura `/etc/hosts`
3. ✅ Prueba acceso a `http://managers.test`
4. ✅ Ejecuta migraciones si es necesario
5. ✅ Comienza a desarrollar

---

**¡Docker + Herd está listo!** 🚀
