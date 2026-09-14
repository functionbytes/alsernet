#!/bin/bash
# Laravel Scheduler Runner Script
#
# IMPORTANTE: hay que situarse en el directorio del proyecto (cd) ANTES de invocar
# schedule:run. Laravel lanza los comandos programados en segundo plano usando la
# ruta RELATIVA 'artisan' (p.ej. `php artisan supplier:sync-models ... &`). Si el
# CWD no es la raíz del proyecto (supervisor lo lanza desde "/"), ese 'artisan'
# relativo no existe y el subcomando falla en silencio: el sync nunca se ejecuta
# aunque schedule:run diga "Running ... DONE".
#
# Además se ejecuta una vez por minuto ALINEADO al reloj (sleep hasta el segundo
# :00 del siguiente minuto) para no acumular drift y no saltarse minutos, de modo
# que las tareas dailyAt('HH:MM') caigan siempre en su minuto exacto.

PROJECT_PATH="/home2/webadmin/web"
LOG_FILE="/var/log/laravel/scheduler.log"

# Create log directory if it doesn't exist
mkdir -p /var/log/laravel

# CRÍTICO: situarse en la raíz del proyecto para que el 'artisan' relativo de los
# comandos en segundo plano de schedule:run resuelva correctamente.
cd "$PROJECT_PATH" || exit 1

while true; do
    /usr/bin/php artisan schedule:run >> "$LOG_FILE" 2>&1
    # Dormir hasta el inicio del siguiente minuto (10# evita error de octal con 08/09).
    sleep $((60 - 10#$(date +%S)))
done
