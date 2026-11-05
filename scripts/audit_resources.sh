#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

PROJECT_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
LOG_DIR="${PROJECT_ROOT}/storage/logs"
REPORT="${LOG_DIR}/resource-audit.log"
mkdir -p -- "$LOG_DIR"

{
    printf '\n[%s] Auditoría de recursos\n' "$(date --iso-8601=seconds)"
    df -h "$PROJECT_ROOT"
    if command -v docker >/dev/null 2>&1; then
        docker compose ps --format 'table {{.Name}}\t{{.State}}\t{{.Publishers}}'
        docker compose stats --no-stream --format 'table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}'
    else
        echo 'Docker no está disponible en PATH.'
    fi
} >> "$REPORT" 2>&1

chmod 0640 "$REPORT"
