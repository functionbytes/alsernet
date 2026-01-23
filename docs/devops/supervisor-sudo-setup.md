# Configuración de Sudo Passwordless para Supervisor

## Problema
El panel de Supervisor requiere permisos de sudo para ejecutar comandos como `supervisorctl status`, `systemctl restart supervisor`, etc. Actualmente el sistema está requiriendo contraseña, lo que causa errores en las peticiones AJAX.

## Detección Automática de Errores (Nuevo)

El panel de Supervisor ahora detecta automáticamente cuando hay problemas de permisos sudo y:

- ⏸️ **Detiene el auto-refresh** - No sigue intentando cada 5 segundos
- ⚠️ **Muestra alerta informativa** - Una sola vez, sin spam de notificaciones
- 🔄 **Botón "Reintentar"** - Permite verificar manualmente después de configurar
- 📖 **Botón "Ver instrucciones"** - Abre un modal con los pasos de configuración detallados

### Qué verás en el navegador

Cuando hay error de sudo, aparece un alert amarillo en la parte superior:

```
⚠️ Configuración de Supervisor requerida

El servidor necesita configuración de passwordless sudo para ejecutar comandos de Supervisor.

Error: sudo: a password is required

[Ver instrucciones] [Reintentar]  [×]
```

**Características:**
- El alert es **persistente** (no desaparece automáticamente)
- Puedes **cerrarlo manualmente** con el botón [×]
- El **auto-refresh se detiene** hasta que cierres el alert o reintentes
- Solo se muestra **una vez**, no cada 5 segundos

## Solución: Configurar Passwordless Sudo

### Opción 1: Usuario web específico (Recomendado)

Encuentra el usuario que ejecuta PHP/Apache/Nginx:
```bash
ps aux | grep -E '(apache|nginx|php-fpm)' | head -n 1
```

Supongamos que el usuario es `www-data`. Crea un archivo de configuración:

```bash
sudo visudo -f /etc/sudoers.d/supervisor-web
```

Agrega las siguientes líneas:
```
# Allow www-data to run supervisorctl commands without password
www-data ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart supervisor
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl status supervisor
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl start supervisor
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl stop supervisor
```

Guarda y cierra (`Ctrl+X`, luego `Y`, luego `Enter`).

Verifica los permisos del archivo:
```bash
sudo chmod 0440 /etc/sudoers.d/supervisor-web
```

### Opción 2: Para desarrollo local

Si estás en un ambiente de desarrollo local, puedes dar permisos más amplios:

```bash
sudo visudo -f /etc/sudoers.d/supervisor-dev
```

Agrega:
```
# Development only - Allow web user full supervisor access
www-data ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl *
www-data ALL=(ALL) NOPASSWD: /usr/bin/systemctl * supervisor
```

## Verificación

Prueba que funciona sin contraseña (como el usuario web):
```bash
sudo -u www-data sudo -n supervisorctl status
```

Si no pide contraseña y muestra el estado, está configurado correctamente.

## Seguridad

- Solo permite comandos específicos de supervisor
- No permite otros comandos de sudo
- El archivo /etc/sudoers.d debe tener permisos 0440
- Solo el usuario web puede ejecutar estos comandos sin contraseña

## Alternativa: Sin Sudo (Avanzado)

Si prefieres no usar sudo, puedes:
1. Ejecutar supervisor como el mismo usuario que PHP
2. Configurar permisos de socket de supervisor para permitir acceso al usuario web
3. Usar la API XML-RPC de supervisor directamente (requiere configuración adicional)

