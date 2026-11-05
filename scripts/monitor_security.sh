#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

PROJECT_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
ACCESS_LOG="${1:-${PROJECT_ROOT}/storage/logs/apache-access.log}"
LOG_DIR="${PROJECT_ROOT}/storage/logs"
REPORT="${LOG_DIR}/security-audit.log"
PATTERN='(\.\./|%2e%2e|union[[:space:]]+select|/\.env|wp-login\.php|<script)'

mkdir -p -- "$LOG_DIR"
if [[ ! -r "$ACCESS_LOG" ]]; then
    echo "No se puede leer el log de acceso: ${ACCESS_LOG}" >&2
    exit 1
fi

MATCH_COUNT="$(grep -Eai -c "$PATTERN" "$ACCESS_LOG" || true)"
{
    printf '\n[%s] Auditoría de solicitudes sospechosas\n' "$(date --iso-8601=seconds)"
    printf 'Coincidencias detectadas: %s\n' "${MATCH_COUNT:-0}"
    grep -Eai "$PATTERN" "$ACCESS_LOG" | awk '{print $1}' | sort | uniq -c | sort -rn | head -20 || true
} >> "$REPORT"

chmod 0640 "$REPORT"
echo "Informe actualizado: ${REPORT}"
