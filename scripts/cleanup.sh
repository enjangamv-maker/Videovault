#!/usr/bin/env bash
# VideoVault — Cleanup old backups
set -euo pipefail

BACKUP_DIR="/var/www/html/35/percobaan/videovault/storage/backups"
RETENTION_DAYS="${1:-30}"

echo "🧹 Cleanup backup > $RETENTION_DAYS hari di $BACKUP_DIR"
find "$BACKUP_DIR" -name "*.tar.gz" -type f -mtime "+$RETENTION_DAYS" -print -delete
echo "✅ Selesai"
