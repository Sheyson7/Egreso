#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

PROJECT_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
DEPLOY_USER="$(id -un)"
MARKER="# seberih-daily-backup"

if [[ "$(id -u)" -eq 0 ]]; then
    echo "Ejecutá este instalador como el usuario técnico dedicado, no como root." >&2
    exit 1
fi
if ! command -v crontab >/dev/null 2>&1; then
    echo "No se encontró crontab. Instalá el servicio cron antes de continuar." >&2
    exit 1
fi
if [[ ! -f "${PROJECT_ROOT}/.env" ]]; then
    echo "Falta ${PROJECT_ROOT}/.env. Configurá las credenciales antes de programar respaldos." >&2
    exit 1
fi
if ! command -v docker >/dev/null 2>&1 || ! docker compose version >/dev/null 2>&1; then
    echo "Instalá Docker Engine y el complemento Docker Compose antes de continuar." >&2
    exit 1
fi
if [[ "${PROJECT_ROOT}" == *"'"* ]]; then
    echo "La ruta del proyecto no puede contener comillas simples para programarla en cron." >&2
    exit 1
fi

mkdir -p -- "${PROJECT_ROOT}/storage/logs" "${PROJECT_ROOT}/backups"
chmod 0750 "${PROJECT_ROOT}/storage" "${PROJECT_ROOT}/storage/logs" "${PROJECT_ROOT}/backups"
chmod 0750 "${PROJECT_ROOT}/scripts/backup_database.sh"

CRON_LINE="0 2 * * * cd '${PROJECT_ROOT}' && BACKUP_RETENTION_DAYS=30 '${PROJECT_ROOT}/scripts/backup_database.sh' >> '${PROJECT_ROOT}/storage/logs/backup-cron.log' 2>&1 ${MARKER}"
EXISTING_CRON="$(crontab -l 2>/dev/null || true)"
FILTERED_CRON="$(printf '%s\n' "$EXISTING_CRON" | grep -Fv "$MARKER" || true)"
{
    [[ -z "$FILTERED_CRON" ]] || printf '%s\n' "$FILTERED_CRON"
    printf '%s\n' "$CRON_LINE"
} | crontab -

echo "Respaldo diario instalado para ${DEPLOY_USER}: 02:00, retención de 30 días."
echo "Volvé a ejecutar este instalador para actualizar la línea sin duplicarla."
