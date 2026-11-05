#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

PROJECT_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
DEPLOY_USER="$(id -un)"
DEPLOY_GROUP="$(id -gn)"
SYSTEMD_USER_DIR="${XDG_CONFIG_HOME:-${HOME}/.config}/systemd/user"

if [[ "$(id -u)" -eq 0 ]]; then
    echo "Ejecutá este instalador como el usuario técnico dedicado, no como root." >&2
    exit 1
fi
if [[ ! -f "${PROJECT_ROOT}/.env" ]]; then
    echo "Falta ${PROJECT_ROOT}/.env. Copiá .env.example y configurá secretos antes de continuar." >&2
    exit 1
fi
if ! command -v docker >/dev/null 2>&1 || ! docker compose version >/dev/null 2>&1; then
    echo "Instalá Docker Engine y el complemento Docker Compose antes de continuar." >&2
    exit 1
fi
if ! docker info --format '{{json .SecurityOptions}}' | grep -qi rootless; then
    echo "Este servicio usa Docker rootless para evitar conceder privilegios de root al usuario de despliegue." >&2
    exit 1
fi

if ! grep -q '^APP_UID=' "${PROJECT_ROOT}/.env"; then
    printf '\nAPP_UID=%s\n' "$(id -u)" >> "${PROJECT_ROOT}/.env"
fi
if ! grep -q '^APP_GID=' "${PROJECT_ROOT}/.env"; then
    printf 'APP_GID=%s\n' "$(id -g)" >> "${PROJECT_ROOT}/.env"
fi

docker compose config -q
docker compose build web

mkdir -p -- "${PROJECT_ROOT}/storage/logs" "${PROJECT_ROOT}/backups" "$SYSTEMD_USER_DIR"
chmod 0750 "${PROJECT_ROOT}/storage" "${PROJECT_ROOT}/storage/logs" "${PROJECT_ROOT}/backups"
chmod 0600 "${PROJECT_ROOT}/.env"
chmod 0750 "${PROJECT_ROOT}/scripts/backup_database.sh" "${PROJECT_ROOT}/scripts/audit_resources.sh" \
    "${PROJECT_ROOT}/scripts/monitor_security.sh" "${PROJECT_ROOT}/provision/init_system.sh"

escape_sed_replacement() {
    printf '%s' "$1" | sed 's/[&|]/\\&/g'
}

PROJECT_ROOT_ESCAPED="$(escape_sed_replacement "$PROJECT_ROOT")"
DEPLOY_USER_ESCAPED="$(escape_sed_replacement "$DEPLOY_USER")"
DEPLOY_GROUP_ESCAPED="$(escape_sed_replacement "$DEPLOY_GROUP")"
for unit in "${PROJECT_ROOT}"/ops/systemd/user/*.service "${PROJECT_ROOT}"/ops/systemd/user/*.timer; do
    sed \
        -e "s|@PROJECT_DIR@|${PROJECT_ROOT_ESCAPED}|g" \
        -e "s|@DEPLOY_USER@|${DEPLOY_USER_ESCAPED}|g" \
        -e "s|@DEPLOY_GROUP@|${DEPLOY_GROUP_ESCAPED}|g" \
        "$unit" > "${SYSTEMD_USER_DIR}/$(basename "$unit")"
    chmod 0644 "${SYSTEMD_USER_DIR}/$(basename "$unit")"
done

LOGROTATE_TEMP="$(mktemp)"
trap 'rm -f -- "$LOGROTATE_TEMP"' EXIT
sed \
    -e "s|@PROJECT_DIR@|${PROJECT_ROOT_ESCAPED}|g" \
    -e "s|@DEPLOY_USER@|${DEPLOY_USER_ESCAPED}|g" \
    -e "s|@DEPLOY_GROUP@|${DEPLOY_GROUP_ESCAPED}|g" \
    "${PROJECT_ROOT}/ops/logrotate/seberih" > "$LOGROTATE_TEMP"
sudo install -o root -g root -m 0644 "$LOGROTATE_TEMP" /etc/logrotate.d/seberih
sudo loginctl enable-linger "$DEPLOY_USER"

systemctl --user daemon-reload
systemctl --user enable --now seberih-compose.service seberih-resource-audit.timer
echo "Servicio, temporizador y rotación de logs configurados para ${PROJECT_ROOT}."
