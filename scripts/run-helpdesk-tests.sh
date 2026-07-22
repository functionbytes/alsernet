#!/usr/bin/env bash
#
# Corre las suites de tests del ecosistema Helpdesk módulo a módulo, con
# resumen por módulo y exit code agregado (0 solo si TODAS pasan).
#
# Uso:
#   scripts/run-helpdesk-tests.sh              # todos los módulos Helpdesk*
#   scripts/run-helpdesk-tests.sh Tickets ...  # solo modules/Helpdesk<arg>
#   composer test:helpdesk
#
# La BD de test la define phpunit.xml (system_test_pristine, MariaDB local).
set -u

cd "$(dirname "$0")/.." || exit 1

PHPUNIT="vendor/bin/phpunit"
[ -x "$PHPUNIT" ] || { echo "vendor/bin/phpunit no existe — corre composer install"; exit 1; }

# Módulos con tests. HelpdeskSocial se ejecuta pero sus fallos por el WIP de
# Jobs (TypeError) son conocidos; ver tests/README-helpdesk-tests.md.
DEFAULT_MODULES=(Helpdesk HelpdeskTickets HelpdeskCampaigns HelpdeskEmailLog HelpdeskPrestashop HelpdeskTranslate HelpdeskAgents)

if [ $# -gt 0 ]; then
    MODULES=()
    for arg in "$@"; do
        case "$arg" in
            Helpdesk*) MODULES+=("$arg") ;;
            *) MODULES+=("Helpdesk$arg") ;;
        esac
    done
else
    MODULES=("${DEFAULT_MODULES[@]}")
fi

overall=0
summary=""

for module in "${MODULES[@]}"; do
    dir="modules/$module/tests"
    if [ ! -d "$dir" ]; then
        summary+=$'\n'"  $module: (sin directorio de tests — omitido)"
        continue
    fi

    echo "==================================================================="
    echo "  $module"
    echo "==================================================================="

    output=$("$PHPUNIT" "$dir" --no-coverage 2>&1)
    status=$?

    # Última línea de resumen de PHPUnit (OK / FAILURES! / ERRORS! ...)
    line=$(printf '%s\n' "$output" | grep -E '^(OK|Tests:|No tests executed)' | tail -1)
    [ -z "$line" ] && line="(sin resumen — ¿error fatal?)"

    if [ $status -ne 0 ]; then
        overall=1
        # Con fallo, muestra el detalle completo del módulo.
        printf '%s\n' "$output"
        summary+=$'\n'"  FAIL  $module: $line"
    else
        printf '%s\n' "$output" | tail -4
        summary+=$'\n'"  ok    $module: $line"
    fi
done

echo
echo "==================== RESUMEN HELPDESK ===================="
printf '%s\n' "$summary"
echo "=========================================================="

exit $overall
