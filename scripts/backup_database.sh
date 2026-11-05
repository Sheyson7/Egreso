#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

PROJECT_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_DIR="${BACKUP_DIR:-${PROJECT_ROOT}/backups}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-30}"
TIMESTAMP="$(date -u '+%Y%m%dT%H%M%SZ')"
BACKUP_FILE="${BACKUP_DIR}/seberih-${TIMESTAMP}.sql.gz"
TEMP_FILE=""

if ! command -v docker >/dev/null 2>&1; then
    echo "Falta Docker en el sistema." >&2
    exit 1
fi
if ! [[ "$RETENTION_DAYS" =~ ^[1-9][0-9]*$ ]]; then
    echo "BACKUP_RETENTION_DAYS debe ser un entero positivo." >&2
    exit 1
fi

mkdir -p -- "$BACKUP_DIR"
chmod 0750 "$BACKUP_DIR"
TEMP_FILE="$(mktemp "${BACKUP_DIR}/.seberih-${TIMESTAMP}.XXXXXX")"
trap '[[ -z "$TEMP_FILE" ]] || rm -f -- "$TEMP_FILE"' EXIT

docker compose exec --no-TTY db sh -ec '
    credentials="$(mktemp)"
    trap '\''rm -f "$credentials"'\'' EXIT
    chmod 0600 "$credentials"
    printf "[client]\nuser=%s\npassword=%s\nhost=127.0.0.1\n" "$MARIADB_USER" "$MARIADB_PASSWORD" > "$credentials"
    mariadb-dump --defaults-extra-file="$credentials" --single-transaction --routines --triggers "$MARIADB_DATABASE"
' | gzip -9 > "$TEMP_FILE"

mv -- "$TEMP_FILE" "$BACKUP_FILE"
TEMP_FILE=""

if [[ -f "${PROJECT_ROOT}/storage/reservations.json" ]]; then
    cp -- "${PROJECT_ROOT}/storage/reservations.json" "${BACKUP_DIR}/seberih-${TIMESTAMP}-demo-reservations.json"
    chmod 0600 "${BACKUP_DIR}/seberih-${TIMESTAMP}-demo-reservations.json"
fi

find "$BACKUP_DIR" -type f \( -name 'seberih-*.sql.gz' -o -name 'seberih-*-demo-reservations.json' \) -mtime "+${RETENTION_DAYS}" -delete
echo "Respaldo creado: ${BACKUP_FILE}"
